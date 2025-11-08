<?php
// Subcontractor Purchase Orders API (scoped read-only)
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/controllers/SubContractorController.php';

if (!headers_sent()) { header('Content-Type: application/json'); }
requireSubContractor();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ctl = new SubContractorController();

try {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        exit;
    }
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    echo json_encode($ctl->purchaseOrdersForAssignedProjects($limit));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
