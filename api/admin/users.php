<?php
// Admin Users Management API
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';

if (!headers_sent()) { header('Content-Type: application/json'); }
requireRole('admin');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? $_POST['action'] ?? null;

function json_input() {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $j = json_decode($raw, true);
    return is_array($j) ? $j : [];
}

try {
    $db = Database::getConnection();

    if ($method === 'GET' && $action === 'list') {
        $role = isset($_GET['role']) && $_GET['role'] !== '' ? strtolower(trim($_GET['role'])) : null;
        $approved = isset($_GET['approved']) ? $_GET['approved'] : null; // '1','0','all'
        $params = [];
        $where = [];
        if ($role) { $where[] = 'LOWER(role) = ?'; $params[] = $role; }
        if ($approved !== null && $approved !== '' && $approved !== 'all') { $where[] = 'approved = ?'; $params[] = (int)$approved; }
        $sql = 'SELECT id, name, email, role, rank, approved, created_at FROM users';
        if (!empty($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
        $sql .= ' ORDER BY name ASC';
        $st = $db->prepare($sql);
        $st->execute($params);
        echo json_encode(['success'=>true,'users'=>$st->fetchAll() ?: []]);
        exit;
    }

    if ($method === 'POST' && $action === 'approve') {
        $p = json_input(); if (empty($p)) { $p = $_POST; }
        $id = (int)($p['id'] ?? 0);
        if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'id is required']); exit; }
        $st = $db->prepare('UPDATE users SET approved = 1 WHERE id = ?');
        $ok = $st->execute([$id]);
        echo json_encode(['success'=>(bool)$ok]);
        exit;
    }

    if ($method === 'POST' && ($action === 'toggle_login' || $action === 'ban' || $action === 'unban')) {
        $p = json_input(); if (empty($p)) { $p = $_POST; }
        $id = (int)($p['id'] ?? 0);
        $enabled = isset($p['enabled']) ? (bool)$p['enabled'] : ($action === 'unban');
        if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'id is required']); exit; }
        $st = $db->prepare('UPDATE users SET approved = ? WHERE id = ?');
        $ok = $st->execute([$enabled ? 1 : 0, $id]);
        echo json_encode(['success'=>(bool)$ok]);
        exit;
    }

    if ($method === 'POST' && $action === 'delete') {
        $p = json_input(); if (empty($p)) { $p = $_POST; }
        $id = (int)($p['id'] ?? 0);
        if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'id is required']); exit; }
        // Prevent self-delete for safety
        if ($id === (int)getCurrentUserId()) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Cannot delete own account']); exit; }
        $st = $db->prepare('DELETE FROM users WHERE id = ?');
        $ok = $st->execute([$id]);
        echo json_encode(['success'=>(bool)$ok]);
        exit;
    }

    if ($method === 'POST' && $action === 'set_role') {
        $p = json_input(); if (empty($p)) { $p = $_POST; }
        $id = (int)($p['id'] ?? 0); $role = isset($p['role']) ? trim((string)$p['role']) : '';
        if ($id <= 0 || $role === '') { http_response_code(400); echo json_encode(['success'=>false,'message'=>'id and role are required']); exit; }
        $st = $db->prepare('UPDATE users SET role = ? WHERE id = ?');
        $ok = $st->execute([$role, $id]);
        echo json_encode(['success'=>(bool)$ok]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Unknown action']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
