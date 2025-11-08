<?php
// API: Project Manager create project (POST)
require_once __DIR__ . '/../../../app/core/auth.php';
require_once __DIR__ . '/../../../app/core/helpers.php';
require_once __DIR__ . '/../../../app/controllers/ProjectController.php';

header('Content-Type: application/json');
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

try {
    requireAuth();
    if (!hasRole('project_manager')) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Access denied']); exit; }
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

    // CSRF optional
    if (function_exists('verify_csrf_token')) {
        $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!verify_csrf_token($token)) { http_response_code(419); echo json_encode(['success'=>false,'message'=>'Invalid CSRF token']); exit; }
    }

    // Collect fields compatible with ProjectController::create
    $data = [
        'name' => $_POST['name'] ?? '',
        'description' => $_POST['description'] ?? '',
        'status' => $_POST['status'] ?? 'planning',
        'start_date' => $_POST['start_date'] ?? null,
        'end_date' => $_POST['end_date'] ?? ($_POST['deadline'] ?? null),
    ];

    $ctl = new ProjectController();
    $result = $ctl->create($data);
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
