<?php
// Delete task API endpoint

// Include necessary files
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/controllers/TaskController.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';

// Require authentication
requireAuth();

// Set content type to JSON
header('Content-Type: application/json');

// Only allow DELETE requests
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
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

// Validate CSRF token (for both form and JSON)
if (!empty($_POST)) {
    if (!isset($input['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $input['csrf_token'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit();
    }
} else {
    if (!isset($input['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $input['csrf_token'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit();
    }
}

// Get task ID from URL parameter or input data
$taskId = isset($_GET['id']) ? (int)$_GET['id'] : (isset($input['id']) ? (int)$input['id'] : 0);

// Validate task ID
if (empty($taskId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Task ID is required']);
    exit();
}

// Create TaskController instance
$taskController = new TaskController();

// Process request
$result = $taskController->delete($taskId);

// Set appropriate HTTP status code
if ($result['success']) {
    http_response_code(200); // OK
} else {
    http_response_code(400); // Bad Request
}

// Return JSON response
echo json_encode($result);
exit();