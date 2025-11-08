<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/ProjectController.php';

// Check authentication
requireAuth();

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
$pageTitle = 'Project Details: ' . $project['name'];
$currentPage = 'projects';
?>

<?php include_once __DIR__ . '/../../views/layouts/header.php'; ?>
<?php include_once __DIR__ . '/../../views/layouts/sidebar.php'; ?>

<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Project: <?php echo htmlspecialchars($project['name']); ?></h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <?php if (hasAnyRole(['admin', 'manager'])): ?>
                <a href="<?php echo url('/projects/edit?id=' . $project['id']); ?>" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="fas fa-edit"></i> Edit
                </a>
            <?php endif; ?>
            <a href="<?php echo url('/projects'); ?>" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Projects
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Project Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-3"><strong>Name:</strong></div>
                        <div class="col-sm-9"><?php echo htmlspecialchars($project['name']); ?></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><strong>Description:</strong></div>
                        <div class="col-sm-9"><?php echo nl2brSafe($project['description']); ?></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><strong>Status:</strong></div>
                        <div class="col-sm-9">
                            <span class="badge bg-<?php 
                                echo $project['status'] === 'active' ? 'success' : 
                                     ($project['status'] === 'completed' ? 'primary' : 
                                     ($project['status'] === 'on hold' ? 'warning' : 'secondary')); ?>">
                                <?php echo htmlspecialchars($project['status']); ?>
                            </span>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><strong>Start Date:</strong></div>
                        <div class="col-sm-9"><?php echo $project['start_date'] ? formatDate($project['start_date']) : 'N/A'; ?></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><strong>End Date:</strong></div>
                        <div class="col-sm-9"><?php echo $project['end_date'] ? formatDate($project['end_date']) : 'N/A'; ?></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><strong>Created:</strong></div>
                        <div class="col-sm-9"><?php echo formatDate($project['created_at']); ?></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><strong>Last Updated:</strong></div>
                        <div class="col-sm-9"><?php echo formatDate($project['updated_at']); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Project Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo url('/tasks?project_id=' . $project['id']); ?>" class="btn btn-primary">
                            <i class="fas fa-tasks"></i> View Tasks
                        </a>
                        <a href="<?php echo url('/materials?project_id=' . $project['id']); ?>" class="btn btn-secondary">
                            <i class="fas fa-boxes"></i> View Materials
                        </a>
                        <a href="<?php echo url('/finance?project_id=' . $project['id']); ?>" class="btn btn-info">
                            <i class="fas fa-money-bill-wave"></i> View Finance
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if (hasAnyRole(['admin', 'manager'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Admin Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                            <i class="fas fa-sync-alt"></i> Update Status
                        </button>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteProjectModal">
                            <i class="fas fa-trash"></i> Delete Project
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (hasAnyRole(['admin', 'manager'])): ?>
<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStatusModalLabel">Update Project Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateStatusForm" method="POST" action="<?php echo url('/api/projects/update_status?id=' . $project['id']); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="id" value="<?php echo $project['id']; ?>">
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="planning" <?php echo $project['status'] === 'planning' ? 'selected' : ''; ?>>Planning</option>
                            <option value="active" <?php echo $project['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="on hold" <?php echo $project['status'] === 'on hold' ? 'selected' : ''; ?>>On Hold</option>
                            <option value="completed" <?php echo $project['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $project['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="updateStatusBtn">Update Status</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Project Modal -->
<div class="modal fade" id="deleteProjectModal" tabindex="-1" aria-labelledby="deleteProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteProjectModalLabel">Delete Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this project? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="deleteProjectBtn">Delete Project</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('updateStatusBtn').addEventListener('click', function() {
    const form = document.getElementById('updateStatusForm');
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            const modalEl = document.getElementById('updateStatusModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal?.hide();
            // Show success alert and reload
            showProjectAlert('success', data.message || 'Status updated successfully.');
            setTimeout(() => { location.reload(); }, 1200);
        } else {
            showProjectAlert('danger', data.message || 'Failed to update status.');
        }
    })
    .catch(error => {
        showProjectAlert('danger', 'An error occurred while updating the project status.');
    });
});

document.getElementById('deleteProjectBtn').addEventListener('click', function() {
    const projectId = <?php echo $project['id']; ?>;
    
    fetch('<?php echo url('/api/projects/delete?id=' . $project['id']); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            csrf_token: '<?php echo generateCSRFToken(); ?>',
            id: projectId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            const modalEl = document.getElementById('deleteProjectModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal?.hide();
            // Show success alert and redirect
            showProjectAlert('success', data.message || 'Project deleted successfully.');
            setTimeout(() => {
                window.location.href = '<?php echo url('/projects'); ?>';
            }, 1200);
        } else {
            showProjectAlert('danger', data.message || 'Failed to delete project.');
        }
    })
    .catch(error => {
        showProjectAlert('danger', 'An error occurred while deleting the project.');
    });
});

function showProjectAlert(type, message) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    const container = document.querySelector('.col-md-9') || document.querySelector('.col-lg-10') || document.body;
    const anchor = document.querySelector('.border-bottom') || container.firstChild;
    container.insertBefore(alert, anchor);
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    }, 3000);
}
</script>
<?php endif; ?>

<?php include_once __DIR__ . '/../../views/layouts/footer.php'; ?>