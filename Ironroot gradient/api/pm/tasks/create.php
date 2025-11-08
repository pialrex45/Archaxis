<?php
require_once __DIR__ . '/../../../app/core/auth.php';
require_once __DIR__ . '/../../../app/core/helpers.php';
require_once __DIR__ . '/../../../app/controllers/ProjectManagerController.php';

header('Content-Type: application/json');
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

try {
    requireAuth();
    if (!hasRole('project_manager')) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Access denied']); exit; }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf_token($csrfToken)) { http_response_code(419); echo json_encode(['success'=>false,'message'=>'CSRF token mismatch']); exit; }

    $data = json_decode(file_get_contents('php://input'), true) ?: [];

    $controller = new ProjectManagerController();
    $result = $controller->createTask($data);
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
