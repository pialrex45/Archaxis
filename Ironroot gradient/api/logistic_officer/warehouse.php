<?php
// API: /api/logistic_officer/warehouse.php
// GET: inventory (default) or transfers list via ?view=transfers
// POST action=update_stock: upsert inventory quantity delta for a material and zone
// POST action=log_transfer: move quantity from one zone to another and log transfer

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/app/core/auth.php';
require_once dirname(__DIR__, 2) . '/app/core/helpers.php';

requireLogisticOfficer();

$pdo = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$view = $_GET['view'] ?? 'inventory';

header('Content-Type: application/json');

try {
    if ($method === 'GET') {
        $limit = min(max((int)($_GET['limit'] ?? 100), 1), 500);
        if ($view === 'transfers') {
            $sql = "SELECT wt.*, u.name as logged_by_name FROM warehouse_transfers wt 
                    LEFT JOIN users u ON u.id = wt.logged_by 
                    ORDER BY wt.created_at DESC LIMIT :limit";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
            exit;
        }
        // inventory view
        $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $sql = "SELECT * FROM warehouse_inventory WHERE 1=1";
        if ($projectId) { $sql .= " AND project_id = :project_id"; }
        $sql .= " ORDER BY updated_at DESC LIMIT :limit";
        $stmt = $pdo->prepare($sql);
        if ($projectId) { $stmt->bindValue(':project_id', $projectId, PDO::PARAM_INT); }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
        exit;
    }

    if ($method === 'POST' && $action === 'update_stock') {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $materialId = (int)($body['material_id'] ?? 0);
        $projectId = (int)($body['project_id'] ?? 0);
        $zone = trim($body['zone'] ?? 'MAIN');
        $qtyDelta = (int)($body['quantity'] ?? 0);
        if ($materialId <= 0 || $projectId <= 0 || $qtyDelta === 0) {
            http_response_code(422);
            echo json_encode(['success'=>false,'message'=>'material_id, project_id, quantity are required']);
            exit;
        }
        $pdo->beginTransaction();
        try {
            // lock row if exists
            $sel = $pdo->prepare('SELECT id, quantity FROM warehouse_inventory WHERE project_id = ? AND material_id = ? AND zone = ? FOR UPDATE');
            $sel->execute([$projectId, $materialId, $zone]);
            $row = $sel->fetch();
            if ($row) {
                $newQty = max(0, (int)$row['quantity'] + $qtyDelta);
                $upd = $pdo->prepare('UPDATE warehouse_inventory SET quantity = ?, updated_at = NOW() WHERE id = ?');
                $upd->execute([$newQty, $row['id']]);
            } else {
                $newQty = max(0, $qtyDelta);
                $ins = $pdo->prepare('INSERT INTO warehouse_inventory (project_id, material_id, zone, quantity) VALUES (?,?,?,?)');
                $ins->execute([$projectId, $materialId, $zone, $newQty]);
            }
            $pdo->commit();
            echo json_encode(['success'=>true,'message'=>'Stock updated','quantity'=>$newQty]);
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        exit;
    }

    if ($method === 'POST' && $action === 'log_transfer') {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $materialId = (int)($body['material_id'] ?? 0);
        $projectId = (int)($body['project_id'] ?? 0);
        $fromZone = trim($body['from_zone'] ?? '');
        $toZone = trim($body['to_zone'] ?? '');
        $qty = max(1, (int)($body['quantity'] ?? 0));
        if ($materialId <= 0 || $projectId <= 0 || $fromZone === '' || $toZone === '' || $qty <= 0) {
            http_response_code(422);
            echo json_encode(['success'=>false,'message'=>'Missing required fields']);
            exit;
        }
        $pdo->beginTransaction();
        try {
            // deduct from source zone
            $sel = $pdo->prepare('SELECT id, quantity FROM warehouse_inventory WHERE project_id = ? AND material_id = ? AND zone = ? FOR UPDATE');
            $sel->execute([$projectId, $materialId, $fromZone]);
            $src = $sel->fetch();
            $srcQty = (int)($src['quantity'] ?? 0);
            if (!$src || $srcQty < $qty) { throw new Exception('Insufficient stock in source zone'); }
            $upd = $pdo->prepare('UPDATE warehouse_inventory SET quantity = quantity - ?, updated_at = NOW() WHERE id = ?');
            $upd->execute([$qty, $src['id']]);

            // add to destination zone
            $sel2 = $pdo->prepare('SELECT id FROM warehouse_inventory WHERE project_id = ? AND material_id = ? AND zone = ? FOR UPDATE');
            $sel2->execute([$projectId, $materialId, $toZone]);
            $dst = $sel2->fetch();
            if ($dst) {
                $upd2 = $pdo->prepare('UPDATE warehouse_inventory SET quantity = quantity + ?, updated_at = NOW() WHERE id = ?');
                $upd2->execute([$qty, $dst['id']]);
            } else {
                $ins = $pdo->prepare('INSERT INTO warehouse_inventory (project_id, material_id, zone, quantity) VALUES (?,?,?,?)');
                $ins->execute([$projectId, $materialId, $toZone, $qty]);
            }

            // log transfer
            $log = $pdo->prepare('INSERT INTO warehouse_transfers (project_id, material_id, from_zone, to_zone, quantity, logged_by) VALUES (?,?,?,?,?,?)');
            $log->execute([$projectId, $materialId, $fromZone, $toZone, $qty, getCurrentUserId()]);

            $pdo->commit();
            echo json_encode(['success'=>true,'message'=>'Transfer logged']);
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
