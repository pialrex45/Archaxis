<?php
// User login API endpoint (robust JSON output)

// Force output buffering early to prevent stray whitespace / notices from corrupting JSON
if (!ob_get_level()) { ob_start(); }

// Suppress HTML error display for JSON endpoint while still logging (if configured)
if (function_exists('ini_set')) {
    ini_set('display_errors', '0');
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

try {
    // Include necessary files (inside try so missing include becomes JSON error)
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../app/controllers/AuthController.php';
    require_once __DIR__ . '/../../app/core/helpers.php';
    require_once __DIR__ . '/../../app/core/auth.php'; // ensure session is started for CSRF token

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        if (ob_get_length()) { ob_clean(); }
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    // Prefer JSON body, fallback to form fields
    $raw = file_get_contents('php://input');
    $input = $raw ? json_decode($raw, true) : null;
    if (!is_array($input)) { $input = $_POST; }

    // CSRF validation (flexible)
    $postedToken = $input['csrf_token'] ?? null;
    if (!validateCSRFTokenFlexible($postedToken)) {
        $newToken = generateCSRFToken();
        http_response_code(400);
        if (ob_get_length()) { ob_clean(); }
        echo json_encode([
            'success' => false,
            'message' => 'Invalid CSRF token',
            'csrf_token' => $newToken,
            'hint' => 'Session may have reset. Use returned csrf_token and retry.'
        ]);
        exit;
    }

    $authController = new AuthController();
    $result = $authController->login($input);

    http_response_code($result['success'] ? 200 : 401);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    // Last-resort JSON error (avoid exposing sensitive details in production)
    $env = function_exists('env') ? env('APP_ENV','production') : 'production';
    $isDev = $env !== 'production';
    http_response_code(500);

    // Attempt to log the error to storage/logs/login_error.log
    try {
        $base = dirname(__DIR__, 2); // /api -> project root
        $logDir = $base . '/storage/logs';
        if (!is_dir($logDir)) { @mkdir($logDir, 0775, true); }
        $logFile = $logDir . '/login_error.log';
        $line = date('c') . ' | ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine();
        $tracePreview = substr(str_replace(["\n","\r"],' ', $e->getTraceAsString()),0,500);
        @file_put_contents($logFile, $line . ' | trace: ' . $tracePreview . PHP_EOL, FILE_APPEND);
    } catch (Throwable $ignore) {
        // suppress logging failures
    }

    if (ob_get_length()) { ob_clean(); }
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error during login',
        'error' => $isDev ? $e->getMessage() : null,
        'env' => $isDev ? $env : null,
        'hint' => $isDev ? 'Check storage/logs/login_error.log for details' : null,
        'csrf_token' => function_exists('generateCSRFToken') ? generateCSRFToken() : null
    ]);
    exit;
}