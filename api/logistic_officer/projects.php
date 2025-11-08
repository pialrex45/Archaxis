<?php
// API: /api/logistic_officer/projects.php
// GET: list projects (id, name) with optional search

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/app/core/auth.php';
require_once dirname(__DIR__, 2) . '/app/core/helpers.php';

requireLogisticOfficer();

$pdo = Database::getConnection();
header('Content-Type: application/json');

try {
    $q = isset($_GET['q']) ? '%'.trim((string)$_GET['q']).'%' : null;
    $limit = min(max((int)($_GET['limit'] ?? 500), 1), 1000);
    $sql = 'SELECT id, name FROM projects';
    if ($q) { $sql .= ' WHERE name LIKE :q'; }
    $sql .= ' ORDER BY id DESC LIMIT :limit';
    $stmt = $pdo->prepare($sql);
    if ($q) { $stmt->bindValue(':q', $q, PDO::PARAM_STR); }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
