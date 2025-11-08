<?php
// Site Manager Materials API (request-only for dashboard modal)
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/models/Material.php';

if (!headers_sent()) { header('Content-Type: application/json'); }
requireSiteManager();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? $_POST['action'] ?? null;

try {
    if ($method === 'POST' && $action === 'request') {
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) { $payload = $_POST; }

        $projectId = (int)($payload['project_id'] ?? 0);
        $name      = trim((string)($payload['material_name'] ?? ($payload['name'] ?? '')));
        $qty       = isset($payload['quantity']) && $payload['quantity'] !== '' ? (float)$payload['quantity'] : 0;
        if ($projectId <= 0 || $name === '' || $qty <= 0) {
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>'project_id, material_name and quantity are required']);
            exit;
        }

        $model = class_exists('Material') ? new Material() : null;
        if ($model && method_exists($model, 'request')) {
            // If a request() helper exists on Material model
            // Align with model signature: request($projectId, $requestedBy, $materialName, $quantity, $status = 'requested')
            $requestedBy = getCurrentUserId();
            $res = $model->request($projectId, $requestedBy, $name, (int)$qty);
            echo json_encode(is_array($res) ? $res : ['success'=> (bool)$res]);
            exit;
        }

        // Fallback direct insert
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO materials (project_id, name, quantity, status, created_at, updated_at) VALUES (?, ?, ?, 'requested', NOW(), NOW())");
        $ok = $stmt->execute([$projectId, $name, $qty]);
        echo json_encode(['success'=>(bool)$ok,'message'=>$ok?'Material requested':'Request failed','material_id'=>$ok?(int)$db->lastInsertId():null]);
        exit;
    }

    // Approve material request (site manager only, and only if they have access to the project's materials)
    if ($method === 'POST' && $action === 'approve') {
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) { $payload = $_POST; }
        $materialId = (int)($payload['id'] ?? ($_GET['id'] ?? 0));
        if ($materialId <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'id is required']); exit; }

        $db = Database::getConnection();
        // Verify that current user is allowed for this material's project
        $uid = (int)getCurrentUserId();
        $allowed = false; $projectId = null;
        try {
            $st = $db->prepare('SELECT m.project_id, p.site_manager_id FROM materials m JOIN projects p ON p.id = m.project_id WHERE m.id = :id');
            $st->execute([':id'=>$materialId]);
            $row = $st->fetch();
            if ($row) {
                $projectId = (int)$row['project_id'];
                if ((int)($row['site_manager_id'] ?? 0) === $uid) { $allowed = true; }
            }
            if (!$allowed && $projectId) {
                try {
                    $st2 = $db->prepare('SELECT 1 FROM project_assignments WHERE project_id = :pid AND user_id = :uid LIMIT 1');
                    $st2->execute([':pid'=>$projectId, ':uid'=>$uid]);
                    $allowed = $st2->fetchColumn() ? true : false;
                } catch (Throwable $e2) { /* table may not exist */ }
            }
        } catch (Throwable $e) { /* keep $allowed false */ }
        if (!$allowed) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Forbidden']); exit; }

        $model = new Material();
        if (method_exists($model, 'approve')) {
            $res = $model->approve($materialId);
        } else {
            try {
                $st3 = $db->prepare("UPDATE materials SET status='approved', updated_at = NOW() WHERE id = :id");
                $ok = $st3->execute([':id'=>$materialId]);
                $res = ['success'=>(bool)$ok, 'message'=>$ok?'Approved':'Failed'];
            } catch (Throwable $e3) { $res = ['success'=>false,'message'=>'Approval failed']; }
        }
        echo json_encode($res);
        exit;
    }

    // Reject material request (site manager)
    if ($method === 'POST' && $action === 'reject') {
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) { $payload = $_POST; }
        $materialId = (int)($payload['id'] ?? ($_GET['id'] ?? 0));
        if ($materialId <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'id is required']); exit; }

        $db = Database::getConnection();
        $uid = (int)getCurrentUserId();
        $allowed = false; $projectId = null;
        try {
            $st = $db->prepare('SELECT m.project_id, p.site_manager_id FROM materials m JOIN projects p ON p.id = m.project_id WHERE m.id = :id');
            $st->execute([':id'=>$materialId]);
            $row = $st->fetch();
            if ($row) {
                $projectId = (int)$row['project_id'];
                if ((int)($row['site_manager_id'] ?? 0) === $uid) { $allowed = true; }
            }
            if (!$allowed && $projectId) {
                try {
                    $st2 = $db->prepare('SELECT 1 FROM project_assignments WHERE project_id = :pid AND user_id = :uid LIMIT 1');
                    $st2->execute([':pid'=>$projectId, ':uid'=>$uid]);
                    $allowed = $st2->fetchColumn() ? true : false;
                } catch (Throwable $e2) { /* table may not exist */ }
            }
        } catch (Throwable $e) { /* keep $allowed false */ }
        if (!$allowed) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Forbidden']); exit; }

        $model = new Material();
        if (method_exists($model, 'reject')) {
            $res = $model->reject($materialId);
        } else {
            try {
                $st3 = $db->prepare("UPDATE materials SET status='rejected', updated_at = NOW() WHERE id = :id");
                $ok = $st3->execute([':id'=>$materialId]);
                $res = ['success'=>(bool)$ok, 'message'=>$ok?'Rejected':'Failed'];
            } catch (Throwable $e3) { $res = ['success'=>false,'message'=>'Rejection failed']; }
        }
        echo json_encode($res);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Unknown action or method']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
