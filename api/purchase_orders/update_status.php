<?php
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';
require_once __DIR__ . '/../../app/models/PurchaseOrder.php';

header('Content-Type: application/json');

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? (int)$input['id'] : 0;
$newStatus = isset($input['status']) ? strtolower(trim($input['status'])) : '';

if ($id <= 0 || $newStatus === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid id or status']);
    exit;
}

// Role-based authorization per transition
$role = $_SESSION['role'] ?? '';
$transRoleMap = [
    'approved'  => ['admin', 'owner', 'project_manager'],
    'rejected'  => ['admin', 'owner', 'project_manager'],
    'ordered'   => ['admin', 'owner', 'project_manager', 'site_manager', 'supervisor'],
    'delivered' => ['admin', 'owner', 'project_manager', 'site_manager', 'supervisor'],
    'cancelled' => ['admin', 'owner', 'project_manager'],
    'pending'   => ['admin', 'owner', 'project_manager'],
];

$allowedRoles = $transRoleMap[$newStatus] ?? [];
if (!in_array($role, $allowedRoles, true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden for this role']);
    exit;
}

try {
    $model = new PurchaseOrder();

    // Validate transition against current status via model logic
    $result = $model->updateStatus($id, $newStatus);

    if ($result['success']) {
        echo json_encode(['success' => true, 'message' => $result['message']]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
