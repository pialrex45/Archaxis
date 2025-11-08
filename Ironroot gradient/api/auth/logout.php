<?php
// User logout API endpoint

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
    if (ob_get_length()) { ob_clean(); }
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Enforce active session (idle timeout)
enforceActiveSession((int) (env('SESSION_IDLE_TIMEOUT', 3600)));

// Validate CSRF token (supports X-CSRF-Token header and form csrf_token)
if (!validateCSRFTokenFlexible($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

// Create AuthController instance
$authController = new AuthController();

// Process logout
$result = $authController->logout();

// Set appropriate HTTP status code
if ($result['success']) {
    http_response_code(200); // OK
} else {
    http_response_code(500); // Internal Server Error
}

// Return JSON response
if (ob_get_length()) { ob_clean(); }
echo json_encode($result);
exit();