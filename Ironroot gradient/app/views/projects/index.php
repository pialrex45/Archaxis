<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/ProjectController.php';

// Check authentication
requireAuth();

// Set page title and current page
$pageTitle = 'Projects';
$currentPage = 'projects';

// Create ProjectController instance
$projectController = new ProjectController();

// Get all projects
$result = $projectController->getAll();
$projects = $result['success'] ? $result['data'] : [];
?>

<?php include_once __DIR__ . '/../../views/layouts/header.php'; ?>
<?php include_once __DIR__ . '/../../views/layouts/sidebar.php'; ?>

<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Projects</h1>
        <?php if (hasAnyRole(['admin', 'manager'])): ?>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo url('/projects/create'); ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-plus"></i> Create New Project
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!$result['success']): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($result['message']); ?>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($projects)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No projects found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($projects as $project): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($project['name']); ?></td>
                            <td><?php echo truncateText(htmlspecialchars($project['description']), 50); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo ($project['status'] === 'active') ? 'success' :
                                         (($project['status'] === 'completed') ? 'primary' :
                                         (($project['status'] === 'on hold' || $project['status'] === 'on_hold') ? 'warning' : 'secondary'));
                                ?>">
                                    <?php echo htmlspecialchars($project['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $project['start_date'] ? formatDate($project['start_date']) : 'N/A'; ?></td>
                            <td><?php echo $project['end_date'] ? formatDate($project['end_date']) : 'N/A'; ?></td>
                            <td>
                                <a href="<?php echo url('/projects/show?id=' . $project['id']); ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <?php if (hasAnyRole(['admin', 'manager'])): ?>
                                    <a href="<?php echo url('/projects/edit?id=' . $project['id']); ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-project" data-project-id="<?php echo (int)$project['id']; ?>" data-project-name="<?php echo htmlspecialchars($project['name']); ?>">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php if (hasAnyRole(['admin', 'manager'])): ?>
<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteProjectModal" tabindex="-1" aria-labelledby="deleteProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteProjectModalLabel">Delete Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="deleteProjectText">Are you sure you want to delete this project? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteProjectBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
// CSRF token for API requests
const CSRF_TOKEN = '<?php echo generateCSRFToken(); ?>';

let deleteTarget = { id: null, row: null };

// Delegate click for delete buttons
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-delete-project');
    if (!btn) return;
    const projectId = parseInt(btn.getAttribute('data-project-id'), 10);
    const projectName = btn.getAttribute('data-project-name') || '';
    deleteTarget.id = projectId;
    deleteTarget.row = btn.closest('tr');
    const textEl = document.getElementById('deleteProjectText');
    if (textEl) {
        textEl.textContent = `Are you sure you want to delete project "${projectName}"? This action cannot be undone.`;
    }
    const modal = new bootstrap.Modal(document.getElementById('deleteProjectModal'));
    modal.show();
});

// Confirm delete
document.getElementById('confirmDeleteProjectBtn')?.addEventListener('click', function() {
    if (!deleteTarget.id) return;
    fetch('<?php echo url('/api/projects/delete'); ?>?id=' + deleteTarget.id, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            csrf_token: CSRF_TOKEN,
            id: deleteTarget.id
        })
    })
    .then(r => r.json())
    .then(data => {
        // Hide modal
        const modalEl = document.getElementById('deleteProjectModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal?.hide();

        if (data.success) {
            // Remove row
            if (deleteTarget.row) {
                deleteTarget.row.parentNode.removeChild(deleteTarget.row);
            }
            showAlert('success', data.message || 'Project deleted successfully.');
        } else {
            showAlert('danger', data.message || 'Failed to delete project.');
        }
        // Reset target
        deleteTarget = { id: null, row: null };
    })
    .catch(() => {
        const modalEl = document.getElementById('deleteProjectModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal?.hide();
        showAlert('danger', 'An error occurred while deleting the project.');
        deleteTarget = { id: null, row: null };
    });
});

function showAlert(type, message) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    const container = document.querySelector('.col-md-9') || document.querySelector('.col-lg-10') || document.body;
    const anchor = document.querySelector('.border-bottom') || container.firstChild;
    container.insertBefore(alert, anchor);
    // Auto-dismiss after 3 seconds
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    }, 3000);
}
</script>
<?php endif; ?>

<?php include_once __DIR__ . '/../../views/layouts/footer.php'; ?>