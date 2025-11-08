<?php
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/controllers/ClientController.php';

if (!headers_sent()) { header('Content-Type: application/json'); }

try {
    requireAuth();
    if (!hasRole('client')) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Access denied']); exit; }
    $ctl = new ClientController();
    $res = $ctl->listReports();
    echo json_encode($res);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
