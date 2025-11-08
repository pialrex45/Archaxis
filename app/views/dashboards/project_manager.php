<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';

requireAuth();
if (!hasRole('project_manager')) { http_response_code(403); die('Access denied. Project Managers only.'); }

// Enriched PM dashboard: reuse modular controller + view (additive)
require_once __DIR__ . '/../../controllers/ProjectManagerController.php';
$pmCtl = new ProjectManagerController();
$payload = $pmCtl->dashboard();
$pageTitle = 'Project Manager Dashboard';
$currentPage = 'dashboard';

// Include the header first
include_once __DIR__ . '/../../views/layouts/header.php';
?>
<?php
// Then include the dashboard content
include __DIR__ . '/../pm/dashboard.php';
?>

<?php include_once __DIR__ . '/../components/messaging_nav.php'; ?>
