<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../../config/database.php';

class MessagingPolicy {
    private $pdo;
    public function __construct() {
        $db = new Database();
        $this->pdo = $db->connect();
    }

    public function canDM($senderId, $receiverId) {
        if (!$senderId || !$receiverId) return false;
        if ($senderId === $receiverId) return false;
        $senderRole = $this->getUserRole($senderId);
        $receiverRole = $this->getUserRole($receiverId);
        if (!$senderRole || !$receiverRole) return false;
        if ($senderRole === 'admin' || $receiverRole === 'admin') return true;
        // Allowed pairs baseline
        $pairs = [
            ['project_manager','client'],
            ['client','project_manager'],
            ['project_manager','supervisor'],
            ['supervisor','project_manager'],
            ['project_manager','worker'],
            ['worker','project_manager'],
            ['project_manager','site_manager'],
            ['site_manager','project_manager'],
            ['project_manager','site_engineer'],
            ['site_engineer','project_manager'],
            ['supervisor','worker'],
            ['worker','supervisor'],
            ['supervisor','sub_contractor'],
            ['sub_contractor','supervisor'],
            ['client','supervisor'],
            ['supervisor','client'],
        ];
        $ok = false;
        foreach ($pairs as $p) {
            if ($p[0] === $senderRole && $p[1] === $receiverRole) { $ok = true; break; }
        }
        if (!$ok) return false;
        // Must share a project via project_assignments or ownership relations
        return $this->shareProject($senderId, $receiverId);
    }

    public function canPostProject($userId, $projectId) {
        if (!$userId || !$projectId) return false;
        $role = $this->getUserRole($userId);
        // Simplified access: core roles can post in any project
        if (in_array($role, ['admin','project_manager','site_manager','site_engineer'])) return true;
        // Any assignment to the project allows posting
        return $this->isAssignedToProject($userId, $projectId);
    }

    public function projectsForUser($userId) {
        if (!$userId) return [];
        // Projects by ownership or explicit assignment
        $sql1 = "SELECT p.id, p.name FROM projects p
                 LEFT JOIN project_assignments pa ON pa.project_id = p.id AND pa.user_id = :uid
                 WHERE p.owner_id = :uid OR pa.user_id IS NOT NULL";
        // Projects via task assignments
        $sql2 = "SELECT DISTINCT p.id, p.name FROM projects p
                 JOIN tasks t ON t.project_id = p.id
                 WHERE t.assigned_to = :uid";
        $sql = "SELECT id, name FROM ((".$sql1.") UNION (".$sql2.")) AS x GROUP BY id, name ORDER BY id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid'=>$userId]);
        return $stmt->fetchAll() ?: [];
    }

    private function getUserRole($userId) {
        $stmt = $this->pdo->prepare('SELECT role FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ? strtolower($row['role']) : null;
    }

    private function isAssignedToProject($userId, $projectId) {
        // Owner counts as assignment
        $stmt = $this->pdo->prepare("SELECT 1 FROM projects WHERE id=? AND owner_id=?");
        $stmt->execute([$projectId, $userId]);
        if ($stmt->fetch()) return true;
        // project_assignments
        try {
            $stmt = $this->pdo->prepare('SELECT 1 FROM project_assignments WHERE project_id=? AND user_id=? LIMIT 1');
            $stmt->execute([$projectId, $userId]);
            if ($stmt->fetch()) return true;
        } catch (PDOException $e) {}
        // task assignments within project
        try {
            $stmt = $this->pdo->prepare('SELECT 1 FROM tasks WHERE project_id=? AND assigned_to=? LIMIT 1');
            $stmt->execute([$projectId, $userId]);
            if ($stmt->fetch()) return true;
        } catch (PDOException $e) {}
        return false;
    }

    private function shareProject($userA, $userB) {
        // Share owner or assignment to the same project
        $sql = "SELECT 1 FROM projects p
                LEFT JOIN project_assignments pa ON pa.project_id = p.id AND pa.user_id = ?
                LEFT JOIN project_assignments pb ON pb.project_id = p.id AND pb.user_id = ?
                WHERE (p.owner_id = ? OR pa.user_id IS NOT NULL)
                  AND (p.owner_id = ? OR pb.user_id IS NOT NULL)
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userA, $userB, $userA, $userB]);
        return (bool)$stmt->fetch();
    }
}
