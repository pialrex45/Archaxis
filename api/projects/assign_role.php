<?php
// Generic role assignment endpoint for project chain
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

try {
    requireAuth();
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true) ?: $_POST;

    $projectId = (int)($payload['project_id'] ?? 0);
    $targetUserId = (int)($payload['user_id'] ?? 0);
    $targetRole = trim(strtolower($payload['role'] ?? ''));

    if ($projectId <= 0 || $targetUserId <= 0 || $targetRole === '') {
        http_response_code(422);
        echo json_encode(['success'=>false,'message'=>'project_id, user_id and role are required']);
        exit;
    }

    $allowedTargetRoles = ['site_manager','sub_contractor','supervisor','worker'];
    if (!in_array($targetRole, $allowedTargetRoles, true)) {
        http_response_code(422);
        echo json_encode(['success'=>false,'message'=>'Unsupported target role']);
        exit;
    }

    $currentUserId = getCurrentUserId();
    $currentRole = $_SESSION['role'] ?? '';

    // Chain validation map: who can assign which next roles
    $chain = [
        'project_manager' => ['site_manager'],
        'site_manager'    => ['sub_contractor','supervisor'],
        'sub_contractor'  => ['supervisor','worker'],
        'supervisor'      => ['worker'],
    ];

    if (!isset($chain[$currentRole]) || !in_array($targetRole, $chain[$currentRole], true)) {
        http_response_code(403);
        echo json_encode(['success'=>false,'message'=>'You are not allowed to assign this role']);
        exit;
    }

    $db = Database::getConnection();

    // Ensure project exists
    $stmt = $db->prepare('SELECT id, owner_id FROM projects WHERE id = :id');
    $stmt->execute([':id'=>$projectId]);
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success'=>false,'message'=>'Project not found']);
        exit;
    }

    // Permission: ensure assigner is linked to project (owner, PM all projects, or already assigned)
    $isAllowed = false;
    if ($currentRole === 'project_manager') {
        $isAllowed = true; // PM sees all
    } else {
        // Check existing assignment
        try {
            $chk = $db->prepare('SELECT 1 FROM project_assignments WHERE project_id = :p AND user_id = :u LIMIT 1');
            $chk->execute([':p'=>$projectId, ':u'=>$currentUserId]);
            if ($chk->rowCount() > 0) $isAllowed = true;
        } catch (Throwable $e) { /* if table missing will fail silently */ }
    }
    if (!$isAllowed) {
        http_response_code(403);
        echo json_encode(['success'=>false,'message'=>'You are not linked to this project']);
        exit;
    }

    // Confirm target user role matches request (so we do not assign mismatched chain role)
    $stmtUser = $db->prepare('SELECT id, role, name FROM users WHERE id = :uid');
    $stmtUser->execute([':uid'=>$targetUserId]);
    $userRow = $stmtUser->fetch();
    if (!$userRow) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'Target user not found']); exit; }
    if ($userRow['role'] !== $targetRole) {
        http_response_code(422);
        echo json_encode(['success'=>false,'message'=>'User role does not match requested assignment role']);
        exit;
    }

    // Create assignments table if missing (non-destructive safety)
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS project_assignments (\n            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,\n            project_id INT UNSIGNED NOT NULL,\n            user_id INT UNSIGNED NOT NULL,\n            role VARCHAR(50) NOT NULL,\n            assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,\n            UNIQUE KEY uniq_project_user_role (project_id, user_id, role),\n            KEY idx_project (project_id),\n            KEY idx_user (user_id),\n            KEY idx_role (role)\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) { /* ignore */ }

    // Upsert assignment
    $ok = false; $err = null;
    try {
        $sql = "INSERT INTO project_assignments (project_id,user_id,role,assigned_at) VALUES(:p,:u,:r,NOW())\n                ON DUPLICATE KEY UPDATE role=VALUES(role), assigned_at=NOW()";
        $ins = $db->prepare($sql);
        $ok = $ins->execute([':p'=>$projectId, ':u'=>$targetUserId, ':r'=>$targetRole]);
    } catch (PDOException $e) { $err = $e->getMessage(); }

    if (!$ok) {
        http_response_code(500);
        echo json_encode(['success'=>false,'message'=>'Assignment failed','error'=>$err]);
        exit;
    }

    echo json_encode([
        'success'=>true,
        'message'=>'Assigned successfully',
        'data'=>[
            'project_id'=>$projectId,
            'user_id'=>$targetUserId,
            'user_name'=>$userRow['name'],
            'role'=>$targetRole
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
