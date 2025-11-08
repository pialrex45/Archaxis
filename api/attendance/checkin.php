<?php
// api/attendance/checkin.php
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';
require_once __DIR__ . '/../../app/controllers/AttendanceWorkflowController.php';

header('Content-Type: application/json');

if (!isAuthenticated()) { http_response_code(401); if (ob_get_length()) { ob_clean(); } echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); if (ob_get_length()) { ob_clean(); } echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

// Enforce session timeout
enforceActiveSession((int) (env('SESSION_IDLE_TIMEOUT', 3600)));

// CSRF (accept header or form)
if (!validateCSRFTokenFlexible($_POST['csrf_token'] ?? null)) { http_response_code(400); if (ob_get_length()) { ob_clean(); } echo json_encode(['success'=>false,'message'=>'Invalid CSRF token']); exit; }

$role = getCurrentUserRole();
$assistedUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null; // optional supervisor-assisted
$selfUserId = (int)getCurrentUserId();

// RBAC: self check-in allowed for worker, sub_contractor, logistic_officer, and admin
$canSelfSubmit = in_array($role, ['worker','sub_contractor','logistic_officer','admin'], true);
// Supervisor-assisted: only supervisor or admin can check in others
$assisted = ($assistedUserId && $assistedUserId !== $selfUserId);
if ($assisted) {
    // require supervisor/admin
    try { requireSupervisor(); } catch (Throwable $e) { http_response_code(403); if (ob_get_length()) { ob_clean(); } echo json_encode(['success'=>false,'message'=>'Forbidden']); exit; }
}
if (!$assisted && !$canSelfSubmit) { http_response_code(403); if (ob_get_length()) { ob_clean(); } echo json_encode(['success'=>false,'message'=>'Forbidden']); exit; }

// Build payload
$payload = [
    'project_id' => isset($_POST['project_id']) ? (int)$_POST['project_id'] : null,
    'zone_id'    => isset($_POST['zone_id']) ? (int)$_POST['zone_id'] : null,
    'role_id'    => isset($_POST['role_id']) ? (int)$_POST['role_id'] : null,
    'remarks'    => isset($_POST['remarks']) ? sanitize($_POST['remarks']) : null,
    'method'     => isset($_POST['method']) ? sanitize($_POST['method']) : 'mobile', // mobile|qr|supervisor
    'status'     => isset($_POST['status']) ? sanitize($_POST['status']) : 'present', // present|late|manual|absent
    // geolocation (optional)
    'lat'        => isset($_POST['lat']) ? (float)$_POST['lat'] : null,
    'lng'        => isset($_POST['lng']) ? (float)$_POST['lng'] : null,
    'accuracy'   => isset($_POST['accuracy']) ? (float)$_POST['accuracy'] : null,
];

$controller = new AttendanceWorkflowController();
$userIdToUse = $assisted ? $assistedUserId : $selfUserId;
$result = $controller->submitCheckIn($userIdToUse, $payload);

if (ob_get_length()) { ob_clean(); }
echo json_encode($result);
