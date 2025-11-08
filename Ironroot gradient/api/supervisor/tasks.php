<?php
// Supervisor Tasks API
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/controllers/SupervisorController.php';

if (!headers_sent()) { header('Content-Type: application/json'); }
requireSupervisor();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ctl = new SupervisorController();

try {
    if ($method === 'GET') {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        echo json_encode($ctl->tasksAssigned($limit));
        exit;
    }

    if ($method === 'POST') {
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        // Backward compatibility for pretty URLs: /api/supervisor/tasks/update_status
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
                $last = end($segments);
                $prev = prev($segments);
                if ($last && ($prev === 'tasks' || $prev === 'tasks.php')) {
                    $action = $last;
                }
            }
        }
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        if ($action === 'update_status') {
            $taskId = (int)($input['task_id'] ?? 0);
            $status = trim((string)($input['status'] ?? ''));
            $note = isset($input['note']) ? (string)$input['note'] : null;

            // Optional photo upload
            $photoPath = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/tasks/';
                if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
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

            // Optional photo upload
            $photoPath = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/tasks/';
                if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
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
