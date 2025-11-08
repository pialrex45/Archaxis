<?php
// Designs workflow API: client submits design files; engineer approves & uploads final PDF;
// list visible to all roles with project mapping.
// Ensure BASE_PATH is defined whether routed via public/index.php or called directly.
if (!defined('BASE_PATH')) {
  define('BASE_PATH', dirname(__DIR__));
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/helpers.php';

$pdo = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
header('Content-Type: application/json');

try {
  if ($method === 'POST' && $action === 'client_submit') {
    // Allow clients and site engineers to save snapshots (JSON/PDF/ZIP). This updates global state by creating a new row.
    requireAuth(); if (!hasAnyRole(['client','site_engineer','admin'])) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Access denied']); exit; }
    $projectId = (int)($_POST['project_id'] ?? 0);
    $title = trim((string)($_POST['title'] ?? ''));
    if ($projectId<=0 || $title==='') { http_response_code(422); echo json_encode(['success'=>false,'message'=>'Missing project or title']); exit; }
    if (!isset($_FILES['file']) || $_FILES['file']['error']!==UPLOAD_ERR_OK) { http_response_code(422); echo json_encode(['success'=>false,'message'=>'File required']); exit; }
    $uploadDir = BASE_PATH.'/public/uploads/designs'; if (!is_dir($uploadDir)) { @mkdir($uploadDir,0775,true); }
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    $safe = 'client_'.time().'_'.bin2hex(random_bytes(3)).'.'.$ext;
    $dest = $uploadDir.'/'.$safe;
    if (!move_uploaded_file($_FILES['file']['tmp_name'],$dest)) { throw new Exception('Upload failed'); }
    $stmt = $pdo->prepare('INSERT INTO designs (project_id, title, client_file, status, created_by) VALUES (?,?,?,?,?)');
    $stmt->execute([$projectId, $title, '/uploads/designs/'.$safe, 'submitted', getCurrentUserId()]);
    $designId = (int)$pdo->lastInsertId();
    // Log activity
    $log = $pdo->prepare('INSERT INTO designs_logs (design_id, project_id, action, title, actor_id, details) VALUES (?,?,?,?,?,?)');
    $log->execute([$designId, $projectId, 'client_submitted', $title, getCurrentUserId(), basename($safe)]);
    echo json_encode(['success'=>true, 'id'=>$designId]); exit;
  }

  if ($method === 'POST' && $action === 'engineer_approve') {
    requireAuth(); if (!hasAnyRole(['site_engineer','admin'])) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Access denied']); exit; }
    $id = (int)($_POST['id'] ?? 0);
    if ($id<=0) { http_response_code(422); echo json_encode(['success'=>false,'message'=>'Missing id']); exit; }
    if (!isset($_FILES['pdf']) || $_FILES['pdf']['error']!==UPLOAD_ERR_OK) { http_response_code(422); echo json_encode(['success'=>false,'message'=>'PDF required']); exit; }
    $uploadDir = BASE_PATH.'/public/uploads/designs'; if (!is_dir($uploadDir)) { @mkdir($uploadDir,0775,true); }
    $safe = 'approved_'.time().'_'.bin2hex(random_bytes(3)).'.pdf';
    $dest = $uploadDir.'/'.$safe;
    if (!move_uploaded_file($_FILES['pdf']['tmp_name'],$dest)) { throw new Exception('Upload failed'); }
    $stmt = $pdo->prepare('UPDATE designs SET engineer_pdf = ?, status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?');
    $stmt->execute(['/uploads/designs/'.$safe, 'approved', getCurrentUserId(), $id]);
    // Fetch project for logging
    $p = $pdo->prepare('SELECT project_id, title FROM designs WHERE id = ?');
    $p->execute([$id]);
    $row = $p->fetch();
    if ($row) {
      $log = $pdo->prepare('INSERT INTO designs_logs (design_id, project_id, action, title, actor_id, details) VALUES (?,?,?,?,?,?)');
      $log->execute([$id, (int)$row['project_id'], 'engineer_approved', (string)$row['title'], getCurrentUserId(), basename($safe)]);
    }
    echo json_encode(['success'=>true]); exit;
  }

  if ($method === 'GET') {
    requireAuth();
    // File download: /api/designs.php?action=download&file=client|approved&id=123
    $act = $_GET['action'] ?? '';
    if ($act === 'download') {
      $id = (int)($_GET['id'] ?? 0);
      $which = $_GET['file'] ?? 'client';
      if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Missing id']); exit; }
      $st = $pdo->prepare('SELECT client_file, engineer_pdf, title FROM designs WHERE id = ?');
      $st->execute([$id]);
      $row = $st->fetch();
      if (!$row) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'Design not found']); exit; }
      $webPath = $which === 'approved' ? ($row['engineer_pdf'] ?? '') : ($row['client_file'] ?? '');
      if (!$webPath) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'File not available']); exit; }
      // Map /uploads/... to filesystem path BASE_PATH/public/uploads/...
      $safeRel = ltrim($webPath, '/');
      // Only allow under uploads/designs
      if (strpos($safeRel, 'uploads/designs/') !== 0) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Forbidden path']); exit; }
      $abs = BASE_PATH . '/public/' . $safeRel;
      if (!is_file($abs)) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'File not found on server']); exit; }
      // Send file
      $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
      $ctype = $ext === 'pdf' ? 'application/pdf' : 'application/octet-stream';
      header('Content-Type: ' . $ctype);
      header('Content-Length: ' . filesize($abs));
      header('Content-Disposition: inline; filename="' . basename($abs) . '"');
      readfile($abs);
      exit;
    }

    if ($act === 'logs') {
      // Recent design activities from designs_logs, joined with projects and users
      $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
      $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
      if ($limit < 1) $limit = 100; if ($limit > 500) $limit = 500;
      $sql = "SELECT l.id, l.created_at, l.project_id, l.design_id, l.title, l.action, l.details,
                     p.name AS project_name, u.name AS actor_name
                FROM designs_logs l
                JOIN projects p ON p.id = l.project_id
           LEFT JOIN users u ON u.id = l.actor_id
               WHERE 1=1";
      $params = [];
      if ($projectId) { $sql .= ' AND l.project_id = :pid'; $params[':pid'] = $projectId; }
      $sql .= ' ORDER BY l.id DESC LIMIT :lim';
      $st = $pdo->prepare($sql);
      foreach ($params as $k=>$v) { $st->bindValue($k,$v, PDO::PARAM_INT); }
      $st->bindValue(':lim', $limit, PDO::PARAM_INT);
      $st->execute();
      echo json_encode(['success'=>true,'data'=>$st->fetchAll()]);
      exit;
    }

    // Default: list
    $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
    $status = isset($_GET['status']) ? trim((string)$_GET['status']) : null;
    $limit = isset($_GET['limit']) ? max(1, min((int)$_GET['limit'], 1000)) : 500;
  $sql = "SELECT d.*, p.name AS project_name, u.name AS created_by_name
        FROM designs d
        JOIN projects p ON p.id = d.project_id
     LEFT JOIN users u ON u.id = d.created_by
       WHERE 1=1";
    $params = [];
    if ($projectId) { $sql .= ' AND d.project_id = :pid'; $params[':pid'] = $projectId; }
    if ($status) { $sql .= ' AND d.status = :st'; $params[':st'] = $status; }
    $sql .= ' ORDER BY d.id DESC LIMIT :lim';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k=>$v) {
      if ($k === ':st') { $stmt->bindValue($k,$v, PDO::PARAM_STR); }
      else { $stmt->bindValue($k,$v, PDO::PARAM_INT); }
    }
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]); exit;
  }

  http_response_code(405); echo json_encode(['success'=>false,'message'=>'Not supported']);
} catch (Throwable $e) {
  http_response_code(500); echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
