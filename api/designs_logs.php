<?php
// Designs logs list (read-only)
if (!defined('BASE_PATH')) { define('BASE_PATH', dirname(__DIR__)); }
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/core/auth.php';
require_once BASE_PATH . '/app/core/helpers.php';

header('Content-Type: application/json');
try {
  requireAuth();
  $pdo = Database::getConnection();
  $limit = isset($_GET['limit']) ? max(1, min((int)$_GET['limit'], 500)) : 100;
  $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
  $sql = "SELECT l.*, p.name AS project_name, u.name AS actor_name
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
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
