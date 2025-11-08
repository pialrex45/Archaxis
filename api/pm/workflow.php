<?php
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/models/ProjectActivity.php';

header('Content-Type: application/json');
requireAuth();
if (!hasAnyRole(['project_manager','admin'])) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Forbidden']); exit; }

$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
if ($projectId <= 0) { echo json_encode(['success'=>false,'message'=>'project_id required']); exit; }

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

try {
    $pa = new ProjectActivity();
    $rows = $pa->listByProject($projectId,$limit,$offset);
    echo json_encode(['success'=>true,'data'=>$rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Error fetching activity']);
}
