<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';

requireAuth();
if (!hasRole('client')) { http_response_code(403); die('Access denied. Clients only.'); }

$dashboardData = [
  'projects' => [],
  'tasks' => [],
  'materials' => [],
  'purchase_orders' => [],
  'reports' => [],
  'finance' => [
    'total_invoiced' => 0,
    'total_paid' => 0,
    'outstanding' => 0,
  ],
  'messages' => []
];

// Try to load optional controller data if available (non-breaking)
@include_once __DIR__ . '/../../controllers/DashboardController.php';
if (class_exists('DashboardController')) {
  $ctl = new DashboardController();
  if (method_exists($ctl, 'getClientDashboard')) {
    $resp = $ctl->getClientDashboard();
    if (is_array($resp) && !empty($resp['success']) && is_array($resp['data'])) {
      $dashboardData = array_merge($dashboardData, $resp['data']);
    }
  }
}

$pageTitle = 'Client Dashboard';
$currentPage = 'dashboard';

include_once __DIR__ . '/../../views/layouts/header.php';
?>
<div class="row">
  <div class="col-md-12">
    <h1 class="mb-4">Client Dashboard</h1>
    <div class="alert alert-info">Overview of your projects, invoices (read-only), and messages with your Project Manager.</div>
  </div>
</div>

