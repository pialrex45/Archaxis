<?php
// New API endpoint for logout functionality

require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/helpers.php';

header('Content-Type: application/json');

// Start session if not already started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Clear all session data
$_SESSION = array();

// If a session cookie exists, destroy it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Set cache control headers to prevent browsers from caching this request
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Return success
echo json_encode(['success' => true]);
