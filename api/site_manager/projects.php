<?php
// Site Manager Projects API (overview)
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/controllers/SiteManagerController.php';
require_once __DIR__ . '/../../app/models/Project.php';

if (!headers_sent()) { header('Content-Type: application/json'); }
requireSiteManager();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ctl = new SiteManagerController();

try {
    if ($method === 'GET') {
        $projectId = $_GET['project_id'] ?? null;
        // If no projectId: use direct model method for robustness
        if ($projectId === null || $projectId === '') {
            $uid = getCurrentUserId();
            $role = getCurrentUserRole();
            $projModel = new Project();
            $list = method_exists($projModel,'getBySiteManager') ? $projModel->getBySiteManager($uid) : [];
            $response = [
                'success'=>true,
                'projects'=>$list,
                'count'=>count($list)
            ];
            if (isset($_GET['debug']) && $_GET['debug']=='1') {
                $response['debug'] = [
                    'user_id'=>$uid,
                    'role'=>$role,
                    'site_manager_id_column_used'=>!empty($list) && array_key_exists('site_manager_id',$list[0]),
                ];
            }
            echo json_encode($response);
        } else {
            echo json_encode($ctl->projectOverview($projectId));
        }
    } else {
        http_response_code(405);
        echo json_encode(['success'=>false,'message'=>'Method not allowed']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
