<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/Material.php';
require_once __DIR__ . '/../models/TaskUpdate.php';
require_once __DIR__ . '/../models/Inspection.php';

class SiteEngineerController {
    private $pdo;
    private $taskModel;
    private $projectModel;
    private $materialModel;
    private $taskUpdateModel;
    private $inspectionModel;

    public function __construct() {
        $this->pdo = Database::getConnection();
        $this->taskModel = new Task();
        $this->projectModel = new Project();
        $this->materialModel = new Material();
        $this->taskUpdateModel = new TaskUpdate();
        $this->inspectionModel = new Inspection();
    }

    private function currentUserId() { return getCurrentUserId(); }

    private function assignedProjectIds($userId) {
        $sql = "SELECT DISTINCT t.project_id FROM tasks t WHERE t.assigned_to = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$userId]);
        return array_map('intval', array_column($stmt->fetchAll() ?: [], 'project_id'));
    }

    private function canAccessTask($taskId, $userId) {
        $stmt = $this->pdo->prepare("SELECT 1 FROM tasks WHERE id=? AND assigned_to=?");
        $stmt->execute([(int)$taskId, (int)$userId]);
        return (bool)$stmt->fetchColumn();
    }

    // --------------------
    // Tasks (read + inspection updates only)
    // --------------------
    public function tasksAssigned($limit = 100) {
        requireSiteEngineer();
        $uid = $this->currentUserId();
        $limit = max(1, min(200, (int)$limit));
        $stmt = $this->pdo->prepare(
            "SELECT t.*, p.name AS project_name FROM tasks t JOIN projects p ON p.id=t.project_id WHERE t.assigned_to=? ORDER BY t.due_date IS NULL, t.due_date ASC, t.id DESC LIMIT {$limit}"
        );
        $stmt->execute([(int)$uid]);
        return ['success'=>true,'data'=>$stmt->fetchAll() ?: []];
    }

    public function createInspection($taskId, $zoneId, $status, $remarks, $attachments = []) {
        requireSiteEngineer();
        $uid = $this->currentUserId();
        if (!$this->canAccessTask($taskId, $uid)) { return ['success'=>false,'message'=>'Forbidden']; }
        $allowed = ['pending','passed','failed','rework','in_review'];
        if (!in_array($status, $allowed, true)) return ['success'=>false,'message'=>'Invalid status'];
        $t = $this->pdo->prepare("SELECT id, project_id FROM tasks WHERE id = ?");
        $t->execute([(int)$taskId]);
        $task = $t->fetch();
        if (!$task) return ['success'=>false,'message'=>'Task not found'];

        $res = $this->inspectionModel->create((int)$taskId, (int)$task['project_id'], $zoneId, $status, $remarks, (int)$uid);
        if (!$res['success']) return $res;
        $iid = (int)$res['inspection_id'];
        foreach ($attachments as $path) { if ($path) $this->inspectionModel->addAttachment($iid, $path); }
        // Optional: also log to task_updates for traceability (without changing task status)
        $this->taskUpdateModel->create((int)$taskId, (int)$uid, null, trim("[inspection: {$status}] ".$remarks), null);
        return ['success'=>true,'inspection_id'=>$iid];
    }

    public function listInspectionsByTask($taskId, $limit = 100) {
        requireSiteEngineer();
        $uid = $this->currentUserId();
        if (!$this->canAccessTask($taskId, $uid)) { return ['success'=>false,'message'=>'Forbidden']; }
        return ['success'=>true,'data'=>$this->inspectionModel->listByTask((int)$taskId, $limit)];
    }

    public function updateTaskInspection($taskId, $status, $notes) {
        requireSiteEngineer();
        return $this->createInspection((int)$taskId, null, $status, $notes, []);
    }

    // --------------------
    // Projects (scoped read-only)
    // --------------------
    public function projects($projectId = null, $limit = 100) {
        requireSiteEngineer();
        $uid = $this->currentUserId();
        $pids = $this->assignedProjectIds($uid);
        if ($projectId) {
            if (!in_array((int)$projectId, $pids, true)) { return ['success'=>false,'message'=>'Forbidden']; }
            $stmt = $this->pdo->prepare("SELECT id, name, description, status, start_date, end_date FROM projects WHERE id = ?");
            $stmt->execute([(int)$projectId]);
            $row = $stmt->fetch();
            if (!$row) return ['success'=>false,'message'=>'Not found'];
            $row['milestones'] = [];
            return ['success'=>true,'data'=>$row];
        }
        if (empty($pids)) return ['success'=>true,'data'=>[]];
        $in = implode(',', array_fill(0, count($pids), '?'));
        $limit = max(1, min(200, (int)$limit));
        $stmt = $this->pdo->prepare("SELECT id, name, description, status, start_date, end_date FROM projects WHERE id IN ($in) ORDER BY id DESC LIMIT {$limit}");
        $params = array_map('intval', $pids);
        $stmt->execute($params);
        return ['success'=>true,'data'=>$stmt->fetchAll() ?: []];
    }

    // --------------------
    // Drawings (scoped read-only)
    // --------------------
    public function drawingsByProject($projectId, $limit = 100) {
        requireSiteEngineer();
        $uid = $this->currentUserId();
        $pids = $this->assignedProjectIds($uid);
        if (!in_array((int)$projectId, $pids, true)) { return ['success'=>false,'message'=>'Forbidden']; }
        $limit = max(1, min(200, (int)$limit));
        $stmt = $this->pdo->prepare("SELECT id, title, file_path, version, created_at FROM drawings WHERE project_id = ? ORDER BY id DESC LIMIT {$limit}");
        $stmt->execute([(int)$projectId]);
        return ['success'=>true,'data'=>$stmt->fetchAll() ?: []];
    }

