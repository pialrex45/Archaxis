<?php
// app/controllers/AttendanceWorkflowController.php
// Additive workflow controller: submission (check-in/out), approvals, listing, export

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';

class AttendanceWorkflowController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // Submit check-in (mobile/QR/supervisor-assisted)
    public function submitCheckIn(int $userId, array $payload): array {
        $now = date('Y-m-d H:i:s');
        $date = date('Y-m-d');
        $projectId = isset($payload['project_id']) ? (int)$payload['project_id'] : null;
        $zoneId = isset($payload['zone_id']) ? (int)$payload['zone_id'] : null;
        $roleId = isset($payload['role_id']) ? (int)$payload['role_id'] : null; // optional if you map roles elsewhere
        $remarks = isset($payload['remarks']) ? sanitize($payload['remarks']) : null;
        $method = isset($payload['method']) ? sanitize($payload['method']) : 'mobile'; // mobile|qr|supervisor
        $status = isset($payload['status']) ? sanitize($payload['status']) : 'present'; // present|late|manual|absent
        // geo (optional)
        $lat = array_key_exists('lat', $payload) ? (float)$payload['lat'] : null;
        $lng = array_key_exists('lng', $payload) ? (float)$payload['lng'] : null;
        $acc = array_key_exists('accuracy', $payload) ? (float)$payload['accuracy'] : null;

        // Ensure one record per user/date (base schema unique key)
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT id, check_in, check_out FROM attendance WHERE user_id = ? AND date = ? FOR UPDATE");
            $stmt->execute([$userId, $date]);
            $row = $stmt->fetch();

            // Determine geo column availability
            $hasLatIn = $this->columnExists('attendance', 'lat_in');
            $hasLngIn = $this->columnExists('attendance', 'lng_in');
            $hasAccIn = $this->columnExists('attendance', 'acc_in');
            $hasMethodIn = $this->columnExists('attendance', 'method_in');
            $geoColsExist = $hasLatIn && $hasLngIn && $hasAccIn && $hasMethodIn;

            $geoAppend = '';
            if (!$geoColsExist && $lat !== null && $lng !== null) {
                $accText = $acc !== null ? (', ±' . round($acc) . 'm') : '';
                $geoAppend = " [geo: {$lat},{$lng}{$accText}]";
            }

            if ($row && !empty($row['check_in'])) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Already checked in'];
            }

            if ($row) {
                // Build dynamic SET for optional columns
                $setParts = ['check_in = ?', 'project_id = ?', 'zone_id = ?', 'role_id = ?', 'status = ?', "remarks = COALESCE(CONCAT(IFNULL(remarks,''), ?), ?)", "approval_status = 'pending'"];
                $params = [$now, $projectId, $zoneId, $roleId, $status, $this->formatRemarkAppend($method, $remarks) . $geoAppend, $this->formatRemarkAppend($method, $remarks) . $geoAppend, (int)$row['id']];

                if ($geoColsExist) {
                    $setParts[] = 'lat_in = ?';
                    $setParts[] = 'lng_in = ?';
                    $setParts[] = 'acc_in = ?';
                    $setParts[] = 'method_in = ?';
                    // Insert before id param
                    array_splice($params, -1, 0, [$lat, $lng, $acc, $method]);
                }

                $sql = 'UPDATE attendance SET ' . implode(', ', $setParts) . ' WHERE id = ?';
                $upd = $this->db->prepare($sql);
                $upd->execute($params);
                $attendanceId = (int)$row['id'];
            } else {
                // Build dynamic INSERT for optional columns
                $cols = ['user_id','date','role_id','project_id','zone_id','check_in','status','approval_status','remarks'];
                $vals = [$userId, $date, $roleId, $projectId, $zoneId, $now, $status, 'pending', $this->formatRemarkAppend($method, $remarks) . $geoAppend];
                if ($geoColsExist) {
                    $cols = array_merge($cols, ['lat_in','lng_in','acc_in','method_in']);
                    $vals = array_merge($vals, [$lat, $lng, $acc, $method]);
                }
                $placeholders = rtrim(str_repeat('?,', count($cols)), ',');
                $ins = $this->db->prepare('INSERT INTO attendance (' . implode(',', $cols) . ") VALUES ($placeholders)");
                $ins->execute($vals);
                $attendanceId = (int)$this->db->lastInsertId();
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Check-in recorded', 'data' => ['attendance_id' => $attendanceId]];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => 'Error during check-in: ' . $e->getMessage()];
        }
    }

    // Submit check-out
    public function submitCheckOut(int $userId, array $payload): array {
        $now = date('Y-m-d H:i:s');
        $date = date('Y-m-d');
        $remarks = isset($payload['remarks']) ? sanitize($payload['remarks']) : null;
        $method = isset($payload['method']) ? sanitize($payload['method']) : 'mobile';
        // geo (optional)
        $lat = array_key_exists('lat', $payload) ? (float)$payload['lat'] : null;
        $lng = array_key_exists('lng', $payload) ? (float)$payload['lng'] : null;
        $acc = array_key_exists('accuracy', $payload) ? (float)$payload['accuracy'] : null;

        try {
            $stmt = $this->db->prepare("SELECT id, check_in, check_out FROM attendance WHERE user_id = ? AND date = ?");
            $stmt->execute([$userId, $date]);
            $row = $stmt->fetch();
            if (!$row) {
                return ['success' => false, 'message' => 'No check-in record found'];
            }
            if (!empty($row['check_out'])) {
                return ['success' => false, 'message' => 'Already checked out'];
            }

            // Determine geo column availability
            $hasLatOut = $this->columnExists('attendance', 'lat_out');
            $hasLngOut = $this->columnExists('attendance', 'lng_out');
            $hasAccOut = $this->columnExists('attendance', 'acc_out');
            $hasMethodOut = $this->columnExists('attendance', 'method_out');
            $geoColsExist = $hasLatOut && $hasLngOut && $hasAccOut && $hasMethodOut;

            $geoAppend = '';
            if (!$geoColsExist && $lat !== null && $lng !== null) {
                $accText = $acc !== null ? (', ±' . round($acc) . 'm') : '';
                $geoAppend = " [geo: {$lat},{$lng}{$accText}]";
            }

            $append = $this->formatRemarkAppend($method, $remarks) . $geoAppend;

            // Build dynamic SET for optional columns
            $setParts = ['check_out = ?', "remarks = COALESCE(CONCAT(IFNULL(remarks,''), ?), ? )"];
            $params = [$now, $append, $append, (int)$row['id']];

            if ($geoColsExist) {
                $setParts[] = 'lat_out = ?';
                $setParts[] = 'lng_out = ?';
                $setParts[] = 'acc_out = ?';
                $setParts[] = 'method_out = ?';
                array_splice($params, -1, 0, [$lat, $lng, $acc, $method]);
            }

            $sql = 'UPDATE attendance SET ' . implode(', ', $setParts) . ' WHERE id = ?';
            $upd = $this->db->prepare($sql);
            $upd->execute($params);
            return ['success' => true, 'message' => 'Check-out recorded'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error during check-out: ' . $e->getMessage()];
        }
    }

    // Supervisor level approval or correction
    public function approveSupervisor(int $attendanceId, int $approverId, string $action, ?string $remarks = null): array {
        if (!in_array($action, ['approved','corrected','rejected'], true)) {
            return ['success' => false, 'message' => 'Invalid action'];
        }
        return $this->applyApproval($attendanceId, $approverId, 'supervisor', $action, $remarks);
    }

    // Site manager final approval
    public function approveSiteManager(int $attendanceId, int $approverId, string $action, ?string $remarks = null): array {
        if (!in_array($action, ['approved','corrected','rejected'], true)) {
            return ['success' => false, 'message' => 'Invalid action'];
        }
        return $this->applyApproval($attendanceId, $approverId, 'site_manager', $action, $remarks);
    }

    private function applyApproval(int $attendanceId, int $approverId, string $level, string $action, ?string $remarks): array {
        $now = date('Y-m-d H:i:s');
        $this->db->beginTransaction();
        try {
            // Optional: log approval only if the table exists in this schema
            $hasApprovalsLog = $this->tableExists('attendance_approvals');
            if ($hasApprovalsLog) {
                // Detect schema variant
                $hasRoleCol = $this->columnExists('attendance_approvals', 'role');
                $hasCreatedAtCol = $this->columnExists('attendance_approvals', 'created_at');
                $hasLevelCol = $this->columnExists('attendance_approvals', 'level');
                $hasApprovedAtCol = $this->columnExists('attendance_approvals', 'approved_at');

                if ($hasRoleCol && $hasCreatedAtCol) {
                    // Newer schema
                    $log = $this->db->prepare("INSERT INTO attendance_approvals (attendance_id, approver_id, role, action, remarks, created_at) VALUES (?,?,?,?,?,?)");
                    $log->execute([$attendanceId, $approverId, $level, $action, $remarks, $now]);
                } elseif ($hasLevelCol && $hasApprovedAtCol) {
                    // Legacy schema
                    $log = $this->db->prepare("INSERT INTO attendance_approvals (attendance_id, level, approver_id, approved_at, action, remarks) VALUES (?,?,?,?,?,?)");
                    $log->execute([$attendanceId, $level, $approverId, $now, $action, $remarks]);
                } else {
                    // Fallback: try minimal compatible set without timestamps
                    $cols = ['attendance_id','approver_id','action'];
                    $vals = [$attendanceId, $approverId, $action];
                    if ($this->columnExists('attendance_approvals', 'remarks')) { $cols[] = 'remarks'; $vals[] = $remarks; }
                    if ($hasRoleCol) { $cols[] = 'role'; $vals[] = $level; }
                    if ($hasLevelCol) { $cols[] = 'level'; $vals[] = $level; }
                    $sql = 'INSERT INTO attendance_approvals (' . implode(',', $cols) . ') VALUES (' . rtrim(str_repeat('?,', count($cols)), ',') . ')';
                    $log = $this->db->prepare($sql);
                    $log->execute($vals);
                }
            }

            // Dynamically build update only for columns that exist
            $hasApprovalStatus = $this->columnExists('attendance', 'approval_status');
            $hasApprovedBy = $this->columnExists('attendance', 'approved_by');
            $hasApprovedAt = $this->columnExists('attendance', 'approved_at');

            $setParts = [];
            $params = [];
            $nextStatus = $level === 'supervisor' ? ($action === 'rejected' ? 'rejected' : 'supervisor_approved')
                                                  : ($action === 'rejected' ? 'rejected' : 'site_manager_approved');
            if ($hasApprovalStatus) { $setParts[] = 'approval_status = ?'; $params[] = $nextStatus; }
            if ($hasApprovedBy) { $setParts[] = 'approved_by = ?'; $params[] = $approverId; }
            if ($hasApprovedAt) { $setParts[] = 'approved_at = ?'; $params[] = $now; }

            if (!empty($setParts)) {
                $sql = 'UPDATE attendance SET ' . implode(', ', $setParts) . ' WHERE id = ?';
                $params[] = $attendanceId;
                $upd = $this->db->prepare($sql);
                $upd->execute($params);
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Approval recorded', 'data' => ['status' => $nextStatus]];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => 'Error during approval: ' . $e->getMessage()];
        }
    }

    // List with filters
    public function list(array $filters): array {
        // Build SELECT with optional joins depending on table existence
        $hasUsers = $this->tableExists('users');
        $hasProjects = $this->tableExists('projects');
        $hasZones = $this->tableExists('zones');
        $hasApprovalStatus = $this->columnExists('attendance', 'approval_status');

        $select = 'SELECT a.*';
        $joins = '';
        if ($hasUsers) { $select .= ', u.name AS user_name, u.role AS user_role'; $joins .= ' LEFT JOIN users u ON u.id = a.user_id'; }
        if ($hasProjects) { $select .= ', p.name AS project_name'; $joins .= ' LEFT JOIN projects p ON p.id = a.project_id'; }
        if ($hasZones) { $select .= ', z.name AS zone_name'; $joins .= ' LEFT JOIN zones z ON z.id = a.zone_id'; }

        $sql = "$select FROM attendance a $joins WHERE 1=1";
        $params = [];

        if (!empty($filters['user_id'])) { $sql .= " AND a.user_id = ?"; $params[] = (int)$filters['user_id']; }
        if (!empty($filters['project_id'])) { $sql .= " AND a.project_id = ?"; $params[] = (int)$filters['project_id']; }
        if (!empty($filters['zone_id'])) { $sql .= " AND a.zone_id = ?"; $params[] = (int)$filters['zone_id']; }
        if (!empty($filters['status'])) { $sql .= " AND a.status = ?"; $params[] = sanitize($filters['status']); }
        if (!empty($filters['approval_status']) && $hasApprovalStatus) { $sql .= " AND a.approval_status = ?"; $params[] = sanitize($filters['approval_status']); }
        if (!empty($filters['role']) && $hasUsers) { $sql .= " AND u.role = ?"; $params[] = sanitize($filters['role']); }
        if (!empty($filters['from'])) { $sql .= " AND a.date >= ?"; $params[] = sanitize($filters['from']); }
        if (!empty($filters['to'])) { $sql .= " AND a.date <= ?"; $params[] = sanitize($filters['to']); }

        $sql .= " ORDER BY a.date DESC, a.user_id ASC LIMIT 1000"; // simple cap

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
            return ['success' => true, 'data' => $rows];
        } catch (Throwable $e) {
            // Fallback to minimal query if any join/column causes failure
            try {
                $fallbackSql = "SELECT a.* FROM attendance a WHERE 1=1";
                $fallbackParams = [];
                if (!empty($filters['user_id'])) { $fallbackSql .= " AND a.user_id = ?"; $fallbackParams[] = (int)$filters['user_id']; }
                if (!empty($filters['project_id'])) { $fallbackSql .= " AND a.project_id = ?"; $fallbackParams[] = (int)$filters['project_id']; }
                if (!empty($filters['zone_id'])) { $fallbackSql .= " AND a.zone_id = ?"; $fallbackParams[] = (int)$filters['zone_id']; }
                if (!empty($filters['status'])) { $fallbackSql .= " AND a.status = ?"; $fallbackParams[] = sanitize($filters['status']); }
                if (!empty($filters['approval_status']) && $hasApprovalStatus) { $fallbackSql .= " AND a.approval_status = ?"; $fallbackParams[] = sanitize($filters['approval_status']); }
                if (!empty($filters['from'])) { $fallbackSql .= " AND a.date >= ?"; $fallbackParams[] = sanitize($filters['from']); }
                if (!empty($filters['to'])) { $fallbackSql .= " AND a.date <= ?"; $fallbackParams[] = sanitize($filters['to']); }
                $fallbackSql .= " ORDER BY a.date DESC, a.user_id ASC LIMIT 1000";
                $stmt = $this->db->prepare($fallbackSql);
                $stmt->execute($fallbackParams);
                $rows = $stmt->fetchAll();
                return ['success' => true, 'data' => $rows, 'warning' => 'Partial data (approval_status filter skipped or no joined names)'];
            } catch (Throwable $e2) {
                return ['success' => false, 'message' => 'Error loading attendance: ' . $e2->getMessage()];
            }
        }
    }

    // Export CSV (basic)
    public function exportCsv(array $filters): array {
        $list = $this->list($filters);
        if (!$list['success']) return $list;
        $rows = $list['data'];
        $headers = ['date','user_id','user_name','project_id','project_name','zone_id','zone_name','check_in','check_out','status','approval_status','approved_by','approved_at','remarks'];
        $fp = fopen('php://temp', 'w+');
        fputcsv($fp, $headers);
        foreach ($rows as $r) {
            fputcsv($fp, [
                $r['date'] ?? '', $r['user_id'] ?? '', $r['user_name'] ?? '', $r['project_id'] ?? '', $r['project_name'] ?? '',
                $r['zone_id'] ?? '', $r['zone_name'] ?? '', $r['check_in'] ?? '', $r['check_out'] ?? '', $r['status'] ?? '',
                $r['approval_status'] ?? '', $r['approved_by'] ?? '', $r['approved_at'] ?? '', $r['remarks'] ?? ''
            ]);
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);
        return ['success' => true, 'data' => $csv, 'content_type' => 'text/csv'];
    }

    // Stats aggregation (daily/weekly/monthly) with optional dimension (user|project)
    public function stats(array $filters, string $groupBy = 'day', ?string $dimension = null): array {
        $list = $this->list($filters);
        if (!$list['success']) return $list;
        $rows = $list['data'] ?? [];

        // Helper: compute hours from check_in/check_out
        $hoursOf = function($r){
            if (empty($r['check_in']) || empty($r['check_out'])) return 0.0;
            $ci = strtotime($r['check_in']);
            $co = strtotime($r['check_out']);
            if (!$ci || !$co || $co <= $ci) return 0.0;
            $h = ($co - $ci) / 3600.0;
            return max(0.0, round($h, 2));
        };

        // Key builders for grouping
        $periodKey = function($dateStr) use ($groupBy){
            if (!$dateStr) return '';
            switch (strtolower($groupBy)) {
                case 'week':
                    $ts = strtotime($dateStr);
                    if (!$ts) return '';
                    // ISO week year-week
                    $y = (int)date('o', $ts);
                    $w = (int)date('W', $ts);
                    return sprintf('%04d-W%02d', $y, $w);
                case 'month':
                    return substr($dateStr, 0, 7); // YYYY-MM
                case 'day':
                default:
                    return substr($dateStr, 0, 10); // YYYY-MM-DD
            }
        };

        $dimKey = function($r) use ($dimension){
            if ($dimension === 'user') return $r['user_id'] ?? null;
            if ($dimension === 'project') return $r['project_id'] ?? null;
            return null;
        };

        $agg = [];
        foreach ($rows as $r) {
            $p = $periodKey($r['date'] ?? null);
            if ($p === '') continue;
            $d = $dimKey($r);
            $key = $d !== null ? ($p . '|' . $d) : $p;
            if (!isset($agg[$key])) {
                $agg[$key] = [
                    'period' => $p,
                    'dimension' => $dimension,
                    'dimension_value' => $d,
                    'count' => 0,
                    'present' => 0,
                    'late' => 0,
                    'manual' => 0,
                    'absent' => 0,
                    'total_hours' => 0.0,
                ];
            }
            $agg[$key]['count'] += 1;
            $st = $r['status'] ?? '';
            if (isset($agg[$key][$st])) $agg[$key][$st] += 1;
            $agg[$key]['total_hours'] += $hoursOf($r);
        }

        // Compute averages
        $out = array_values($agg);
        foreach ($out as &$row) {
            $row['avg_hours'] = $row['count'] > 0 ? round($row['total_hours'] / $row['count'], 2) : 0.0;
        }
        unset($row);

        return ['success' => true, 'data' => $out];
    }

    private function formatRemarkAppend(string $method, ?string $remarks): string {
        $tag = strtoupper($method);
        $text = trim((string)$remarks);
        $suffix = $text !== '' ? " [$text]" : '';
        // Lightweight device/IP hints (additive)
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        $uaHint = $ua ? (preg_match('/Mobile|Android|iPhone|iPad/i', $ua) ? 'mobile' : 'web') : null;
        $env = '';
        if ($ip) { $env .= " [ip $ip]"; }
        if ($uaHint) { $env .= " [ua $uaHint]"; }
        return " [" . date('H:i') . " via $tag]" . $suffix . $env;
    }

    private function tableExists(string $name): bool {
        try {
            $stmt = $this->db->query("SHOW TABLES LIKE '" . str_replace("'", "''", $name) . "'");
            return $stmt && $stmt->fetchColumn() !== false;
        } catch (Throwable $e) { return false; }
    }

    private function columnExists(string $table, string $column): bool {
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
            $stmt->execute([$column]);
            return $stmt->fetchColumn() !== false;
        } catch (Throwable $e) { return false; }
    }
}
