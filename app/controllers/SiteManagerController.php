<?php
// SiteManagerController.php - Additive controller for Site Manager role
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../../config/database.php';

// Models (defensive includes)
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Material.php';
require_once __DIR__ . '/../models/Finance.php';
require_once __DIR__ . '/../models/PurchaseOrder.php';

class SiteManagerController {
    private $db;
    private $projectModel;
    private $taskModel;
    private $materialModel;
    private $financeModel;
    private $poModel;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->projectModel  = class_exists('Project') ? new Project() : null;
        $this->taskModel     = class_exists('Task') ? new Task() : null;
        $this->materialModel = class_exists('Material') ? new Material() : null;
        $this->financeModel  = class_exists('Finance') ? new Finance() : null;
        $this->poModel       = class_exists('PurchaseOrder') ? new PurchaseOrder() : null;
    }

    private function ensureSiteManager() {
        requireAnyRole(['site_manager','admin']);
    }

    // Project overview for Site Manager
    // If $projectId provided: return project meta + task/material/finance/PO summaries
    // If null: return a helpful message and optionally a projects list
    public function projectOverview($projectId = null) {
        $this->ensureSiteManager();
        $pid = $projectId !== null ? (int)$projectId : null;

        try {
            if ($pid !== null) {
                if ($pid <= 0) {
                    return ['success' => false, 'message' => 'Invalid project_id'];
                }

                // Project (attempt enriched join for owner + site manager names if columns exist)
                $project = null;
                try {
                    // Detect site_manager_id column
                    $hasSiteManager = false;
                    try {
                        $colChk = $this->db->query("SHOW COLUMNS FROM projects LIKE 'site_manager_id'");
                        $hasSiteManager = $colChk && $colChk->rowCount() > 0;
                    } catch (Throwable $eCol) { $hasSiteManager = false; }
                    if ($hasSiteManager) {
                        $sql = "SELECT p.*, ow.name AS owner_name, sm.name AS site_manager_name
                                FROM projects p
                                JOIN users ow ON ow.id = p.owner_id
                                LEFT JOIN users sm ON sm.id = p.site_manager_id
                                WHERE p.id = :id";
                        $stmt = $this->db->prepare($sql);
                        $stmt->bindValue(':id', $pid, PDO::PARAM_INT);
                        $stmt->execute();
                        $project = $stmt->fetch();
                    }
                } catch (Throwable $eEnrich) { /* fall back below */ }
                if (!$project) {
                    if ($this->projectModel && method_exists($this->projectModel, 'getById')) {
                        $project = $this->projectModel->getById($pid);
                    } else {
                        $stmt = $this->db->prepare('SELECT * FROM projects WHERE id = :id');
                        $stmt->bindValue(':id', $pid, PDO::PARAM_INT);
                        $stmt->execute();
                        $project = $stmt->fetch();
                    }
                }
                if (!$project) {
                    return ['success' => false, 'message' => 'Project not found'];
                }

                // Permission: current user must be assigned as site manager (projects.site_manager_id)
                // or present in project_assignments for this project.
                $uid = getCurrentUserId();
                $allowed = false;
                if ((int)($project['site_manager_id'] ?? 0) === (int)$uid) {
                    $allowed = true;
                } else {
                    try {
                        $stmt = $this->db->prepare('SELECT 1 FROM project_assignments WHERE project_id = :pid AND user_id = :uid LIMIT 1');
                        $stmt->execute([':pid'=>$pid, ':uid'=>$uid]);
                        $allowed = $stmt->fetchColumn() ? true : false;
                    } catch (Throwable $e) { /* table may not exist; keep $allowed as false */ }
                }
                if (!$allowed) {
                    return ['success' => false, 'message' => 'Access denied for this project'];
                }

                // Tasks summary (include progress metric)
                $tasks = [];
                if ($this->taskModel && method_exists($this->taskModel, 'getByProject')) {
                    $tasks = $this->taskModel->getByProject($pid) ?: [];
                } else {
                    $stmt = $this->db->prepare('SELECT * FROM tasks WHERE project_id = :pid ORDER BY created_at DESC');
                    $stmt->bindValue(':pid', $pid, PDO::PARAM_INT);
                    $stmt->execute();
                    $tasks = $stmt->fetchAll() ?: [];
                }
                $taskCounts = [
                    'total' => count($tasks),
                    'pending' => 0,
                    'in_progress' => 0,
                    'completed' => 0
                ];
                foreach ($tasks as $t) {
                    $st = $t['status'] ?? 'pending';
                    if (!isset($taskCounts[$st])) { $taskCounts[$st] = 0; }
                    $taskCounts[$st]++;
                }
                $progressPercent = 0;
                if ($taskCounts['total'] > 0) {
                    $progressPercent = round(($taskCounts['completed'] / max(1,$taskCounts['total'])) * 100, 1);
                }

                // Materials summary
                $materials = [];
                if ($this->materialModel && method_exists($this->materialModel, 'getByProject')) {
                    $materials = $this->materialModel->getByProject($pid) ?: [];
                } else {
                    $stmt = $this->db->prepare('SELECT * FROM materials WHERE project_id = :pid ORDER BY created_at DESC');
                    $stmt->bindValue(':pid', $pid, PDO::PARAM_INT);
                    $stmt->execute();
                    $materials = $stmt->fetchAll() ?: [];
                }
                $materialCounts = [
                    'total' => count($materials),
                    'requested' => 0,
                    'approved' => 0,
                    'ordered' => 0,
                    'delivered' => 0,
                    'rejected' => 0,
                ];
                foreach ($materials as $m) {
                    $st = $m['status'] ?? 'requested';
                    if (!isset($materialCounts[$st])) { $materialCounts[$st] = 0; }
                    $materialCounts[$st]++;
                }

                // Finance summary
                $financeSummary = null;
                if ($this->financeModel && method_exists($this->financeModel, 'getSummary')) {
                    $financeSummary = $this->financeModel->getSummary($pid);
                } else {
                    // Fallback simple aggregates
                    $financeSummary = ['income' => 0, 'expense' => 0, 'balance' => 0];
                    try {
                        $stmt = $this->db->prepare("SELECT type, SUM(amount) AS total FROM finance WHERE project_id = :pid GROUP BY type");
                        $stmt->bindValue(':pid', $pid, PDO::PARAM_INT);
                        $stmt->execute();
                        $rows = $stmt->fetchAll() ?: [];
                        foreach ($rows as $r) {
                            $t = $r['type'];
                            $financeSummary[$t] = (float)$r['total'];
                        }
                        $financeSummary['balance'] = ($financeSummary['income'] ?? 0) - ($financeSummary['expense'] ?? 0);
                    } catch (Throwable $e) { /* ignore, keep zeros */ }
                }

                // Purchase orders (basic list)
                $purchaseOrders = [];
                if ($this->poModel && method_exists($this->poModel, 'getAll')) {
                    // Filter in-memory for simplicity if model lacks project filter
                    $all = $this->poModel->getAll() ?: [];
                    foreach ($all as $po) { if ((int)($po['project_id'] ?? 0) === $pid) { $purchaseOrders[] = $po; } }
                } else {
                    try {
                        $stmt = $this->db->prepare(
                            'SELECT po.*, s.name AS supplier_name, u.name AS created_by_name
                             FROM purchase_orders po
                             JOIN suppliers s ON po.supplier_id = s.id
                             JOIN users u ON po.created_by = u.id
                             WHERE po.project_id = :pid
                             ORDER BY po.created_at DESC'
                        );
                        $stmt->bindValue(':pid', $pid, PDO::PARAM_INT);
                        $stmt->execute();
                        $purchaseOrders = $stmt->fetchAll() ?: [];
                    } catch (Throwable $e) { $purchaseOrders = []; }
                }

                // Assignment summary (people involved)
                $assignments = [ 'site_manager'=>null, 'owner'=>null, 'other_members'=>[], 'counts'=>['members'=>0] ];
                $assignments['owner'] = [ 'id'=>$project['owner_id'] ?? null, 'name'=>$project['owner_name'] ?? null ];
                if (isset($project['site_manager_id'])) {
                    $assignments['site_manager'] = [ 'id'=>$project['site_manager_id'], 'name'=>$project['site_manager_name'] ?? null ];
                }
                // Collect other assigned users (project_assignments) excluding owner + site manager
                try {
                    $chk = $this->db->query("SHOW TABLES LIKE 'project_assignments'");
                    if ($chk && $chk->rowCount() > 0) {
                        $stmt = $this->db->prepare('SELECT pa.user_id, u.name, u.role FROM project_assignments pa JOIN users u ON u.id = pa.user_id WHERE pa.project_id = :pid');
                        $stmt->execute([':pid'=>$pid]);
                        $rows = $stmt->fetchAll() ?: [];
                        foreach ($rows as $r) {
                            if ((int)$r['user_id'] === (int)($project['owner_id']??0)) continue;
                            if (isset($project['site_manager_id']) && (int)$r['user_id'] === (int)$project['site_manager_id']) continue;
                            $assignments['other_members'][] = [ 'id'=>$r['user_id'], 'name'=>$r['name'] ?? null, 'role'=>$r['role'] ?? null ];
                        }
                        $assignments['counts']['members'] = count($assignments['other_members']);
                    }
                } catch (Throwable $eA) { /* ignore */ }

                return [
                    'success' => true,
                    'data' => [
                        'project' => $project,
                        'assignments' => $assignments,
                        'tasks' => [
                            'counts' => $taskCounts,
                            'progress_percent' => $progressPercent,
                            'recent' => array_slice($tasks, 0, 10),
                        ],
                        'materials' => [
                            'counts' => $materialCounts,
                            'recent' => array_slice($materials, 0, 10),
                        ],
                        'finance' => $financeSummary,
                        'purchase_orders' => [
                            'total' => count($purchaseOrders),
                            'recent' => array_slice($purchaseOrders, 0, 10),
                        ],
                    ]
                ];
            }

            // No projectId case: return projects where user is site manager or assigned via project_assignments
            $uid = getCurrentUserId();
            $projects = [];
            $debug = [ 'user_id'=>$uid, 'steps'=>[] ];
            // Fast path: if Project model provides getBySiteManager use it
            if ($this->projectModel && method_exists($this->projectModel,'getBySiteManager')) {
                try {
                    $projects = $this->projectModel->getBySiteManager($uid) ?: [];
                    $debug['steps'][] = 'getBySiteManager rows: '.count($projects);
                } catch (Throwable $eFast) { $debug['steps'][]='getBySiteManager failed: '.$eFast->getMessage(); }
            }
            if (empty($projects)) try {
                // Step 1: detect existence of assignments table
                $hasAssignments = false;
                try {
                    $chk = $this->db->query("SHOW TABLES LIKE 'project_assignments'");
                    $hasAssignments = $chk && $chk->rowCount() > 0;
                } catch (Throwable $eT) { $hasAssignments = false; $debug['steps'][] = 'assignments table detection failed: '.$eT->getMessage(); }
                $debug['has_assignments_table'] = $hasAssignments;

                // Helper for executing a query with graceful fallback ordering
                $runQuery = function($sql, $params) use (&$debug) {
                    try {
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute($params);
                        return $stmt->fetchAll() ?: [];
                    } catch (Throwable $e) {
                        // Try without created_at ordering (in case column absent)
                        $debug['steps'][] = 'query failed primary: '.$e->getMessage();
                        if (stripos($sql, 'ORDER BY') !== false) {
                            $sql2 = preg_replace('/ORDER BY[^\\n]+$/i','ORDER BY p.id DESC',$sql);
                            try {
                                $stmt2 = $this->db->prepare($sql2);
                                $stmt2->execute($params);
                                $rows = $stmt2->fetchAll() ?: [];
                                if (!empty($rows)) { $debug['steps'][] = 'fallback ordering used'; }
                                return $rows;
                            } catch (Throwable $e2) { $debug['steps'][] = 'fallback also failed: '.$e2->getMessage(); }
                        }
                        return [];
                    }
                };

                // Step 2: attempt combined retrieval
                if ($hasAssignments) {
                    $combinedSql = "SELECT DISTINCT p.id, p.name, p.status, p.start_date, p.end_date, p.site_manager_id\n                                    FROM projects p\n                                    LEFT JOIN project_assignments pa ON pa.project_id = p.id AND pa.user_id = :uid\n                                    WHERE p.site_manager_id = :uid OR pa.user_id IS NOT NULL\n                                    ORDER BY p.created_at DESC";
                    $projects = $runQuery($combinedSql, [':uid'=>$uid]);
                    $debug['steps'][] = 'combined rows: '.count($projects);
                }

                // Step 3: column only
                if (empty($projects)) {
                    $colSql = 'SELECT id, name, status, start_date, end_date, site_manager_id FROM projects WHERE site_manager_id = :uid ORDER BY created_at DESC';
                    $colProjects = $runQuery($colSql, [':uid'=>$uid]);
                    $debug['steps'][] = 'column rows: '.count($colProjects);
                    if (!empty($colProjects)) { $projects = $colProjects; }
                }

                // Step 4: assignments only
                if ($hasAssignments && empty($projects)) {
                    $assignSql = 'SELECT p.id, p.name, p.status, p.start_date, p.end_date, p.site_manager_id FROM projects p JOIN project_assignments pa ON pa.project_id = p.id WHERE pa.user_id = :uid ORDER BY p.created_at DESC';
                    $assignOnly = $runQuery($assignSql, [':uid'=>$uid]);
                    $debug['steps'][] = 'assign only rows: '.count($assignOnly);
                    if (!empty($assignOnly)) { $projects = $assignOnly; }
                }
            } catch (Throwable $eOuter) { $debug['steps'][] = 'outer failure: '.$eOuter->getMessage(); $projects = []; }

            $response = [
                'success' => true,
                'message' => 'Provide project_id to get an overview. Listing your assigned projects.',
                'projects' => array_slice($projects, 0, 20)
            ];
            if (isset($_GET['debug']) && $_GET['debug'] == '1') { $response['debug'] = $debug; }
            return $response;
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Failed to load overview'];
        }
    }
}
