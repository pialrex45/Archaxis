<?php
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/models/Project.php';

if(!headers_sent()) header('Content-Type: application/json');
try {
  requireAuth();
  if(!hasRole('client')){ http_response_code(403); echo json_encode(['success'=>false,'message'=>'Access denied']); exit; }
  $pdo = Database::getConnection();
  $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
  $action = $_GET['action'] ?? ($_POST['action'] ?? 'list');
  $projectModel = new Project();
  $clientId = getCurrentUserId();

  if($method==='GET' && $action==='list'){
    // Ensure projects table has site_manager_id column (safety in case migrations not applied)
    try { $col=$pdo->query("SHOW COLUMNS FROM projects LIKE 'site_manager_id'"); if(!$col || $col->rowCount()==0){ $pdo->exec("ALTER TABLE projects ADD COLUMN site_manager_id INT UNSIGNED NULL AFTER owner_id, ADD INDEX idx_site_manager_id (site_manager_id)"); } } catch(Throwable $e){}
    $avgSql = "SELECT u.id, u.name,
       COUNT(p.id) AS projects_managed,
       GROUP_CONCAT(DISTINCT CASE WHEN p.status IN ('active','planning','in progress') THEN LEFT(p.name,60) END ORDER BY p.id SEPARATOR ' | ') AS ongoing_projects
       FROM users u
       LEFT JOIN projects p ON p.site_manager_id = u.id
  WHERE u.role IN ('manager','project_manager') AND u.approved=1
       GROUP BY u.id, u.name
       ORDER BY u.name";
  $rows = [];
  try { $rows = $pdo->query($avgSql)->fetchAll(PDO::FETCH_ASSOC) ?: []; }
  catch(Throwable $qe){ if(isset($_GET['dbg'])) echo json_encode(['success'=>false,'message'=>'Query failed','error'=>$qe->getMessage()]); }
    if(!$rows){
        // Diagnostics only if explicitly requested or empty
        $diag=[]; try { $dbName=$pdo->query('SELECT DATABASE()')->fetchColumn(); $diag['database']=$dbName; } catch(Throwable $e){}
        try { $rct=$pdo->query("SELECT role, COUNT(*) c FROM users WHERE role IN ('manager','site_manager','project_manager') GROUP BY role")->fetchAll(PDO::FETCH_KEY_PAIR); $diag['role_counts']=$rct; } catch(Throwable $e){}
        try { $any=$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(); $diag['total_users']=$any; } catch(Throwable $e){}
        echo json_encode(['success'=>true,'data'=>[], 'diagnostics'=>$diag]); exit;
    }
    echo json_encode(['success'=>true,'data'=>$rows]); exit;
  }
  if($method==='POST' && $action==='assign'){
    $data = json_decode(file_get_contents('php://input'),true); if(!is_array($data)) $data=$_POST;
    $projectId = (int)($data['project_id'] ?? 0); $managerId=(int)($data['manager_user_id'] ?? 0);
    if($projectId<=0||$managerId<=0){ http_response_code(422); echo json_encode(['success'=>false,'message'=>'Invalid project or manager']); exit; }
    // Ensure project belongs to this client
    $stmt=$pdo->prepare("SELECT owner_id FROM projects WHERE id=? LIMIT 1"); $stmt->execute([$projectId]); $owner=$stmt->fetchColumn();
    if((int)$owner !== (int)$clientId){ http_response_code(403); echo json_encode(['success'=>false,'message'=>'Not your project']); exit; }
    // Update site_manager_id (create column if needed)
    $hasCol=false; try { $c=$pdo->query("SHOW COLUMNS FROM projects LIKE 'site_manager_id'"); $hasCol=$c&&$c->rowCount()>0; } catch(Throwable $e){ $hasCol=false; }
    if(!$hasCol){ $pdo->exec("ALTER TABLE projects ADD COLUMN site_manager_id INT UNSIGNED NULL"); }
    $up=$pdo->prepare("UPDATE projects SET site_manager_id=:mid, updated_at=NOW() WHERE id=:pid");
    $ok=$up->execute([':mid'=>$managerId, ':pid'=>$projectId]);
    echo json_encode(['success'=>$ok,'manager_user_id'=>$managerId]); exit;
  }
  if($method==='POST' && $action==='unassign'){
    $data = json_decode(file_get_contents('php://input'),true); if(!is_array($data)) $data=$_POST;
    $projectId = (int)($data['project_id'] ?? 0);
    if($projectId<=0){ http_response_code(422); echo json_encode(['success'=>false,'message'=>'Invalid project']); exit; }
    $stmt=$pdo->prepare("SELECT owner_id FROM projects WHERE id=? LIMIT 1"); $stmt->execute([$projectId]); $owner=$stmt->fetchColumn();
    if((int)$owner !== (int)$clientId){ http_response_code(403); echo json_encode(['success'=>false,'message'=>'Not your project']); exit; }
    $pdo->prepare("UPDATE projects SET site_manager_id=NULL, updated_at=NOW() WHERE id=?")->execute([$projectId]);
    echo json_encode(['success'=>true,'message'=>'Unassigned']); exit;
  }
  http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid action']);
} catch(Throwable $e){ http_response_code(500); echo json_encode(['success'=>false,'message'=>'Server error']); }
