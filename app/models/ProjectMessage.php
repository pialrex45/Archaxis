<?php
require_once __DIR__ . '/../../config/database.php';

class ProjectMessage {
    private $pdo;
    public function __construct() {
        $db = new Database();
        $this->pdo = $db->connect();
    }

    public function list($projectId, $channel = null, $before = null, $limit = 50) {
        $sql = "SELECT pm.*, u.name AS sender_name
                FROM project_messages pm
                JOIN users u ON pm.sender_id = u.id
                WHERE pm.project_id = :pid";
        $params = [':pid' => $projectId];
        if ($channel) { $sql .= " AND pm.channel = :ch"; $params[':ch'] = $channel; }
        if ($before) { $sql .= " AND pm.created_at < :before"; $params[':before'] = $before; }
        $sql .= " ORDER BY pm.created_at DESC LIMIT :lim";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':pid', (int)$projectId, PDO::PARAM_INT);
        if ($channel) $stmt->bindValue(':ch', $channel, PDO::PARAM_STR);
        if ($before) $stmt->bindValue(':before', $before, PDO::PARAM_STR);
        $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        return array_reverse($rows); // chronological asc for UI
    }

    public function create($projectId, $senderId, $body, $channel = null, $metadata = null) {
        $stmt = $this->pdo->prepare("INSERT INTO project_messages(project_id, sender_id, channel, body, metadata, created_at)
                                      VALUES(?,?,?,?,?, NOW())");
        $meta = $metadata ? json_encode($metadata) : null;
        $ok = $stmt->execute([$projectId, $senderId, $channel, $body, $meta]);
        if (!$ok) return false;
        return $this->getById($this->pdo->lastInsertId());
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT pm.*, u.name AS sender_name FROM project_messages pm JOIN users u ON u.id=pm.sender_id WHERE pm.id=?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function listChannels($projectId) {
        $stmt = $this->pdo->prepare("SELECT id, key_slug, title FROM project_message_channels WHERE project_id=? ORDER BY id ASC");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll();
    }

    public function createChannel($projectId, $key, $title, $userId) {
        $stmt = $this->pdo->prepare("INSERT INTO project_message_channels(project_id, key_slug, title, created_by) VALUES(?,?,?,?)");
        $stmt->execute([$projectId, $key, $title, $userId]);
        return [ 'id' => $this->pdo->lastInsertId(), 'key_slug'=>$key, 'title'=>$title ];
    }
}
