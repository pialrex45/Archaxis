<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/TaskController.php';

// Check authentication and role
requireAuth();
if (!hasRole('worker')) {
    http_response_code(403);
    die('Access denied. Workers only.');
}

// Get tasks data
$taskController = new TaskController();
$userId = getCurrentUserId();
$tasksData = $taskController->getAssignedTo($userId);

$pageTitle = 'My Tasks';
$currentPage = 'tasks';
?>

<?php include_once __DIR__ . '/../../views/layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>My Tasks</h1>
        </div>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Task status updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">All Assigned Tasks</h5>
                    <div>
                        <button class="btn btn-primary" onclick="filterTasks('all')">All</button>
                        <button class="btn btn-secondary" onclick="filterTasks('pending')">Pending</button>
                        <button class="btn btn-info" onclick="filterTasks('in progress')">In Progress</button>
                        <button class="btn btn-success" onclick="filterTasks('completed')">Completed</button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($tasksData['data'])): ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="tasksTable">
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Project</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Due Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tasksData['data'] as $task): ?>
                                        <tr class="task-row" data-status="<?php echo htmlspecialchars($task['status']); ?>">
                                            <td><?php echo htmlspecialchars($task['title']); ?></td>
                                            <td><?php echo htmlspecialchars($task['project_name']); ?></td>
                                            <td><?php echo strlen($task['description']) > 50 ? htmlspecialchars(substr($task['description'], 0, 50)) . '...' : htmlspecialchars($task['description']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $task['status'] === 'completed' ? 'success' : 
                                                         ($task['status'] === 'in progress' ? 'primary' : 'warning'); ?>">
                                                    <?php echo htmlspecialchars($task['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $task['due_date'] ? date('M j, Y', strtotime($task['due_date'])) : 'N/A'; ?></td>
                                            <td>
                                                <a href="/worker/task_details?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-info">
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
    </div>
</div>

<script>
function updateTaskStatus(taskId, status) {
    if (confirm('Are you sure you want to update this task status to ' + status + '?')) {
        const formData = new FormData();
        formData.append('task_id', taskId);
        formData.append('status', status);
        
        fetch('/api/tasks/update_status', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'tasks.php?success=1';
            } else {
                window.location.href = 'tasks.php?error=' + encodeURIComponent(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.location.href = 'tasks.php?error=Failed to update task status';
        });
    }
}

function filterTasks(status) {
    const rows = document.querySelectorAll('.task-row');
    
    rows.forEach(row => {
        if (status === 'all' || row.dataset.status === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php include_once __DIR__ . '/../../views/layouts/footer.php'; ?>
