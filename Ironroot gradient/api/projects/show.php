<?php
// Get project details API endpoint

// Include necessary files
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/controllers/ProjectController.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';

// Require authentication
requireAuth();

// Set content type to JSON
header('Content-Type: application/json');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get project ID from URL parameter
$projectId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validate project ID
if (empty($projectId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Project ID is required']);
    exit();
}

// Create ProjectController instance
$projectController = new ProjectController();

// Process request
$result = $projectController->get($projectId);

// Set appropriate HTTP status code
if ($result['success']) {
    http_response_code(200); // OK
} else {
    http_response_code(404); // Not Found
}

// Return JSON response
echo json_encode($result);
exit();