    // --------------------
    // Incidents (create only; scoped to assigned tasks/projects)
    // --------------------
    public function reportIncident($type, $description, $taskId = null, $photoPath = null) {
        requireSiteEngineer();
        $uid = $this->currentUserId();
        $projectId = null;
        if ($taskId) {
            if (!$this->canAccessTask($taskId, $uid)) { return ['success'=>false,'message'=>'Forbidden']; }
            $stmt = $this->pdo->prepare("SELECT project_id FROM tasks WHERE id = ?");
            $stmt->execute([(int)$taskId]);
            $row = $stmt->fetch();
            if ($row) { $projectId = (int)$row['project_id']; }
        }
        $stmt = $this->pdo->prepare("INSERT INTO incidents (reported_by, project_id, task_id, title, description, severity, photo_path, status, created_at) VALUES (?,?,?,?,?,?,?, 'open', NOW())");
        $title = $type ?: 'Incident';
        $severity = 'medium';
        $ok = $stmt->execute([(int)$uid, $projectId, $taskId, $title, $description, $severity, $photoPath]);
        if (!$ok) return ['success'=>false,'message'=>'Failed to report incident'];
        $incidentId = (int)$this->pdo->lastInsertId();
        return ['success'=>true,'incident_id'=>$incidentId];
    }

    // --------------------
    // Materials (scoped read-only)
    // --------------------
    public function materials($projectId = null, $limit = 100) {
        requireSiteEngineer();
        $uid = $this->currentUserId();
        $pids = $this->assignedProjectIds($uid);
        $limit = max(1, min(200, (int)$limit));
        if ($projectId) {
            if (!in_array((int)$projectId, $pids, true)) { return ['success'=>false,'message'=>'Forbidden']; }
            $stmt = $this->pdo->prepare("SELECT * FROM materials WHERE project_id = ? ORDER BY created_at DESC LIMIT {$limit}");
            $stmt->execute([(int)$projectId]);
            return ['success'=>true,'data'=>$stmt->fetchAll() ?: []];
        }
        if (empty($pids)) { return ['success'=>true,'data'=>[]]; }
        $in = implode(',', array_fill(0, count($pids), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM materials WHERE project_id IN ($in) ORDER BY created_at DESC LIMIT {$limit}");
        $stmt->execute(array_map('intval', $pids));
        return ['success'=>true,'data'=>$stmt->fetchAll() ?: []];
    }

    // --------------------
    // Purchase Orders (scoped read-only)
    // --------------------
    public function purchaseOrders($projectId = null, $limit = 100) {
        requireSiteEngineer();
        $uid = $this->currentUserId();
        $pids = $this->assignedProjectIds($uid);
        $limit = max(1, min(200, (int)$limit));
        if ($projectId) {
            if (!in_array((int)$projectId, $pids, true)) { return ['success'=>false,'message'=>'Forbidden']; }
            $stmt = $this->pdo->prepare("SELECT id, project_id, supplier_id, status, total_amount, created_at FROM purchase_orders WHERE project_id = ? ORDER BY id DESC LIMIT {$limit}");
            $stmt->execute([(int)$projectId]);
            return ['success'=>true,'data'=>$stmt->fetchAll() ?: []];
        }
        if (empty($pids)) { return ['success'=>true,'data'=>[]]; }
        $in = implode(',', array_fill(0, count($pids), '?'));
        $stmt = $this->pdo->prepare("SELECT id, project_id, supplier_id, status, total_amount, created_at FROM purchase_orders WHERE project_id IN ($in) ORDER BY id DESC LIMIT {$limit}");
        $stmt->execute(array_map('intval', $pids));
        return ['success'=>true,'data'=>$stmt->fetchAll() ?: []];
    }

    // --------------------
    // Catalogs (read-only)
    // --------------------
    public function products($limit = 50) {
        requireSiteEngineer();
        $limit = max(1, min(200, (int)$limit));
        $stmt = $this->pdo->query("SELECT id, name, unit, unit_price, supplier_id, status FROM products ORDER BY id DESC LIMIT {$limit}");
        return ['success'=>true,'data'=>$stmt->fetchAll() ?: []];
    }

    public function suppliers($limit = 50) {
        requireSiteEngineer();
        $limit = max(1, min(200, (int)$limit));
        $stmt = $this->pdo->query("SELECT id, name, email, phone, address, rating FROM suppliers ORDER BY created_at DESC LIMIT {$limit}");
        return ['success'=>true,'data'=>$stmt->fetchAll() ?: []];
    }

    // --------------------
    // Messaging (send only)
    // --------------------
    public function sendMessage($receiverId, $subject, $body) {
        requireSiteEngineer();
        $uid = $this->currentUserId();
        $text = $subject ? ("[".$subject."]\n\n".$body) : $body;
        $stmt = $this->pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message_text, created_at) VALUES (?,?,?,NOW())");
        $ok = $stmt->execute([(int)$uid, (int)$receiverId, $text]);
        return $ok ? ['success'=>true] : ['success'=>false,'message'=>'Failed to send'];
    }
}
