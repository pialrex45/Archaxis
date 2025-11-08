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

