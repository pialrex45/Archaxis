<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/ProjectController.php';

// Check authentication and role
requireAuth();
if (!hasAnyRole(['admin', 'manager'])) {
    http_response_code(403);
    die('Access denied. Admins and managers only.');
}

// Get project ID from URL parameter
$projectId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($projectId)) {
    http_response_code(400);
    die('Project ID is required');
}

// Create ProjectController instance
$projectController = new ProjectController();

// Get project details
$result = $projectController->get($projectId);

if (!$result['success']) {
    http_response_code(404);
    die('Project not found');
}

$project = $result['data'];

// Set page title and current page
$pageTitle = 'Edit Project: ' . $project['name'];
$currentPage = 'projects';
?>

<?php include_once __DIR__ . '/../../views/layouts/header.php'; ?>
<?php include_once __DIR__ . '/../../views/layouts/sidebar.php'; ?>

<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Edit Project: <?php echo htmlspecialchars($project['name']); ?></h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <form id="projectForm" method="POST" action="<?php echo url('/api/projects/update?id=' . $project['id']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="id" value="<?php echo $project['id']; ?>">
                
                <div class="mb-3">
                    <label for="name" class="form-label">Project Name *</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($project['name']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($project['description']); ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $project['start_date']; ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $project['end_date']; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="planning" <?php echo $project['status'] === 'planning' ? 'selected' : ''; ?>>Planning</option>
                        <option value="active" <?php echo $project['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="on hold" <?php echo $project['status'] === 'on hold' ? 'selected' : ''; ?>>On Hold</option>
                        <option value="completed" <?php echo $project['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $project['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Update Project</button>
                    <a href="<?php echo url('/projects/show?id=' . $project['id']); ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('projectForm').addEventListener('submit', function(e) {
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
            
            // Redirect to project details after 2 seconds
            setTimeout(() => {
                window.location.href = '<?php echo url('/projects/show?id=' . $project['id']); ?>';
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
            An error occurred while updating the project.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.querySelector('.col-md-9').insertBefore(alert, document.querySelector('.row'));
    });
});
</script>

<?php include_once __DIR__ . '/../../views/layouts/footer.php'; ?>