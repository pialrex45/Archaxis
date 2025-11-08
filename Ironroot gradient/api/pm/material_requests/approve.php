<?php
require_once __DIR__ . '/../../../app/core/auth.php';
require_once __DIR__ . '/../../../app/core/helpers.php';
require_once __DIR__ . '/../../../app/controllers/ProjectManagerController.php';

header('Content-Type: application/json');
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

try {
    requireAuth(); if (!hasRole('project_manager')) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Access denied']); exit; }
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid CSRF']); exit; }
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id<=0) { http_response_code(422); echo json_encode(['success'=>false,'message'=>'Invalid id']); exit; }
    $ctl = new ProjectManagerController();
    $res = $ctl->approveMaterial($id);
    echo json_encode($res);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
