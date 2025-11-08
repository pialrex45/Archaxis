<?php
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/controllers/ClientController.php';

if (!headers_sent()) {
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

try {
    requireAuth();
    if (!hasRole('client')) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Access denied']); exit; }
    $ctl = new ClientController();
    $res = $ctl->listProjects();
    // Debug logging (safe: server error_log only)
    if (function_exists('getCurrentUserId')) {
        error_log('[CLIENT PROJECTS API] user=' . getCurrentUserId() . ' count=' . count($res['data'] ?? []));
    }
    echo json_encode($res);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
