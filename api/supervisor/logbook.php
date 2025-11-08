<?php
// Supervisor Task Logbook API
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/models/TaskActivity.php';
require_once __DIR__ . '/../../app/models/Task.php';
requireSupervisor();
if(!headers_sent()) header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$actorId = getCurrentUserId();
$activity = new TaskActivity();
$taskModel = new Task();

function read_json(){ $d=json_decode(file_get_contents('php://input'),true); return is_array($d)?$d:$_POST; }

try {
  if($method==='GET'){
    $taskId = (int)($_GET['task_id'] ?? 0); if($taskId<=0){ http_response_code(400); echo json_encode(['success'=>false,'message'=>'task_id required']); exit; }
    $limit = (int)($_GET['limit'] ?? 50); $offset=(int)($_GET['offset'] ?? 0); $type=$_GET['type'] ?? null;
  $dateFrom = isset($_GET['date_from']) ? preg_replace('/[^0-9\-]/','',$_GET['date_from']) : null;
  $dateTo = isset($_GET['date_to']) ? preg_replace('/[^0-9\-]/','',$_GET['date_to']) : null;
  if($dateFrom && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$dateFrom)) $dateFrom=null;
  if($dateTo && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$dateTo)) $dateTo=null;
  $rows = $activity->list($taskId,$limit,$offset,$type,$dateFrom,$dateTo);
    $dbg = isset($_GET['dbg']) ? 1:0;
    if($dbg){ echo json_encode(['success'=>true,'count'=>count($rows),'data'=>$rows]); exit; }
    echo json_encode(['success'=>true,'data'=>$rows]); exit;
  }
  if($method==='POST'){
    $input = read_json();
    if($action==='add'){
      $taskId=(int)($input['task_id'] ?? 0); $type=$input['event_type'] ?? $input['type'] ?? 'note';
      $summary=trim((string)($input['summary'] ?? ''));
      $details = isset($input['details']) ? (is_array($input['details'])?json_encode($input['details']): (string)$input['details']) : null;
      $statusAfter = isset($input['status_after']) ? (string)$input['status_after'] : null;
      $hours = isset($input['hours']) ? (float)$input['hours'] : null;
      if($taskId<=0 || $summary===''){ http_response_code(400); echo json_encode(['success'=>false,'message'=>'task_id & summary required']); exit; }
      $projectId=null; try { $row=$taskModel->getById($taskId); $projectId=$row? (int)$row['project_id']:null; } catch(Throwable $e){}
      $id = $activity->add($taskId,$projectId,$actorId,null,$type,$summary,$details,$statusAfter,$hours);
      echo json_encode(['success'=>(bool)$id,'entry_id'=>$id,'debug'=>isset($_GET['dbg'])?['payload'=>$input,'project_id'=>$projectId]:null]); exit;
    }
    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid action']); exit;
  }
  http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']);
} catch(Throwable $e){ http_response_code(500); echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]); }
