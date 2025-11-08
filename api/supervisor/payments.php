<?php
// Supervisor Payments API (additive, minimal)
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';

if (!headers_sent()) { header('Content-Type: application/json'); }
requireSupervisor();

$pdo = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? $_POST['action'] ?? null;
$supervisorId = (int)(getCurrentUserId() ?? 0);

function read_json_body() {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) { $payload = $_POST; }
    return $payload ?: [];
}

// Helper: ensure worker belongs to this supervisor
function ensure_worker_in_team($pdo, $supervisorId, $workerId) {
    $stmt = $pdo->prepare('SELECT 1 FROM supervisor_assignments WHERE supervisor_id = :sid AND worker_id = :wid');
    $stmt->bindValue(':sid', (int)$supervisorId, PDO::PARAM_INT);
    $stmt->bindValue(':wid', (int)$workerId, PDO::PARAM_INT);
    $stmt->execute();
    return (bool)$stmt->fetchColumn();
}

try {
    if ($method === 'GET') {
        $workerId = (int)($_GET['worker_id'] ?? 0);
        if ($workerId <= 0) {
            echo json_encode(['success'=>true,'data'=>[], 'message'=>'worker_id required']);
            exit;
        }
        if (!ensure_worker_in_team($pdo, $supervisorId, $workerId)) {
            http_response_code(403);
            echo json_encode(['success'=>false,'message'=>'Forbidden']);
            exit;
        }
        $stmt = $pdo->prepare('SELECT wp.*, u.name AS worker_name
                               FROM worker_payments wp
                               JOIN users u ON u.id = wp.worker_id
                               WHERE wp.worker_id = :wid AND wp.supervisor_id = :sid
                               ORDER BY wp.created_at DESC
                               LIMIT 50');
        $stmt->bindValue(':wid', $workerId, PDO::PARAM_INT);
        $stmt->bindValue(':sid', $supervisorId, PDO::PARAM_INT);
        try {
            $stmt->execute();
            $rows = $stmt->fetchAll() ?: [];
            echo json_encode(['success'=>true,'data'=>$rows]);
        } catch (Throwable $e) {
            // Likely table missing
            echo json_encode(['success'=>false,'message'=>'Payments table not found. Please run migration to enable payments.']);
        }
        exit;
    }

    if ($method === 'POST') {
        if ($action === 'add') {
            $input = read_json_body();
            $workerId = (int)($input['worker_id'] ?? 0);
            $amount = isset($input['amount']) ? (float)$input['amount'] : 0.0;
            $note = isset($input['note']) ? trim((string)$input['note']) : null;
            if ($workerId <= 0 || $amount <= 0) {
                http_response_code(400);
                echo json_encode(['success'=>false,'message'=>'worker_id and positive amount required']);
                exit;
            }
            if (!ensure_worker_in_team($pdo, $supervisorId, $workerId)) {
                http_response_code(403);
                echo json_encode(['success'=>false,'message'=>'Forbidden']);
                exit;
            }
            try {
                $stmt = $pdo->prepare('INSERT INTO worker_payments (worker_id, supervisor_id, amount, note, created_at) VALUES (:wid, :sid, :amt, :note, NOW())');
                $stmt->bindValue(':wid', $workerId, PDO::PARAM_INT);
                $stmt->bindValue(':sid', $supervisorId, PDO::PARAM_INT);
                $stmt->bindValue(':amt', $amount);
                $stmt->bindValue(':note', $note);
                $ok = $stmt->execute();
                echo json_encode(['success'=>(bool)$ok,'message'=>$ok?'Payment recorded':'Insert failed']);
            } catch (Throwable $e) {
                http_response_code(400);
                echo json_encode(['success'=>false,'message'=>'Payments table not found. Please run migration to enable payments.']);
            }
            exit;
        }
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>'Unknown action']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method not allowed']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
