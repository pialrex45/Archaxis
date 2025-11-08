<?php
// API endpoint for user contacts

require_once __DIR__ . '/../../app/controllers/UserController.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';

// Check if user is logged in
if (!isAuthenticated()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get the current user ID from session
$userId = getCurrentUserId();

// Initialize controller
$userController = new UserController();

// Handle GET request to fetch contacts
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all contacts for the current user
    $result = $userController->getUserContacts($userId);
    echo json_encode($result);
    exit;
}

// Unsupported method
echo json_encode(['success' => false, 'message' => 'Unsupported request method']);
