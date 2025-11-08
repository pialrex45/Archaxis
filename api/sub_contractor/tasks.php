<?php
// Subcontractor Tasks API (scoped)
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/controllers/SubContractorController.php';

if (!headers_sent()) { header('Content-Type: application/json'); }
requireSubContractor();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ctl = new SubContractorController();

try {
    if ($method === 'GET') {
        // Support /api/sub_contractor/tasks/{id} or ?task_id=ID for detail
        $taskId = isset($_GET['task_id']) ? (int)$_GET['task_id'] : null;
        if (!$taskId) {
            $path = $_SERVER['REQUEST_URI'] ?? '';
            $clean = strtok($path,'?');
            $segments = array_values(array_filter(explode('/', $clean)));
            $last = end($segments);
            if (ctype_digit($last)) { $taskId = (int)$last; }
        }
        if ($taskId) {
            // Return minimal task row + prior updates if accessible via controller logic
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('SELECT t.id, t.project_id, t.title, t.description, t.status, t.due_date, t.assigned_to, t.created_at, t.updated_at FROM tasks t WHERE t.id = ? LIMIT 1');
            $stmt->execute([$taskId]);
            $task = $stmt->fetch();
            if (!$task) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }
            // Access gate reuse via controller private method path (simpler: instantiate and call canAccessTask via reflection not available; replicate minimal logic)
            $uid = getCurrentUserId();
            $allowed = false;
            if ((int)$task['assigned_to'] === (int)$uid) { $allowed = true; }
            if (!$allowed) {
                // recent assigned-away
                $recent = $_SESSION['sc_recent_assigned_away'] ?? [];
                $now = time();
                foreach ($recent as $tid => $ts) { if ((int)$tid === (int)$taskId && ($now - (int)$ts) <= 86400) { $allowed = true; break; } }
            }
            if (!$allowed) {
                try {
                    $cs = $pdo->prepare('SELECT 1 FROM task_updates WHERE task_id = ? AND user_id = ? LIMIT 1');
                    $cs->execute([$taskId, $uid]);
                    if ($cs->fetchColumn()) { $allowed = true; }
                } catch (Throwable $e) { /* ignore */ }
            }
            if (!$allowed) { echo json_encode(['success'=>false,'message'=>'Forbidden']); exit; }
            // Fetch updates
            $updates = [];
            try {
                $us = $pdo->prepare('SELECT id, status, note, photo_path, created_at FROM task_updates WHERE task_id = ? ORDER BY id DESC LIMIT 500');
                $us->execute([$taskId]);
                $updates = $us->fetchAll() ?: [];
            } catch (Throwable $e) { /* ignore */ }
            $task['updates'] = $updates;
            echo json_encode(['success'=>true,'data'=>$task]);
            exit;
        }
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        echo json_encode($ctl->tasksAssigned($limit));
        exit;
    }

    if ($method === 'POST') {
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        // Backward compatibility: support pretty URLs like /api/sub_contractor/tasks/update_status
        if ($action === '') {
            $pathInfo = '';
            if (!empty($_SERVER['PATH_INFO'])) {
                $pathInfo = $_SERVER['PATH_INFO'];
            } else {
                $uri = $_SERVER['REQUEST_URI'] ?? '';
                $pathInfo = explode('?', $uri, 2)[0];
            }
            if ($pathInfo) {
                $segments = array_values(array_filter(explode('/', $pathInfo)));
                if (!empty($segments)) {
                    $last = end($segments);
                    $prev = prev($segments);
                    // If we came via index.php fallback, PATH_INFO may only be '/update_status'
                    if ($last && (($prev === 'tasks' || $prev === 'tasks.php') || count($segments) === 1)) {
                        $action = $last;
                    }
                }
            }
        }
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        if ($action === 'update_status') {
            $taskId = (int)($input['task_id'] ?? 0);
            $status = trim((string)($input['status'] ?? ''));
            $note = isset($input['note']) ? (string)$input['note'] : null;
            
            // Handle file upload if present
            $photoPath = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/tasks/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['photo']['name']);
                $uploadFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadFile)) {
                    $photoPath = '/uploads/tasks/' . $fileName;
                }
            } else {
                $photoPath = isset($input['photo_path']) ? (string)$input['photo_path'] : null;
            }
            
            echo json_encode($ctl->taskUpdateStatus($taskId, $status, $note, $photoPath));
            exit;
        } elseif ($action === 'add_update' || $action === 'add_progress') {
            $taskId = (int)($input['task_id'] ?? 0);
            $status = isset($input['status']) ? (string)$input['status'] : null;
            $note = isset($input['note']) ? (string)$input['note'] : null;
            
            // Handle file upload if present
            $photoPath = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/tasks/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['photo']['name']);
                $uploadFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadFile)) {
                    $photoPath = '/uploads/tasks/' . $fileName;
                }
            } else {
                $photoPath = isset($input['photo_path']) ? (string)$input['photo_path'] : null;
            }
            echo json_encode($ctl->taskAddProgress($taskId, $note, $status, $photoPath));
            exit;
        } elseif ($action === 'assign_to_supervisor') {
            // Re-assign a task from the current subcontractor to a supervisor
            $taskId = (int)($input['task_id'] ?? 0);
            $supervisorId = (int)($input['supervisor_id'] ?? 0);
            echo json_encode($ctl->taskAssignSupervisor($taskId, $supervisorId));
            exit;
        }
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>'Invalid action']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method not allowed']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}

