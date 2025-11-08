<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/TaskController.php';
require_once __DIR__ . '/../../controllers/ProjectController.php';

// Check authentication and role
requireAuth();
if (!hasAnyRole(['admin', 'manager', 'supervisor'])) {
    http_response_code(403);
    die('Access denied. Admins, managers, and supervisors only.');
}

// Get task ID from URL parameter
$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($taskId)) {
    http_response_code(400);
    die('Task ID is required');
}

// Create TaskController instance
$taskController = new TaskController();

// Get task details
$result = $taskController->get($taskId);

if (!$result['success']) {
    http_response_code(404);
    die('Task not found');
}

$task = $result['data'];

// Create ProjectController instance to get projects for dropdown
$projectController = new ProjectController();
$projectsResult = $projectController->getAll();
$projects = $projectsResult['success'] ? $projectsResult['data'] : [];

// Set page title and current page
$pageTitle = 'Edit Task: ' . $task['title'];
$currentPage = 'tasks';
?>

<?php include_once __DIR__ . '/../../views/layouts/header.php'; ?>
<?php include_once __DIR__ . '/../../views/layouts/sidebar.php'; ?>

<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Edit Task: <?php echo htmlspecialchars($task['title']); ?></h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <form id="taskForm" method="POST" action="<?php echo url('/api/tasks/update?id=' . $task['id']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
                
                <div class="mb-3">
                    <label for="project_id" class="form-label">Project *</label>
                    <select class="form-select" id="project_id" name="project_id" required>
                        <option value="">Select a project</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>" <?php echo $task['project_id'] == $project['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($project['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="title" class="form-label">Task Title *</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($task['title']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($task['description']); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="due_date" class="form-label">Due Date</label>
                    <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo $task['due_date']; ?>">
                </div>
                
                <div class="mb-3">
                    <label for="priority" class="form-label">Priority</label>
                    <select class="form-select" id="priority" name="priority">
                        <option value="low" <?php echo $task['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo $task['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?php echo $task['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="urgent" <?php echo $task['priority'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="pending" <?php echo $task['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in progress" <?php echo $task['status'] === 'in progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $task['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $task['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Update Task</button>
                    <a href="<?php echo url('/tasks/show?id=' . $task['id']); ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('taskForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show';
            alert.innerHTML = `
                ${data.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.col-md-9').insertBefore(alert, document.querySelector('.row'));
            
            // Redirect to task details after 2 seconds
            setTimeout(() => {
                window.location.href = '<?php echo url('/tasks/show?id=' . $task['id']); ?>';
            }, 2000);
        } else {
            // Show error message
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger alert-dismissible fade show';
            alert.innerHTML = `
                ${data.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.col-md-9').insertBefore(alert, document.querySelector('.row'));
        }
    })
    .catch(error => {
        // Show error message
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show';
        alert.innerHTML = `
            An error occurred while updating the task.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.querySelector('.col-md-9').insertBefore(alert, document.querySelector('.row'));
    });
});
</script>

<?php include_once __DIR__ . '/../../views/layouts/footer.php'; ?>
</content>
<line_count