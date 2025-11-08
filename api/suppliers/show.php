<?php
// Show supplier by ID
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/models/Supplier.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

requireAuth();
if (!hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admins only']);
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit();
}

$model = new Supplier();
$row = $model->getById($id);
if (!$row) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Supplier not found']);
    exit();
}

echo json_encode(['success' => true, 'data' => $row]);
exit();
