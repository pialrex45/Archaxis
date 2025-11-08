<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../app/controllers/ProjectMessageController.php';
require_once __DIR__ . '/../../../app/core/auth.php';
require_once __DIR__ . '/../../../app/core/helpers.php';

header('Content-Type: application/json');
if (!isAuthenticated()) { jsonResponse(['success'=>false,'message'=>'User not authenticated'], 401); }
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { jsonResponse(['success'=>false,'message'=>'Invalid request method'], 405); }

$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$channel = isset($_GET['channel']) ? trim($_GET['channel']) : null;
$before = isset($_GET['before']) ? $_GET['before'] : null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

$ctl = new ProjectMessageController();
$res = $ctl->list($projectId, $channel, $before, $limit);
http_response_code($res['success'] ? 200 : 400);
echo json_encode($res);
