<?php
class ProjectActivity {
    private $pdo;
    public function __construct() {
        $db = new Database();
        $this->pdo = $db->connect();
    }
    public function add($projectId,$entityType,$entityId,$action,$summary,$old=null,$new=null,$actor=null){
        try {
            $stmt = $this->pdo->prepare("INSERT INTO project_activity (project_id,entity_type,entity_id,action,summary,old_value,new_value,actor_user_id,created_at) VALUES (:p,:et,:eid,:a,:s,:old,:new,:u,NOW())");
            $stmt->bindValue(':p',$projectId,PDO::PARAM_INT);
            $stmt->bindValue(':et',$entityType);
            $stmt->bindValue(':eid',$entityId?:null,PDO::PARAM_INT);
            $stmt->bindValue(':a',$action);
            $stmt->bindValue(':s',mb_substr($summary,0,255));
            $stmt->bindValue(':old', $old!==null ? json_encode($old) : null, PDO::PARAM_STR);
            $stmt->bindValue(':new', $new!==null ? json_encode($new) : null, PDO::PARAM_STR);
            $stmt->bindValue(':u',$actor?:null,PDO::PARAM_INT);
            $stmt->execute();
            return true;
        } catch (Throwable $e){ return false; }
    }
    public function listByProject($projectId,$limit=50,$offset=0){
        try {
            $limit = max(1,min(200,(int)$limit));
            $offset = max(0,(int)$offset);
            $stmt=$this->pdo->prepare("SELECT pa.*, u.name AS actor_name FROM project_activity pa LEFT JOIN users u ON pa.actor_user_id=u.id WHERE pa.project_id=:pid ORDER BY pa.created_at DESC LIMIT :lim OFFSET :off");
            $stmt->bindValue(':pid',$projectId,PDO::PARAM_INT);
            $stmt->bindValue(':lim',$limit,PDO::PARAM_INT);
            $stmt->bindValue(':off',$offset,PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(Throwable $e){ return []; }
    }
}
