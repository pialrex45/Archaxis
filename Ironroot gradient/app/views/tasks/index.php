<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/TaskController.php';

// Check authentication
requireAuth();

// Set page title and current page
$pageTitle = 'Tasks';
$currentPage = 'tasks';

// Get project ID from URL parameter (optional)
$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

// Create TaskController instance
$taskController = new TaskController();

// Get tasks
if ($projectId > 0) {
    // Get tasks for specific project
    $result = $taskController->getByProject($projectId);
} else {
    // Get all tasks
    $result = $taskController->getAll();
}

$tasks = $result['success'] ? $result['data'] : [];
?>

<?php include_once __DIR__ . '/../../views/layouts/header.php'; ?>
<?php include_once __DIR__ . '/../../views/layouts/sidebar.php'; ?>

<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Tasks</h1>
        <?php if (hasAnyRole(['admin', 'manager', 'supervisor'])): ?>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo url('/tasks/create'); ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-plus"></i> Create New Task
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
                    <th>Title</th>
                    <th>Project</th>
                    <th>Assigned To</th>
                    <th>Status</th>
                    <th>Due Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tasks)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No tasks found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($task['title']); ?></td>
                            <td><?php echo htmlspecialchars($task['project_name']); ?></td>
                            <td><?php echo htmlspecialchars($task['assigned_to_name'] ?? 'Unassigned'); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $task['status'] === 'completed' ? 'success' : 
                                         ($task['status'] === 'in progress' ? 'primary' : 
                                         ($task['status'] === 'cancelled' ? 'danger' : 'warning')); ?>">
                                    <?php echo htmlspecialchars($task['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $task['due_date'] ? formatDate($task['due_date']) : 'N/A'; ?></td>
                            <td>
                                <a href="<?php echo url('/tasks/show?id=' . $task['id']); ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <?php if (hasAnyRole(['admin', 'manager', 'supervisor', 'site_manager'])): ?>
                                    <button type="button" class="btn btn-sm btn-outline-info btn-assign-task" data-task-id="<?php echo (int)$task['id']; ?>">
                                        <i class="fas fa-user-tag"></i> Assign
                                    </button>
                                <?php endif; ?>
                                <?php if (hasAnyRole(['admin', 'manager', 'supervisor'])): ?>
                                    <a href="<?php echo url('/tasks/edit?id=' . $task['id']); ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-task" data-task-id="<?php echo (int)$task['id']; ?>" data-task-title="<?php echo htmlspecialchars($task['title']); ?>">
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
</div>

<?php if (hasAnyRole(['admin', 'manager', 'supervisor', 'site_manager'])): ?>
<!-- Assign Task Modal -->
<div class="modal fade" id="assignTaskModal" tabindex="-1" aria-labelledby="assignTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignTaskModalLabel">Assign Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="assignTaskForm" method="POST" action="<?php echo url('/api/tasks/assign'); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="task_id" id="assign_task_id" value="">
                    <div class="mb-3">
                        <label for="assign_user_id" class="form-label">Assign To</label>
                        <select class="form-select" id="assign_user_id" name="user_id" required>
                            <option value="">Loading...</option>
                        </select>
                        <div class="form-text">Only approved subcontractors are listed.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="assignTaskSubmitBtn">Assign Task</button>
            </div>
        </div>
    </div>
    </div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteTaskModal" tabindex="-1" aria-labelledby="deleteTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteTaskModalLabel">Delete Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="deleteTaskText">Are you sure you want to delete this task? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteTaskBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
const CSRF_TOKEN = '<?php echo generateCSRFToken(); ?>';
let deleteTaskTarget = { id: null, row: null };

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-delete-task');
    if (!btn) return;
    const taskId = parseInt(btn.getAttribute('data-task-id'), 10);
    const title = btn.getAttribute('data-task-title') || '';
    deleteTaskTarget.id = taskId;
    deleteTaskTarget.row = btn.closest('tr');
    const textEl = document.getElementById('deleteTaskText');
    if (textEl) {
        textEl.textContent = `Are you sure you want to delete task "${title}"? This action cannot be undone.`;
    }
    const modal = new bootstrap.Modal(document.getElementById('deleteTaskModal'));
    modal.show();
});

document.getElementById('confirmDeleteTaskBtn')?.addEventListener('click', function() {
    if (!deleteTaskTarget.id) return;
    fetch('<?php echo url('/api/tasks/delete'); ?>?id=' + deleteTaskTarget.id, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ csrf_token: CSRF_TOKEN, id: deleteTaskTarget.id })
    })
    .then(r => r.json())
    .then(data => {
        const modalEl = document.getElementById('deleteTaskModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal?.hide();
        if (data.success) {
            if (deleteTaskTarget.row) {
                deleteTaskTarget.row.parentNode.removeChild(deleteTaskTarget.row);
            }
            showTaskAlert('success', data.message || 'Task deleted successfully.');
        } else {
            showTaskAlert('danger', data.message || 'Failed to delete task.');
        }
        deleteTaskTarget = { id: null, row: null };
    })
    .catch(() => {
        const modalEl = document.getElementById('deleteTaskModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal?.hide();
        showTaskAlert('danger', 'An error occurred while deleting the task.');
        deleteTaskTarget = { id: null, row: null };
    });
});

// Assign button click: open modal and load subcontractors
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-assign-task');
    if (!btn) return;
    const taskId = parseInt(btn.getAttribute('data-task-id'), 10);
    document.getElementById('assign_task_id').value = taskId;
    const select = document.getElementById('assign_user_id');
    select.innerHTML = '<option value="">Loading...</option>';
    fetch('<?php echo url('/api/site_manager/subcontractors.php'); ?>', { headers: { 'X-Requested-With':'XMLHttpRequest' } })
      .then(r => r.json())
      .then(d => {
        const list = (d && d.success && Array.isArray(d.data)) ? d.data : [];
        if (!list.length) {
            select.innerHTML = '<option value="">No subcontractors found</option>';
            return;
        }
        select.innerHTML = '<option value="">Select a subcontractor</option>';
        list.forEach(u => {
            const id = u.id;
            const name = u.name || u.email || ('User #' + id);
            const opt = document.createElement('option');
            opt.value = id; opt.textContent = name;
            select.appendChild(opt);
        });
      })
      .catch(() => { select.innerHTML = '<option value="">Failed to load subcontractors</option>'; });
    new bootstrap.Modal(document.getElementById('assignTaskModal')).show();
});

// Submit assign
document.getElementById('assignTaskSubmitBtn')?.addEventListener('click', function() {
    const form = document.getElementById('assignTaskForm');
    const formData = new FormData(form);
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(d => {
        const modalEl = document.getElementById('assignTaskModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal?.hide();
        if (d.success) {
            showTaskAlert('success', d.message || 'Task assigned successfully.');
            setTimeout(() => { location.reload(); }, 1000);
        } else {
            showTaskAlert('danger', d.message || 'Failed to assign task.');
        }
    })
    .catch(() => {
        const modalEl = document.getElementById('assignTaskModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal?.hide();
        showTaskAlert('danger', 'An error occurred while assigning the task.');
    });
});

function showTaskAlert(type, message) {
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