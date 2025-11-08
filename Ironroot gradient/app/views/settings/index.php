<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';

// Check authentication and role
requireAuth();
if (!hasRole('admin')) {
    http_response_code(403);
    die('Access denied. Admins only.');
}

$pageTitle = 'Settings';
$currentPage = 'settings';
?>

<?php include_once __DIR__ . '/../layouts/header.php'; ?>
<?php include_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">System Settings</h1>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Configuration</h5>
                </div>
                <div class="card-body">
                    <p>System settings features would be implemented here.</p>
                    <p>In a full implementation, this would include:</p>
                    <ul>
                        <li>General system settings</li>
                        <li>Security configuration</li>
                        <li>Email settings</li>
                        <li>Notification preferences</li>
                        <li>Backup and maintenance options</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>