<?php
// Site Manager Products API (read-only)
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/models/Product.php';

if (!headers_sent()) { header('Content-Type: application/json'); }
// Allow site_manager, supervisor, or admin (additive, preserves behavior)
requireAnyRole(['site_manager','supervisor','admin']);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        exit;
    }

    $limit = isset($_GET['limit']) ? max(1, min(200, (int)$_GET['limit'])) : 50;
    $db = Database::getConnection();

    // Prefer model if available
    if (class_exists('Product')) {
        // Fallback to direct query since Product model may not have a list method
        $stmt = $db->query("SELECT id, name, unit, unit_price, supplier_id, status FROM products ORDER BY id DESC LIMIT {$limit}");
        $rows = $stmt->fetchAll() ?: [];
        echo json_encode(['success'=>true,'data'=>$rows]);
        exit;
    }

    // Direct query fallback
    $stmt = $db->query("SELECT id, name, unit, unit_price, supplier_id, status FROM products ORDER BY id DESC LIMIT {$limit}");
    $rows = $stmt->fetchAll() ?: [];
    echo json_encode(['success'=>true,'data'=>$rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
