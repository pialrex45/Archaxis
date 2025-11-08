<?php
// API endpoint for marking attendance (check-in/check-out)

require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';
require_once __DIR__ . '/../../app/controllers/AttendanceController.php';

// Set response content type to JSON
header('Content-Type: application/json');

// Check if the user is authenticated
if (!isAuthenticated()) {
    http_response_code(401);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Enforce session idle-timeout
enforceActiveSession((int) (env('SESSION_IDLE_TIMEOUT', 3600)));

// CSRF validation (supports X-CSRF-Token header and form csrf_token)
if (!validateCSRFTokenFlexible($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Get and validate input data
$action = isset($_POST['action']) ? sanitize($_POST['action']) : '';

// Validate action
if (empty($action) || !in_array($action, ['check_in', 'check_out'])) {
    http_response_code(400);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode(['success' => false, 'message' => 'Invalid action. Must be check_in or check_out']);
    exit;
}

// Get current user ID
$userId = getCurrentUserId();
if (!$userId) {
    http_response_code(400);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode(['success' => false, 'message' => 'User ID not found']);
    exit;
}

// Create AttendanceController instance
$attendanceController = new AttendanceController();

// Current date and time
$date = date('Y-m-d');
$time = date('H:i:s');

// Perform action based on request
if ($action === 'check_in') {
    // Check if already checked in today
    $todayAttendance = $attendanceController->getByUserAndDate($userId, $date);
    
    if (!empty($todayAttendance['data'])) {
        // Already checked in
        http_response_code(400);
        if (ob_get_length()) { ob_clean(); }
        echo json_encode(['success' => false, 'message' => 'You have already checked in today']);
        exit;
    }
    
    // Mark attendance for check-in
    $result = $attendanceController->checkIn($userId, $date, $time);
} else {
    // Check if already checked in today
    $todayAttendance = $attendanceController->getByUserAndDate($userId, $date);
    
    if (empty($todayAttendance['data'])) {
        // Not checked in yet
        http_response_code(400);
        if (ob_get_length()) { ob_clean(); }
        echo json_encode(['success' => false, 'message' => 'You have not checked in today']);
        exit;
    }
    
    if (!empty($todayAttendance['data']['check_out_time'])) {
        // Already checked out
        http_response_code(400);
        if (ob_get_length()) { ob_clean(); }
        echo json_encode(['success' => false, 'message' => 'You have already checked out today']);
        exit;
    }
    
    // Mark attendance for check-out
    $result = $attendanceController->checkOut($userId, $date, $time);
}

// Return response
if (ob_get_length()) { ob_clean(); }
echo json_encode($result);
exit;
