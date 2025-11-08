<?php
// api/attendance/stats.php
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if (!isAuthenticated()) { http_response_code(401); if (ob_get_length()) { ob_clean(); } echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); if (ob_get_length()) { ob_clean(); } echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

// Enforce session timeout for authenticated GETs
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

// Default last 7 days for convenience
if (empty($filters['to'])) { $filters['to'] = date('Y-m-d'); }
if (empty($filters['from'])) { $filters['from'] = date('Y-m-d', strtotime('-7 days')); }

// RBAC scoping for worker-like roles
if (in_array($role, ['worker','sub_contractor','logistic_officer'], true)) {
    $filters['user_id'] = $userId; // force self
}

$groupBy = isset($_GET['group_by']) ? strtolower(sanitize($_GET['group_by'])) : 'day'; // day|week|month
$dimension = isset($_GET['dimension']) ? strtolower(sanitize($_GET['dimension'])) : null; // user|project|null
if (!in_array($groupBy, ['day','week','month'], true)) $groupBy = 'day';
if ($dimension !== null && !in_array($dimension, ['user','project'], true)) $dimension = null;

$pdo = Database::getConnection();

// Helpers to check schema
function stats_column_exists(PDO $pdo, $table, $column) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

// Read filters
$from = isset($_GET['from']) ? sanitize($_GET['from']) : '';
$to = isset($_GET['to']) ? sanitize($_GET['to']) : '';
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$approval = isset($_GET['approval_status']) ? sanitize($_GET['approval_status']) : '';

$where = [];
$params = [];
if ($from !== '') { $where[] = 'date >= ?'; $params[] = $from; }
if ($to !== '') { $where[] = 'date <= ?'; $params[] = $to; }
if ($userId > 0) { $where[] = 'user_id = ?'; $params[] = $userId; }
if ($projectId > 0) { $where[] = 'project_id = ?'; $params[] = $projectId; }

$hasApprovalCol = stats_column_exists($pdo, 'attendance', 'approval_status');
if ($hasApprovalCol && $approval !== '') { $where[] = 'approval_status = ?'; $params[] = $approval; }

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Totals and status breakdown
$sqlTotals = "SELECT 
    COUNT(*) AS total,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present,
    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) AS late,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS absent
  FROM attendance $whereSql";
$stmt = $pdo->prepare($sqlTotals);
$stmt->execute($params);
$totals = $stmt->fetch() ?: ['total'=>0,'present'=>0,'late'=>0,'absent'=>0];

$approvalBreakdown = null;
if ($hasApprovalCol) {
    $sqlApproval = "SELECT approval_status, COUNT(*) AS cnt FROM attendance $whereSql GROUP BY approval_status";
    $stmt2 = $pdo->prepare($sqlApproval);
    $stmt2->execute($params);
    $approvalBreakdown = $stmt2->fetchAll();
}

$controller = new AttendanceWorkflowController();
$result = $controller->stats($filters, $groupBy, $dimension);

if (ob_get_length()) { ob_clean(); }
echo json_encode([
    'success' => true,
    'data' => [
        'totals' => $totals,
        'by_approval' => $approvalBreakdown,
        'stats' => $result,
    ],
]);
exit;
