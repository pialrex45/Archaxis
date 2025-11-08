<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';
require_once __DIR__ . '/../../app/core/policies/MessagingPolicy.php';

header('Content-Type: application/json');
if (!isAuthenticated()) { echo json_encode(['success'=>false,'message'=>'User not authenticated']); http_response_code(401); exit; }

$db = new Database(); $pdo = $db->connect();
$policy = new MessagingPolicy();
$uid = getCurrentUserId();

// Optional: restrict by project context
$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

// Strategy: get candidates via assignments sharing, then filter by canDM
$candidates = [];
try {
    if ($projectId) {
        $stmt = $pdo->prepare("SELECT u.id, u.name, u.role FROM users u
            JOIN project_assignments pa ON pa.user_id = u.id
            WHERE pa.project_id = ? AND u.id <> ?
            GROUP BY u.id, u.name, u.role");
        $stmt->execute([$projectId, $uid]);
    } else {
        $stmt = $pdo->prepare("SELECT DISTINCT u.id, u.name, u.role FROM users u WHERE u.id <> ? LIMIT 500");
        $stmt->execute([$uid]);
    }
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
        if ($policy->canDM($uid, (int)$r['id'])) {
            $candidates[] = [ 'id'=>(int)$r['id'], 'name'=>$r['name'], 'role'=>$r['role'] ];
        }
    }
    echo json_encode(['success'=>true,'data'=>$candidates]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Error']);
}
