<?php
require_once __DIR__ . '/../../../app/core/auth.php';
require_once __DIR__ . '/../../../app/core/helpers.php';
require_once __DIR__ . '/../../../app/controllers/ProjectManagerController.php';

header('Content-Type: application/json');
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

try {
    requireAuth(); if (!(hasRole('project_manager') || hasRole('admin'))) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Access denied']); exit; }
    // Accept JSON or form-data; CSRF for form-posts only
    $raw = file_get_contents('php://input');
    $asJson = json_decode($raw, true);
    if (is_array($asJson)) {
        $id = (int)($asJson['id'] ?? 0);
        $status = isset($asJson['status']) ? trim((string)$asJson['status']) : '';
    } else {
        if (function_exists('verifyCSRFToken')) {
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid CSRF']); exit; }
        }
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $status = isset($_POST['status']) ? trim((string)$_POST['status']) : '';
    }
    if ($id<=0 || $status==='') { http_response_code(422); echo json_encode(['success'=>false,'message'=>'Invalid input']); exit; }
    $ctl = new ProjectManagerController();
    $res = $ctl->updatePOStatus($id, $status);
    echo json_encode($res);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
