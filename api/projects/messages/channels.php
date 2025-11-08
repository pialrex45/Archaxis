<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../app/controllers/ProjectMessageController.php';
require_once __DIR__ . '/../../../app/core/auth.php';
require_once __DIR__ . '/../../../app/core/helpers.php';

header('Content-Type: application/json');
if (!isAuthenticated()) { jsonResponse(['success'=>false,'message'=>'User not authenticated'], 401); }

$ctl = new ProjectMessageController();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
    $res = $ctl->channels($projectId);
    http_response_code($res['success'] ? 200 : 400);
    echo json_encode($res);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
    $key = isset($_POST['key']) ? sanitize($_POST['key']) : '';
    $title = isset($_POST['title']) ? sanitize($_POST['title']) : '';
    $res = $ctl->createChannel($projectId, $key, $title);
    http_response_code($res['success'] ? 200 : 400);
    echo json_encode($res);
    exit;
}

jsonResponse(['success'=>false,'message'=>'Invalid request method'], 405);
