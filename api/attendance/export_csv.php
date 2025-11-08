<?php
// Attendance CSV export (GET)
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';
require_once __DIR__ . '/../../config/database.php';

if (!isAuthenticated()) {
    http_response_code(401);
    if (ob_get_length()) { ob_clean(); }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    if (ob_get_length()) { ob_clean(); }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

enforceActiveSession((int) (env('SESSION_IDLE_TIMEOUT', 3600)));

// RBAC: admin, project_manager, site_manager
if (!hasAnyRole(['admin', 'project_manager', 'site_manager'])) {
    http_response_code(403);
    if (ob_get_length()) { ob_clean(); }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$pdo = Database::getConnection();

function csv_col_exists(PDO $pdo, $table, $column) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

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

$hasApprovalCol = csv_col_exists($pdo, 'attendance', 'approval_status');
if ($hasApprovalCol && $approval !== '') { $where[] = 'approval_status = ?'; $params[] = $approval; }

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Build column list based on schema
$cols = ['id','user_id','project_id','date','check_in_time','check_out_time','status'];
if ($hasApprovalCol) { $cols[] = 'approval_status'; }
$colSql = implode(', ', array_map(function($c){ return "`$c`"; }, $cols));

$sql = "SELECT $colSql FROM attendance $whereSql ORDER BY date DESC, id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filename = 'attendance_export_' . date('Ymd_His') . '.csv';
if (ob_get_length()) { ob_clean(); }
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$out = fopen('php://output', 'w');
// header row
fputcsv($out, $cols);
foreach ($rows as $row) {
    $line = [];
    foreach ($cols as $c) { $line[] = $row[$c] ?? ''; }
    fputcsv($out, $line);
}
fclose($out);
exit;
