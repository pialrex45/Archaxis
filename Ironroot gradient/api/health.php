<?php
// Simple health check for app and DB connectivity
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$checks = [
  'app' => true,
  'db'  => false,
];

try {
    $pdo = Database::getConnection();
    // trivial query to verify connection
    $stmt = $pdo->query('SELECT 1');
    $checks['db'] = $stmt !== false;
    http_response_code(200);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'checks' => $checks, 'error' => $e->getMessage()]);
    exit;
}

echo json_encode(['ok' => $checks['app'] && $checks['db'], 'checks' => $checks]);
