<?php
// API endpoint for updating task progress

require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';
require_once __DIR__ . '/../../app/controllers/TaskController.php';

// Set response content type to JSON
header('Content-Type: application/json');

// Check if the user is authenticated
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get and validate input data
$taskId = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
$notes = isset($_POST['progress_notes']) ? sanitize($_POST['progress_notes']) : '';
$completionPercentage = isset($_POST['completion_percentage']) ? (int)$_POST['completion_percentage'] : 0;

// Validate task ID
if (empty($taskId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Task ID is required']);
    exit;
}

// Validate notes
if (empty($notes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Progress notes are required']);
    exit;
}

// Validate completion percentage
if ($completionPercentage < 0 || $completionPercentage > 100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Completion percentage must be between 0 and 100']);
    exit;
}

// Create TaskController instance
$taskController = new TaskController();

// Handle photo uploads if any
$photos = [];
if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
    $uploadDir = __DIR__ . '/../../public/uploads/tasks/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Process each uploaded file
    foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
        $fileName = $_FILES['photos']['name'][$key];
        $fileSize = $_FILES['photos']['size'][$key];
        $fileError = $_FILES['photos']['error'][$key];
        
        // Skip if there was an upload error
        if ($fileError !== UPLOAD_ERR_OK) {
            continue;
        }
        
        // Generate a unique filename
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = uniqid('task_' . $taskId . '_') . '.' . $fileExtension;
        $targetFile = $uploadDir . $newFileName;
        
        // Move the uploaded file to the target directory
        if (move_uploaded_file($tmp_name, $targetFile)) {
            $photos[] = 'uploads/tasks/' . $newFileName;
        }
    }
}

// Update task progress
$result = $taskController->updateProgress($taskId, $notes, $completionPercentage, $photos);

// Return response
echo json_encode($result);
exit;
