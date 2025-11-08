<?php
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/models/Project.php';

if (!headers_sent()) header('Content-Type: application/json');
requireSiteManager();

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
$uid = getCurrentUserId();

try {
    $pdo = Database::getConnection();
    $model = new Project();
    $projects = method_exists($model,'getBySiteManager') ? $model->getBySiteManager($uid) : [];

    // Absolute minimal fallback if model method returned empty: direct queries
    if (empty($projects)) {
        $rows = [];
        // Column path
        try {
            $stmt = $pdo->prepare("SELECT id, name, status, start_date, end_date FROM projects WHERE site_manager_id = :uid ORDER BY id DESC");
            $stmt->execute([':uid'=>$uid]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) { /* ignore */ }
        // Assignments path (only if still empty)
        if (empty($rows)) {
            try {
                $stmt = $pdo->prepare("SELECT p.id, p.name, p.status, p.start_date, p.end_date FROM projects p JOIN project_assignments pa ON pa.project_id = p.id WHERE pa.user_id = :uid ORDER BY p.id DESC");
                $stmt->execute([':uid'=>$uid]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e2) { /* ignore */ }
        }
        $projects = $rows;
    }

    echo json_encode(['success'=>true,'count'=>count($projects),'projects'=>$projects]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
