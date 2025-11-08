<?php
require_once __DIR__ . '/../../../app/core/auth.php';
require_once __DIR__ . '/../../../app/core/helpers.php';
require_once __DIR__ . '/../../../app/controllers/ProjectManagerController.php';

header('Content-Type: application/json');
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

try {
    requireAuth();
    if (!hasRole('project_manager')) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Access denied']); exit; }
    $ctl = new ProjectManagerController();
    $res = $ctl->listTasks();
    echo json_encode($res);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
