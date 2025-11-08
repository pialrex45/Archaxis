<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/DashboardController.php';

// Check authentication and role
requireAuth();
if (!hasRole('manager')) {
    http_response_code(403);
    die('Access denied. General Managers only.');
}

// Get dashboard data
$dashboardController = new DashboardController();
$dashboardData = $dashboardController->getManagerDashboard();

$pageTitle = 'General Manager Dashboard';
$currentPage = 'dashboard';
?>

<?php include_once __DIR__ . '/../../views/layouts/header.php'; ?>

<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">General Manager Dashboard</h1>
    </div>
</div>

<?php if ($dashboardData['success']): ?>
    <div class="row">
        <!-- Stats Cards -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Projects</h5>
                    <p class="card-text display-4"><?php echo htmlspecialchars($dashboardData['data']['projects']['total']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Tasks</h5>
                    <p class="card-text display-4"><?php echo htmlspecialchars($dashboardData['data']['tasks']['total']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Pending Tasks</h5>
                    <p class="card-text display-4"><?php echo htmlspecialchars($dashboardData['data']['tasks']['pending']); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Task Status Chart -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Task Status Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="taskStatusChart" width="400" height="400"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Projects List -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Your Projects</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboardData['data']['projects']['list'])): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dashboardData['data']['projects']['list'] as $project): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($project['name']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $project['status'] === 'active' ? 'success' : 
                                                         ($project['status'] === 'completed' ? 'primary' : 
                                                         ($project['status'] === 'on hold' ? 'warning' : 'secondary')); ?>">
                                                    <?php echo htmlspecialchars($project['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No projects found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Recent Messages -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Messages</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboardData['data']['recent_messages'])): ?>
                        <ul class="list-group">
                            <?php foreach ($dashboardData['data']['recent_messages'] as $message): ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong><?php echo htmlspecialchars($message['message_text']); ?></strong>
                                            <br>
                                            <small class="text-muted">From: <?php echo htmlspecialchars($message['sender_name']); ?></small>
                                        </div>
                                        <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></small>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No recent messages found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Pending Materials -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Pending Material Requests</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboardData['data']['pending_materials'])): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Material</th>
                                        <th>Quantity</th>
                                        <th>Project</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dashboardData['data']['pending_materials'] as $material): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($material['material_name']); ?></td>
                                            <td><?php echo htmlspecialchars($material['quantity']); ?></td>
                                            <td><?php echo htmlspecialchars($material['project_name']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No pending material requests.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-danger">
        Error loading dashboard data: <?php echo htmlspecialchars($dashboardData['message']); ?>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Task Status Chart
    var ctx = document.getElementById('taskStatusChart').getContext('2d');
    var taskStatusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'In Progress', 'Pending'],
            datasets: [{
                data: [
                    <?php echo $dashboardData['data']['tasks']['completed']; ?>,
                    <?php echo $dashboardData['data']['tasks']['in_progress'] ?? 0; ?>,
                    <?php echo $dashboardData['data']['tasks']['pending']; ?>
                ],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(0, 123, 255, 0.8)',
                    'rgba(255, 193, 7, 0.8)'
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(0, 123, 255, 1)',
                    'rgba(255, 193, 7, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
});
</script>

<?php include_once __DIR__ . '/../../views/layouts/footer.php'; ?>