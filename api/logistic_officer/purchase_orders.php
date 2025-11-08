<?php
// API: /api/logistic_officer/purchase_orders.php
// GET: list purchase orders (basic fields, optional project_id/supplier_id filters)
// POST action=update_status: update PO status (ordered|delivered) and optionally attach GRN record
// POST action=create: create a new purchase order (status=pending) with optional items

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
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $includeItems = isset($_GET['include_items']) ? (int)$_GET['include_items'] : 0;

        if ($id > 0 && $includeItems === 1) {
            // Return single PO with items and product names/units
            $poStmt = $pdo->prepare('SELECT id, project_id, supplier_id, status, total_amount, created_at, updated_at FROM purchase_orders WHERE id = ?');
            $poStmt->execute([$id]);
            $po = $poStmt->fetch(PDO::FETCH_ASSOC);
            if (!$po) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'PO not found']); exit; }
            $it = $pdo->prepare('SELECT i.id, i.product_id, i.quantity, i.unit_price, p.name AS product_name, p.unit AS product_unit FROM purchase_order_items i JOIN products p ON p.id = i.product_id WHERE i.purchase_order_id = ? ORDER BY i.id ASC');
            $it->execute([$id]);
            $items = $it->fetchAll(PDO::FETCH_ASSOC) ?: [];
            echo json_encode(['success'=>true,'data'=>['order'=>$po,'items'=>$items]]);
            exit;
        } else {
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

    if ($method === 'POST' && $action === 'create') {
        // Create a new PO initiated by Logistic Officer; status is set to 'pending' to await approval
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $projectId  = (int)($body['project_id'] ?? 0);
        $supplierId = (int)($body['supplier_id'] ?? 0);
        $totalInput = isset($body['total_amount']) ? (float)$body['total_amount'] : null;
        $items      = is_array($body['items'] ?? null) ? $body['items'] : null; // [{product_id, quantity, unit_price}]

        if ($projectId <= 0 || $supplierId <= 0) {
            http_response_code(422);
            echo json_encode(['success'=>false,'message'=>'project_id and supplier_id are required']);
            exit;
        }

        // Validate FKs best-effort
        $chk = $pdo->prepare('SELECT 1 FROM projects WHERE id = ?'); $chk->execute([$projectId]);
        if (!$chk->fetchColumn()) { throw new Exception('Invalid project'); }
        $chk = $pdo->prepare('SELECT 1 FROM suppliers WHERE id = ?'); $chk->execute([$supplierId]);
        if (!$chk->fetchColumn()) { throw new Exception('Invalid supplier'); }

        $pdo->beginTransaction();
        try {
            $creator = getCurrentUserId();
            $status = 'pending';
            $total = 0.0;

            // Pre-compute total from items if provided
            if ($items) {
                foreach ($items as $it) {
                    $qty = (int)($it['quantity'] ?? 0);
                    $price = (float)($it['unit_price'] ?? 0);
                    if ($qty > 0 && $price >= 0) { $total += ($qty * $price); }
                }
            } else if ($totalInput !== null) {
                $total = max(0.0, (float)$totalInput);
            }

            $ins = $pdo->prepare('INSERT INTO purchase_orders (project_id, supplier_id, created_by, status, total_amount) VALUES (?,?,?,?,?)');
            $ins->execute([$projectId, $supplierId, $creator, $status, $total]);
            $poId = (int)$pdo->lastInsertId();

            if ($items) {
                $poi = $pdo->prepare('INSERT INTO purchase_order_items (purchase_order_id, product_id, quantity, unit_price) VALUES (?,?,?,?)');
                foreach ($items as $it) {
                    $pid = (int)($it['product_id'] ?? 0);
                    $qty = (int)($it['quantity'] ?? 0);
                    $price = (float)($it['unit_price'] ?? 0);
                    if ($pid > 0 && $qty > 0 && $price >= 0) {
                        $poi->execute([$poId, $pid, $qty, $price]);
                    }
                }
                // If items were inserted but total_input was not provided, keep computed $total
                $upd = $pdo->prepare('UPDATE purchase_orders SET total_amount = ? WHERE id = ?');
                $upd->execute([round($total,2), $poId]);
            }

            $pdo->commit();
            echo json_encode(['success'=>true,'message'=>'PO created and set to pending','id'=>$poId]);
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        exit;
    }

    if ($method === 'POST' && $action === 'receive_to_inventory') {
        // Receive PO items into warehouse inventory for a given zone
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $poId = (int)($body['po_id'] ?? 0);
        $zone = trim((string)($body['zone'] ?? 'MAIN'));
        if ($poId <= 0) { http_response_code(422); echo json_encode(['success'=>false,'message'=>'po_id is required']); exit; }
        // Load PO and ensure status is eligible
        $poStmt = $pdo->prepare('SELECT id, project_id, status FROM purchase_orders WHERE id = ?');
        $poStmt->execute([$poId]);
        $po = $poStmt->fetch(PDO::FETCH_ASSOC);
        if (!$po) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'PO not found']); exit; }
        $eligible = in_array($po['status'], ['approved','ordered','delivered'], true);
        if (!$eligible) { http_response_code(409); echo json_encode(['success'=>false,'message'=>'PO must be approved, ordered, or delivered']); exit; }
        $projectId = (int)$po['project_id'];
        // Fetch items with product names
        $it = $pdo->prepare('SELECT i.product_id, i.quantity, p.name AS product_name FROM purchase_order_items i JOIN products p ON p.id = i.product_id WHERE i.purchase_order_id = ?');
        $it->execute([$poId]);
        $items = $it->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (!$items) { echo json_encode(['success'=>false,'message'=>'No items to receive']); exit; }

        $pdo->beginTransaction();
        try {
            $results = [];
            foreach ($items as $row) {
                $prodName = (string)($row['product_name'] ?? '');
                $qty = max(0, (int)($row['quantity'] ?? 0));
                if ($qty <= 0 || $prodName === '') { continue; }
                // Find or create matching material by name within the project
                $matSel = $pdo->prepare('SELECT id FROM materials WHERE project_id = ? AND material_name = ? LIMIT 1');
                $matSel->execute([$projectId, $prodName]);
                $materialId = (int)($matSel->fetchColumn() ?: 0);
                if ($materialId <= 0) {
                    $insM = $pdo->prepare("INSERT INTO materials (project_id, requested_by, material_name, quantity, status, created_at, updated_at) VALUES (?,?,?,?, 'delivered', NOW(), NOW())");
                    $insM->execute([$projectId, getCurrentUserId(), $prodName, 0]);
                    $materialId = (int)$pdo->lastInsertId();
                } else {
                    // Optionally mark as delivered
                    $pdo->prepare("UPDATE materials SET status = 'delivered', updated_at = NOW() WHERE id = ?")->execute([$materialId]);
                }
                // Upsert into warehouse_inventory
                $selInv = $pdo->prepare('SELECT id, quantity FROM warehouse_inventory WHERE project_id = ? AND material_id = ? AND zone = ? FOR UPDATE');
                $selInv->execute([$projectId, $materialId, $zone]);
                $inv = $selInv->fetch(PDO::FETCH_ASSOC);
                if ($inv) {
                    $newQty = (int)$inv['quantity'] + $qty;
                    $pdo->prepare('UPDATE warehouse_inventory SET quantity = ?, updated_at = NOW() WHERE id = ?')->execute([$newQty, $inv['id']]);
                } else {
                    $newQty = $qty;
                    $pdo->prepare('INSERT INTO warehouse_inventory (project_id, material_id, zone, quantity) VALUES (?,?,?,?)')->execute([$projectId, $materialId, $zone, $newQty]);
                }
                $results[] = ['material_id'=>$materialId, 'material_name'=>$prodName, 'quantity_added'=>$qty, 'zone'=>$zone];
            }
            $pdo->commit();
            echo json_encode(['success'=>true,'message'=>'Received into inventory','project_id'=>$projectId,'zone'=>$zone,'items'=>$results]);
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
