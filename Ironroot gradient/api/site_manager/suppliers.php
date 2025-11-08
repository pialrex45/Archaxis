<?php
// Site Manager Suppliers API (read-only)
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/models/Supplier.php';

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

    if (class_exists('Supplier')) {
        // Use model if available
        $model = new Supplier();
        $rows = $model->getAll() ?: [];
        if ($limit) { $rows = array_slice($rows, 0, $limit); }
        echo json_encode(['success'=>true,'data'=>$rows]);
        exit;
    }

    // Direct query fallback
    $stmt = $db->query("SELECT id, name, email, phone, address, rating FROM suppliers ORDER BY created_at DESC LIMIT {$limit}");
    $rows = $stmt->fetchAll() ?: [];
    echo json_encode(['success'=>true,'data'=>$rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
