<?php
// Supervisor Attendance API
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/controllers/SupervisorController.php';

if (!headers_sent()) { header('Content-Type: application/json'); }
requireSupervisor();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? $_POST['action'] ?? null;
$ctl = new SupervisorController();

try {
    if ($method === 'GET') {
        $userId = (int)($_GET['user_id'] ?? 0);
        $limit = (int)($_GET['limit'] ?? 50);
        if ($userId) {
            echo json_encode($ctl->viewAttendanceHistory($userId, $limit));
        } else {
            echo json_encode(['success' => true, 'message' => 'Supervisor Attendance API', 'usage' => ['GET?user_id=ID&limit=50', 'POST?action=approve&attendance_id=ID']]);
        }
    } elseif ($method === 'POST') {
        if ($action === 'approve') {
            $payload = json_decode(file_get_contents('php://input'), true);
            if (!is_array($payload)) { $payload = $_POST; }
            $attendanceId = (int)($_GET['attendance_id'] ?? $payload['attendance_id'] ?? 0);
            echo json_encode($ctl->approveAttendance($attendanceId));
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
}
