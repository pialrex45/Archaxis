<?php
require_once __DIR__ . '/../../config/database.php';

class TaskAssignment {
    private $pdo;
    public function __construct() { $this->pdo = Database::getConnection(); }

    public function bulkAssign($taskId, array $workerIds, $actorUserId = null, $role = 'worker') {
        $taskId = (int)$taskId; if ($taskId<=0 || empty($workerIds)) return 0;
        $role = in_array($role,['worker','lead']) ? $role : 'worker';
        $ins = $this->pdo->prepare('INSERT INTO task_assignments (task_id, worker_id, assigned_by, role, active, assigned_at) VALUES (:tid,:wid,:aid,:role,1,NOW()) ON DUPLICATE KEY UPDATE active=1, assigned_by=VALUES(assigned_by), role=VALUES(role), assigned_at=VALUES(assigned_at), unassigned_at=NULL');
        $count=0;
        foreach ($workerIds as $wid) {
            $wid=(int)$wid; if($wid<=0) continue;
            $ins->execute([':tid'=>$taskId, ':wid'=>$wid, ':aid'=>$actorUserId, ':role'=>$role]);
            $count += $ins->rowCount() ? 1 : 0; // approximate
        }
        return $count;
    }

    public function unassign($taskId, $workerId) {
        $taskId=(int)$taskId; $workerId=(int)$workerId; if($taskId<=0||$workerId<=0) return false;
        $stmt = $this->pdo->prepare('UPDATE task_assignments SET active=0, unassigned_at=NOW() WHERE task_id=:tid AND worker_id=:wid AND active=1');
        $stmt->execute([':tid'=>$taskId, ':wid'=>$workerId]);
        return $stmt->rowCount()>0;
    }

    public function listActiveByTask($taskId) {
        $stmt = $this->pdo->prepare('SELECT ta.*, u.name, u.email FROM task_assignments ta JOIN users u ON u.id=ta.worker_id WHERE ta.task_id=:tid AND ta.active=1 ORDER BY u.name');
        $stmt->execute([':tid'=>(int)$taskId]);
        return $stmt->fetchAll() ?: [];
    }

    public function activeCountsForWorkers(array $workerIds) {
        if(empty($workerIds)) return [];
        $in = implode(',', array_fill(0,count($workerIds),'?'));
        $sql = "SELECT worker_id, COUNT(*) cnt FROM task_assignments WHERE active=1 AND worker_id IN ($in) GROUP BY worker_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_map('intval',$workerIds));
        $out=[]; foreach($stmt->fetchAll() as $r){ $out[(int)$r['worker_id']]=(int)$r['cnt']; } return $out;
    }
}
