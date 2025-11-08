<?php
// Subcontractor Products API (read-only)
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';

if (!headers_sent()) { header('Content-Type: application/json'); }
requireSubContractor();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
try {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        exit;
    }
    $limit = isset($_GET['limit']) ? max(1, min(200, (int)$_GET['limit'])) : 50;
    $db = Database::getConnection();
    $stmt = $db->query("SELECT id, name, unit, unit_price, supplier_id, status FROM products ORDER BY id DESC LIMIT {$limit}");
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll() ?: []]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
