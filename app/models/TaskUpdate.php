<?php
// TaskUpdate model for logging task progress notes/photos

require_once __DIR__ . '/../../config/database.php';

class TaskUpdate {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function create($taskId, $userId, $status = null, $note = null, $photoPath = null) {
        $sql = "INSERT INTO task_updates (task_id, user_id, status, note, photo_path, created_at) VALUES (?,?,?,?,?,NOW())";
        $stmt = $this->pdo->prepare($sql);
        $ok = $stmt->execute([(int)$taskId, (int)$userId, $status, $note, $photoPath]);
        if ($ok) {
            return ['success'=>true,'update_id'=>$this->pdo->lastInsertId()];
        }
        return ['success'=>false,'message'=>'Failed to log task update'];
    }

    public function getByTask($taskId, $limit = 50) {
        $limit = max(1, min(200, (int)$limit));
        $stmt = $this->pdo->prepare("SELECT id, task_id, user_id, status, note, photo_path, created_at FROM task_updates WHERE task_id = ? ORDER BY id DESC LIMIT {$limit}");
        $stmt->execute([(int)$taskId]);
        return $stmt->fetchAll() ?: [];
    }

    public function getByUser($userId, $limit = 50) {
        $limit = max(1, min(200, (int)$limit));
        $stmt = $this->pdo->prepare("SELECT id, task_id, user_id, status, note, photo_path, created_at FROM task_updates WHERE user_id = ? ORDER BY id DESC LIMIT {$limit}");
        $stmt->execute([(int)$userId]);
        return $stmt->fetchAll() ?: [];
    }
}