<?php if (!empty($dashboardData['statistics'])): ?>
  <!-- Admin-like Statistics Cards -->
  <div class="row">
    <div class="col-md-4 mb-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Total Projects</h5>
          <p class="card-text display-6 mb-0"><?php echo htmlspecialchars($dashboardData['statistics']['total_projects']); ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Open Tasks</h5>
          <p class="card-text display-6 mb-0"><?php echo htmlspecialchars($dashboardData['statistics']['open_tasks']); ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Outstanding Invoices</h5>
          <p class="card-text display-6 mb-0 text-warning"><?php echo number_format((float)$dashboardData['statistics']['outstanding_invoices'], 2); ?></p>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if (!empty($dashboardData['recent_projects_client']) || !empty($dashboardData['recent_activities'])): ?>
  <div class="row">
    <!-- Recent Projects (client) -->
    <div class="col-md-6 mb-4">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Recent Projects</h5>
        </div>
        <div class="card-body">
          <?php if (!empty($dashboardData['recent_projects_client'])): ?>
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Created</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($dashboardData['recent_projects_client'] as $project): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($project['name']); ?></td>
                      <td>
                        <span class="badge bg-<?php 
                          echo ($project['status'] ?? '') === 'active' ? 'success' :
                               (($project['status'] ?? '') === 'completed' ? 'primary' :
                               (($project['status'] ?? '') === 'on hold' ? 'warning' : 'secondary')); ?>">
                          <?php echo htmlspecialchars($project['status'] ?? 'n/a'); ?>
                        </span>
                      </td>
                      <td><?php echo !empty($project['created_at']) ? date('M j, Y', strtotime($project['created_at'])) : '-'; ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <p class="mb-0">No recent projects found.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Recent Activities -->
    <div class="col-md-6 mb-4">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Recent Activities</h5>
        </div>
        <div class="card-body">
          <?php if (!empty($dashboardData['recent_activities'])): ?>
            <ul class="list-group">
              <?php foreach ($dashboardData['recent_activities'] as $activity): ?>
                <li class="list-group-item">
                  <div class="d-flex justify-content-between">
                    <div>
                      <strong><?php echo htmlspecialchars($activity['description'] ?? ''); ?></strong><br>
                      <small class="text-muted"><?php echo htmlspecialchars(($activity['type'] ?? '') . ' ' . ($activity['action'] ?? '')); ?></small>
                    </div>
                    <small class="text-muted"><?php echo !empty($activity['timestamp']) ? date('M j, Y g:i A', strtotime($activity['timestamp'])) : ''; ?></small>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="mb-0">No recent activities found.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Project Overview -->
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Project Overview</h5>
        <a class="btn btn-sm btn-outline-primary" href="<?php echo url('/projects'); ?>">View All Projects</a>
      </div>
      <div class="card-body p-0">
        <?php if (!empty($dashboardData['projects'])): ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Project</th>
                  <th>Status</th>
                  <th>Deadline</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($dashboardData['projects'] as $p): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($p['name'] ?? ''); ?></td>
                    <td><span class="badge bg-<?php echo ($p['status'] ?? 'secondary') === 'on-track' ? 'success' : (($p['status'] ?? '') === 'at-risk' ? 'warning' : 'secondary'); ?>">
                      <?php echo htmlspecialchars(ucfirst($p['status'] ?? 'n/a')); ?></span></td>
                    <td><?php echo htmlspecialchars($p['deadline'] ?? ''); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="p-3">
            <div class="alert alert-secondary mb-0">No project data available yet.</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <!-- Task Overview -->
  <div class="col-12 col-lg-6">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Task Overview</h5>
        <a class="btn btn-sm btn-outline-primary" href="<?php echo url('/tasks'); ?>">View All Tasks</a>
      </div>
      <div class="card-body p-0">
        <?php if (!empty($dashboardData['tasks'])): ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($dashboardData['tasks'] as $t): ?>
              <li class="list-group-item">
                <div class="d-flex justify-content-between">
                  <strong><?php echo htmlspecialchars($t['name'] ?? ''); ?></strong>
                  <small class="text-muted"><?php echo htmlspecialchars($t['status'] ?? ''); ?></small>
                </div>
                <div class="text-muted small">Due: <?php echo htmlspecialchars($t['due_date'] ?? ''); ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="p-3">
            <div class="alert alert-secondary mb-0">No task data available yet.</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Material Overview -->
  <div class="col-12 col-lg-6">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Material Overview</h5>
        <a class="btn btn-sm btn-outline-primary" href="<?php echo url('/materials'); ?>">View All Materials</a>
      </div>
      <div class="card-body p-0">
        <?php if (!empty($dashboardData['materials'])): ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($dashboardData['materials'] as $m): ?>
              <li class="list-group-item">
                <div class="d-flex justify-content-between">
                  <strong><?php echo htmlspecialchars($m['name'] ?? ''); ?></strong>
                  <small class="text-muted"><?php echo htmlspecialchars($m['quantity'] ?? ''); ?></small>
                </div>
                <div class="text-muted small">Unit Price: <?php echo htmlspecialchars($m['unit_price'] ?? ''); ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="p-3">
            <div class="alert alert-secondary mb-0">No material data available yet.</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Purchase Order Overview -->
  <div class="col-12 col-lg-6">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Purchase Order Overview</h5>
        <a class="btn btn-sm btn-outline-primary" href="<?php echo url('/purchase-orders'); ?>">View All Purchase Orders</a>
      </div>
      <div class="card-body p-0">
        <?php if (!empty($dashboardData['purchase_orders'])): ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($dashboardData['purchase_orders'] as $po): ?>
              <li class="list-group-item">
                <div class="d-flex justify-content-between">
                  <strong><?php echo htmlspecialchars($po['number'] ?? ''); ?></strong>
                  <small class="text-muted"><?php echo htmlspecialchars($po['status'] ?? ''); ?></small>
                </div>
                <div class="text-muted small">Total: <?php echo htmlspecialchars($po['total'] ?? ''); ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="p-3">
            <div class="alert alert-secondary mb-0">No purchase order data available yet.</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Report Overview -->
  <div class="col-12 col-lg-6">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Report Overview</h5>
        <a class="btn btn-sm btn-outline-primary" href="<?php echo url('/reports'); ?>">View All Reports</a>
      </div>
      <div class="card-body p-0">
        <?php if (!empty($dashboardData['reports'])): ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($dashboardData['reports'] as $r): ?>
              <li class="list-group-item">
                <div class="d-flex justify-content-between">
                  <strong><?php echo htmlspecialchars($r['name'] ?? ''); ?></strong>
                  <small class="text-muted"><?php echo htmlspecialchars($r['date'] ?? ''); ?></small>
                </div>
                <div class="text-muted small">Description: <?php echo htmlspecialchars($r['description'] ?? ''); ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="p-3">
            <div class="alert alert-secondary mb-0">No report data available yet.</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Finance Summary (Read-only) -->
  <div class="col-12 col-lg-6">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Invoices / Finance Summary</h5>
        <a class="btn btn-sm btn-outline-primary" href="<?php echo url('/finance'); ?>">Open Finance</a>
      </div>
      <div class="card-body">
        <div class="row text-center">
          <div class="col-4">
            <div class="small-text text-muted">Total Invoiced</div>
            <div class="h4 mb-0"><?php echo number_format((float)$dashboardData['finance']['total_invoiced'], 2); ?></div>
          </div>
          <div class="col-4">
            <div class="small-text text-muted">Paid</div>
            <div class="h4 mb-0 text-success"><?php echo number_format((float)$dashboardData['finance']['total_paid'], 2); ?></div>
          </div>
          <div class="col-4">
            <div class="small-text text-muted">Outstanding</div>
            <div class="h4 mb-0 text-warning"><?php echo number_format((float)$dashboardData['finance']['outstanding'], 2); ?></div>
          </div>
        </div>
        <div class="mt-3 alert alert-info mb-0">Read-only view. Contact your Project Manager for changes.</div>
      </div>
    </div>
  </div>

  <!-- Messages with Project Manager -->
  <div class="col-12 col-lg-6">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Messages with Project Manager</h5>
        <a class="btn btn-sm btn-outline-primary" href="<?php echo url('/messages'); ?>">Open Messages</a>
      </div>
      <div class="card-body p-0">
        <?php if (!empty($dashboardData['messages'])): ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($dashboardData['messages'] as $m): ?>
              <li class="list-group-item">
                <div class="d-flex justify-content-between">
                  <strong><?php echo htmlspecialchars($m['from'] ?? 'PM'); ?></strong>
                  <small class="text-muted"><?php echo htmlspecialchars($m['time'] ?? ''); ?></small>
                </div>
                <div class="text-muted small">Subject: <?php echo htmlspecialchars($m['subject'] ?? ''); ?></div>
                <div><?php echo htmlspecialchars($m['snippet'] ?? ''); ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="p-3">
            <div class="alert alert-secondary mb-0">No recent messages. Click "Open Messages" to view all.</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>
