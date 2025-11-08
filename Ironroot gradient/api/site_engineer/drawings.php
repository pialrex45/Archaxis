<?php
// Site Engineer Drawings API (read-only)
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/controllers/SiteEngineerController.php';

if (!headers_sent()) { header('Content-Type: application/json'); }
requireSiteEngineer();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ctl = new SiteEngineerController();

try {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        exit;
    }

    $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
    if (!$projectId) {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>'project_id is required']);
        exit;
    }
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    echo json_encode($ctl->drawingsByProject($projectId, $limit));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
