<?php
// Get material details API endpoint

// Include necessary files
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/controllers/MaterialController.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';

// Set content type to JSON
header('Content-Type: application/json');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get material ID from URL parameter
$materialId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validate material ID
if (empty($materialId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Material ID is required']);
    exit();
}

// Create MaterialController instance
$materialController = new MaterialController();

// Process request
$result = $materialController->get($materialId);

// Set appropriate HTTP status code
if ($result['success']) {
    http_response_code(200); // OK
} else {
    http_response_code(404); // Not Found
}

// Return JSON response
echo json_encode($result);
exit();