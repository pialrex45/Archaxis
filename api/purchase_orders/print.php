<?php
// Printable PO with approved signature stamp
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/app/core/auth.php';
require_once dirname(__DIR__, 2) . '/app/core/helpers.php';

// Any authenticated role can view; typically logistics/PM/Admin
requireAuth();

$pdo = Database::getConnection();
header('Content-Type: text/html; charset=utf-8');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); echo '<p>Invalid PO id</p>'; exit; }

// Fetch PO, project, supplier, items
$po = null; $project = null; $supplier = null; $items = [];
try {
  $stmt = $pdo->prepare('SELECT * FROM purchase_orders WHERE id = ?');
  $stmt->execute([$id]);
  $po = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$po) { throw new Exception('PO not found'); }

  $ps = $pdo->prepare('SELECT id, name FROM projects WHERE id = ?');
  $ps->execute([$po['project_id']]);
  $project = $ps->fetch(PDO::FETCH_ASSOC) ?: ['id'=>$po['project_id'], 'name'=>'Project #'.$po['project_id']];

  $ss = $pdo->prepare('SELECT id, name, email, phone, address FROM suppliers WHERE id = ?');
  $ss->execute([$po['supplier_id']]);
  $supplier = $ss->fetch(PDO::FETCH_ASSOC) ?: ['id'=>$po['supplier_id'], 'name'=>'Supplier #'.$po['supplier_id']];

  $it = $pdo->prepare('SELECT i.*, p.name AS product_name, p.unit FROM purchase_order_items i JOIN products p ON p.id = i.product_id WHERE i.purchase_order_id = ?');
  $it->execute([$id]);
  $items = $it->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  http_response_code(500);
  echo '<p>Error Loading PO</p>';
  exit;
}

$status = strtolower((string)($po['status'] ?? ''));
$approved = ($status === 'approved' || $status === 'ordered' || $status === 'delivered');
$total = number_format((float)($po['total_amount'] ?? 0), 2);
$created = htmlspecialchars((string)($po['created_at'] ?? ''));

// Optional signature image path (if you have an approver signature stored)
// You can place a PNG at /public/assets/approved-stamp.png
$baseUrl = function_exists('url') ? url('') : '';
$stampUrl = $baseUrl . 'assets/approved-stamp.png';
$stampExists = file_exists(dirname(__DIR__,2).'/public/assets/approved-stamp.png');

?><!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Purchase Order #<?= (int)$po['id'] ?></title>
<style>
  body { font-family: Arial, sans-serif; margin: 20px; }
  .header { display:flex; justify-content:space-between; align-items:flex-start; }
  .muted { color:#666; }
  .box { border:1px solid #ddd; padding:12px; border-radius:6px; }
  table { width:100%; border-collapse: collapse; margin-top: 12px; }
  th, td { border:1px solid #ddd; padding:8px; text-align:left; }
  th { background:#f7f7f7; }
  .right { text-align:right; }
  .stamp { position:absolute; right:40px; top:120px; opacity:0.85; transform: rotate(-12deg); }
  @media print {
    .noprint { display:none; }
  }
</style>
</head>
<body>
  <div class="noprint" style="text-align:right; margin-bottom:10px;">
    <button onclick="window.print()">Print</button>
  </div>
  <div class="header">
    <div>
      <h2 style="margin:0;">Purchase Order</h2>
      <div class="muted">PO #<?= (int)$po['id'] ?> • Created <?= $created ?></div>
      <div class="muted">Status: <strong><?= htmlspecialchars((string)$po['status']) ?></strong></div>
    </div>
    <div class="box" style="min-width:280px;">
      <div><strong>Project:</strong> <?= htmlspecialchars((string)$project['name']) ?> (ID <?= (int)$project['id'] ?>)</div>
      <div style="margin-top:6px;"><strong>Supplier:</strong> <?= htmlspecialchars((string)$supplier['name']) ?></div>
      <?php if (!empty($supplier['email'])): ?><div class="muted">Email: <?= htmlspecialchars((string)$supplier['email']) ?></div><?php endif; ?>
      <?php if (!empty($supplier['phone'])): ?><div class="muted">Phone: <?= htmlspecialchars((string)$supplier['phone']) ?></div><?php endif; ?>
      <?php if (!empty($supplier['address'])): ?><div class="muted">Address: <?= htmlspecialchars((string)$supplier['address']) ?></div><?php endif; ?>
    </div>
  </div>

  <?php if ($approved): ?>
    <div class="stamp">
      <?php if ($stampExists): ?>
        <img src="<?= htmlspecialchars($stampUrl) ?>" alt="Approved" width="220" height="auto" />
      <?php else: ?>
        <div style="border:4px solid #28a745; color:#28a745; font-size:28px; font-weight:bold; padding:12px 16px; border-radius:8px;">APPROVED</div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <table>
    <thead>
      <tr>
        <th style="width:50px;">#</th>
        <th>Product</th>
        <th style="width:110px;">Unit</th>
        <th style="width:110px;" class="right">Qty</th>
        <th style="width:140px;" class="right">Unit Price</th>
        <th style="width:140px;" class="right">Line Total</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($items): $i=1; foreach ($items as $it): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= htmlspecialchars((string)($it['product_name'] ?? '')) ?></td>
          <td><?= htmlspecialchars((string)($it['unit'] ?? 'unit')) ?></td>
          <td class="right"><?= (int)($it['quantity'] ?? 0) ?></td>
          <td class="right"><?= number_format((float)($it['unit_price'] ?? 0),2) ?></td>
          <td class="right"><?= number_format(((float)($it['quantity'] ?? 0) * (float)($it['unit_price'] ?? 0)),2) ?></td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="6" class="muted">No items provided for this PO.</td></tr>
      <?php endif; ?>
    </tbody>
    <tfoot>
      <tr>
        <th colspan="5" class="right">Total</th>
        <th class="right"><?= $total ?></th>
      </tr>
    </tfoot>
  </table>

  <p class="muted" style="margin-top:20px;">Generated by Ironroot • <?= date('Y-m-d H:i') ?></p>
</body>
</html>
