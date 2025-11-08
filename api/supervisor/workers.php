<?php
// Supervisor Workers Utility API
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireSupervisor();
if(!headers_sent()) header('Content-Type: application/json');
$pdo = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if($method!=='GET'){ http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }
$action = $_GET['action'] ?? 'unassigned';
try {
  if($action==='unassigned') {
    // Users not in any team_members or supervisor_assignments (excluding admins)
    $sql = "SELECT u.id, u.name, u.email
            FROM users u
            WHERE u.role IN ('supervisor', 'site_manager', 'site_engineer', 'logistic_officer', 'sub_contractor')
              AND NOT EXISTS (SELECT 1 FROM team_members tm WHERE tm.worker_id = u.id)
              AND NOT EXISTS (SELECT 1 FROM supervisor_assignments sa WHERE sa.worker_id = u.id)
            ORDER BY u.name LIMIT 200";
    $rows = $pdo->query($sql)->fetchAll() ?: [];
    echo json_encode(['success'=>true,'data'=>$rows]); exit;
  } elseif($action==='available_for_task') {
    $taskId = (int)($_GET['task_id'] ?? 0);
    $sql = "SELECT u.id, u.name, u.email,
              (SELECT COUNT(*) FROM task_assignments ta WHERE ta.worker_id=u.id AND ta.active=1) AS active_tasks
            FROM users u
            WHERE u.role='worker'
              AND u.id NOT IN (SELECT worker_id FROM task_assignments WHERE task_id=:tid AND active=1)
            ORDER BY u.name LIMIT 200";
    $stmt=$pdo->prepare($sql); $stmt->execute([':tid'=>$taskId]);
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll() ?: []]); exit;
  }
  http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid action']);
} catch(Throwable $e){ http_response_code(500); echo json_encode(['success'=>false,'message'=>'Server error']); }
