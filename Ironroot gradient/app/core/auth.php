<?php
// Authentication and session management

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Harden session cookies
    $cookieParams = session_get_cookie_params();
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    if (function_exists('env')) {
        $secure = (bool) env('SESSION_SECURE', $secure);
        $samesite = env('SESSION_SAMESITE', 'Lax');
    } else {
        $samesite = 'Lax';
    }
    session_set_cookie_params([
        'lifetime' => $cookieParams['lifetime'],
        'path' => $cookieParams['path'],
        'domain' => $cookieParams['domain'],
        'secure' => $secure,
        'httponly' => true,
        'samesite' => $samesite,
    ]);
    session_start();
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Additive: read CSRF token from headers or params
function getRequestCsrfToken() {
    $headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
    $headers = array_change_key_case($headers, CASE_LOWER);
    if (isset($headers['x-csrf-token'])) {
        return $headers['x-csrf-token'];
    }
    if (isset($_POST['csrf_token'])) {
        return $_POST['csrf_token'];
    }
    if (isset($_GET['csrf_token'])) {
        return $_GET['csrf_token'];
    }
    return null;
}

// Additive: flexible CSRF validator (uses header if $token not provided)
function validateCSRFTokenFlexible($token = null) {
    $t = $token ?? getRequestCsrfToken();
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return is_string($t) && hash_equals($_SESSION['csrf_token'], $t);
}

// Check if user is authenticated
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user ID
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

// Get current user role
function getCurrentUserRole() {
    return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
}

// Redirect to login if not authenticated
function requireAuth() {
    if (!isAuthenticated()) {
        redirect(function_exists('url') ? url('/login') : '/login');
        exit();
    }
}

// Redirect to home if already authenticated
function redirectIfAuthenticated() {
    if (isAuthenticated()) {
        redirect(function_exists('url') ? url('/dashboard') : '/dashboard');
        exit();
    }
}

// Check if user has specific role
function hasRole($role) {
    return isAuthenticated() && normalizeRole(getCurrentUserRole()) === normalizeRole($role);
}

// Check if user has any of the specified roles
function hasAnyRole($roles) {
    if (!isAuthenticated()) {
        return false;
    }
    
    $userRole = normalizeRole(getCurrentUserRole());
    $normalized = array_map('normalizeRole', (array)$roles);
    return in_array($userRole, $normalized, true);
}

// Require specific role
function requireRole($role) {
    requireAuth();
    
    if (!hasRole($role)) {
        http_response_code(403);
        die('Access denied. Insufficient permissions.');
    }
}

// Require any of the specified roles
function requireAnyRole($roles) {
    requireAuth();
    
    if (!hasAnyRole($roles)) {
        http_response_code(403);
        die('Access denied. Insufficient permissions.');
    }
}

// Normalize role to a canonical format: lowercase and underscores
function normalizeRole($role) {
    $r = strtolower(trim((string)$role));
    // Convert spaces and hyphens to underscores for consistent comparisons
    $r = str_replace([' ', '-'], '_', $r);
    return $r;
}

// Require supervisor (or admin)
function requireSupervisor() {
    // Additive helper to align with supervisor API guards
    requireAnyRole(['supervisor', 'admin']);
}

// Require site manager (or admin)
function requireSiteManager() {
    // Additive helper to align with site manager API guards
    requireAnyRole(['site_manager', 'admin']);
}

// Require subcontractor (or admin)
function requireSubContractor() {
    // Additive helper for subcontractor-specific API guards
    requireAnyRole(['sub_contractor', 'admin']);
}

// Require site engineer (or admin)
function requireSiteEngineer() {
    // Additive helper for site engineer-specific API guards
    requireAnyRole(['site_engineer', 'admin']);
}

// Require logistic officer (or admin) [additive]
function requireLogisticOfficer() {
    requireAnyRole(['logistic_officer', 'admin']);
}

// Login user
function loginUser($userId, $username, $role) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['user_role'] = $role;
    $_SESSION['last_activity'] = time();
    
    // Regenerate session ID for security
    session_regenerate_id(true);
}

// Logout user
function logoutUser() {
    // Unset all session variables
    $_SESSION = array();
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
}

// Check for session timeout
function checkSessionTimeout($timeout = 3600) { // 1 hour default
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        logoutUser();
        return true;
    }
    
    $_SESSION['last_activity'] = time();
    return false;
}

// Additive: enforce active session and return JSON on timeout
function enforceActiveSession($timeout = 3600) {
    if (checkSessionTimeout($timeout)) {
        http_response_code(440);
        header('Content-Type: application/json');
        if (ob_get_length()) { ob_clean(); }
        echo json_encode(['success' => false, 'message' => 'Session timed out. Please log in again.']);
        exit();
    }
}