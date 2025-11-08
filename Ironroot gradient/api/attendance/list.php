<?php
// api/attendance/list.php
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';
require_once __DIR__ . '/../../app/controllers/AttendanceWorkflowController.php';

header('Content-Type: application/json');

if (!isAuthenticated()) { http_response_code(401); if (ob_get_length()) { ob_clean(); } echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); if (ob_get_length()) { ob_clean(); } echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

// Enforce session timeout for authenticated GETs
enforceActiveSession((int) (env('SESSION_IDLE_TIMEOUT', 3600)));

$role = getCurrentUserRole();
$userId = (int)getCurrentUserId();

// Build filters from query
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

// Additive defaults to improve Approvals UX
// Default date range to last 7 days if not provided
if (empty($filters['to'])) { $filters['to'] = date('Y-m-d'); }
if (empty($filters['from'])) { $filters['from'] = date('Y-m-d', strtotime('-7 days')); }
// Supervisors/Site Managers/Admins typically want pending by default
if (empty($filters['approval_status']) && in_array(strtolower((string)$role), ['supervisor','site_manager','admin'], true)) {
    $filters['approval_status'] = 'pending';
}

// Basic RBAC scoping:
// - worker/sub_contractor/logistic_officer: force own user_id
// - others (admin/supervisor/site_manager/project_manager/general_manager/client/site_engineer): allow provided filters
if (in_array($role, ['worker','sub_contractor','logistic_officer'], true)) {
    $filters['user_id'] = $userId; // override to self
}

$controller = new AttendanceWorkflowController();
$result = $controller->list($filters);

if (ob_get_length()) { ob_clean(); }
echo json_encode($result);
