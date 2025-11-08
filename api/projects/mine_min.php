<?php
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/models/Project.php';
require_once __DIR__ . '/../../app/core/policies/MessagingPolicy.php';

header('Content-Type: application/json');
requireAuth();
try {
    $uid = getCurrentUserId();
    $policy = new MessagingPolicy();
    $projects = $policy->projectsForUser($uid);
    $list = [];
    foreach ($projects as $p) { $list[] = ['id'=>(int)$p['id'], 'name'=>$p['name']]; }
    echo json_encode(['success'=>true,'data'=>$list]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Error']);
}
