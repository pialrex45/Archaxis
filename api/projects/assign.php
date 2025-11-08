<?php
// Assign project to user API endpoint

// Include necessary files
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/controllers/ProjectController.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';

// Set content type to JSON
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input data
$input = json_decode(file_get_contents('php://input'), true);

// If JSON decode failed, get form data
if (json_last_error() !== JSON_ERROR_NONE) {
    $input = $_POST;
}

// Get project ID and user ID from input data
$projectId = isset($input['project_id']) ? (int)$input['project_id'] : 0;
$userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;

// Validate project ID and user ID
if (empty($projectId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Project ID is required']);
    exit();
}

if (empty($userId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

// Create ProjectController instance
$projectController = new ProjectController();

// Process request
$result = $projectController->assign($projectId, $userId);

// Set appropriate HTTP status code
if ($result['success']) {
    http_response_code(200); // OK
} else {
    http_response_code(400); // Bad Request
}

// Return JSON response
echo json_encode($result);
exit();