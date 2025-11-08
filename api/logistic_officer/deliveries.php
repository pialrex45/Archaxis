<?php
// API: /api/logistic_officer/deliveries.php
// Responsibilities: list deliveries (GET), log a new delivery (POST action=log_delivery)

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
        // List recent deliveries with optional project_id or po_id filters
        $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $poId = isset($_GET['po_id']) ? (int)$_GET['po_id'] : null;
        $limit = min(max((int)($_GET['limit'] ?? 50), 1), 200);

    $sql = "SELECT d.*, 
               u.name AS logged_by_name,
               pr.name AS project_name,
               s.name AS supplier_name,
               COALESCE(pc.product_names, '') AS product_names
        FROM delivery_logs d 
        LEFT JOIN users u ON u.id = d.logged_by
        LEFT JOIN projects pr ON pr.id = d.project_id
        LEFT JOIN suppliers s ON s.id = d.supplier_id
        LEFT JOIN (
          SELECT i.purchase_order_id,
             GROUP_CONCAT(DISTINCT p.name ORDER BY p.name SEPARATOR ', ') AS product_names
          FROM purchase_order_items i 
          JOIN products p ON p.id = i.product_id
          GROUP BY i.purchase_order_id
        ) pc ON pc.purchase_order_id = d.purchase_order_id
        WHERE 1=1";
        $params = [];
        if ($projectId) { $sql .= " AND d.project_id = :project_id"; $params[':project_id'] = $projectId; }
        if ($poId) { $sql .= " AND d.purchase_order_id = :po_id"; $params[':po_id'] = $poId; }
        $sql .= " ORDER BY d.created_at DESC LIMIT :limit";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v, PDO::PARAM_INT); }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        echo json_encode(['success'=>true,'data'=>$rows]);
        exit;
    }

    if ($method === 'POST' && $action === 'log_delivery') {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $poId = (int)($body['po_id'] ?? 0);
        $projectId = (int)($body['project_id'] ?? 0);
        $supplierId = (int)($body['supplier_id'] ?? 0);
        $deliveryDate = trim($body['delivery_date'] ?? '');
        $status = in_array(($body['status'] ?? 'received'), ['received','partial','rejected']) ? $body['status'] : 'received';
        $paid = (int)($body['paid'] ?? 0) === 1 ? 1 : 0;

        if ($poId <= 0 || $projectId <= 0 || $supplierId <= 0 || !$deliveryDate) {
            http_response_code(422);
            echo json_encode(['success'=>false,'message'=>'Missing required fields']);
            exit;
        }

        // Validate foreign keys exist (best-effort)
        $exists = $pdo->prepare('SELECT 1 FROM purchase_orders WHERE id = ?');
        $exists->execute([$poId]);
        if (!$exists->fetchColumn()) { throw new Exception('Invalid purchase order'); }

        $exists = $pdo->prepare('SELECT 1 FROM projects WHERE id = ?');
        $exists->execute([$projectId]);
        if (!$exists->fetchColumn()) { throw new Exception('Invalid project'); }

        $exists = $pdo->prepare('SELECT 1 FROM suppliers WHERE id = ?');
        $exists->execute([$supplierId]);
        if (!$exists->fetchColumn()) { throw new Exception('Invalid supplier'); }

        $stmt = $pdo->prepare('INSERT INTO delivery_logs (purchase_order_id, project_id, supplier_id, delivery_date, status, paid, logged_by) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([$poId, $projectId, $supplierId, $deliveryDate, $status, $paid, getCurrentUserId()]);
        echo json_encode(['success'=>true,'message'=>'Delivery logged']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method/Action not supported']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
