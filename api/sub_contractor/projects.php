<?php
// Subcontractor Projects API (scoped read-only)
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/controllers/SubContractorController.php';

if (!headers_sent()) { header('Content-Type: application/json'); }
requireSubContractor();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ctl = new SubContractorController();

try {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        exit;
    }

    // Support /api/sub_contractor/projects/{id} and ?project_id=ID
    $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
    if (!$projectId) {
        $path = $_SERVER['REQUEST_URI'] ?? '';
        $clean = strtok($path,'?');
        $segments = array_values(array_filter(explode('/', $clean)));
        $last = end($segments);
        if (ctype_digit($last)) { $projectId = (int)$last; }
    }

    if ($projectId) {
        $snap = $ctl->projectSnapshot($projectId);
        if (!$snap['success']) { echo json_encode($snap); exit; }
        // Enrich with tasks & materials (best-effort; ignore errors gracefully)
        $data = $snap['data'];
        try {
            $pdo = Database::getConnection();
            // Tasks (limited to 200) with basic fields
            $stmtT = $pdo->prepare('SELECT id, title, status, due_date, assigned_to FROM tasks WHERE project_id = ? ORDER BY due_date IS NULL, due_date ASC, id DESC LIMIT 200');
            $stmtT->execute([$projectId]);
            $data['tasks'] = $stmtT->fetchAll() ?: [];
        } catch (Throwable $e) { $data['tasks'] = []; }
        try {
            $pdo = Database::getConnection();
            $stmtM = $pdo->prepare('SELECT id, material_name, quantity, status, created_at FROM materials WHERE project_id = ? ORDER BY id DESC LIMIT 200');
            $stmtM->execute([$projectId]);
            $data['materials'] = $stmtM->fetchAll() ?: [];
        } catch (Throwable $e) { $data['materials'] = []; }
        echo json_encode(['success'=>true,'data'=>$data]);
        exit;
    }

    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    echo json_encode($ctl->projectsAssigned($limit));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
