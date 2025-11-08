<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/ProjectController.php';

// Check authentication and role
requireAuth();
if (!hasAnyRole(['admin', 'manager', 'supervisor'])) {
    http_response_code(403);
    die('Access denied. Admins, managers, and supervisors only.');
}

// Set page title and current page
$pageTitle = 'Create Task';
$currentPage = 'tasks';

// Get project ID from URL parameter (optional)
$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

// Create ProjectController instance to get projects for dropdown
$projectController = new ProjectController();
$projectsResult = $projectController->getAll();
$projects = $projectsResult['success'] ? $projectsResult['data'] : [];
?>

<?php include_once __DIR__ . '/../../views/layouts/header.php'; ?>
<?php include_once __DIR__ . '/../../views/layouts/sidebar.php'; ?>

<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Create New Task</h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <form id="taskForm" method="POST" action="<?php echo url('/api/tasks/create'); ?>" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="mb-3">
                    <label for="project_id" class="form-label">Project *</label>
                    <select class="form-select" id="project_id" name="project_id" required>
                        <option value="">Select a project</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>" <?php echo $projectId == $project['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($project['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Please select a project.</div>
                </div>
                
                <div class="mb-3">
                    <label for="title" class="form-label">Task Title *</label>
                    <input type="text" class="form-control" id="title" name="title" required minlength="3" maxlength="150">
                    <div class="invalid-feedback">Please enter a task title (3-150 characters).</div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4" maxlength="1000"></textarea>
                    <div class="invalid-feedback">Description must be less than 1000 characters.</div>
                </div>
                
                <div class="mb-3">
                    <label for="due_date" class="form-label">Due Date</label>
                    <input type="date" class="form-control" id="due_date" name="due_date">
                    <div class="invalid-feedback">Please enter a valid due date.</div>
                </div>
                
                <div class="mb-3">
                    <label for="priority" class="form-label">Priority</label>
                    <select class="form-select" id="priority" name="priority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="pending">Pending</option>
                        <option value="in progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Create Task</button>
                    <a href="<?php echo url('/tasks'); ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('taskForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Reset validation styles
    this.classList.remove('was-validated');
    
    // Get form data
    const formData = new FormData(this);
    
    // Client-side validation
    let isValid = true;
    
    // Validate project
    const projectId = formData.get('project_id');
    if (!projectId) {
        document.getElementById('project_id').classList.add('is-invalid');
        isValid = false;
    } else {
        document.getElementById('project_id').classList.remove('is-invalid');
        document.getElementById('project_id').classList.add('is-valid');
    }
    
    // Validate title
    const title = formData.get('title');
    if (!title || title.length < 3 || title.length > 150) {
        document.getElementById('title').classList.add('is-invalid');
        isValid = false;
    } else {
        document.getElementById('title').classList.remove('is-invalid');
        document.getElementById('title').classList.add('is-valid');
    }
    
    // If client-side validation passes, submit to server
    if (isValid) {
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
                
                // Reset form
                this.reset();
                document.querySelectorAll('.is-valid').forEach(el => el.classList.remove('is-valid'));
                
                // Redirect to tasks list after 2 seconds
                setTimeout(() => {
                    window.location.href = '<?php echo url('/tasks'); ?>';
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
                
                // Handle field-specific errors
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        const fieldElement = document.getElementById(field);
                        if (fieldElement) {
                            fieldElement.classList.add('is-invalid');
                            fieldElement.nextElementSibling.textContent = data.errors[field];
                        }
                    });
                }
            }
        })
        .catch(error => {
            // Show error message
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger alert-dismissible fade show';
            alert.innerHTML = `
                An error occurred while creating the task.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.col-md-9').insertBefore(alert, document.querySelector('.row'));
        });
    }
    
    // Add validation class to show feedback
    this.classList.add('was-validated');
});
</script>

<?php include_once __DIR__ . '/../../views/layouts/footer.php'; ?>