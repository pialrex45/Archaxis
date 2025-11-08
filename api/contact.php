<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/helpers.php';

if (!headers_sent()) header('Content-Type: application/json');

try {
    // Basic CSRF check (best-effort)
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>'Invalid CSRF token']);
        exit;
    }

    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));

    if ($name === '' || $email === '' || $message === '') {
        http_response_code(422);
        echo json_encode(['success'=>false,'message'=>'All fields are required.']);
        exit;
    }

    // Optionally persist to database or send email; for now log to file
    $logDir = __DIR__ . '/../data';
    if (!is_dir($logDir)) @mkdir($logDir, 0775, true);
    $entry = date('c') . "\t" . $name . "\t" . $email . "\t" . str_replace(["\r","\n"], ' ', $message) . "\n";
    file_put_contents($logDir . '/contact_submissions.log', $entry, FILE_APPEND);

    echo json_encode(['success'=>true,'message'=>'Thanks! We9ll reach out shortly.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
