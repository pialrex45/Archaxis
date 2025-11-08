<?php
// Create project API endpoint

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

// Validate CSRF token for both form and JSON
if (!isset($input['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $input['csrf_token'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

// Create ProjectController instance
$projectController = new ProjectController();

// Process request
$result = $projectController->create($input);

// Set appropriate HTTP status code
if ($result['success']) {
    http_response_code(201); // Created
} else {
    http_response_code(400); // Bad Request
}

// Return JSON response
echo json_encode($result);
exit();