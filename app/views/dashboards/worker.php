<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/DashboardController.php';

// Check authentication and role
requireAuth();
if (!hasRole('worker')) {
    http_response_code(403);
    die('Access denied. Workers only.');
}

// Get dashboard data
$dashboardController = new DashboardController();
$dashboardData = $dashboardController->getWorkerDashboard();

$pageTitle = 'Worker Dashboard';
$currentPage = 'dashboard';
?>

<?php include_once __DIR__ . '/../../views/layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="mb-4">Worker Dashboard</h1>
        </div>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Action completed successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($dashboardData['success']): ?>
        <!-- Quick Actions Row -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-list-task" style="font-size: 3rem; color: #007bff;"></i>
                                <h4 class="mt-3">My Tasks</h4>
                                <p>View and manage your assigned tasks</p>
                                <a href="<?php echo url('/worker/tasks.php'); ?>" class="btn btn-primary">View Tasks</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-calendar-check" style="font-size: 3rem; color: #28a745;"></i>
                                <h4 class="mt-3">Attendance</h4>
                                <p>Check in, check out, and view your attendance history</p>
                                <a href="<?php echo url('/worker/attendance.php'); ?>" class="btn btn-success">Manage Attendance</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-tools" style="font-size: 3rem; color: #ffc107;"></i>
                                <h4 class="mt-3">Materials</h4>
                                <p>Request materials and check request status</p>
                                <a href="<?php echo url('/worker/material_requests.php'); ?>" class="btn btn-warning">View Materials</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-chat-dots" style="font-size: 3rem; color: #17a2b8;"></i>
                                <h4 class="mt-3">Messages</h4>
                                <p>View and send messages to team members</p>
                                <a href="<?php echo url('/worker/messages.php'); ?>" class="btn btn-info">Open Messages</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Stats Cards -->
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Tasks</h5>
                        <p class="card-text display-4"><?php echo htmlspecialchars($dashboardData['data']['tasks']['total']); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Completed</h5>
                        <p class="card-text display-4"><?php echo htmlspecialchars($dashboardData['data']['tasks']['completed']); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">In Progress</h5>
                        <p class="card-text display-4"><?php echo htmlspecialchars($dashboardData['data']['tasks']['in_progress']); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Pending</h5>
                        <p class="card-text display-4"><?php echo htmlspecialchars($dashboardData['data']['tasks']['pending']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Assigned Tasks -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Your Assigned Tasks</h5>
                        <a href="<?php echo url('/worker/tasks.php'); ?>" class="btn btn-sm btn-primary">View All Tasks</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($dashboardData['data']['tasks']['recent'])): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Task</th>
                                            <th>Project</th>
                                            <th>Status</th>
                                            <th>Due Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dashboardData['data']['tasks']['recent'] as $task): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($task['title']); ?></td>
                                                <td><?php echo htmlspecialchars($task['project_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $task['status'] === 'completed' ? 'success' : 
                                                             ($task['status'] === 'in progress' ? 'primary' : 'warning'); ?>">
                                                        <?php echo htmlspecialchars($task['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $task['due_date'] ? date('M j, Y', strtotime($task['due_date'])) : 'N/A'; ?></td>
                                                <td>
                                                    <a href="<?php echo url('/worker/task_details.php'); ?>?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                    <?php if ($task['status'] !== 'completed'): ?>
                                                        <button class="btn btn-sm btn-primary" 
                                                                onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'in progress')">
                                                            <i class="bi bi-play"></i> Start
                                                        </button>
                                                        <button class="btn btn-sm btn-success" 
                                                                onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'completed')">
                                                            <i class="bi bi-check2"></i> Complete
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>No assigned tasks found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Attendance and Materials -->
            <div class="col-md-4 mb-4">
                <!-- Attendance Status -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Today's Attendance</h5>
                        <a href="<?php echo url('/worker/attendance.php'); ?>" class="btn btn-sm btn-primary">View History</a>
                    </div>
                    <div class="card-body">
                        <?php if ($dashboardData['data']['attendance']): ?>
                            <div class="text-center">
                                <h3 class="display-6">Checked In</h3>
                                <?php if (isset($dashboardData['data']['attendance']['check_in_time'])): ?>
                                    <p class="text-muted">
                                        <?php echo date('g:i A', strtotime($dashboardData['data']['attendance']['check_in_time'])); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (isset($dashboardData['data']['attendance']['check_out_time'])): ?>
                                    <p>Checked Out: <?php echo date('g:i A', strtotime($dashboardData['data']['attendance']['check_out_time'])); ?></p>
                                <?php else: ?>
                                    <button class="btn btn-danger" onclick="markAttendance('check_out')">Check Out</button>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center">
                                <h3 class="display-6">Not Checked In</h3>
                                <button class="btn btn-success" onclick="markAttendance('check_in')">Check In</button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Materials -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Your Material Requests</h5>
                        <a href="<?php echo url('/worker/material_requests.php'); ?>" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($dashboardData['data']['recent_materials'])): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Material</th>
                                            <th>Quantity</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dashboardData['data']['recent_materials'] as $material): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($material['material_name']); ?></td>
                                                <td><?php echo htmlspecialchars($material['quantity']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $material['status'] === 'approved' ? 'success' : 
                                                             ($material['status'] === 'delivered' ? 'primary' : 'warning'); ?>">
                                                        <?php echo htmlspecialchars($material['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>No material requests found.</p>
                            <a href="<?php echo url('/worker/material_requests.php'); ?>" class="btn btn-primary btn-sm">Request Materials</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Messages -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Messages</h5>
                        <a href="<?php echo url('/worker/messages.php'); ?>" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($dashboardData['data']['recent_messages'])): ?>
                            <ul class="list-group">
                                <?php foreach ($dashboardData['data']['recent_messages'] as $message): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong><?php echo htmlspecialchars(substr($message['message_text'], 0, 30)); ?>...</strong>
                                            </div>
                                            <small class="text-muted"><?php echo date('M j, Y', strtotime($message['created_at'])); ?></small>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No recent messages.</p>
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
</div>

<script>
// Build URLs relative to app base URL
const baseUrl = '<?php echo rtrim(url(''), '/'); ?>';

function updateTaskStatus(taskId, status) {
    if (confirm('Are you sure you want to update this task status to ' + status + '?')) {
        const formData = new FormData();
        formData.append('task_id', taskId);
        formData.append('status', status);
        
        fetch(baseUrl + '/api/tasks/update_status', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = baseUrl + '/worker/dashboard.php?success=1';
            } else {
                window.location.href = baseUrl + '/worker/dashboard.php?error=' + encodeURIComponent(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.location.href = baseUrl + '/worker/dashboard.php?error=Failed to update task status';
        });
    }
}

function markAttendance(action) {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('csrf_token', '<?php echo generate_csrf_token(); ?>');
    
    fetch(baseUrl + '/api/attendance/mark', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = baseUrl + '/worker/dashboard.php?success=1';
        } else {
            window.location.href = baseUrl + '/worker/dashboard.php?error=' + encodeURIComponent(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        window.location.href = baseUrl + '/worker/dashboard.php?error=Failed to mark attendance';
    });
}
</script>

<?php include_once __DIR__ . '/../../views/layouts/footer.php'; ?>