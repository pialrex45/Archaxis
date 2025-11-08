<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/SubContractorController.php';

requireAuth();
if (!hasRole('sub_contractor')) { http_response_code(403); die('Access denied. Sub-Contractors only.'); }

// Get data for dashboard
$controller = new SubContractorController();
$assignedProjects = $controller->projectsAssigned(5);
$assignedTasks = $controller->tasksAssigned(5);
$materials = $controller->materialsList();
$purchaseOrders = $controller->purchaseOrdersForAssignedProjects(5);

// Extract data from responses
$projects = ($assignedProjects['success'] && isset($assignedProjects['data'])) ? $assignedProjects['data'] : [];
$tasks = ($assignedTasks['success'] && isset($assignedTasks['data'])) ? $assignedTasks['data'] : [];
$materials = ($materials['success'] && isset($materials['data'])) ? $materials['data'] : [];
$pos = ($purchaseOrders['success'] && isset($purchaseOrders['data'])) ? $purchaseOrders['data'] : [];

// Calculate KPIs
$totalProjects = count($projects);
$pendingTasks = 0;
$inProgressTasks = 0;
$completedTasks = 0;

foreach ($tasks as $task) {
    if ($task['status'] === 'pending') {
        $pendingTasks++;
    } elseif ($task['status'] === 'in progress') {
        $inProgressTasks++;
    } elseif ($task['status'] === 'completed') {
        $completedTasks++;
    }
}

$totalTasks = count($tasks);
$pendingMaterials = count(array_filter($materials, function($m) {
    return $m['status'] === 'requested';
}));

$pageTitle = 'Sub-Contractor Dashboard';
$currentPage = 'dashboard';

include_once __DIR__ . '/../../views/layouts/header.php';
?>
<div class="row">
  <div class="col-md-12">
    <h1 class="mb-4">Sub-Contractor Dashboard</h1>
  </div>
</div>



<!-- KPI Cards -->
<div class="row mb-4">
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-primary shadow h-100 py-2">
      <div class="card-body">
        <div class="row no-gutters align-items-center">
          <div class="col mr-2">
            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Assigned Projects</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalProjects; ?></div>
          </div>
          <div class="col-auto">
            <i class="fas fa-project-diagram fa-2x text-gray-300"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-success shadow h-100 py-2">
      <div class="card-body">
        <div class="row no-gutters align-items-center">
          <div class="col mr-2">
            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Tasks In Progress</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $inProgressTasks; ?></div>
          </div>
          <div class="col-auto">
            <i class="fas fa-tasks fa-2x text-gray-300"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-info shadow h-100 py-2">
      <div class="card-body">
        <div class="row no-gutters align-items-center">
          <div class="col mr-2">
            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Completion Rate</div>
            <div class="row no-gutters align-items-center">
              <div class="col-auto">
                <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                  <?php echo $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0; ?>%
                </div>
              </div>
              <div class="col">
                <div class="progress progress-sm mr-2">
                  <div class="progress-bar bg-info" role="progressbar" 
                       style="width: <?php echo $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0; ?>%"></div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-auto">
            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-warning shadow h-100 py-2">
      <div class="card-body">
        <div class="row no-gutters align-items-center">
          <div class="col mr-2">
            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Material Requests</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pendingMaterials; ?></div>
          </div>
          <div class="col-auto">
            <i class="fas fa-toolbox fa-2x text-gray-300"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Projects and Tasks -->
<div class="row">
  <!-- Projects -->
  <div class="col-lg-6 mb-4">
    <div class="card shadow mb-4">
      <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Assigned Projects</h6>
        <a href="<?php echo url('/sub-contractor/projects'); ?>" class="btn btn-sm btn-primary">View All</a>
      </div>
      <div class="card-body">
        <?php if (empty($projects)): ?>
          <p class="text-center">No projects assigned yet.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th>Project</th>
                  <th>Status</th>
                  <th>Start Date</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($projects as $project): ?>
                <tr>
                  <td><?php echo htmlspecialchars($project['name']); ?></td>
                  <td>
                    <span class="badge bg-<?php 
                      echo $project['status'] === 'completed' ? 'success' : 
                          ($project['status'] === 'active' ? 'primary' : 
                          ($project['status'] === 'on hold' ? 'warning' : 'secondary')); 
                    ?>">
                      <?php echo htmlspecialchars(ucfirst($project['status'])); ?>
                    </span>
                  </td>
                  <td><?php echo htmlspecialchars($project['start_date'] ?? 'Not set'); ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Tasks -->
  <div class="col-lg-6 mb-4">
    <div class="card shadow mb-4">
      <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Recent Tasks</h6>
        <a href="<?php echo url('/sub-contractor/tasks'); ?>" class="btn btn-sm btn-primary">View All</a>
      </div>
      <div class="card-body">
        <?php if (empty($tasks)): ?>
          <p class="text-center">No tasks assigned yet.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th>Task</th>
                  <th>Project</th>
                  <th>Status</th>
                  <th>Due Date</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($tasks as $task): ?>
                <tr>
                  <td><?php echo htmlspecialchars($task['title']); ?></td>
                  <td><?php echo htmlspecialchars($task['project_name'] ?? 'Unknown'); ?></td>
                  <td>
                    <span class="badge bg-<?php 
                      echo $task['status'] === 'completed' ? 'success' : 
                          ($task['status'] === 'in progress' ? 'primary' : 
                          ($task['status'] === 'pending' ? 'warning' : 'secondary')); 
                    ?>">
                      <?php echo htmlspecialchars(ucfirst($task['status'])); ?>
                    </span>
                  </td>
                  <td><?php echo $task['due_date'] ? htmlspecialchars($task['due_date']) : 'Not set'; ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Materials and Purchase Orders -->
