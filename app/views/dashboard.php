<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/helpers.php';

// Check authentication
requireAuth();

// Get user role
$userRole = getCurrentUserRole();

// Redirect to role-specific dashboard
switch ($userRole) {
    case 'admin':
        include __DIR__ . '/dashboards/admin.php';
        break;
    case 'client':
        include __DIR__ . '/dashboards/client.php';
        break;
    case 'project_manager':
        include __DIR__ . '/dashboards/project_manager.php';
        break;
    case 'site_manager':
        include __DIR__ . '/dashboards/site_manager.php';
        break;
    case 'site_engineer':
        include __DIR__ . '/dashboards/site_engineer.php';
        break;
    case 'logistic_officer':
        include __DIR__ . '/dashboards/logistic_officer.php';
        break;
    case 'supervisor':
        include __DIR__ . '/dashboards/supervisor.php';
        break;
    case 'sub_contractor':
        include __DIR__ . '/dashboards/sub_contractor.php';
        break;
    case 'finance_officer':
        include __DIR__ . '/dashboards/finance_officer.php';
        break;
    default:
        // If no specific role, show a generic dashboard
        $pageTitle = 'Dashboard';
        include __DIR__ . '/layouts/header.php';
        ?>
        <div class="row">
            <div class="col-md-12">
                <h1 class="mb-4">Dashboard</h1>
                <div class="alert alert-info">
                    <h4>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!</h4>
                    <p>Your role: <?php echo htmlspecialchars($userRole ?? 'Unknown'); ?></p>
                </div>
                <!-- Fallback popup for unmapped roles -->
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#roleHelpModal">
                  View Role Access Info
                </button>
            </div>
        </div>

        <!-- Role Help Modal (non-breaking UI-only) -->
        <div class="modal fade" id="roleHelpModal" tabindex="-1" aria-labelledby="roleHelpModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="roleHelpModalLabel">Role Access Overview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
<pre class="mb-0">Admin
└── Client
    └── Project Manager
        ├── Site Manager
        │   ├── Supervisor
        │   │   └── Worker
        │   ├── Site Engineer
        │   └── Logistic Officer
        └── General Manager
            └── Sub-Contractor
</pre>
                <p class="mt-3">If you believe your dashboard is missing, please contact an administrator.</p>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
        <?php include_once __DIR__ . '/components/messaging_nav.php'; ?>
        <?php
        include __DIR__ . '/layouts/footer.php';
        break;
}
?>