<?php
// Subcontractor Purchase Orders API (scoped read-only)
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/controllers/SubContractorController.php';

if (!headers_sent()) { header('Content-Type: application/json'); }
requireSubContractor();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ctl = new SubContractorController();

try {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        exit;
    }

    // Support detail route /api/sub_contractor/purchase_orders/{id}
    $path = $_SERVER['REQUEST_URI'] ?? '';
    // Extract numeric ID at end of path (after purchase_orders or purchase_orders.php)
    $id = null;
    if ($path) {
        $clean = strtok($path,'?');
        $segments = array_values(array_filter(explode('/', $clean)));
        $last = end($segments);
        if (ctype_digit($last)) { $id = (int)$last; }
    }

    if ($id) {
        // Return single PO with basic items
        $pdo = Database::getConnection();
        // Ensure PO belongs to one of subcontractor's assigned projects
        $projects = $ctl->projectsAssigned(500);
        $pids = [];
        if ($projects['success']) { foreach (($projects['data']??[]) as $p){ $pids[] = (int)$p['id']; } }
        if (empty($pids)) { echo json_encode(['success'=>false,'message'=>'Forbidden']); exit; }
        $in = implode(',', array_fill(0,count($pids),'?'));
        $stmt = $pdo->prepare("SELECT id, project_id, supplier_id, status, total_amount, created_at FROM purchase_orders WHERE id=? AND project_id IN ($in) LIMIT 1");
        $stmt->execute(array_merge([$id], $pids));
        $po = $stmt->fetch();
        if (!$po) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }
        // Fetch items if table exists
        $items = [];
        try {
            $it = $pdo->prepare('SELECT poi.id, poi.product_id, poi.quantity, poi.unit_price, poi.total, p.name FROM purchase_order_items poi LEFT JOIN products p ON p.id = poi.product_id WHERE poi.purchase_order_id = ? ORDER BY poi.id ASC');
            $it->execute([$po['id']]);
            $rows = $it->fetchAll() ?: [];
            foreach ($rows as $r) {
                $items[] = [
                    'id' => (int)$r['id'],
                    'product_id' => (int)$r['product_id'],
                    'name' => $r['name'] ?? ('Product #'.$r['product_id']),
                    'quantity' => (int)$r['quantity'],
                    'unit_price' => (float)$r['unit_price'],
                    'total' => isset($r['total']) ? (float)$r['total'] : ((float)$r['unit_price'] * (int)$r['quantity'])
                ];
            }
        } catch (Throwable $ignored) { /* table may not exist */ }
        $po['items'] = $items;
        echo json_encode(['success'=>true,'data'=>$po]);
        exit;
    }

    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    echo json_encode($ctl->purchaseOrdersForAssignedProjects($limit));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
