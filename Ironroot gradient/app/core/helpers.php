<?php
// Helper functions

// Global redirect helper (additive)
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit();
    }
}

// Global url helper (additive) to prefix APP_URL base
if (!function_exists('url')) {
    function url($path = '') {
        // Try to get the base URL from the environment
        $baseUrl = defined('APP_URL') ? APP_URL : '';
        if (empty($baseUrl) && function_exists('env')) {
            $baseUrl = env('APP_URL', '');
        }
        
        // If no base URL is defined, construct it from the request
        if (empty($baseUrl) && isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_SCHEME'])) {
            $baseUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        } else if (empty($baseUrl)) {
            // Fallback to just using relative paths
            $baseUrl = '';
        }
        
        $baseUrl = rtrim($baseUrl, '/');
        return $baseUrl . ($path ? '/' . ltrim($path, '/') : '');
    }
}

// Sanitize user input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// --- Additive: CSRF token helpers for secure POST forms ---
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

// For compatibility with the existing code
if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        return generate_csrf_token();
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        return is_string($token) && is_string($sessionToken) && hash_equals($sessionToken, $token);
    }
}

// Generate JSON response
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Get the current user's name
function getCurrentUserName() {
    $userId = getCurrentUserId();
    if (!$userId) {
        return 'Guest';
    }
    
    // Get the user from session if available
    if (isset($_SESSION['user']['name'])) {
        return $_SESSION['user']['name'];
    }
    
    // Otherwise fetch from database
    require_once __DIR__ . '/../models/User.php';
    $userModel = new User();
    $user = $userModel->getById($userId);
    
    return $user ? ($user['name'] ?? 'User') : 'User';
}

// Validate email format
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate phone number format
function validatePhone($phone) {
    // Simple validation for common phone number formats
    return preg_match('/^[\+]?[0-9\s\-\(\)]{10,20}$/', $phone);
}

// Format date for display
function formatDate($date, $format = 'M j, Y') {
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

// Format currency
function formatCurrency($amount, $currency = 'USD') {
    return '$' . number_format($amount, 2);
}

// Generate random string
function generateRandomString($length = 10) {
    // Ensure we pass an integer number of bytes and return exact length
    $length = max(1, (int)$length);
    $bytes = intdiv($length + 1, 2); // enough bytes for hex to cover length
    return substr(bin2hex(random_bytes($bytes)), 0, $length);
}

// Calculate time ago
function timeAgo($timestamp) {
    $timeDiff = time() - strtotime($timestamp);
    
    if ($timeDiff < 60) {
        return 'Just now';
    } elseif ($timeDiff < 3600) {
        $minutes = floor($timeDiff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($timeDiff < 86400) {
        $hours = floor($timeDiff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        $days = floor($timeDiff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
}

// Truncate text
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}

// Get file extension
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

// Check if file type is allowed
function isAllowedFileType($filename, $allowedTypes = []) {
    if (empty($allowedTypes)) {
        $allowedTypes = explode(',', env('ALLOWED_FILE_TYPES', 'jpg,jpeg,png,pdf,doc,docx'));
    }
    
    $extension = getFileExtension($filename);
    return in_array($extension, $allowedTypes);
}

// Format file size
function formatFileSize($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Convert line breaks to <br> tags
function nl2brSafe($string) {
    return nl2br(htmlspecialchars($string, ENT_QUOTES, 'UTF-8'));
}

// Check if string contains only alphanumeric characters
function isAlphanumeric($string) {
    return ctype_alnum($string);
}

// Generate slug from string
function generateSlug($string) {
    $slug = strtolower($string);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    return trim($slug, '-');
}

/**
 * Handle file upload with validation
 *
 * @param array $file File data from $_FILES
 * @return array Result with success status and file path or error message
 */
function handleFileUpload($file) {
    // Check if file was uploaded
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'file_path' => null]; // No file uploaded is not an error
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error: ' . $file['error']];
    }
    
    // Validate file size
    $maxFileSize = env('MAX_FILE_SIZE', 5242880); // Default 5MB
    if ($file['size'] > $maxFileSize) {
        return ['success' => false, 'message' => 'File size exceeds maximum allowed size'];
    }
    
    // Validate file type
    $allowedTypes = explode(',', env('ALLOWED_FILE_TYPES', 'jpg,jpeg,png,pdf,doc,docx'));
    $fileExtension = getFileExtension($file['name']);
    
    if (!in_array($fileExtension, $allowedTypes)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    // Generate unique filename
    $uniqueFilename = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExtension;
    $uploadPath = __DIR__ . '/../../public/uploads/files/';
    
    // Create upload directory if it doesn't exist
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    // Move uploaded file
    $destination = $uploadPath . $uniqueFilename;
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Return relative path for database storage
        return [
            'success' => true,
            'file_path' => '/uploads/files/' . $uniqueFilename
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }
}