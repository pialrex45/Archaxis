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
    if ($view === 'low_stock') {
            // Enriched low-stock listing: join material and project details. Optional delivered_only filter.
            $threshold = max(0, (int)($_GET['threshold'] ?? 10));
            $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
            $deliveredOnly = isset($_GET['delivered_only']) ? (int)$_GET['delivered_only'] : 1;
            $sql = "SELECT wi.*, m.material_name, m.status AS material_status, p.name AS project_name
                    FROM warehouse_inventory wi
                    JOIN materials m ON m.id = wi.material_id
                    JOIN projects p ON p.id = wi.project_id
                    WHERE wi.quantity < :threshold";
            if ($projectId) { $sql .= " AND wi.project_id = :project_id"; }
            if ($deliveredOnly === 1) { $sql .= " AND m.status = 'delivered'"; }
            $sql .= " ORDER BY wi.quantity ASC, wi.updated_at DESC LIMIT :limit";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':threshold', $threshold, PDO::PARAM_INT);
            if ($projectId) { $stmt->bindValue(':project_id', $projectId, PDO::PARAM_INT); }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
            exit;
        }
    if ($view === 'inventory_enriched') {
        // All inventory with names and statuses, plus optional filters
        $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $zone = isset($_GET['zone']) ? trim((string)$_GET['zone']) : '';
        $q = isset($_GET['q']) ? ('%'.trim((string)$_GET['q']).'%') : null;
        $sql = "SELECT wi.*, m.material_name, m.status AS material_status, p.name AS project_name
            FROM warehouse_inventory wi
            JOIN materials m ON m.id = wi.material_id
            JOIN projects p ON p.id = wi.project_id
            WHERE 1=1";
        if ($projectId) { $sql .= " AND wi.project_id = :project_id"; }
        if ($zone !== '') { $sql .= " AND wi.zone = :zone"; }
        if ($q) { $sql .= " AND m.material_name LIKE :q"; }
        $sql .= " ORDER BY wi.updated_at DESC LIMIT :limit";
        $stmt = $pdo->prepare($sql);
        if ($projectId) { $stmt->bindValue(':project_id', $projectId, PDO::PARAM_INT); }
        if ($zone !== '') { $stmt->bindValue(':zone', $zone, PDO::PARAM_STR); }
        if ($q) { $stmt->bindValue(':q', $q, PDO::PARAM_STR); }
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
            $oldQty = (int)($row['quantity'] ?? 0);
            if ($row) {
                $newQty = max(0, $oldQty + $qtyDelta);
                $upd = $pdo->prepare('UPDATE warehouse_inventory SET quantity = ?, updated_at = NOW() WHERE id = ?');
                $upd->execute([$newQty, $row['id']]);
            } else {
                $newQty = max(0, $qtyDelta);
                $ins = $pdo->prepare('INSERT INTO warehouse_inventory (project_id, material_id, zone, quantity) VALUES (?,?,?,?)');
                $ins->execute([$projectId, $materialId, $zone, $newQty]);
            }
            // enrich with material and project names
            $metaStmt = $pdo->prepare('SELECT m.material_name, m.status AS material_status, p.name AS project_name FROM materials m JOIN projects p ON p.id = ? WHERE m.id = ?');
            $metaStmt->execute([$projectId, $materialId]);
            $meta = $metaStmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $pdo->commit();
            echo json_encode([
                'success'=>true,
                'message'=>'Stock updated',
                'previous_quantity'=>$oldQty,
                'new_quantity'=>$newQty,
                'project_id'=>$projectId,
                'material_id'=>$materialId,
                'zone'=>$zone,
                'material_name'=>$meta['material_name'] ?? null,
                'material_status'=>$meta['material_status'] ?? null,
                'project_name'=>$meta['project_name'] ?? null,
            ]);
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
