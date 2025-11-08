<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';

// Check authentication and role
requireAuth();
if (!hasAnyRole(['admin', 'manager'])) {
    http_response_code(403);
    die('Access denied. Admins and managers only.');
}

$pageTitle = 'Reports';
$currentPage = 'reports';
?>

<?php include_once __DIR__ . '/../layouts/header.php'; ?>
<?php include_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Reports</h1>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Reports Dashboard</h5>
                </div>
                <div class="card-body">
                    <p>Reporting features would be implemented here.</p>
                    <p>In a full implementation, this would include:</p>
                    <ul>
                        <li>Project progress reports</li>
                        <li>Financial summary reports</li>
                        <li>Task completion analytics</li>
                        <li>Material usage reports</li>
                        <li>Attendance summaries</li>
                        <li>Performance metrics</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>