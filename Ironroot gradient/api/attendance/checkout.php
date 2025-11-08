<?php
// api/attendance/checkout.php
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
$selfUserId = (int)getCurrentUserId();
$canSelfSubmit = in_array($role, ['worker','sub_contractor','logistic_officer','admin'], true);
if (!$canSelfSubmit) { http_response_code(403); if (ob_get_length()) { ob_clean(); } echo json_encode(['success'=>false,'message'=>'Forbidden']); exit; }

$payload = [
    'remarks'  => isset($_POST['remarks']) ? sanitize($_POST['remarks']) : null,
    'method'   => isset($_POST['method']) ? sanitize($_POST['method']) : 'mobile',
    // geolocation (optional)
    'lat'      => isset($_POST['lat']) ? (float)$_POST['lat'] : null,
    'lng'      => isset($_POST['lng']) ? (float)$_POST['lng'] : null,
    'accuracy' => isset($_POST['accuracy']) ? (float)$_POST['accuracy'] : null,
];

$controller = new AttendanceWorkflowController();
$result = $controller->submitCheckOut($selfUserId, $payload);

if (ob_get_length()) { ob_clean(); }
echo json_encode($result);
