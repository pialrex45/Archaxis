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
        <div class="col-md-8">
            <h1>Worker Dashboard</h1>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars(getCurrentUserName()); ?>!</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="current-date-time">
                <h5 id="currentDate"></h5>
                <h3 id="currentTime"></h3>
            </div>
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
                <div class="card">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col">
                                <a href="tasks.php" class="quick-action-link">
                                    <div class="quick-action-icon bg-primary">
                                        <i class="bi bi-list-task"></i>
                                    </div>
                                    <h5>My Tasks</h5>
                                </a>
                            </div>
                            <div class="col">
                                <a href="attendance.php" class="quick-action-link">
                                    <div class="quick-action-icon bg-success">
                                        <i class="bi bi-calendar-check"></i>
                                    </div>
                                    <h5>Attendance</h5>
                                </a>
                            </div>
                            <div class="col">
                                <a href="material_requests.php" class="quick-action-link">
                                    <div class="quick-action-icon bg-warning">
                                        <i class="bi bi-tools"></i>
                                    </div>
                                    <h5>Materials</h5>
                                </a>
                            </div>
                            <div class="col">
                                <a href="#" class="quick-action-link" data-bs-toggle="modal" data-bs-target="#reportIssueModal">
                                    <div class="quick-action-icon bg-danger">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </div>
                                    <h5>Report Issue</h5>
                                </a>
                            </div>
                            <div class="col">
                                <a href="../messages/inbox.php" class="quick-action-link">
                                    <div class="quick-action-icon bg-info">
                                        <i class="bi bi-chat-dots"></i>
                                    </div>
                                    <h5>Messages</h5>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title">Total Tasks</h5>
                            <div class="stat-icon bg-light text-primary">
                                <i class="bi bi-list-check"></i>
                            </div>
                        </div>
                        <h2 class="display-4"><?php echo htmlspecialchars($dashboardData['data']['tasks']['total']); ?></h2>
                        <div class="progress mt-3" style="height: 10px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title">Completed</h5>
                            <div class="stat-icon bg-light text-success">
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                        <h2 class="display-4"><?php echo htmlspecialchars($dashboardData['data']['tasks']['completed']); ?></h2>
                        <?php 
                            $completionRate = $dashboardData['data']['tasks']['total'] > 0 
                                ? ($dashboardData['data']['tasks']['completed'] / $dashboardData['data']['tasks']['total']) * 100 
                                : 0;
                        ?>
                        <div class="progress mt-3" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                style="width: <?php echo $completionRate; ?>%;" 
                                aria-valuenow="<?php echo $completionRate; ?>" 
                                aria-valuemin="0" 
                                aria-valuemax="100"></div>
                        </div>
                        <p class="text-muted mt-2"><?php echo round($completionRate); ?>% completion rate</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title">In Progress</h5>
                            <div class="stat-icon bg-light text-primary">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                        </div>
                        <h2 class="display-4"><?php echo htmlspecialchars($dashboardData['data']['tasks']['in_progress']); ?></h2>
                        <?php 
                            $inProgressRate = $dashboardData['data']['tasks']['total'] > 0 
                                ? ($dashboardData['data']['tasks']['in_progress'] / $dashboardData['data']['tasks']['total']) * 100 
                                : 0;
                        ?>
                        <div class="progress mt-3" style="height: 10px;">
                            <div class="progress-bar bg-primary" role="progressbar" 
                                style="width: <?php echo $inProgressRate; ?>%;" 
                                aria-valuenow="<?php echo $inProgressRate; ?>" 
                                aria-valuemin="0" 
                                aria-valuemax="100"></div>
                        </div>
                        <p class="text-muted mt-2"><?php echo round($inProgressRate); ?>% of total tasks</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title">Pending</h5>
                            <div class="stat-icon bg-light text-warning">
                                <i class="bi bi-clock-history"></i>
                            </div>
                        </div>
                        <h2 class="display-4"><?php echo htmlspecialchars($dashboardData['data']['tasks']['pending']); ?></h2>
                        <?php 
                            $pendingRate = $dashboardData['data']['tasks']['total'] > 0 
                                ? ($dashboardData['data']['tasks']['pending'] / $dashboardData['data']['tasks']['total']) * 100 
                                : 0;
                        ?>
                        <div class="progress mt-3" style="height: 10px;">
                            <div class="progress-bar bg-warning" role="progressbar" 
                                style="width: <?php echo $pendingRate; ?>%;" 
                                aria-valuenow="<?php echo $pendingRate; ?>" 
                                aria-valuemin="0" 
                                aria-valuemax="100"></div>
                        </div>
                        <p class="text-muted mt-2"><?php echo round($pendingRate); ?>% of total tasks</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Assigned Tasks -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Your Assigned Tasks</h5>
                        <a href="tasks.php" class="btn btn-sm btn-primary">View All Tasks</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($dashboardData['data']['tasks']['recent'])): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
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
                                                <td>
                                                    <a href="task_details.php?id=<?php echo $task['id']; ?>">
                                                        <?php echo htmlspecialchars($task['title']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo htmlspecialchars($task['project_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $task['status'] === 'completed' ? 'success' : 
                                                             ($task['status'] === 'in progress' ? 'primary' : 'warning'); ?>">
                                                        <?php echo htmlspecialchars($task['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($task['due_date']): ?>
                                                        <?php 
                                                            $dueDate = strtotime($task['due_date']);
                                                            $today = strtotime('today');
                                                            $daysDiff = round(($dueDate - $today) / (60 * 60 * 24));
                                                            
                                                            echo date('M j, Y', $dueDate);
                                                            
                                                            if ($daysDiff < 0 && $task['status'] !== 'completed') {
                                                                echo ' <span class="badge bg-danger">Overdue</span>';
                                                            } else if ($daysDiff <= 2 && $daysDiff >= 0 && $task['status'] !== 'completed') {
                                                                echo ' <span class="badge bg-warning">Due Soon</span>';
                                                            }
                                                        ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="task_details.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <?php if ($task['status'] !== 'completed'): ?>
                                                        <button class="btn btn-sm btn-primary" 
                                                                onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'in progress')">
                                                            <i class="bi bi-play"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-success" 
                                                                onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'completed')">
                                                            <i class="bi bi-check2"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <img src="../../public/assets/images/no-tasks.svg" alt="No Tasks" style="width: 120px; opacity: 0.6;">
                                <p class="mt-3 text-muted">No assigned tasks found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Task Chart -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Task Status Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="taskStatusChart" height="200"></canvas>
                    </div>
                </div>
                
                <!-- Recent Materials -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Your Material Requests</h5>
                        <a href="material_requests.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($dashboardData['data']['recent_materials'])): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Material</th>
                                            <th>Quantity</th>
                                            <th>Status</th>
                                            <th>Requested On</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dashboardData['data']['recent_materials'] as $material): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($material['material_name']); ?></td>
                                                <td><?php echo htmlspecialchars($material['quantity']); ?> <?php echo htmlspecialchars($material['unit'] ?? ''); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $material['status'] === 'approved' ? 'success' : 
                                                             ($material['status'] === 'delivered' ? 'primary' : 
                                                             ($material['status'] === 'rejected' ? 'danger' : 'warning')); ?>">
                                                        <?php echo htmlspecialchars($material['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($material['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <p class="text-muted">No material requests found.</p>
                                <a href="material_requests.php" class="btn btn-primary">Request Materials</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Attendance Card -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Today's Attendance</h5>
                        <a href="attendance.php" class="btn btn-sm btn-primary">Attendance History</a>
                    </div>
                    <div class="card-body">
                        <div class="text-center">
                            <div class="mb-3">
                                <h5 class="mb-2"><?php echo date('l, F j, Y'); ?></h5>
                            </div>
                            
                            <?php if ($dashboardData['data']['attendance']): ?>
                                <div class="mb-4">
                                    <div class="attendance-status checked-in">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Checked In</span>
                                    </div>
                                    <p class="display-6 mb-0">
                                        <?php echo date('g:i A', strtotime($dashboardData['data']['attendance']['check_in_time'])); ?>
                                    </p>
                                </div>
                                
                                <?php if (isset($dashboardData['data']['attendance']['check_out_time'])): ?>
                                    <div class="mb-3">
                                        <div class="attendance-status checked-out">
                                            <i class="bi bi-box-arrow-right"></i>
                                            <span>Checked Out</span>
                                        </div>
                                        <p class="display-6 mb-0">
                                            <?php echo date('g:i A', strtotime($dashboardData['data']['attendance']['check_out_time'])); ?>
                                        </p>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <?php 
                                            $checkIn = new DateTime($dashboardData['data']['attendance']['check_in_time']);
                                            $checkOut = new DateTime($dashboardData['data']['attendance']['check_out_time']);
                                            $interval = $checkIn->diff($checkOut);
                                            echo 'Total Hours: ' . $interval->format('%h hours, %i minutes');
                                        ?>
                                    </div>
                                <?php else: ?>
                                    <button class="btn btn-danger btn-lg" onclick="markAttendance('check_out')">
                                        <i class="bi bi-box-arrow-right"></i> Check Out
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="mb-4">
                                    <div class="attendance-status not-checked">
                                        <i class="bi bi-x-circle"></i>
                                        <span>Not Checked In</span>
                                    </div>
                                </div>
                                <button class="btn btn-success btn-lg" onclick="markAttendance('check_in')">
                                    <i class="bi bi-box-arrow-in-right"></i> Check In
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Messages -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Messages</h5>
                        <a href="../messages/inbox.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($dashboardData['data']['recent_messages'])): ?>
                            <ul class="list-group message-list">
                                <?php foreach ($dashboardData['data']['recent_messages'] as $message): ?>
                                    <li class="list-group-item border-0 border-bottom">
                                        <div class="d-flex align-items-start">
                                            <div class="message-avatar me-3">
                                                <div class="avatar-placeholder">
                                                    <?php 
                                                        $initials = '';
                                                        if (isset($message['sender_name'])) {
                                                            $nameParts = explode(' ', $message['sender_name']);
                                                            if (count($nameParts) >= 2) {
                                                                $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[count($nameParts)-1], 0, 1));
                                                            } else {
                                                                $initials = strtoupper(substr($message['sender_name'], 0, 2));
                                                            }
                                                        } else {
                                                            $initials = '?';
                                                        }
                                                        echo $initials;
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="message-content">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($message['sender_name'] ?? 'Unknown'); ?></h6>
                                                <p class="mb-1 text-truncate" style="max-width: 250px;">
                                                    <?php echo htmlspecialchars($message['message_text']); ?>
                                                </p>
                                                <small class="text-muted">
                                                    <i class="bi bi-clock me-1"></i>
                                                    <?php 
                                                        $messageDate = new DateTime($message['created_at']);
                                                        $now = new DateTime();
                                                        $diff = $now->diff($messageDate);
                                                        
                                                        if ($diff->days > 0) {
                                                            echo $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
                                                        } elseif ($diff->h > 0) {
                                                            echo $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
                                                        } elseif ($diff->i > 0) {
                                                            echo $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
                                                        } else {
                                                            echo 'Just now';
                                                        }
                                                    ?>
                                                </small>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <p class="text-muted">No recent messages.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Weather Card -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Work Site Weather</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center">
                            <div id="weather-widget">
                                <div class="weather-loading">
                                    <p>Loading weather data...</p>
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
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

<!-- Report Issue Modal -->
<div class="modal fade" id="reportIssueModal" tabindex="-1" aria-labelledby="reportIssueModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportIssueModalLabel">Report Issue</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="issueReportForm">
                    <div class="mb-3">
                        <label for="issue_type" class="form-label">Issue Type</label>
                        <select class="form-select" id="issue_type" name="issue_type" required>
                            <option value="" selected disabled>Select issue type</option>
                            <option value="safety">Safety Concern</option>
                            <option value="material">Material Quality Issue</option>
                            <option value="equipment">Equipment Malfunction</option>
                            <option value="delay">Project Delay</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="issue_description" class="form-label">Description</label>
                        <textarea class="form-control" id="issue_description" name="issue_description" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="issue_location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="issue_location" name="issue_location" required>
                    </div>
                    <div class="mb-3">
                        <label for="issue_severity" class="form-label">Severity</label>
                        <select class="form-select" id="issue_severity" name="issue_severity" required>
                            <option value="low">Low - Not urgent</option>
                            <option value="medium" selected>Medium - Needs attention</option>
                            <option value="high">High - Urgent problem</option>
                            <option value="critical">Critical - Work stoppage</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="issue_photos" class="form-label">Photos (optional)</label>
                        <input class="form-control" type="file" id="issue_photos" name="issue_photos[]" multiple accept="image/*">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitIssueReport()">Submit Report</button>
            </div>
        </div>
    </div>
</div>

<style>
.quick-action-link {
    display: block;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
}

.quick-action-link:hover {
    transform: translateY(-5px);
}

.quick-action-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    color: white;
    font-size: 24px;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.current-date-time {
    text-align: right;
    color: #6c757d;
}

.attendance-status {
    display: inline-flex;
    align-items: center;
    padding: 5px 15px;
    border-radius: 20px;
    margin-bottom: 10px;
    font-weight: 500;
}

.attendance-status.checked-in {
    background-color: #d4edda;
    color: #28a745;
}

.attendance-status.checked-out {
    background-color: #f8d7da;
    color: #dc3545;
}

.attendance-status.not-checked {
    background-color: #e2e3e5;
    color: #6c757d;
}

.attendance-status i {
    margin-right: 5px;
    font-size: 18px;
}

.message-avatar {
    flex-shrink: 0;
}

.avatar-placeholder {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #007bff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.message-list {
    max-height: 300px;
    overflow-y: auto;
}

#weather-widget {
    min-height: 150px;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Current date and time display
function updateDateTime() {
    const now = new Date();
    const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit' };
    
    document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', dateOptions);
    document.getElementById('currentTime').textContent = now.toLocaleTimeString('en-US', timeOptions);
}

// Initial call and set interval
updateDateTime();
setInterval(updateDateTime, 1000);

// Task Status Chart
const taskData = {
    labels: ['Completed', 'In Progress', 'Pending'],
    datasets: [{
        data: [
            <?php echo $dashboardData['data']['tasks']['completed']; ?>,
            <?php echo $dashboardData['data']['tasks']['in_progress']; ?>,
            <?php echo $dashboardData['data']['tasks']['pending']; ?>
        ],
        backgroundColor: ['#28a745', '#007bff', '#ffc107'],
        hoverOffset: 4
    }]
};

const taskCtx = document.getElementById('taskStatusChart').getContext('2d');
const taskChart = new Chart(taskCtx, {
    type: 'doughnut',
    data: taskData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
            }
        }
    }
});

