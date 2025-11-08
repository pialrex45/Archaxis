<?php
// Returns latest design JSON summary and computed estimates per project
if (!defined('BASE_PATH')) { define('BASE_PATH', dirname(__DIR__)); }
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/core/auth.php';
require_once BASE_PATH . '/app/core/helpers.php';

header('Content-Type: application/json');

try {
  requireAuth();
  $pdo = Database::getConnection();

  $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
  $mine = isset($_GET['mine']) ? (int)$_GET['mine'] : 0;

  // determine list of projects
  $projects = [];
  if ($projectId > 0) {
    $st = $pdo->prepare('SELECT id, name FROM projects WHERE id = ?');
    $st->execute([$projectId]);
    $row = $st->fetch();
    if ($row) $projects[] = $row;
  } else if ($mine && hasRole('client')) {
    $st = $pdo->prepare('SELECT id, name FROM projects WHERE owner_id = ? ORDER BY id DESC LIMIT 500');
    $st->execute([getCurrentUserId()]);
    $projects = $st->fetchAll();
  } else {
    // fallback: all projects limited
    $st = $pdo->query('SELECT id, name FROM projects ORDER BY id DESC LIMIT 100');
    $projects = $st->fetchAll();
  }

  $data = [];

  foreach ($projects as $p) {
    $pid = (int)$p['id'];
    // latest design entry with client_file ending with json
    $st = $pdo->prepare("SELECT id, title, client_file, created_at FROM designs WHERE project_id = ? AND client_file IS NOT NULL ORDER BY id DESC LIMIT 20");
    $st->execute([$pid]);
    $rows = $st->fetchAll();
    $jsonRow = null;
    foreach ($rows as $r) {
      $cf = (string)$r['client_file'];
      if (strtolower(substr($cf, -5)) === '.json') { $jsonRow = $r; break; }
    }
    if (!$jsonRow) { continue; }

    $webPath = (string)$jsonRow['client_file'];
    $rel = ltrim($webPath, '/');
    if (strpos($rel, 'uploads/designs/') !== 0) { continue; }
    $abs = BASE_PATH . '/public/' . $rel;
    if (!is_file($abs)) { continue; }

    $summary = null; $estimates = null; $plan = null; $createdAt = null;
    try {
      $raw = file_get_contents($abs);
      $json = json_decode($raw, true);
      if (isset($json['summary'])) $summary = $json['summary'];
      if (isset($json['estimates'])) $estimates = $json['estimates'];
      if (isset($json['plan'])) $plan = $json['plan'];
      if (isset($json['createdAt'])) $createdAt = $json['createdAt'];
    } catch (Throwable $e) {
      $summary = null; $estimates = null;
    }

    // If no estimates present, try computing from summary
    if (!$estimates && is_array($summary)) {
      $estimates = (function($s){
        $safeNum = function($v,$d=0){ return (is_numeric($v) && is_finite((float)$v)) ? (float)$v : $d; };
        $totalWallLength = $safeNum($s['totalWallLength'] ?? 0, 0);
        $totalArea = $safeNum($s['totalArea'] ?? 0, 0);
        // Wall
        $wallHeight = 3.048; $bricksPerSqM = 33; $cementBagsPerM2 = 0.15; $sandM3PerM2 = 0.04; $waterLPerM2 = 35;
        $area = $totalWallLength * $wallHeight;
        $totalBricks = round($area * $bricksPerSqM);
        $cementBags = $area * $cementBagsPerM2;
        $sandM3 = $area * $sandM3PerM2;
        $waterLiters = $area * $waterLPerM2;
        $rates = [ 'brick'=>12, 'cementBag'=>550, 'sandPerM3'=>800, 'laborPerM2'=>220 ];
        $bricksCost = $totalBricks * $rates['brick'];
        $cementCost = ceil($cementBags) * $rates['cementBag'];
        $sandCost = $sandM3 * $rates['sandPerM3'];
        $laborCost = $area * $rates['laborPerM2'];
        $materialsCost = $bricksCost + $cementCost + $sandCost;
        $grandTotal = $materialsCost + $laborCost;

        // RCC roof
        $thickness = 0.125; $volume = $totalArea * $thickness; // m3
        $perM3 = [ 'steelKg'=>80, 'cementBags'=>7, 'sandM3'=>0.44, 'stoneM3'=>0.88, 'waterL'=>180, 'laborBDT'=>800, 'shutteringBDTPerM2'=>150 ];
        $cost = [ 'steelPerTonBDT'=>100000, 'cementBagBDT'=>550, 'sandPerM3BDT'=>800, 'stonePerM3BDT'=>1200 ];
        $steelKg = $volume * $perM3['steelKg'];
        $cementBagsRcc = $volume * $perM3['cementBags'];
        $sandM3Rcc = $volume * $perM3['sandM3'];
        $stoneM3 = $volume * $perM3['stoneM3'];
        $waterLRcc = $volume * $perM3['waterL'];
        $laborRcc = $volume * $perM3['laborBDT'];
        $shutteringCost = $totalArea * $perM3['shutteringBDTPerM2'];
        $steelCost = ($steelKg/1000) * $cost['steelPerTonBDT'];
        $cementCostRcc = ceil($cementBagsRcc) * $cost['cementBagBDT'];
        $sandCostRcc = $sandM3Rcc * $cost['sandPerM3BDT'];
        $stoneCostRcc = $stoneM3 * $cost['stonePerM3BDT'];
        $materialsCostRcc = $steelCost + $cementCostRcc + $sandCostRcc + $stoneCostRcc;
        $grandTotalRcc = $materialsCostRcc + $laborRcc + $shutteringCost;

        return [
          'wall' => [
            'areaM2' => $area,
            'bricks' => $totalBricks,
            'cementBags' => $cementBags,
            'sandM3' => $sandM3,
            'waterLiters' => $waterLiters,
            'materialsCostBDT' => $materialsCost,
            'laborCostBDT' => $laborCost,
            'grandTotalBDT' => $grandTotal,
          ],
          'rcc' => [
            'areaM2' => $totalArea,
            'volumeM3' => $volume,
            'steelKg' => $steelKg,
            'cementBags' => $cementBagsRcc,
            'sandM3' => $sandM3Rcc,
            'stoneM3' => $stoneM3,
            'waterLiters' => $waterLRcc,
            'shutteringAreaM2' => $totalArea,
            'materialsCostBDT' => $materialsCostRcc,
            'laborCostBDT' => $laborRcc,
            'shutteringCostBDT' => $shutteringCost,
            'grandTotalBDT' => $grandTotalRcc,
          ]
        ];
      })($summary);
    }

    $data[] = [
      'project_id' => $pid,
      'project_name' => (string)$p['name'],
      'design_id' => (int)$jsonRow['id'],
      'title' => (string)$jsonRow['title'],
      'saved_at' => $createdAt ?: $jsonRow['created_at'],
      'file' => $webPath,
      'summary' => $summary,
      'estimates' => $estimates,
    ];
  }

  echo json_encode(['success'=>true,'data'=>$data]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
