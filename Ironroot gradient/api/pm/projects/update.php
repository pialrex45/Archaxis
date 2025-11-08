<?php
require_once __DIR__ . '/../../../app/core/auth.php';
require_once __DIR__ . '/../../../app/core/helpers.php';
require_once __DIR__ . '/../../../app/controllers/ProjectManagerController.php';

header('Content-Type: application/json');
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

try {
    // Check authentication and authorization
    requireAuth();
    if (!hasRole('project_manager')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    
    // CSRF check (expects X-CSRF-TOKEN header)
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        http_response_code(419);
        echo json_encode(['success' => false, 'message' => 'CSRF token mismatch']);
        exit;
    }
    
    // Get request data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['project_id']) || !isset($data['data'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request data']);
        exit;
    }
    
    // Sanitize and whitelist fields
    $projectId = (int)$data['project_id'];
    $payload = (array)$data['data'];
    // Only columns that exist in projects table schema
    $allowed = ['name','status','description','start_date','end_date','site_manager_id'];
    $filtered = [];
    foreach ($allowed as $k) {
        if (array_key_exists($k, $payload)) { $filtered[$k] = $payload[$k]; }
    }
    // Normalize status value to DB enum (schema uses 'on hold')
    if (isset($filtered['status']) && $filtered['status'] === 'on_hold') {
        $filtered['status'] = 'on hold';
    }
    
    // Debug logging
    error_log('PM update payload: id=' . $projectId . ' data=' . json_encode($filtered));
    
    // Instantiate controller and call update method
    $controller = new ProjectManagerController();
    $result = $controller->updateProject($projectId, $filtered);
    
    error_log('PM update result: ' . json_encode($result));
    
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
