<?php
// API endpoint for worker projects

require_once __DIR__ . '/../../app/controllers/ProjectController.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isAuthenticated()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get the current user ID from session
$userId = getCurrentUserId();

// Initialize controller
$projectController = new ProjectController();

// Handle GET request to fetch projects
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all projects for the current worker
    $result = $projectController->getProjectsForWorker($userId);
    echo json_encode($result);
    exit;
}

// Unsupported method
echo json_encode(['success' => false, 'message' => 'Unsupported request method']);
