<?php
// Subcontractor Materials API (scoped)
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/controllers/SubContractorController.php';

if (!headers_sent()) { header('Content-Type: application/json'); }
requireSubContractor();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ctl = new SubContractorController();

try {
    if ($method === 'GET') {
        echo json_encode($ctl->materialsList());
        exit;
    }

    if ($method === 'POST') {
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        if ($action === 'request') {
            $projectId = (int)($input['project_id'] ?? 0);
            $name = trim((string)($input['material_name'] ?? ''));
            $qty = (int)($input['quantity'] ?? 0);
            if (!$projectId || !$name || $qty < 1) { echo json_encode(['success'=>false,'message'=>'Invalid inputs']); exit; }
            echo json_encode($ctl->materialsRequest($projectId, $name, $qty));
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
