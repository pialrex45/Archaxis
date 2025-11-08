<?php
// Printable HTML for products stock with supplier, intended for browser "Save as PDF"
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/app/core/auth.php';
require_once dirname(__DIR__, 2) . '/app/core/helpers.php';

requireLogisticOfficer();

$pdo = Database::getConnection();
header('Content-Type: text/html; charset=utf-8');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : null;
$supplierId = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : null;
$status = isset($_GET['status']) ? trim((string)$_GET['status']) : null; // active|inactive
$minStock = isset($_GET['min_stock']) ? (int)$_GET['min_stock'] : null;
$maxStock = isset($_GET['max_stock']) ? (int)$_GET['max_stock'] : null;
$limit = min(max((int)($_GET['limit'] ?? 2000), 1), 5000);

$sql = "SELECT p.id, p.name, p.unit, p.unit_price, p.stock, p.status, s.name AS supplier_name
        FROM products p JOIN suppliers s ON s.id = p.supplier_id WHERE 1=1";
$params = [];
if ($q !== null && $q !== '') { $sql .= " AND (p.name LIKE :q OR s.name LIKE :q)"; $params[':q'] = '%'.$q.'%'; }
if ($supplierId) { $sql .= " AND p.supplier_id = :supplier_id"; $params[':supplier_id'] = $supplierId; }
if ($status && in_array($status, ['active','inactive'], true)) { $sql .= " AND p.status = :status"; $params[':status'] = $status; }
if ($minStock !== null) { $sql .= " AND p.stock >= :min_stock"; $params[':min_stock'] = $minStock; }
if ($maxStock !== null) { $sql .= " AND p.stock <= :max_stock"; $params[':max_stock'] = $maxStock; }
$sql .= " ORDER BY s.name ASC, p.name ASC LIMIT :limit";
$stmt = $pdo->prepare($sql);
foreach ($params as $k=>$v) { $stmt->bindValue($k, $v, is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR); }
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$now = date('Y-m-d H:i');
$title = 'Warehouse Products Stock'.($supplierId? ' — Supplier #'.$supplierId : '');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= h($title) ?></title>
  <style>
    body { font-family: Arial, sans-serif; margin: 24px; }
    h1 { font-size: 20px; margin: 0 0 8px; }
    .meta { color: #555; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #999; padding: 6px 8px; font-size: 12px; }
    th { background: #f0f0f0; text-align: left; }
    tfoot td { border: none; font-weight: bold; }
    @media print {
      .noprint { display: none; }
      th, td { font-size: 11px; }
    }
  </style>
</head>
<body>
  <div class="noprint" style="text-align:right; margin-bottom:8px;">
    <button onclick="window.print()">Print / Save as PDF</button>
  </div>
  <h1><?= h($title) ?></h1>
  <div class="meta">Generated: <?= h($now) ?> | Total items: <?= count($rows) ?></div>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Product</th>
        <th>Supplier</th>
        <th>Unit</th>
        <th>Unit Price</th>
        <th>Stock</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $i => $r): ?>
      <tr>
        <td><?= $i+1 ?></td>
        <td><?= h($r['name']) ?></td>
        <td><?= h($r['supplier_name']) ?></td>
        <td><?= h($r['unit']) ?></td>
        <td><?= number_format((float)$r['unit_price'], 2) ?></td>
        <td><?= (int)$r['stock'] ?></td>
        <td><?= h($r['status']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
