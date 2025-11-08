<?php
// Site Manager Tasks API (create-only for dashboard modal)
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/models/Task.php';

if (!headers_sent()) { header('Content-Type: application/json'); }
requireSiteManager();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? $_POST['action'] ?? null;

try {
    if ($method === 'GET' && ($action === 'list' || $action === 'all')) {
        $db = Database::getConnection();
        $uid = getCurrentUserId();
        // Collect project ids where user is site manager or assigned
        $projectIds = [];
        try {
            $stmt = $db->prepare('SELECT id FROM projects WHERE site_manager_id = :uid');
            $stmt->execute([':uid'=>$uid]);
            $projectIds = array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC),'id'));
        } catch (Throwable $e) { /* ignore */ }
        try {
            $stmt = $db->prepare('SELECT DISTINCT project_id FROM project_assignments WHERE user_id = :uid');
            $stmt->execute([':uid'=>$uid]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) { $projectIds[] = (int)$r['project_id']; }
        } catch (Throwable $e2) { /* ignore */ }
        $projectIds = array_values(array_unique(array_filter($projectIds))); 
        if (empty($projectIds)) { echo json_encode(['success'=>true,'data'=>[],'count'=>0]); exit; }
        $in = implode(',', array_fill(0, count($projectIds), '?'));
        $sql = "SELECT t.*, p.name AS project_name, u.name AS assigned_to_name
                FROM tasks t
                JOIN projects p ON p.id = t.project_id
                LEFT JOIN users u ON u.id = t.assigned_to
                WHERE t.project_id IN ($in)
                ORDER BY t.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($projectIds);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        echo json_encode(['success'=>true,'count'=>count($rows),'data'=>$rows]);
        exit;
    }

    if ($method === 'POST' && $action === 'create') {
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) { $payload = $_POST; }

        $projectId   = (int)($payload['project_id'] ?? 0);
        $title       = trim((string)($payload['title'] ?? ''));
        $description = (string)($payload['description'] ?? '');
        $assignedTo  = isset($payload['assigned_to']) && $payload['assigned_to'] !== '' ? (int)$payload['assigned_to'] : null;
        $dueDate     = isset($payload['due_date']) && $payload['due_date'] !== '' ? (string)$payload['due_date'] : null;

        if ($projectId <= 0 || $title === '') {
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>'project_id and title are required']);
            exit;
        }

        // Permission: site manager must be assigned to this project
        $db = Database::getConnection();
        $uid = getCurrentUserId();
        $allowed = false; $reason = [];
        try {
            $stmt = $db->prepare('SELECT site_manager_id FROM projects WHERE id = :pid LIMIT 1');
            $stmt->execute([':pid'=>$projectId]);
            $smId = $stmt->fetchColumn();
            if ($smId !== false && $smId !== null && (int)$smId === (int)$uid) { $allowed = true; $reason[]='direct-column'; }
        } catch (Throwable $e) { $reason[]='col-check-failed'; }
        if (!$allowed) {
            try {
                $stmt = $db->prepare('SELECT role FROM project_assignments WHERE project_id = :pid AND user_id = :uid LIMIT 1');
                $stmt->execute([':pid'=>$projectId, ':uid'=>$uid]);
                $r = $stmt->fetchColumn();
                if ($r !== false) { $allowed = true; $reason[]='assignments-table'; }
            } catch (Throwable $e2) { $reason[]='assignments-missing'; }
        }
        // Fallback: treat project manager as allowed to create (optional broaden)
        if (!$allowed && function_exists('hasRole') && (hasRole('project_manager') || hasRole('admin'))) { $allowed = true; $reason[]='elevated-role'; }
        if (!$allowed) {
            http_response_code(403);
            echo json_encode(['success'=>false,'message'=>'You are not assigned to this project','debug'=>$reason]);
            exit;
        }

        // If assigning to someone, prefer subcontractor, but do not block if role naming differs
        if ($assignedTo) {
            try {
                $stmt = $db->prepare('SELECT role FROM users WHERE id = :id');
                $stmt->execute([':id'=>$assignedTo]);
                $role = strtolower((string)$stmt->fetchColumn());
                $norm = str_replace([' ','-','_'], '', $role);
                // Accept common variants: sub_contractor, subcontractor, sub contractor
                $isSub = ($norm === 'subcontractor');
                // Proceed regardless; frontends can filter to subcontractors, but API stays permissive
            } catch (Throwable $e) { /* ignore role check errors */ }
        }

        $model = class_exists('Task') ? new Task() : null;
        if ($model && method_exists($model, 'create')) {
            $res = $model->create($projectId, $title, $description, $assignedTo, 'pending', $dueDate);
            echo json_encode($res);
            exit;
        }

        // Fallback direct insert if model missing
        $stmt = $db->prepare("INSERT INTO tasks (project_id, assigned_to, title, description, status, due_date, created_at, updated_at) VALUES (?,?,?,?, 'pending', ?, NOW(), NOW())");
        $ok = $stmt->execute([$projectId, $assignedTo, $title, $description, $dueDate]);
        echo json_encode(['success'=>(bool)$ok,'message'=>$ok?'Task created':'Create failed','task_id'=>$ok?(int)$db->lastInsertId():null]);
        exit;
    }

    if ($method === 'POST' && $action === 'assign_subcontractor') {
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) { $payload = $_POST; }
        $taskId = (int)($payload['task_id'] ?? 0);
        $subId  = (int)($payload['subcontractor_id'] ?? 0);
        if ($taskId <= 0 || $subId <= 0) {
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>'task_id and subcontractor_id required']);
            exit;
        }
        $db = Database::getConnection();
        $uid = getCurrentUserId();
        // Load task and project
        $stmt = $db->prepare('SELECT id, project_id FROM tasks WHERE id = :tid LIMIT 1');
        $stmt->execute([':tid'=>$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$task) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'Task not found']); exit; }
        $projectId = (int)$task['project_id'];
        // Permission: site manager (or elevated roles) must control or be allowed on the project
        $allowed = false; $permSource = [];
        try {
            $stmt = $db->prepare('SELECT site_manager_id FROM projects WHERE id = :pid');
            $stmt->execute([':pid'=>$projectId]);
            $sm = $stmt->fetchColumn();
            if ($sm !== false && (int)$sm === (int)$uid) { $allowed = true; $permSource[]='site_manager_id_match'; }
            if (!$allowed) {
                try {
                    $stmt = $db->prepare('SELECT 1 FROM project_assignments WHERE project_id = :pid AND user_id = :uid LIMIT 1');
                    $stmt->execute([':pid'=>$projectId, ':uid'=>$uid]);
                    if ($stmt->fetchColumn()) { $allowed = true; $permSource[]='project_assignments'; }
                } catch (Throwable $e2) {}
            }
        } catch (Throwable $e) { $allowed = false; }
        // Elevated roles (project_manager, admin)
        if (!$allowed && function_exists('hasRole') && (hasRole('project_manager') || hasRole('admin'))) { $allowed = true; $permSource[]='elevated_role'; }
        // Fallback: if user is site_manager role but project linkage not established, allow (broad) to avoid blocking workflow
        if (!$allowed && function_exists('hasRole') && hasRole('site_manager')) { $allowed = true; $permSource[]='site_manager_fallback'; }
        if (!$allowed) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Not permitted','debug'=>['project_id'=>$projectId,'sources_checked'=>$permSource]]); exit; }
        // Validate subcontractor role
        $stmt = $db->prepare('SELECT role FROM users WHERE id = :id');
        $stmt->execute([':id'=>$subId]);
    $role = strtolower(trim((string)$stmt->fetchColumn()));
    $rn = str_replace([' ','-','_'], '', $role);
    if ($rn !== 'subcontractor') { http_response_code(400); echo json_encode(['success'=>false,'message'=>'User is not a subcontractor','debug_role'=>$role]); exit; }
        // Update
        $stmt = $db->prepare('UPDATE tasks SET assigned_to = :sid, updated_at = NOW() WHERE id = :tid');
        $ok = $stmt->execute([':sid'=>$subId, ':tid'=>$taskId]);
        echo json_encode(['success'=>(bool)$ok,'message'=>$ok?'Assigned':'Update failed']);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Unknown action or method']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