<div class="row">
  <!-- Materials -->
  <div class="col-lg-6 mb-4">
    <div class="card shadow mb-4">
      <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Material Requests</h6>
        <a href="<?php echo url('/sub-contractor/materials'); ?>" class="btn btn-sm btn-primary">View All</a>
      </div>
      <div class="card-body">
        <?php if (empty($materials)): ?>
          <p class="text-center">No material requests found.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th>Material</th>
                  <th>Quantity</th>
                  <th>Status</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $recentMaterials = array_slice($materials, 0, 5);
                foreach ($recentMaterials as $material): 
                ?>
                <tr>
                  <td><?php echo htmlspecialchars($material['material_name']); ?></td>
                  <td><?php echo htmlspecialchars($material['quantity']); ?></td>
                  <td>
                    <span class="badge bg-<?php 
                      echo $material['status'] === 'delivered' ? 'success' : 
                          ($material['status'] === 'approved' ? 'primary' : 
                          ($material['status'] === 'requested' ? 'warning' : 'secondary')); 
                    ?>">
                      <?php echo htmlspecialchars(ucfirst($material['status'])); ?>
                    </span>
                  </td>
                  <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($material['created_at']))); ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Purchase Orders -->
  <div class="col-lg-6 mb-4">
    <div class="card shadow mb-4">
      <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Purchase Orders</h6>
        <a href="<?php echo url('/sub-contractor/purchase-orders'); ?>" class="btn btn-sm btn-primary">View All</a>
      </div>
      <div class="card-body">
        <?php if (empty($pos)): ?>
          <p class="text-center">No purchase orders found.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th>PO ID</th>
                  <th>Amount</th>
                  <th>Status</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pos as $po): ?>
                <tr>
                  <td>#<?php echo htmlspecialchars($po['id']); ?></td>
                  <td>$<?php echo htmlspecialchars(number_format($po['total_amount'], 2)); ?></td>
                  <td>
                    <span class="badge bg-<?php 
                      echo $po['status'] === 'delivered' ? 'success' : 
                          ($po['status'] === 'approved' ? 'primary' : 
                          ($po['status'] === 'pending' ? 'warning' : 'secondary')); 
                    ?>">
                      <?php echo htmlspecialchars(ucfirst($po['status'])); ?>
                    </span>
                  </td>
                  <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($po['created_at']))); ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>


<!-- Task Completion Chart -->
<div class="row">
  <div class="col-12 mb-4">
    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Task Completion Progress</h6>
      </div>
      <div class="card-body">
        <canvas id="taskCompletionChart" width="100%" height="40"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Task Chart JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  var ctx = document.getElementById('taskCompletionChart').getContext('2d');
  var taskChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Completed', 'In Progress', 'Pending'],
      datasets: [{
        data: [<?php echo $completedTasks; ?>, <?php echo $inProgressTasks; ?>, <?php echo $pendingTasks; ?>],
        backgroundColor: ['#4e73df', '#1cc88a', '#f6c23e'],
        hoverBackgroundColor: ['#2e59d9', '#17a673', '#f4b619'],
        hoverBorderColor: "rgba(234, 236, 244, 1)",
      }],
    },
    options: {
      maintainAspectRatio: false,
      tooltips: {
        backgroundColor: "rgb(255,255,255)",
        bodyFontColor: "#858796",
        borderColor: '#dddfeb',
        borderWidth: 1,
        xPadding: 15,
        yPadding: 15,
        displayColors: false,
        caretPadding: 10,
      },
      legend: {
        display: true,
        position: 'bottom'
      },
      cutoutPercentage: 80,
    },
  });
});

// (Removed modal snapshot code; using direct navigation for project details.)
</script>

<?php include_once __DIR__ . '/../components/messaging_nav.php'; ?>
<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>