// Update task status function
function updateTaskStatus(taskId, status) {
    if (confirm('Are you sure you want to update this task status to ' + status + '?')) {
        const formData = new FormData();
        formData.append('task_id', taskId);
        formData.append('status', status);
        
        fetch('../api/tasks/update_status.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'dashboard.php?success=1';
            } else {
                window.location.href = 'dashboard.php?error=' + encodeURIComponent(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.location.href = 'dashboard.php?error=Failed to update task status';
        });
    }
}

// Mark attendance function
function markAttendance(action) {
    const formData = new FormData();
    formData.append('action', action);
    
    fetch('../api/attendance/mark.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'dashboard.php?success=1';
        } else {
            window.location.href = 'dashboard.php?error=' + encodeURIComponent(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        window.location.href = 'dashboard.php?error=Failed to mark attendance';
    });
}

// Submit issue report function
function submitIssueReport() {
    const form = document.getElementById('issueReportForm');
    const formData = new FormData(form);
    
    // Add files from the file input
    const fileInput = document.getElementById('issue_photos');
    for (let i = 0; i < fileInput.files.length; i++) {
        formData.append('photos[]', fileInput.files[i]);
    }
    
    fetch('../api/incidents/report.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'dashboard.php?success=1';
        } else {
            window.location.href = 'dashboard.php?error=' + encodeURIComponent(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        window.location.href = 'dashboard.php?error=Failed to submit issue report';
    });
}

