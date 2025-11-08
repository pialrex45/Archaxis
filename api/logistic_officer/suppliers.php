<?php
// API: /api/logistic_officer/suppliers.php
// GET: list suppliers (basic fields, optional search)
// POST action=create: create a new supplier (enroll company)

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/app/core/auth.php';
require_once dirname(__DIR__, 2) . '/app/core/helpers.php';

requireLogisticOfficer();

$pdo = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

try {
    if ($method === 'GET') {
        $q = isset($_GET['q']) ? '%'.trim((string)$_GET['q']).'%' : null;
        $limit = min(max((int)($_GET['limit'] ?? 100), 1), 500);
        $sql = 'SELECT id, name, email, phone, address, rating, created_at FROM suppliers';
        if ($q) { $sql .= ' WHERE name LIKE :q'; }
        $sql .= ' ORDER BY id DESC LIMIT :limit';
        $stmt = $pdo->prepare($sql);
        if ($q) { $stmt->bindValue(':q', $q, PDO::PARAM_STR); }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
        exit;
    }

    if ($method === 'POST' && $action === 'create') {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $name = trim((string)($body['name'] ?? ''));
        $email = trim((string)($body['email'] ?? '')) ?: null;
        $phone = trim((string)($body['phone'] ?? '')) ?: null;
        $address = trim((string)($body['address'] ?? '')) ?: null;
        $rating = isset($body['rating']) ? (float)$body['rating'] : null;
        if ($name === '') { http_response_code(422); echo json_encode(['success'=>false,'message'=>'Supplier name is required']); exit; }

        $stmt = $pdo->prepare('INSERT INTO suppliers (name, email, phone, address, rating) VALUES (?,?,?,?,?)');
        $ok = $stmt->execute([$name, $email, $phone, $address, $rating]);
        echo json_encode(['success'=>(bool)$ok,'message'=>$ok?'Supplier created':'Create failed','id'=>$ok?(int)$pdo->lastInsertId():null]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method/Action not supported']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
