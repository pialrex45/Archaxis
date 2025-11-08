<?php
// API endpoint for getting user contacts for messaging

require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';
require_once __DIR__ . '/../../app/controllers/UserController.php';

// Set response content type to JSON
header('Content-Type: application/json');

// Check if the user is authenticated
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get current user ID
$userId = getCurrentUserId();
if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID not found']);
    exit;
}

// Create UserController instance
$userController = new UserController();

// Get contacts list - all users except current user
$contacts = $userController->getAllExceptCurrent($userId);

// Return response
echo json_encode(['success' => true, 'data' => $contacts]);
exit;
