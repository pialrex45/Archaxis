<?php
// Supervisor Teams API (additive; backward compatible)
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';

if (!headers_sent()) { header('Content-Type: application/json'); }
requireSupervisor();

$pdo = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? $_POST['action'] ?? null;
$supervisorId = (int)(getCurrentUserId() ?? 0);

function read_json_body() {
  $payload = json_decode(file_get_contents('php://input'), true);
  if (!is_array($payload)) { $payload = $_POST; }
  return $payload ?: [];
}

try {
  if ($method === 'GET') {
    if ($action === 'list') {
      // List teams owned by this supervisor
      $stmt = $pdo->prepare('SELECT id, name, created_at FROM teams WHERE supervisor_id = :sid ORDER BY name');
      $stmt->bindValue(':sid', $supervisorId, PDO::PARAM_INT);
      $stmt->execute();
      echo json_encode(['success'=>true,'data'=>$stmt->fetchAll() ?: []]);
      exit;
    } elseif ($action === 'members') {
      $teamId = (int)($_GET['team_id'] ?? 0);
      if ($teamId <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'team_id required']); exit; }
      // Ensure ownership
      $own = $pdo->prepare('SELECT 1 FROM teams WHERE id = :tid AND supervisor_id = :sid');
      $own->execute([':tid'=>$teamId, ':sid'=>$supervisorId]);
      if (!$own->fetchColumn()) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Forbidden']); exit; }
      $stmt = $pdo->prepare('SELECT u.id, u.name, u.email, u.approved, tm.created_at
                             FROM team_members tm JOIN users u ON u.id = tm.worker_id
                             WHERE tm.team_id = :tid ORDER BY u.name');
      $stmt->execute([':tid'=>$teamId]);
      echo json_encode(['success'=>true,'data'=>$stmt->fetchAll() ?: []]);
      exit;
    } elseif ($action === 'available_workers') {
      $teamId = (int)($_GET['team_id'] ?? 0);
      if ($teamId <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'team_id required']); exit; }
      // Ensure ownership
      $own = $pdo->prepare('SELECT 1 FROM teams WHERE id = :tid AND supervisor_id = :sid');
      $own->execute([':tid'=>$teamId, ':sid'=>$supervisorId]);
      if (!$own->fetchColumn()) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Forbidden']); exit; }
      // List workers not already in this team
      $stmt = $pdo->prepare('SELECT u.id, u.name, u.email, u.approved
                             FROM users u
                             WHERE u.role = "worker"
                               AND u.id NOT IN (SELECT worker_id FROM team_members WHERE team_id = :tid)
                             ORDER BY u.name LIMIT 100');
      $stmt->execute([':tid'=>$teamId]);
      echo json_encode(['success'=>true,'data'=>$stmt->fetchAll() ?: []]);
      exit;
    } else {
      http_response_code(400);
      echo json_encode(['success'=>false,'message'=>'Unknown action']);
      exit;
    }
  }

  if ($method === 'POST') {
    if ($action === 'create') {
      $input = read_json_body();
      $name = trim((string)($input['name'] ?? ''));
      if ($name === '') { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Team name required']); exit; }
      $stmt = $pdo->prepare('INSERT INTO teams (supervisor_id, name, created_at) VALUES (:sid, :name, NOW())');
      $ok = $stmt->execute([':sid'=>$supervisorId, ':name'=>$name]);
      if ($ok) { echo json_encode(['success'=>true,'team_id'=>$pdo->lastInsertId()]); }
      else { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Create failed']); }
      exit;
    } elseif ($action === 'add_member') {
      $input = read_json_body();
      $teamId = (int)($input['team_id'] ?? 0);
      $workerId = (int)($input['worker_id'] ?? 0);
      if ($teamId<=0 || $workerId<=0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'team_id and worker_id required']); exit; }
      // Ensure ownership
      $own = $pdo->prepare('SELECT 1 FROM teams WHERE id = :tid AND supervisor_id = :sid');
      $own->execute([':tid'=>$teamId, ':sid'=>$supervisorId]);
      if (!$own->fetchColumn()) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Forbidden']); exit; }
      $stmt = $pdo->prepare('INSERT IGNORE INTO team_members (team_id, worker_id, created_at) VALUES (:tid, :wid, NOW())');
      $ok = $stmt->execute([':tid'=>$teamId, ':wid'=>$workerId]);
      echo json_encode(['success'=>true]);
      exit;
    } elseif ($action === 'remove_member') {
      $input = read_json_body();
      $teamId = (int)($input['team_id'] ?? 0);
      $workerId = (int)($input['worker_id'] ?? 0);
      if ($teamId<=0 || $workerId<=0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'team_id and worker_id required']); exit; }
      // Ensure ownership
      $own = $pdo->prepare('SELECT 1 FROM teams WHERE id = :tid AND supervisor_id = :sid');
      $own->execute([':tid'=>$teamId, ':sid'=>$supervisorId]);
      if (!$own->fetchColumn()) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Forbidden']); exit; }
      $stmt = $pdo->prepare('DELETE FROM team_members WHERE team_id = :tid AND worker_id = :wid');
      $ok = $stmt->execute([':tid'=>$teamId, ':wid'=>$workerId]);
      echo json_encode(['success'=>true]);
      exit;
    }
    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Unknown action']);
    exit;
  }

  http_response_code(405);
  echo json_encode(['success'=>false,'message'=>'Method not allowed']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
