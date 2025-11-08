<?php
// List approved subcontractors for Site Managers

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';

header('Content-Type: application/json');

try {
    requireAuth();
    // Allow site_manager, admin, manager to fetch
    if (!(hasRole('site_manager') || hasRole('admin') || hasRole('manager'))) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }

    $db = Database::getConnection();
    // Support common role variants
    $sql = "
        SELECT id, name, email, role
        FROM users
        WHERE approved = 1 AND (
            LOWER(role) = 'sub_contractor' OR
            LOWER(role) = 'subcontractor' OR
            LOWER(role) = 'sub contractor'
        )
        ORDER BY name ASC, id ASC
    ";
    $stmt = $db->query($sql);
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching subcontractors']);
}