// Simulated weather data (since we don't have an actual API)
setTimeout(function() {
    const weatherWidget = document.getElementById('weather-widget');
    weatherWidget.innerHTML = `
        <div class="d-flex justify-content-center align-items-center">
            <div class="text-center me-4">
                <i class="bi bi-cloud-sun" style="font-size: 3rem; color: #f0ad4e;"></i>
                <h2 class="mt-2 mb-0">24°C</h2>
                <p class="text-muted">Partly Cloudy</p>
            </div>
            <div>
                <ul class="list-unstyled">
                    <li><i class="bi bi-droplet me-2"></i> Humidity: 65%</li>
                    <li><i class="bi bi-wind me-2"></i> Wind: 12 km/h</li>
                    <li><i class="bi bi-umbrella me-2"></i> Precipitation: 10%</li>
                </ul>
            </div>
        </div>
        <div class="mt-3">
            <p class="mb-1 text-center"><strong>Weather Forecast:</strong> Suitable for construction work</p>
            <div class="d-flex justify-content-between mt-2">
                <div class="text-center">
                    <small>Tomorrow</small><br>
                    <i class="bi bi-cloud"></i> 22°C
                </div>
                <div class="text-center">
                    <small>Wed</small><br>
                    <i class="bi bi-cloud-rain"></i> 19°C
                </div>
                <div class="text-center">
                    <small>Thu</small><br>
                    <i class="bi bi-cloud-rain-heavy"></i> 17°C
                </div>
                <div class="text-center">
                    <small>Fri</small><br>
                    <i class="bi bi-sun"></i> 23°C
                </div>
            </div>
        </div>
    `;
}, 1500);
</script>

<?php include_once __DIR__ . '/../../views/layouts/footer.php'; ?>
