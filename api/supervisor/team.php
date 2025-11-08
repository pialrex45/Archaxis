<?php
// Supervisor Team Management API
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/controllers/AuthController.php';

if (!headers_sent()) { header('Content-Type: application/json'); }
requireSupervisor();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? $_POST['action'] ?? null;

$pdo = Database::getConnection();
$auth = new AuthController();
$supervisorId = (int) (getCurrentUserId() ?? 0);

function json_input() {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) { $payload = $_POST; }
    return $payload ?: [];
}

try {
    if ($method === 'GET') {
        // List team members for current supervisor
        $stmt = $pdo->prepare(
            'SELECT u.id, u.name, u.email, u.role, u.rank, u.approved, sa.created_at AS assigned_at
             FROM supervisor_assignments sa
             JOIN users u ON u.id = sa.worker_id
             WHERE sa.supervisor_id = :sid
             ORDER BY u.name ASC, u.id ASC'
        );
        $stmt->bindValue(':sid', $supervisorId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll() ?: [];
        echo json_encode(['success'=>true,'data'=>$rows]);
        exit;
    }

    if ($method === 'POST') {
        if ($action === 'enroll') {
            // Enroll (register) a new user and assign to this supervisor
            // Note: This now creates supervisors who can be managed by other supervisors
            $input = json_input();
            $input['role'] = 'supervisor'; // Changed from 'worker' to 'supervisor'
            // Minimal required fields: name, email, password, confirm_password
            $result = $auth->signup($input);
            if (!$result['success']) {
                http_response_code(400);
                echo json_encode($result);
                exit;
            }
            $userId = (int)$result['user_id'];
            // Create assignment (ignore if already assigned)
            try {
                $stmt = $pdo->prepare('INSERT IGNORE INTO supervisor_assignments (worker_id, supervisor_id, created_at) VALUES (:wid, :sid, NOW())');
                $stmt->bindValue(':wid', $userId, PDO::PARAM_INT);
                $stmt->bindValue(':sid', $supervisorId, PDO::PARAM_INT);
                $stmt->execute();
            } catch (Throwable $e) {
                // Best-effort; if unique constraint prevents insert, we still proceed
            }
            echo json_encode(['success'=>true,'message'=>'Team member enrolled. Awaiting approval by admin/manager.','user_id'=>$userId]);
            exit;
        } elseif ($action === 'assign_existing') {
            // Assign an existing worker (by email or id) to current supervisor
            $input = json_input();
            $workerId = (int)($input['worker_id'] ?? 0);
            $email = trim((string)($input['email'] ?? ''));
            if (!$workerId && $email) {
                $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :e');
                $stmt->bindValue(':e', $email);
                $stmt->execute();
                $row = $stmt->fetch();
                $workerId = $row ? (int)$row['id'] : 0;
            }
            if ($workerId <= 0) {
                http_response_code(400);
                echo json_encode(['success'=>false,'message'=>'Worker not found']);
                exit;
            }
            $stmt = $pdo->prepare('INSERT IGNORE INTO supervisor_assignments (worker_id, supervisor_id, created_at) VALUES (:wid, :sid, NOW())');
            $stmt->bindValue(':wid', $workerId, PDO::PARAM_INT);
            $stmt->bindValue(':sid', $supervisorId, PDO::PARAM_INT);
            $ok = $stmt->execute();
            echo json_encode(['success'=>true,'message'=>'Assigned to your team']);
            exit;
        } elseif ($action === 'remove') {
            // Remove a worker from this supervisor's team
            $input = json_input();
            $workerId = (int)($input['worker_id'] ?? 0);
            if ($workerId <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid worker_id']); exit; }
            $stmt = $pdo->prepare('DELETE FROM supervisor_assignments WHERE worker_id = :wid AND supervisor_id = :sid');
            $stmt->bindValue(':wid', $workerId, PDO::PARAM_INT);
            $stmt->bindValue(':sid', $supervisorId, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode(['success'=>true,'message'=>'Removed from your team']);
            exit;
        } else {
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>'Unknown action']);
            exit;
        }
    }

    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method not allowed']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
