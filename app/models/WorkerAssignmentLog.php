<?php
require_once __DIR__ . '/../../config/database.php';

class WorkerAssignmentLog {
    private $pdo;
    public function __construct(){ $this->pdo = Database::getConnection(); }

    public function add($workerId,$taskId,$logDate,$hours,$note,$createdBy){
        $workerId=(int)$workerId; if($workerId<=0) return false;
        $taskId = $taskId? (int)$taskId : null;
        if(!$logDate) $logDate=date('Y-m-d');
        $sql="INSERT INTO worker_assignment_log (worker_user_id, task_id, log_date, hours, note, created_by) VALUES (:wid,:tid,:ld,:hrs,:note,:cb)";
        $st=$this->pdo->prepare($sql);
        $st->bindValue(':wid',$workerId, PDO::PARAM_INT);
        $st->bindValue(':tid',$taskId!==null?$taskId:null, $taskId!==null?PDO::PARAM_INT:PDO::PARAM_NULL);
        $st->bindValue(':ld',$logDate);
        if($hours===null||$hours===''){ $st->bindValue(':hrs', null, PDO::PARAM_NULL);} else { $st->bindValue(':hrs',(float)$hours); }
        $st->bindValue(':note',$note!==null?$note:null,$note!==null?PDO::PARAM_STR:PDO::PARAM_NULL);
        $st->bindValue(':cb',$createdBy? (int)$createdBy : null, $createdBy?PDO::PARAM_INT:PDO::PARAM_NULL);
        if($st->execute()) return $this->pdo->lastInsertId();
        return false;
    }

    public function list($dateFrom,$dateTo,$workerId=null,$taskId=null,$limit=200){
        $clauses=['1=1']; $params=[];
        if($dateFrom){ $clauses[]='log_date >= :df'; $params[':df']=$dateFrom; }
        if($dateTo){ $clauses[]='log_date <= :dt'; $params[':dt']=$dateTo; }
        if($workerId){ $clauses[]='worker_user_id=:wid'; $params[':wid']=(int)$workerId; }
        if($taskId){ $clauses[]='task_id=:tid'; $params[':tid']=(int)$taskId; }
        $where=implode(' AND ',$clauses); $limit = max(1,min(500,(int)$limit));
        $sql="SELECT l.*, u.name as worker_name, t.title as task_title FROM worker_assignment_log l LEFT JOIN users u ON l.worker_user_id=u.id LEFT JOIN tasks t ON l.task_id=t.id WHERE $where ORDER BY log_date DESC, l.id DESC LIMIT $limit";
        $st=$this->pdo->prepare($sql); foreach($params as $k=>$v){ $st->bindValue($k,$v); } $st->execute(); return $st->fetchAll() ?: [];
    }

    public function aggregateByWorker($dateFrom,$dateTo){
        $sql="SELECT worker_user_id, u.name as worker_name, SUM(hours) as total_hours, COUNT(*) as entries FROM worker_assignment_log l LEFT JOIN users u ON l.worker_user_id=u.id WHERE log_date BETWEEN :df AND :dt GROUP BY worker_user_id ORDER BY worker_name";
        $st=$this->pdo->prepare($sql); $st->execute([':df'=>$dateFrom, ':dt'=>$dateTo]); return $st->fetchAll() ?: [];
    }
}
?>