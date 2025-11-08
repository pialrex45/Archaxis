<?php
// API: /api/logistic_officer/products.php
// GET: list products (basic fields) with optional filters
// POST action=create: create a new product under a supplier

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
    $q = isset($_GET['q']) ? '%'.trim((string)$_GET['q']).'%' : null;
    $supplierId = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : null;
    $status = isset($_GET['status']) ? trim((string)$_GET['status']) : null; // active|inactive
    $limit = min(max((int)($_GET['limit'] ?? 500), 1), 2000);

    $sql = 'SELECT p.id, p.name, p.unit, p.unit_price, p.stock, p.status, p.supplier_id, s.name AS supplier_name
            FROM products p JOIN suppliers s ON s.id = p.supplier_id WHERE 1=1';
    $params = [];
    if ($q) { $sql .= ' AND (p.name LIKE :q OR s.name LIKE :q)'; $params[':q'] = $q; }
    if ($supplierId) { $sql .= ' AND p.supplier_id = :supplier_id'; $params[':supplier_id'] = $supplierId; }
    if ($status && in_array($status, ['active','inactive'], true)) { $sql .= ' AND p.status = :status'; $params[':status'] = $status; }
    $sql .= ' ORDER BY p.name ASC LIMIT :limit';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k=>$v) { $stmt->bindValue($k, $v, is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR); }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
    exit;
  }

  if ($method === 'POST' && $action === 'create') {
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $supplierId = (int)($body['supplier_id'] ?? 0);
    $name = trim((string)($body['name'] ?? ''));
    $unit = trim((string)($body['unit'] ?? 'unit')) ?: 'unit';
    $unitPrice = isset($body['unit_price']) ? (float)$body['unit_price'] : null;
    $stock = isset($body['stock']) ? (int)$body['stock'] : 0;
    $status = in_array(($body['status'] ?? 'active'), ['active','inactive'], true) ? $body['status'] : 'active';

    if ($supplierId <= 0 || $name === '' || $unitPrice === null) {
      http_response_code(422);
      echo json_encode(['success'=>false,'message'=>'supplier_id, name, unit_price are required']);
      exit;
    }

    // Validate supplier
    $chk = $pdo->prepare('SELECT 1 FROM suppliers WHERE id = ?');
    $chk->execute([$supplierId]);
    if (!$chk->fetchColumn()) { throw new Exception('Invalid supplier'); }

    $stmt = $pdo->prepare('INSERT INTO products (supplier_id, name, description, unit, unit_price, stock, status) VALUES (?,?,?,?,?,?,?)');
    $ok = $stmt->execute([$supplierId, $name, null, $unit, $unitPrice, max(0,$stock), $status]);
    echo json_encode(['success'=>(bool)$ok,'id'=>$ok?(int)$pdo->lastInsertId():null]);
    exit;
  }

  http_response_code(405);
  echo json_encode(['success'=>false,'message'=>'Method/Action not supported']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
