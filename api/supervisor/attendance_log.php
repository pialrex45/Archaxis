<?php
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/models/WorkerAssignmentLog.php';
require_once __DIR__ . '/../../app/models/Task.php';
requireSupervisor();
if(!headers_sent()) header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$actorId = getCurrentUserId();
$logModel = new WorkerAssignmentLog();
$taskModel = new Task();

function read_json(){ $d=json_decode(file_get_contents('php://input'),true); return is_array($d)?$d:$_POST; }

try {
  if($method==='GET'){
    if($action==='list'){
      $df = $_GET['date_from'] ?? date('Y-m-d');
      $dt = $_GET['date_to'] ?? $df;
      $wid = isset($_GET['worker_id'])?(int)$_GET['worker_id']:null;
      $tid = isset($_GET['task_id'])?(int)$_GET['task_id']:null;
      $rows = $logModel->list($df,$dt,$wid,$tid,200);
      echo json_encode(['success'=>true,'data'=>$rows]); exit;
    }
    if($action==='export'){
      $df = $_GET['date_from'] ?? date('Y-m-01');
      $dt = $_GET['date_to'] ?? date('Y-m-d');
      $group = $_GET['group'] ?? 'raw'; // raw|day|month
      $rows = $logModel->list($df,$dt,null,null,5000);
      if(isset($_GET['raw'])){ echo json_encode(['success'=>true,'data'=>$rows]); exit; }
      header('Content-Type: text/html');
      echo "<html><head><title>Attendance Export</title><style>body{font-family:Arial;font-size:12px;margin:16px}table{border-collapse:collapse;width:100%;margin-bottom:18px}th,td{border:1px solid #999;padding:4px 6px;font-size:12px}th{background:#eee}h3{margin:8px 0 4px} .tot{font-weight:bold;background:#f5f5f5}</style></head><body>";
      echo "<h2>Attendance / Assignment Log</h2><div><strong>Range:</strong> $df to $dt</div><div><strong>Generated:</strong> ".date('Y-m-d H:i')."</div><hr />";
      if($group==='day'){
        $byDay=[]; foreach($rows as $r){ $byDay[$r['log_date']][]=$r; }
        ksort($byDay);
        foreach($byDay as $day=>$list){ $dayTotal=0; foreach($list as $r){ $dayTotal += (float)$r['hours']; }
          echo "<h3>Date: $day (Total Hours: ".number_format($dayTotal,2).")</h3><table><tr><th>Worker</th><th>Task</th><th>Hours</th><th>Note</th></tr>";
          foreach($list as $r){ $w=htmlspecialchars($r['worker_name']??('#'.$r['worker_user_id'])); $t=htmlspecialchars($r['task_title']??''); $h=htmlspecialchars($r['hours']); $n=htmlspecialchars($r['note']); echo "<tr><td>$w</td><td>$t</td><td style='text-align:right'>$h</td><td>$n</td></tr>"; }
          echo "<tr class='tot'><td colspan='2'>Day Total</td><td style='text-align:right'>".number_format($dayTotal,2)."</td><td></td></tr></table>";
        }
      } elseif($group==='month') {
        $byMonth=[]; foreach($rows as $r){ $m=substr($r['log_date'],0,7); $byMonth[$m][]=$r; }
        ksort($byMonth);
        foreach($byMonth as $m=>$list){ $monthTotal=0; foreach($list as $r){ $monthTotal += (float)$r['hours']; }
          echo "<h3>Month: $m (Total Hours: ".number_format($monthTotal,2).")</h3><table><tr><th>Date</th><th>Worker</th><th>Task</th><th>Hours</th><th>Note</th></tr>";
          foreach($list as $r){ $d=htmlspecialchars($r['log_date']); $w=htmlspecialchars($r['worker_name']??('#'.$r['worker_user_id'])); $t=htmlspecialchars($r['task_title']??''); $h=htmlspecialchars($r['hours']); $n=htmlspecialchars($r['note']); echo "<tr><td>$d</td><td>$w</td><td>$t</td><td style='text-align:right'>$h</td><td>$n</td></tr>"; }
          echo "<tr class='tot'><td colspan='3'>Month Total</td><td style='text-align:right'>".number_format($monthTotal,2)."</td><td></td></tr></table>";
        }
      } else {
        echo "<table><tr><th>Date</th><th>Worker</th><th>Task</th><th>Hours</th><th>Note</th></tr>";
        foreach($rows as $r){ $d=htmlspecialchars($r['log_date']); $w=htmlspecialchars($r['worker_name']??('#'.$r['worker_user_id'])); $t=htmlspecialchars($r['task_title']??''); $h=htmlspecialchars($r['hours']); $n=htmlspecialchars($r['note']); echo "<tr><td>$d</td><td>$w</td><td>$t</td><td style='text-align:right'>$h</td><td>$n</td></tr>"; }
        echo "</table>";
      }
      echo "</body></html>"; exit;
    }
    if($action==='workers'){
      $pdo = Database::getConnection();
      $rows = $pdo->query("SELECT id,name FROM users ORDER BY name, id LIMIT 2000")->fetchAll();
      if(!$rows) $rows=[];
      echo json_encode(['success'=>true,'data'=>$rows]); exit;
    }
    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid action']); exit;
  }
  if($method==='POST'){
    if($action==='add'){
      $input = read_json();
      $wid=(int)($input['worker_id'] ?? 0); $tid=isset($input['task_id'])?(int)$input['task_id']:null; $date=$input['date'] ?? date('Y-m-d'); $hours= isset($input['hours'])?$input['hours']:null; $note= trim((string)($input['note'] ?? '')) ?: null;
      if($wid<=0){ http_response_code(400); echo json_encode(['success'=>false,'message'=>'Worker required']); exit; }
      $id = $logModel->add($wid,$tid,$date,$hours,$note,$actorId);
      if($id){ echo json_encode(['success'=>true,'entry_id'=>$id,'message'=>'Added']); exit; }
      http_response_code(500); echo json_encode(['success'=>false,'message'=>'Insert failed']); exit;
    }
    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid action']); exit;
  }
  http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']);
} catch(Throwable $e){ http_response_code(500); echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]); }