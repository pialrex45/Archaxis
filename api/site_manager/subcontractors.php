<?php
// List approved subcontractors for Site Managers

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';

header('Content-Type: application/json');

try {
    requireAuth();
    if (!(hasRole('site_manager') || hasRole('admin') || hasRole('manager'))) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }

    $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
    $db = Database::getConnection();
    $uid = getCurrentUserId();

    $projectScoped = false;
    if ($projectId > 0 && hasRole('site_manager')) {
        $allowed = false;
        try {
            $stmt = $db->prepare('SELECT site_manager_id FROM projects WHERE id = :pid');
            $stmt->execute([':pid'=>$projectId]);
            $sm = $stmt->fetchColumn();
            if ($sm !== false && (int)$sm === (int)$uid) { $allowed = true; }
            if (!$allowed) {
                try {
                    $stmt = $db->prepare('SELECT 1 FROM project_assignments WHERE project_id = :pid AND user_id = :uid LIMIT 1');
                    $stmt->execute([':pid'=>$projectId, ':uid'=>$uid]);
                    $allowed = $stmt->fetchColumn() ? true : false;
                } catch (Throwable $e2) { /* table missing: ignore */ }
            }
        } catch (Throwable $e3) { $allowed = false; }
        if ($allowed) { $projectScoped = true; }
        // If not allowed, silently fallback to global list instead of blocking
    }

        // Normalize roles in query: remove spaces, hyphens, underscores for comparison
        // Keep approved = 1 requirement per user instruction
        $sql = "SELECT id, name, email, role FROM users 
                        WHERE approved = 1 
                            AND (
                                REPLACE(REPLACE(REPLACE(LOWER(TRIM(role)),'-',''),'_',''),' ','') = 'subcontractor'
                            )";
        $params = [];

    // If project-specific list desired and assignment records exist, prefer those specific subcontractors
    if ($projectId > 0 && $projectScoped) {
        try {
            $stmt = $db->prepare("SELECT u.id, u.name, u.email, u.role 
                                   FROM project_assignments pa 
                                   JOIN users u ON u.id = pa.user_id 
                                   WHERE pa.project_id = :pid 
                                     AND REPLACE(REPLACE(REPLACE(LOWER(TRIM(COALESCE(pa.role,u.role)) ),'-',''),'_',''),' ','') = 'subcontractor' ");
            $stmt->execute([':pid'=>$projectId]);
            $assigned = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            if (!empty($assigned)) {
                echo json_encode(['success'=>true,'project_id'=>$projectId,'data'=>$assigned,'scope'=>'project']);
                exit;
            }
        } catch (Throwable $ignore) { /* fall back to global list */ }
    }

    $stmt = $db->query($sql . ' ORDER BY name ASC, id ASC');
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    $reason = null;
    $approvedCount = null; $potentialCount = null;
    if (empty($rows)) {
        // Provide diagnostics: count approved subcontractors and total potential roles ignoring approved flag
        try {
            $countSql = "SELECT COUNT(*) FROM users WHERE approved = 1 AND REPLACE(REPLACE(REPLACE(LOWER(TRIM(role)),'-',''),'_',''),' ','')='subcontractor'";
            $approvedCount = (int)$db->query($countSql)->fetchColumn();
        } catch (Throwable $c1) { $approvedCount = 0; }
        try {
            $potSql = "SELECT COUNT(*) FROM users WHERE REPLACE(REPLACE(REPLACE(LOWER(TRIM(role)),'-',''),'_',''),' ','')='subcontractor'";
            $potentialCount = (int)$db->query($potSql)->fetchColumn();
        } catch (Throwable $c2) { $potentialCount = 0; }
        $reason = 'none_found';
    }
    echo json_encode([
        'success'=>true,
        'project_id'=>$projectId?:null,
        'data'=>$rows,
        'scope'=>$projectId>0?'global_fallback':'global',
        'debug'=>[
            'reason'=>$reason,
            'approved_subcontractors'=>$approvedCount,
            'potential_subcontractors'=>$potentialCount
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching subcontractors']);
}
