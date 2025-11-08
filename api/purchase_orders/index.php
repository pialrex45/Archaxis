<?php
require_once __DIR__ . '/../../app/models/PurchaseOrder.php';
require_once __DIR__ . '/../../app/core/auth.php';

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$poModel = new PurchaseOrder();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $orders = $poModel->getAll();
        if ($orders === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch purchase orders']);
            exit;
        }
        echo json_encode(['data' => $orders]);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
