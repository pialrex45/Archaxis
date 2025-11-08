<?php
// API endpoint for getting projects assigned to a worker

require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';
require_once __DIR__ . '/../../app/controllers/ProjectController.php';

// Set response content type to JSON
header('Content-Type: application/json');

// Check if the user is authenticated
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if user is a worker
if (!hasRole('worker')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Workers only.']);
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

// Create ProjectController instance
$projectController = new ProjectController();

// Get projects that the worker is assigned to
$projects = $projectController->getProjectsForWorker($userId);

// Return response
echo json_encode(['success' => true, 'data' => $projects]);
exit;
