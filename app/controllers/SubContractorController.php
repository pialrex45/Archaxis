<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/Material.php';
require_once __DIR__ . '/../models/TaskUpdate.php';
require_once __DIR__ . '/../../config/database.php';

class SubContractorController {
    private $pdo;
    private $taskModel;
    private $projectModel;
    private $materialModel;
    private $taskUpdateModel;

    public function __construct() {
        $this->pdo = Database::getConnection();
        $this->taskModel = new Task();
        $this->projectModel = new Project();
        $this->materialModel = new Material();
        $this->taskUpdateModel = new TaskUpdate();
    }

    private function currentUserId() { return getCurrentUserId(); }

    private function assignedProjectIds($userId) {
        $sql = "SELECT DISTINCT t.project_id FROM tasks t WHERE t.assigned_to = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$userId]);
        return array_column($stmt->fetchAll() ?: [], 'project_id');
    }

    // Projects the subcontractor can access based on current assignment, activity, or recent assigned-away cache
    private function accessibleProjectIds($userId) {
        // Build recent assigned-away IDs from session (valid for 24h)
        $recent = isset($_SESSION['sc_recent_assigned_away']) && is_array($_SESSION['sc_recent_assigned_away']) ? $_SESSION['sc_recent_assigned_away'] : [];
        $now = time();
        $recentIds = [];
        foreach ($recent as $tid => $ts) { if (($now - (int)$ts) <= 86400) { $recentIds[] = (int)$tid; } }

        // Determine if task_updates exists
        $hasUpdates = false;
        try { $this->pdo->query("SELECT 1 FROM task_updates LIMIT 1"); $hasUpdates = true; } catch (Throwable $e) { $hasUpdates = false; }

        // Collect distinct project IDs from tasks where subcontractor is assigned, has activity, or recently assigned-away
        $base = "SELECT DISTINCT t.project_id FROM tasks t";
        $where = " WHERE (t.assigned_to = ?";
        $params = [(int)$userId];
        if ($hasUpdates) {
            $where .= " OR EXISTS (SELECT 1 FROM task_updates tu WHERE tu.task_id = t.id AND tu.user_id = ?)";
            $params[] = (int)$userId;
        }
        if (!empty($recentIds)) {
            $inRecent = implode(',', array_fill(0, count($recentIds), '?'));
            $where .= " OR t.id IN ($inRecent)";
            $params = array_merge($params, $recentIds);
        }
        $sql = $base . $where . ")";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $taskProjectIds = array_values(array_filter(array_map('intval', array_column($stmt->fetchAll() ?: [], 'project_id'))));

        // Also include projects explicitly assigned via project_assignments (if table exists)
        $assignedProjectIds = [];
        try {
            $st = $this->pdo->prepare('SELECT project_id FROM project_assignments WHERE user_id = ?');
            $st->execute([(int)$userId]);
            $assignedProjectIds = array_values(array_filter(array_map('intval', array_column($st->fetchAll() ?: [], 'project_id'))));
        } catch (Throwable $e) { /* table may not exist */ }

        // Union and unique
        $all = array_values(array_unique(array_merge($taskProjectIds, $assignedProjectIds)));
        return $all;
    }

