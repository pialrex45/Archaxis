<?php
// API: /api/logistic_officer/purchase_orders.php
// GET: list purchase orders (basic fields, optional project_id/supplier_id filters)
// POST action=update_status: update PO status (ordered|delivered) and optionally attach GRN record

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/app/core/auth.php';
require_once dirname(__DIR__, 2) . '/app/core/helpers.php';

requireLogisticOfficer();

$pdo = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

try {
    if ($method === 'GET') {
        $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $supplierId = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : null;
        $status = isset($_GET['status']) ? trim($_GET['status']) : null;
        $limit = min(max((int)($_GET['limit'] ?? 50), 1), 200);

        $sql = "SELECT id, project_id, supplier_id, status, total_amount, created_at, updated_at FROM purchase_orders WHERE 1=1";
        $params = [];
        if ($projectId) { $sql .= " AND project_id = :project_id"; $params[':project_id'] = $projectId; }
        if ($supplierId) { $sql .= " AND supplier_id = :supplier_id"; $params[':supplier_id'] = $supplierId; }
        if ($status) { $sql .= " AND status = :status"; $params[':status'] = $status; }
        $sql .= " ORDER BY id DESC LIMIT :limit";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v, is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR); }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
        exit;
    }

    if ($method === 'POST' && $action === 'update_status') {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $poId = (int)($body['po_id'] ?? 0);
        $status = ($body['status'] ?? '');
        $allowed = ['ordered','delivered'];
        if ($poId <= 0 || !in_array($status, $allowed, true)) {
            http_response_code(422);
            echo json_encode(['success'=>false,'message'=>'Invalid PO or status']);
            exit;
        }

        $pdo->beginTransaction();
        try {
            // Ensure PO exists
            $exists = $pdo->prepare('SELECT id FROM purchase_orders WHERE id = ? FOR UPDATE');
            $exists->execute([$poId]);
            if (!$exists->fetchColumn()) { throw new Exception('Purchase Order not found'); }

            $upd = $pdo->prepare('UPDATE purchase_orders SET status = ?, updated_at = NOW() WHERE id = ?');
            $upd->execute([$status, $poId]);

            // Optional GRN attach
            $grnFile = trim($body['grn_file'] ?? '');
            if ($grnFile !== '') {
                $ins = $pdo->prepare('INSERT INTO grn_receipts (purchase_order_id, file_path, uploaded_by) VALUES (?,?,?)');
                $ins->execute([$poId, $grnFile, getCurrentUserId()]);
            }

            $pdo->commit();
            echo json_encode(['success'=>true,'message'=>'PO updated']);
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        exit;
    }

    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method/Action not supported']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
