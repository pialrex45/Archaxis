<?php
// User login API endpoint

// Include necessary files
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/controllers/AuthController.php';
require_once __DIR__ . '/../../app/core/helpers.php';
require_once __DIR__ . '/../../app/core/auth.php'; // ensure session is started for CSRF token

// Set content type to JSON
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input data
$input = json_decode(file_get_contents('php://input'), true);

// If JSON decode failed, get form data
if (json_last_error() !== JSON_ERROR_NONE) {
    $input = $_POST;
}

// Validate CSRF token
// For JSON requests, accept X-CSRF-Token header; for form submissions, accept csrf_token field
$csrfValid = false;
if (!empty($_POST)) {
    $csrfValid = validateCSRFTokenFlexible($input['csrf_token'] ?? null);
} else {
    $csrfValid = validateCSRFTokenFlexible();
}
if (!$csrfValid) {
    http_response_code(400);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

// Create AuthController instance
$authController = new AuthController();

// Process login
$result = $authController->login($input);

// Set appropriate HTTP status code
if ($result['success']) {
    http_response_code(200); // OK
} else {
    http_response_code(401); // Unauthorized
}

// Return JSON response
if (ob_get_length()) { ob_clean(); }
echo json_encode($result);
exit();