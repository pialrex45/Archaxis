<?php
// Inspection model for Site Engineer inspections
require_once __DIR__ . '/../../config/database.php';

class Inspection {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function create($taskId, $projectId, $zoneId, $status, $remarks, $createdBy) {
        $stmt = $this->pdo->prepare("INSERT INTO inspections (task_id, project_id, zone_id, status, remarks, created_by, created_at) VALUES (?,?,?,?,?,?,NOW())");
        $ok = $stmt->execute([(int)$taskId, (int)$projectId, $zoneId, $status, $remarks, (int)$createdBy]);
        if (!$ok) return ['success'=>false,'message'=>'Failed to create inspection'];
        return ['success'=>true,'inspection_id'=>$this->pdo->lastInsertId()];
    }

    public function addAttachment($inspectionId, $filePath) {
        $stmt = $this->pdo->prepare("INSERT INTO inspection_attachments (inspection_id, file_path, created_at) VALUES (?,?,NOW())");
        $ok = $stmt->execute([(int)$inspectionId, $filePath]);
        return $ok ? ['success'=>true] : ['success'=>false,'message'=>'Attachment failed'];
    }

    public function listByTask($taskId, $limit = 100) {
        $limit = max(1, min(200, (int)$limit));
        $stmt = $this->pdo->prepare("SELECT * FROM inspections WHERE task_id = ? ORDER BY id DESC LIMIT {$limit}");
        $stmt->execute([(int)$taskId]);
        return $stmt->fetchAll() ?: [];
    }

    public function listByProject($projectId, $limit = 100) {
        $limit = max(1, min(200, (int)$limit));
        $stmt = $this->pdo->prepare("SELECT * FROM inspections WHERE project_id = ? ORDER BY id DESC LIMIT {$limit}");
        $stmt->execute([(int)$projectId]);
        return $stmt->fetchAll() ?: [];
    }
}
