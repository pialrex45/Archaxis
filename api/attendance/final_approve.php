<?php
// api/attendance/final_approve.php (Site Manager final approval)
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';
require_once __DIR__ . '/../../app/controllers/AttendanceWorkflowController.php';

// Start output buffering to prevent stray output breaking JSON
if (ob_get_level() === 0) { ob_start(); }
header('Content-Type: application/json; charset=utf-8');

if (!isAuthenticated()) { http_response_code(401); if (ob_get_length()) { ob_clean(); } echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); if (ob_get_length()) { ob_clean(); } echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

// Enforce session timeout
enforceActiveSession((int) (env('SESSION_IDLE_TIMEOUT', 3600)));

// CSRF flexible validation (header or form)
if (!validateCSRFTokenFlexible($_POST['csrf_token'] ?? null)) { http_response_code(400); if (ob_get_length()) { ob_clean(); } echo json_encode(['success'=>false,'message'=>'Invalid CSRF token']); exit; }

// RBAC: admin or manager
try { requireAnyRole(['admin', 'manager']); } catch (Throwable $e) { http_response_code(403); if (ob_get_length()) { ob_clean(); } echo json_encode(['success'=>false,'message'=>'Forbidden']); exit; }

$attendanceId = isset($_POST['attendance_id']) ? (int)$_POST['attendance_id'] : 0;
$action = isset($_POST['action']) ? sanitize($_POST['action']) : '';// approved|corrected|rejected
$remarks = isset($_POST['remarks']) ? sanitize($_POST['remarks']) : null;

if ($attendanceId <= 0 || !in_array($action, ['approved','corrected','rejected'], true)) {
    http_response_code(400);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode(['success'=>false,'message'=>'Invalid input']);
    exit;
}

$approverId = (int)getCurrentUserId();
$controller = new AttendanceWorkflowController();
$result = $controller->approveSiteManager($attendanceId, $approverId, $action, $remarks);

if (ob_get_length()) { ob_clean(); }
echo json_encode($result);
exit;
