<?php
// Shared: list users (id, label) for dropdowns
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

enforceActiveSession((int) (env('SESSION_IDLE_TIMEOUT', 3600)));

// RBAC: allow admin, project_manager, site_manager
if (!hasAnyRole(['admin','project_manager','site_manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

try {
    $pdo = Database::getConnection();
    // Be schema-agnostic for label
    $sql = "SELECT id,
        COALESCE(name, full_name, CONCAT(first_name, ' ', last_name), username, email) AS label
        FROM users
        WHERE id IS NOT NULL
        ORDER BY label IS NULL, label, id";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load users']);
}
