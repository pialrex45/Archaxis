<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/models/Product.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';

if (!headers_sent()) {
    header('Content-Type: application/json');
}

// Optional: restrict to authenticated users
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$productModel = new Product();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $supplierId = isset($_GET['supplier_id']) ? (int) $_GET['supplier_id'] : null;
        if ($supplierId) {
            $products = $productModel->getBySupplier($supplierId);
        } else {
            $products = $productModel->getAll();
        }
        if ($products === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch products']);
            exit;
        }
        echo json_encode(['data' => $products]);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
