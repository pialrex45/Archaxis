<?php
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/models/Project.php';

header('Content-Type: application/json');
requireAuth();
if (!hasAnyRole(['project_manager','admin','manager'])) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Forbidden']); exit; }
try {
    $p = new Project();
    $all = $p->getAll();
    $list = [];
    if ($all) {
        foreach ($all as $row) {
            $list[] = [ 'id'=>(int)$row['id'], 'name'=>$row['name'] ];
        }
    }
    echo json_encode(['success'=>true,'data'=>$list]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Error']);
}
