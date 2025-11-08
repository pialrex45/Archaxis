<?php
// Supervisor Purchase Orders API
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
        $projectId = (int)($_GET['project_id'] ?? 0);
        if ($projectId) {
            echo json_encode($ctl->listPOsByProject($projectId));
        } else {
            echo json_encode(['success' => true, 'message' => 'Supervisor POs API', 'usage' => ['GET?project_id=ID', 'POST?action=update_status&po_id=ID&status=ordered|delivered']]);
        }
    } elseif ($method === 'POST') {
        if ($action === 'update_status') {
            $payload = json_decode(file_get_contents('php://input'), true);
            if (!is_array($payload)) { $payload = $_POST; }
            $poId = (int)($_GET['po_id'] ?? $payload['po_id'] ?? 0);
            $status = ($payload['status'] ?? $_GET['status'] ?? '');
            $note = $payload['note'] ?? $_GET['note'] ?? null;
            echo json_encode($ctl->updatePOStatus($poId, $status, $note));
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
