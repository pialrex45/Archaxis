<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/TaskController.php';
require_once __DIR__ . '/../../controllers/ProjectController.php';

// Check authentication and role
requireAuth();
if (!hasRole('worker')) {
    http_response_code(403);
    die('Access denied. Workers only.');
}

// Get task ID from URL
$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$taskId) {
    header('Location: tasks.php');
    exit;
}

// Get task data
$taskController = new TaskController();
$taskData = $taskController->get($taskId);

// Check if the task exists and is assigned to the current user
if (!$taskData['success'] || $taskData['data']['assigned_to'] != getCurrentUserId()) {
    header('Location: tasks.php?error=Task not found or not assigned to you');
    exit;
}

$task = $taskData['data'];

// Get project data
$projectController = new ProjectController();
$projectData = $projectController->get($task['project_id']);
$project = $projectData['success'] ? $projectData['data'] : null;

$pageTitle = 'Task Details: ' . $task['title'];
$currentPage = 'tasks';
?>

<?php include_once __DIR__ . '/../../views/layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="tasks.php">My Tasks</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Task Details</li>
                </ol>
            </nav>
            <h1><?php echo htmlspecialchars($task['title']); ?></h1>
        </div>
        <div class="col-md-4 text-end">
            <?php if ($task['status'] !== 'completed'): ?>
                <button class="btn btn-primary" onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'in progress')">
                    <i class="bi bi-play-fill"></i> Start Task
                </button>
                <button class="btn btn-success" onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'completed')">
                    <i class="bi bi-check-lg"></i> Complete Task
                </button>
            <?php else: ?>
                <button class="btn btn-success" disabled>
                    <i class="bi bi-check-circle"></i> Completed
                </button>
            <?php endif; ?>
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

    <div class="row">
        <div class="col-md-8">
            <!-- Task Details Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Task Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Status:</div>
                        <div class="col-md-9">
                            <span class="badge bg-<?php 
                                echo $task['status'] === 'completed' ? 'success' : 
                                     ($task['status'] === 'in progress' ? 'primary' : 'warning'); ?>">
                                <?php echo htmlspecialchars($task['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Project:</div>
                        <div class="col-md-9">
                            <?php echo $project ? htmlspecialchars($project['name']) : 'N/A'; ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Description:</div>
                        <div class="col-md-9">
                            <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Created On:</div>
                        <div class="col-md-9">
                            <?php echo date('F j, Y', strtotime($task['created_at'])); ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Due Date:</div>
                        <div class="col-md-9">
                            <?php echo $task['due_date'] ? date('F j, Y', strtotime($task['due_date'])) : 'No due date'; ?>
                            <?php if ($task['due_date'] && strtotime($task['due_date']) < time() && $task['status'] !== 'completed'): ?>
                                <span class="badge bg-danger">Overdue</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Task Progress Update Form -->
            <?php if ($task['status'] !== 'completed'): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Update Progress</h5>
                </div>
                <div class="card-body">
                    <form id="progressForm">
                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                        <div class="mb-3">
                            <label for="progress_notes" class="form-label">Progress Notes</label>
                            <textarea class="form-control" id="progress_notes" name="progress_notes" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="completion_percentage" class="form-label">Completion Percentage: <span id="percentage_value">0</span>%</label>
                            <input type="range" class="form-range" id="completion_percentage" name="completion_percentage" min="0" max="100" value="0" oninput="document.getElementById('percentage_value').textContent = this.value">
                        </div>
                        <div class="mb-3">
                            <label for="progress_photos" class="form-label">Photos (optional)</label>
                            <input class="form-control" type="file" id="progress_photos" name="progress_photos[]" multiple accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Progress Update</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <!-- Task Timeline Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Task Timeline</h5>
                </div>
                <div class="card-body">
                    <ul class="timeline">
                        <li class="timeline-item mb-3">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Task Created</h6>
                                <p class="text-muted mb-0 small"><?php echo date('M j, Y g:i A', strtotime($task['created_at'])); ?></p>
                            </div>
                        </li>
                        <?php if ($task['status'] === 'in progress'): ?>
                        <li class="timeline-item mb-3">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Started Work</h6>
                                <p class="text-muted mb-0 small"><?php echo date('M j, Y g:i A', strtotime($task['updated_at'])); ?></p>
                            </div>
                        </li>
                        <?php endif; ?>
                        <?php if ($task['status'] === 'completed'): ?>
                        <li class="timeline-item mb-3">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Started Work</h6>
                                <p class="text-muted mb-0 small">Date information not available</p>
                            </div>
                        </li>
                        <li class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Task Completed</h6>
                                <p class="text-muted mb-0 small"><?php echo date('M j, Y g:i A', strtotime($task['updated_at'])); ?></p>
                            </div>
                        </li>
                        <?php endif; ?>
                        <?php if ($task['status'] !== 'completed'): ?>
                        <li class="timeline-item">
                            <div class="timeline-marker bg-light"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Task Completion</h6>
                                <p class="text-muted mb-0 small">Pending</p>
                            </div>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Related Materials Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Task Resources</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="../api/materials/request.php?project_id=<?php echo $task['project_id']; ?>" class="btn btn-outline-primary">
                            <i class="bi bi-tools"></i> Request Materials
                        </a>
                        <a href="material_requests.php" class="btn btn-outline-secondary">
                            <i class="bi bi-list-check"></i> View My Material Requests
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    list-style: none;
    padding: 0;
    margin: 0;
}

.timeline-item {
    position: relative;
    padding-left: 40px;
    padding-bottom: 20px;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-marker {
    position: absolute;
    width: 15px;
    height: 15px;
    border-radius: 50%;
    left: 0;
    top: 6px;
}

.timeline-item:not(:last-child):before {
    content: '';
    position: absolute;
    left: 7px;
    top: 24px;
    height: calc(100% - 24px);
    width: 2px;
    background-color: #e9ecef;
}
</style>

<script>
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
                window.location.href = 'task_details.php?id=' + taskId + '&success=1';
            } else {
                window.location.href = 'task_details.php?id=' + taskId + '&error=' + encodeURIComponent(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.location.href = 'task_details.php?id=' + taskId + '&error=Failed to update task status';
        });
    }
}

document.getElementById('progressForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const taskId = formData.get('task_id');
    
    // Add files from the file input
    const fileInput = document.getElementById('progress_photos');
    for (let i = 0; i < fileInput.files.length; i++) {
        formData.append('photos[]', fileInput.files[i]);
    }
    
    fetch('../api/tasks/update_progress.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'task_details.php?id=' + taskId + '&success=1';
        } else {
            window.location.href = 'task_details.php?id=' + taskId + '&error=' + encodeURIComponent(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        window.location.href = 'task_details.php?id=' + taskId + '&error=Failed to update task progress';
    });
});
</script>

<?php include_once __DIR__ . '/../../views/layouts/footer.php'; ?>
