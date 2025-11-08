<?php
// User approval API endpoint

// Include necessary files
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/controllers/AuthController.php';
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

// Check if user is authenticated
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

// Get current user role
$userRole = getCurrentUserRole();

// Check if user has permission to approve users (admin or manager)
if (!in_array($userRole, ['admin', 'manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions to approve users']);
    exit();
}

// Get JSON input data
$input = json_decode(file_get_contents('php://input'), true);

// If JSON decode failed, get form data
if (json_last_error() !== JSON_ERROR_NONE) {
    $input = $_POST;
}

// Validate CSRF token if it's a form submission
if (!empty($_POST)) {
    // For form submissions, check CSRF token
    if (!isset($input['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $input['csrf_token'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit();
    }
}

// Get user ID to approve
$userId = isset($input['user_id']) ? (int)$input['user_id'] : null;

if (empty($userId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

// Get current user ID
$currentUserId = getCurrentUserId();

// Create AuthController instance
$authController = new AuthController();

// Process user approval
$result = $authController->approveUser($userId, $currentUserId);

// Set appropriate HTTP status code
if ($result['success']) {
    http_response_code(200); // OK
} else {
    http_response_code(400); // Bad Request
}

// Return JSON response
echo json_encode($result);
exit();