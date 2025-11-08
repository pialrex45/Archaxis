<?php
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';

if (!headers_sent()) { header('Content-Type: application/json'); }

try {
    // Do not redirect for AJAX, just return empty list if not permitted
    if (!isAuthenticated() || !hasAnyRole(['admin','sub_contractor','project_manager','site_manager','supervisor'])) {
        echo json_encode(['success'=>true,'data'=>[]]);
        exit;
    }
    $pdo = Database::getConnection();
    // Normalize role in SQL to be resilient to case/spacing variants
    $sql = "SELECT id, name, email
            FROM users
            WHERE LOWER(REPLACE(REPLACE(TRIM(role),'-','_'),' ','_')) = 'supervisor'
            ORDER BY name ASC, id ASC
            LIMIT 500";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll() ?: [];
    // Map to id, name friendly
    $data = array_map(function($u){
        return [
            'id' => (int)$u['id'],
            'name' => $u['name'] ?: ($u['email'] ?: ('Supervisor #'.$u['id']))
        ];
    }, $rows);
    echo json_encode(['success'=>true,'data'=>$data]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
