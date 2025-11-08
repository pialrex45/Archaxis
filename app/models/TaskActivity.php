<?php
require_once __DIR__ . '/../../config/database.php';

class TaskActivity {
    private $pdo;
    public function __construct() { $this->pdo = Database::getConnection(); }

    public function add($taskId,$projectId,$actorId,$targetUserId,$type,$summary,$details=null,$statusAfter=null,$hours=null){
        $taskId=(int)$taskId; if($taskId<=0||!$type||!$summary) return false;
        $sql='INSERT INTO task_activity (task_id, project_id, actor_user_id, target_user_id, event_type, status_after, hours, summary, details, created_at) VALUES (:tid,:pid,:aid,:tuid,:etype,:status_after,:hours,:summary,:details,NOW())';
        $stmt=$this->pdo->prepare($sql);
        $stmt->bindValue(':tid',$taskId, PDO::PARAM_INT);
    $stmt->bindValue(':pid',$projectId? (int)$projectId : null, $projectId?PDO::PARAM_INT:PDO::PARAM_NULL);
    $stmt->bindValue(':aid',$actorId? (int)$actorId : null, $actorId?PDO::PARAM_INT:PDO::PARAM_NULL);
    $stmt->bindValue(':tuid',$targetUserId? (int)$targetUserId : null, $targetUserId?PDO::PARAM_INT:PDO::PARAM_NULL);
        $stmt->bindValue(':etype',$type);
    $stmt->bindValue(':status_after',$statusAfter!==null?$statusAfter:null, $statusAfter!==null?PDO::PARAM_STR:PDO::PARAM_NULL);
    if($hours===null||$hours===''){ $stmt->bindValue(':hours', null, PDO::PARAM_NULL); } else { $stmt->bindValue(':hours', (float)$hours); }
    $stmt->bindValue(':summary',$summary, PDO::PARAM_STR);
    $stmt->bindValue(':details',$details!==null?$details:null, $details!==null?PDO::PARAM_STR:PDO::PARAM_NULL);
        if($stmt->execute()) return $this->pdo->lastInsertId();
        return false;
    }

    public function list($taskId,$limit=50,$offset=0,$type=null,$dateFrom=null,$dateTo=null){
        $taskId=(int)$taskId; if($taskId<=0) return [];
        $limit=max(1,min(200,(int)$limit)); $offset=max(0,(int)$offset);
        $clauses=['task_id=:tid'];
        if($type){ $clauses[]='event_type=:et'; }
        if($dateFrom){ $clauses[]='created_at >= :df'; }
        if($dateTo){ $clauses[]='created_at <= :dt'; }
        $where=implode(' AND ',$clauses);
    $sql="SELECT id, task_id, project_id, actor_user_id, target_user_id, event_type, status_after, hours, summary, details, created_at FROM task_activity WHERE $where ORDER BY created_at DESC, id DESC LIMIT :lim OFFSET :off";
        $stmt=$this->pdo->prepare($sql);
        $stmt->bindValue(':tid',$taskId, PDO::PARAM_INT);
        if($type){ $stmt->bindValue(':et',$type); }
        if($dateFrom){ $stmt->bindValue(':df',$dateFrom.' 00:00:00'); }
        if($dateTo){ $stmt->bindValue(':dt',$dateTo.' 23:59:59'); }
        $stmt->bindValue(':lim',$limit, PDO::PARAM_INT);
        $stmt->bindValue(':off',$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }
}
