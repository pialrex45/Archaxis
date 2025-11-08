<?php
// Site Manager Projects API (overview)
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/controllers/SiteManagerController.php';

if (!headers_sent()) { header('Content-Type: application/json'); }
requireSiteManager();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ctl = new SiteManagerController();

try {
    if ($method === 'GET') {
        $projectId = $_GET['project_id'] ?? null;
        echo json_encode($ctl->projectOverview($projectId));
    } else {
        http_response_code(405);
        echo json_encode(['success'=>false,'message'=>'Method not allowed']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
