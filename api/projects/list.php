<?php
// Fallback: generic projects list for any authenticated user (minimal fields)
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/app/core/auth.php';
require_once dirname(__DIR__, 2) . '/app/core/helpers.php';

requireAuth();

$pdo = Database::getConnection();
header('Content-Type: application/json');

try {
  $q = isset($_GET['q']) ? '%'.trim((string)$_GET['q']).'%' : null;
  $mine = isset($_GET['mine']) ? (int)$_GET['mine'] : 0; // when set and user is client, restrict to their projects
  $limit = min(max((int)($_GET['limit'] ?? 500), 1), 2000);
  $sql = 'SELECT id, name, status FROM projects WHERE 1=1';
  if ($q) { $sql .= ' AND name LIKE :q'; }
  if ($mine && hasRole('client')) { $sql .= ' AND owner_id = :owner_id'; }
  $sql .= ' ORDER BY id DESC LIMIT :limit';
  $st = $pdo->prepare($sql);
  if ($q) { $st->bindValue(':q', $q, PDO::PARAM_STR); }
  if ($mine && hasRole('client')) { $st->bindValue(':owner_id', getCurrentUserId(), PDO::PARAM_INT); }
  $st->bindValue(':limit', $limit, PDO::PARAM_INT);
  $st->execute();
  echo json_encode(['success'=>true,'data'=>$st->fetchAll()]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
