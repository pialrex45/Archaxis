<?php
// Site Engineer Inspections API
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/controllers/SiteEngineerController.php';

if (!headers_sent()) { header('Content-Type: application/json'); }
requireSiteEngineer();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ctl = new SiteEngineerController();

try {
    if ($method === 'GET') {
        $taskId = isset($_GET['task_id']) ? (int)$_GET['task_id'] : 0;
        if (!$taskId) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'task_id is required']); exit; }
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        echo json_encode($ctl->listInspectionsByTask($taskId, $limit));
        exit;
    }

    if ($method === 'POST') {
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        if ($action === 'create_inspection') {
            $taskId = (int)($input['task_id'] ?? 0);
            $zoneId = isset($input['zone_id']) ? (string)$input['zone_id'] : null;
            $status = (string)($input['status'] ?? 'in_review');
            $remarks = isset($input['remarks']) ? (string)$input['remarks'] : null;
            $attachments = isset($input['attachments']) && is_array($input['attachments']) ? $input['attachments'] : [];
            if (!$taskId) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'task_id required']); exit; }
            echo json_encode($ctl->createInspection($taskId, $zoneId, $status, $remarks, $attachments));
            exit;
        } elseif ($action === 'update_task_inspection') {
            $taskId = (int)($input['task_id'] ?? 0);
            $status = (string)($input['status'] ?? 'in_review');
            $notes = isset($input['notes']) ? (string)$input['notes'] : null;
            if (!$taskId) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'task_id required']); exit; }
            echo json_encode($ctl->updateTaskInspection($taskId, $status, $notes));
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
