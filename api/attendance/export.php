<?php
// api/attendance/export.php
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';
require_once __DIR__ . '/../../app/controllers/AttendanceWorkflowController.php';

if (!isAuthenticated()) { http_response_code(401); header('Content-Type: application/json'); if (ob_get_length()) { ob_clean(); } echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); header('Content-Type: application/json'); if (ob_get_length()) { ob_clean(); } echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

// Enforce session timeout for GET
enforceActiveSession((int) (env('SESSION_IDLE_TIMEOUT', 3600)));

$role = getCurrentUserRole();
$userId = (int)getCurrentUserId();

$filters = [
    'user_id' => isset($_GET['user_id']) ? (int)$_GET['user_id'] : null,
    'project_id' => isset($_GET['project_id']) ? (int)$_GET['project_id'] : null,
    'zone_id' => isset($_GET['zone_id']) ? (int)$_GET['zone_id'] : null,
    'status' => isset($_GET['status']) ? sanitize($_GET['status']) : null,
    'approval_status' => isset($_GET['approval_status']) ? sanitize($_GET['approval_status']) : null,
    'role' => isset($_GET['role']) ? sanitize($_GET['role']) : null,
    'from' => isset($_GET['from']) ? sanitize($_GET['from']) : null,
    'to' => isset($_GET['to']) ? sanitize($_GET['to']) : null,
];

if (in_array($role, ['worker','sub_contractor','logistic_officer'], true)) {
    $filters['user_id'] = $userId;
}

$controller = new AttendanceWorkflowController();
$res = $controller->exportCsv($filters);
if (!$res['success']) {
    http_response_code(500);
    header('Content-Type: application/json');
    if (ob_get_length()) { ob_clean(); }
    echo json_encode($res);
    exit;
}

$filename = 'attendance_export_' . date('Ymd_His') . '.csv';
// Ensure clean output before CSV headers/body
if (ob_get_length()) { ob_clean(); }
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');
echo $res['data'];