    private function canAccessTask($taskId, $userId) {
        // Also allow if task is in the user's recent assigned-away session cache
        $recent = isset($_SESSION['sc_recent_assigned_away']) && is_array($_SESSION['sc_recent_assigned_away']) ? $_SESSION['sc_recent_assigned_away'] : [];
        $now = time();
        $recentIds = [];
        foreach ($recent as $tid => $ts) { if (($now - (int)$ts) <= 86400) { $recentIds[] = (int)$tid; } }
        if (in_array((int)$taskId, $recentIds, true)) { return true; }
        // Access if currently assigned OR if the user has prior activity on the task (if task_updates table exists)
        try {
            $sql = "SELECT 1
                    FROM tasks t
                    WHERE t.id = ? AND (
                        t.assigned_to = ? OR EXISTS (
                            SELECT 1 FROM task_updates tu
                            WHERE tu.task_id = t.id AND tu.user_id = ?
                        )
                    )
                    LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([(int)$taskId, (int)$userId, (int)$userId]);
            return (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            // If task_updates table is missing, fallback to current assignment only
            $stmt = $this->pdo->prepare("SELECT 1 FROM tasks WHERE id=? AND assigned_to=? LIMIT 1");
            $stmt->execute([(int)$taskId, (int)$userId]);
            return (bool)$stmt->fetchColumn();
        }
    }

    // Tasks
    public function tasksAssigned($limit = 100) {
        requireSubContractor();
        $uid = $this->currentUserId();
        $limit = max(1, min(200, (int)$limit));
        // Build recent assigned-away IDs from session (valid for 24h)
        $recent = isset($_SESSION['sc_recent_assigned_away']) && is_array($_SESSION['sc_recent_assigned_away']) ? $_SESSION['sc_recent_assigned_away'] : [];
        $now = time();
        $recentIds = [];
        foreach ($recent as $tid => $ts) { if (($now - (int)$ts) <= 86400) { $recentIds[] = (int)$tid; } }
        // Show tasks still assigned OR ones they have activity on (if table exists) OR recent assigned-away by session cache
        $base = "SELECT DISTINCT t.id, t.*, p.name AS project_name, u.name AS assigned_to_name
                 FROM tasks t
                 JOIN projects p ON p.id = t.project_id
                 LEFT JOIN users u ON u.id = t.assigned_to";
        $joins = '';
        $where = " WHERE (t.assigned_to = ?";
        $params = [(int)$uid];
        // Try including task_updates via LEFT JOIN for robustness
        $hasUpdates = false;
        try { $this->pdo->query("SELECT 1 FROM task_updates LIMIT 1"); $hasUpdates = true; } catch (Throwable $e) { $hasUpdates = false; }
        if ($hasUpdates) {
            $joins .= " LEFT JOIN task_updates tu_self ON tu_self.task_id = t.id AND tu_self.user_id = ?";
            $where .= " OR tu_self.user_id IS NOT NULL";
            $params[] = (int)$uid;
        }
        if (!empty($recentIds)) {
            $in = implode(',', array_fill(0, count($recentIds), '?'));
            $where .= " OR t.id IN ($in)";
            $params = array_merge($params, $recentIds);
        }
        $order = " ORDER BY t.due_date IS NULL, t.due_date ASC, t.id DESC LIMIT {$limit}";
        $sql = $base . $joins . $where . ')' . $order;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return ['success'=>true,'data'=>$stmt->fetchAll() ?: []];
    }

    public function taskUpdateStatus($taskId, $status, $note = null, $photoPath = null) {
        requireSubContractor();
        $uid = $this->currentUserId();
        $allowed = ['pending','in progress','completed'];
        if (!in_array($status, $allowed, true)) {
            return ['success'=>false,'message'=>'Invalid status'];
        }
        if (!$this->canAccessTask($taskId, $uid)) {
            return ['success'=>false,'message'=>'Forbidden'];
        }
        $res = $this->taskModel->updateStatus((int)$taskId, $status);
        if (!$res || !$res['success']) { return $res ?: ['success'=>false,'message'=>'Update failed']; }
        $log = $this->taskUpdateModel->create((int)$taskId, (int)$uid, $status, $note, $photoPath);
        return $log['success'] ? ['success'=>true,'message'=>'Status updated'] : $log;
    }

    public function taskAddProgress($taskId, $note = null, $status = null, $photoPath = null) {
        requireSubContractor();
        $uid = $this->currentUserId();
        if (!$this->canAccessTask($taskId, $uid)) { return ['success'=>false,'message'=>'Forbidden']; }
        if ($status && !in_array($status, ['pending','in progress','completed'], true)) {
            return ['success'=>false,'message'=>'Invalid status'];
        }
        return $this->taskUpdateModel->create((int)$taskId, (int)$uid, $status, $note, $photoPath);
    }

    public function taskAssignSupervisor($taskId, $supervisorId) {
        requireSubContractor();
        $uid = $this->currentUserId();
        $tid = (int)$taskId; $sid = (int)$supervisorId;
        if ($tid <= 0 || $sid <= 0) { return ['success'=>false,'message'=>'Invalid parameters']; }
        // Ensure current user owns the task prior to assignment
        if (!$this->canAccessTask($tid, $uid)) { return ['success'=>false,'message'=>'Forbidden']; }
        // Validate target is a supervisor
        try {
            $stmt = $this->pdo->prepare("SELECT id, role, name FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$sid]);
            $u = $stmt->fetch();
            if (!$u || normalizeRole($u['role'] ?? '') !== 'supervisor') {
                return ['success'=>false,'message'=>'Target user is not a supervisor'];
            }
        } catch (Throwable $e) {
            return ['success'=>false,'message'=>'Validation failed'];
        }
        // Perform assignment
        $res = $this->taskModel->assign($tid, $sid);
        if (!$res || !$res['success']) { return $res ?: ['success'=>false,'message'=>'Assignment failed']; }
        // Log transfer as a task update
        $note = 'Task assigned to supervisor ID ' . $sid;
        $this->taskUpdateModel->create($tid, (int)$uid, null, $note, null);
        // Keep visibility for this subcontractor after refresh (24h)
        if (!isset($_SESSION['sc_recent_assigned_away']) || !is_array($_SESSION['sc_recent_assigned_away'])) {
            $_SESSION['sc_recent_assigned_away'] = [];
        }
        $_SESSION['sc_recent_assigned_away'][(int)$tid] = time();
        return ['success'=>true,'message'=>'Task assigned to supervisor'];
    }

    // Projects
    public function projectsAssigned($limit = 100) {
        requireSubContractor();
        $uid = $this->currentUserId();
        $limit = max(1, min(200, (int)$limit));
        // Build recent assigned-away IDs from session (valid for 24h)
        $recent = isset($_SESSION['sc_recent_assigned_away']) && is_array($_SESSION['sc_recent_assigned_away']) ? $_SESSION['sc_recent_assigned_away'] : [];
        $now = time();
        $recentIds = [];
        foreach ($recent as $tid => $ts) { if (($now - (int)$ts) <= 86400) { $recentIds[] = (int)$tid; } }
        // Determine if task_updates exists
        $hasUpdates = false;
        try { $this->pdo->query("SELECT 1 FROM task_updates LIMIT 1"); $hasUpdates = true; } catch (Throwable $e) { $hasUpdates = false; }
        // Collect distinct project IDs from tasks where subcontractor is assigned, has activity, or recently assigned-away
        $base = "SELECT DISTINCT t.project_id FROM tasks t";
        $where = " WHERE (t.assigned_to = ?";
        $params = [(int)$uid];
        if ($hasUpdates) {
            $where .= " OR EXISTS (SELECT 1 FROM task_updates tu WHERE tu.task_id = t.id AND tu.user_id = ?)";
            $params[] = (int)$uid;
        }
        if (!empty($recentIds)) {
            $inRecent = implode(',', array_fill(0, count($recentIds), '?'));
            $where .= " OR t.id IN ($inRecent)";
            $params = array_merge($params, $recentIds);
        }
        $sql = $base . $where . ") ORDER BY t.project_id DESC LIMIT 500"; // cap
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $pids = array_values(array_filter(array_map('intval', array_column($stmt->fetchAll() ?: [], 'project_id'))));
        if (empty($pids)) { return ['success'=>true,'data'=>[]]; }
        // Fetch project rows
        $in = implode(',', array_fill(0, count($pids), '?'));
        $q = "SELECT id, name, description, status, start_date, end_date FROM projects WHERE id IN ($in) ORDER BY id DESC LIMIT {$limit}";
        $ps = $this->pdo->prepare($q);
        $ps->execute($pids);
        return ['success'=>true,'data'=>$ps->fetchAll() ?: []];
    }

    public function projectSnapshot($projectId) {
        requireSubContractor();
        $uid = $this->currentUserId();
        $pids = $this->assignedProjectIds($uid);
        if (!in_array((int)$projectId, array_map('intval', $pids), true)) { return ['success'=>false,'message'=>'Forbidden']; }
        $stmt = $this->pdo->prepare("SELECT id, name, description, status, start_date, end_date FROM projects WHERE id=?");
        $stmt->execute([(int)$projectId]);
        $proj = $stmt->fetch();
        if (!$proj) return ['success'=>false,'message'=>'Not found'];
        // Minimal snapshot; documents placeholder (no file system coupling added)
        $proj['documents'] = [];
        return ['success'=>true,'data'=>$proj];
    }

    // Materials
    public function materialsList() {
        requireSubContractor();
        $uid = $this->currentUserId();
        // Use accessible projects (assigned, activity, or recent assigned-away)
        $pids = $this->accessibleProjectIds($uid);
        if (empty($pids)) { return ['success'=>true,'data'=>[]]; }
        $in = implode(',', array_fill(0, count($pids), '?'));
        $stmt = $this->pdo->prepare("SELECT m.* FROM materials m WHERE m.project_id IN ($in) ORDER BY m.created_at DESC LIMIT 200");
        $stmt->execute(array_map('intval', $pids));
        return ['success'=>true,'data'=>$stmt->fetchAll() ?: []];
    }

    public function materialsRequest($projectId, $materialName, $quantity) {
        requireSubContractor();
        $uid = $this->currentUserId();
        if ($quantity < 1) return ['success'=>false,'message'=>'Quantity must be >=1'];
        // Allow request for any accessible project (matches dropdown source)
        $pids = $this->accessibleProjectIds($uid);
        if (!in_array((int)$projectId, array_map('intval', $pids), true)) { return ['success'=>false,'message'=>'Forbidden']; }
        return $this->materialModel->request((int)$projectId, (int)$uid, $materialName, (int)$quantity);
    }

    // Purchase Orders (read-only)
    public function purchaseOrdersForAssignedProjects($limit = 100) {
        requireSubContractor();
        $uid = $this->currentUserId();
        $pids = $this->assignedProjectIds($uid);
        if (empty($pids)) { return ['success'=>true,'data'=>[]]; }
        $in = implode(',', array_fill(0, count($pids), '?'));
        $limit = max(1, min(200, (int)$limit));
        $stmt = $this->pdo->prepare("SELECT id, project_id, supplier_id, status, total_amount, created_at FROM purchase_orders WHERE project_id IN ($in) ORDER BY id DESC LIMIT {$limit}");
        $stmt->execute(array_map('intval', $pids));
        return ['success'=>true,'data'=>$stmt->fetchAll() ?: []];
    }
}
