<?php
// Shared: list projects (id, name) for dropdowns
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
    // Schema-agnostic for project display name
    $sql = "SELECT id, COALESCE(name, project_name, code, CONCAT('Project #', id)) AS name
            FROM projects
            ORDER BY name IS NULL, name, id";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load projects']);
}
