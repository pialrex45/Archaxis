<?php
require_once __DIR__ . '/../../app/models/PurchaseOrder.php';
require_once __DIR__ . '/../../app/core/auth.php';

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$poModel = new PurchaseOrder();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing id']);
    exit;
}

$order = $poModel->getById($id);
if (!$order) {
    http_response_code(404);
    echo json_encode(['error' => 'Purchase order not found']);
    exit;
}

$items = $poModel->getItems($id);
if ($items === false) {
    $items = [];
}

echo json_encode(['data' => ['order' => $order, 'items' => $items]]);
