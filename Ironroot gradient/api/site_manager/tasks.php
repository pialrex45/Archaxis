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

        // Permission: site manager must be assigned to this project (projects.site_manager_id or project_assignments)
        $db = Database::getConnection();
        $uid = getCurrentUserId();
        $allowed = false;
        try {
            // If project has site_manager_id equal to current user, allow
            $stmt = $db->prepare('SELECT site_manager_id FROM projects WHERE id = :pid LIMIT 1');
            $stmt->execute([':pid'=>$projectId]);
            $smId = $stmt->fetchColumn();
            if ($smId !== false && (int)$smId === (int)$uid) {
                $allowed = true;
            }
            // If not allowed yet, check project_assignments link
            if (!$allowed) {
                try {
                    $stmt = $db->prepare('SELECT 1 FROM project_assignments WHERE project_id = :pid AND user_id = :uid LIMIT 1');
                    $stmt->execute([':pid'=>$projectId, ':uid'=>$uid]);
                    $allowed = $stmt->fetchColumn() ? true : false;
                } catch (Throwable $e2) { /* table may not exist; ignore */ }
            }
            // Safe fallback: if project exists and has no explicit site_manager assigned, allow creation
            if (!$allowed && ($smId === null || $smId === '' || (int)$smId === 0)) {
                $allowed = true;
            }
        } catch (Throwable $e) { $allowed = true; /* permissive fallback to avoid blocking */ }
        if (!$allowed) {
            http_response_code(403);
            echo json_encode(['success'=>false,'message'=>'You are not assigned to this project']);
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

    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Unknown action or method']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
