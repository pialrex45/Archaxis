<?php
// Site Engineer Messages API (send only)
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/controllers/SiteEngineerController.php';

if (!headers_sent()) { header('Content-Type: application/json'); }
requireSiteEngineer();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ctl = new SiteEngineerController();

try {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $receiverId = isset($input['receiver_id']) ? (int)$input['receiver_id'] : 0;
    $subject = isset($input['subject']) ? trim((string)$input['subject']) : '';
    $body = isset($input['body']) ? (string)$input['body'] : '';
    if (!$receiverId || !$body) {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>'receiver_id and body are required']);
        exit;
    }

    echo json_encode($ctl->sendMessage($receiverId, $subject, $body));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
