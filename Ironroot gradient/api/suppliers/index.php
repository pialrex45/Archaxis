<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/models/Supplier.php';
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

$supplierModel = new Supplier();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $suppliers = $supplierModel->getAll();
        if ($suppliers === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch suppliers']);
            exit;
        }
        echo json_encode(['data' => $suppliers]);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
