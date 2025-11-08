<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';

// Check authentication and role
requireAuth();
if (!hasRole('project_manager')) {
    http_response_code(403);
    die('Access denied. Project Managers only.');
}

$pageTitle = 'PM • Tasks Test';
$currentPage = 'pm_tasks';
include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Task Management</h1>
            <p class="text-muted">This is a test page to verify routing</p>
            
            <div class="alert alert-success">
                Navigation is working correctly! This page is accessible.
            </div>
            
            <p>Here are links to other PM pages:</p>
            <ul>
                <li><a href="<?php echo url('/dashboard/project-manager'); ?>">Dashboard</a></li>
                <li><a href="<?php echo url('/pm/projects'); ?>">Projects</a></li>
                <li><a href="<?php echo url('/pm/material-requests'); ?>">Material Requests</a></li>
                <li><a href="<?php echo url('/pm/purchase-orders'); ?>">Purchase Orders</a></li>
            </ul>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
