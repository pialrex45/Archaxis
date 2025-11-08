<?php
// Supervisor Task Assignments API
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/models/TaskAssignment.php';
require_once __DIR__ . '/../../app/models/TaskActivity.php';
require_once __DIR__ . '/../../app/models/Task.php';
requireSupervisor();
if(!headers_sent()) header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$actorId = getCurrentUserId();
$assignModel = new TaskAssignment();
$activityModel = new TaskActivity();
$taskModel = new Task();

function read_json(){ $d=json_decode(file_get_contents('php://input'),true); return is_array($d)?$d:$_POST; }

try {
  if($method==='GET') {
    $taskId = (int)($_GET['task_id'] ?? 0);
    if($taskId<=0){ http_response_code(400); echo json_encode(['success'=>false,'message'=>'task_id required']); exit; }
    $workers = $assignModel->listActiveByTask($taskId);
    echo json_encode(['success'=>true,'data'=>$workers]); exit;
  }
  if($method==='POST') {
    $input = read_json();
    if($action==='bulk_assign') {
      $taskId = (int)($input['task_id'] ?? 0);
      $workerIds = isset($input['worker_ids']) && is_array($input['worker_ids']) ? $input['worker_ids'] : [];
      if($taskId<=0||empty($workerIds)){ http_response_code(400); echo json_encode(['success'=>false,'message'=>'task_id and worker_ids required']); exit; }
      $added = $assignModel->bulkAssign($taskId,$workerIds,$actorId,'worker');
      // Activity log per worker (concise summary) + aggregated project_id
      $projectId = null; try { $row = $taskModel->getById($taskId); $projectId = $row? (int)$row['project_id'] : null; } catch(Throwable $e){}
      foreach($workerIds as $wid){ $activityModel->add($taskId,$projectId,$actorId,(int)$wid,'assign','Assigned worker '.(int)$wid,null,null,null); }
      echo json_encode(['success'=>true,'assigned'=>$added]); exit;
    } elseif($action==='unassign') {
      $taskId = (int)($input['task_id'] ?? 0); $workerId=(int)($input['worker_id'] ?? 0);
      if($taskId<=0||$workerId<=0){ http_response_code(400); echo json_encode(['success'=>false,'message'=>'task_id and worker_id required']); exit; }
      $ok = $assignModel->unassign($taskId,$workerId);
      if($ok){ $projectId=null; try{$row=$taskModel->getById($taskId); $projectId=$row? (int)$row['project_id']:null;}catch(Throwable $e){} $activityModel->add($taskId,$projectId,$actorId,$workerId,'unassign','Unassigned worker '.$workerId); }
      echo json_encode(['success'=>$ok]); exit;
    }
    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid action']); exit;
  }
  http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']);
} catch(Throwable $e){ http_response_code(500); echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]); }
