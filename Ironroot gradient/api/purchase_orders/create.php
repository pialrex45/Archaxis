<?php
require_once __DIR__ . '/../../app/models/PurchaseOrder.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Read JSON body, fallback to form
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $input = $_POST;
}

// For form submissions, validate CSRF
if (!empty($_POST)) {
    if (!isset($input['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $input['csrf_token'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

// Extract and validate
$projectId  = isset($input['project_id']) ? (int)$input['project_id'] : 0;
$supplierId = isset($input['supplier_id']) ? (int)$input['supplier_id'] : 0;
$items      = isset($input['items']) && is_array($input['items']) ? $input['items'] : [];
$createdBy  = $_SESSION['user_id'] ?? 0;

if ($projectId <= 0 || $supplierId <= 0 || $createdBy <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$poModel = new PurchaseOrder();
$result = $poModel->create($projectId, $supplierId, $createdBy, $items);

if ($result['success'] ?? false) {
    http_response_code(201);
} else {
    http_response_code(400);
}

echo json_encode($result);
