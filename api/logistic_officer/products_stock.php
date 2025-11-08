<?php
// API: /api/logistic_officer/products_stock.php
// GET: list products with supplier name and current stock, with filters

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/app/core/auth.php';
require_once dirname(__DIR__, 2) . '/app/core/helpers.php';

requireLogisticOfficer();

$pdo = Database::getConnection();
header('Content-Type: application/json');

try {
    $q = isset($_GET['q']) ? trim((string)$_GET['q']) : null;
    $supplierId = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : null;
    $status = isset($_GET['status']) ? trim((string)$_GET['status']) : null; // active|inactive
    $minStock = isset($_GET['min_stock']) ? (int)$_GET['min_stock'] : null;
    $maxStock = isset($_GET['max_stock']) ? (int)$_GET['max_stock'] : null;
    $limit = min(max((int)($_GET['limit'] ?? 500), 1), 2000);

    $sql = "SELECT p.id, p.name, p.unit, p.unit_price, p.stock, p.status, s.name AS supplier_name, s.id AS supplier_id
            FROM products p
            JOIN suppliers s ON s.id = p.supplier_id
            WHERE 1=1";
    $params = [];
    if ($q !== null && $q !== '') {
        $sql .= " AND (p.name LIKE :q OR s.name LIKE :q)";
        $params[':q'] = '%'.$q.'%';
    }
    if ($supplierId) { $sql .= " AND p.supplier_id = :supplier_id"; $params[':supplier_id'] = $supplierId; }
    if ($status && in_array($status, ['active','inactive'], true)) { $sql .= " AND p.status = :status"; $params[':status'] = $status; }
    if ($minStock !== null) { $sql .= " AND p.stock >= :min_stock"; $params[':min_stock'] = $minStock; }
    if ($maxStock !== null) { $sql .= " AND p.stock <= :max_stock"; $params[':max_stock'] = $maxStock; }

    $sql .= " ORDER BY p.name ASC LIMIT :limit";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k=>$v) { $stmt->bindValue($k, $v, is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR); }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
