<?php
// Site Engineer Incidents API (create + optional list by task)
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/controllers/SiteEngineerController.php';

if (!headers_sent()) { header('Content-Type: application/json'); }
requireSiteEngineer();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ctl = new SiteEngineerController();

try {
    if ($method === 'GET') {
        // Minimal: reuse inspections listing style is not applicable; we can expose no-op or future implementation
        http_response_code(405);
        echo json_encode(['success'=>false,'message'=>'Listing incidents not exposed here']);
        exit;
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $type = trim((string)($input['type'] ?? 'Incident'));
        $description = (string)($input['description'] ?? '');
        $taskId = isset($input['task_id']) ? (int)$input['task_id'] : null;
        $photo = isset($input['photo_path']) ? (string)$input['photo_path'] : null;
        if (!$description) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'description required']); exit; }
        echo json_encode($ctl->reportIncident($type, $description, $taskId, $photo));
        exit;
    }

    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method not allowed']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
