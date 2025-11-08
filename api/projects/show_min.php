<?php
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/models/Project.php';
require_once __DIR__ . '/../../app/core/policies/MessagingPolicy.php';

header('Content-Type: application/json');
requireAuth();

try {
    $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
    if ($projectId <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'project_id required']); exit; }

    $uid = getCurrentUserId();
    $policy = new MessagingPolicy();
    if (!$policy->canPostProject($uid, $projectId)) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Forbidden']); exit; }

    $p = new Project();
    $row = $p->getById($projectId);
    if (!$row) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'Not found']); exit; }
    $data = [
        'id' => (int)$row['id'],
        'name' => $row['name'] ?? '',
        'status' => $row['status'] ?? '',
        'owner_id' => isset($row['owner_id']) ? (int)$row['owner_id'] : null,
        'owner_name' => $row['owner_name'] ?? null,
        'start_date' => $row['start_date'] ?? null,
        'end_date' => $row['end_date'] ?? null,
    ];
    echo json_encode(['success'=>true,'data'=>$data]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Error']);
}
