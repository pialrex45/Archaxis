<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/TaskController.php';

// Check authentication
requireAuth();

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

// Set page title and current page
$pageTitle = 'Task Details: ' . $task['title'];
$currentPage = 'tasks';
?>

<?php include_once __DIR__ . '/../../views/layouts/header.php'; ?>
<?php include_once __DIR__ . '/../../views/layouts/sidebar.php'; ?>

<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Task: <?php echo htmlspecialchars($task['title']); ?></h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <?php if (hasAnyRole(['admin', 'manager', 'supervisor', 'site_manager'])): ?>
                <a href="<?php echo url('/tasks/edit?id=' . $task['id']); ?>" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="fas fa-edit"></i> Edit
                </a>
            <?php endif; ?>
            <a href="<?php echo url('/tasks'); ?>" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Tasks
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Task Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-3"><strong>Title:</strong></div>
                        <div class="col-sm-9"><?php echo htmlspecialchars($task['title']); ?></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><strong>Description:</strong></div>
                        <div class="col-sm-9"><?php echo nl2brSafe($task['description']); ?></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><strong>Project:</strong></div>
                        <div class="col-sm-9"><?php echo htmlspecialchars($task['project_name']); ?></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><strong>Assigned To:</strong></div>
                        <div class="col-sm-9"><?php echo htmlspecialchars($task['assigned_to_name'] ?? 'Unassigned'); ?></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><strong>Status:</strong></div>
                        <div class="col-sm-9">
                            <span class="badge bg-<?php 
                                echo $task['status'] === 'completed' ? 'success' : 
                                     ($task['status'] === 'in progress' ? 'primary' : 
                                     ($task['status'] === 'cancelled' ? 'danger' : 'warning')); ?>">
                                <?php echo htmlspecialchars($task['status']); ?>
                            </span>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><strong>Priority:</strong></div>
                        <div class="col-sm-9">
                            <span class="badge bg-<?php 
                                echo $task['priority'] === 'urgent' ? 'danger' : 
                                     ($task['priority'] === 'high' ? 'warning' : 
                                     ($task['priority'] === 'low' ? 'secondary' : 'primary')); ?>">
                                <?php echo htmlspecialchars($task['priority']); ?>
                            </span>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><strong>Due Date:</strong></div>
                        <div class="col-sm-9"><?php echo $task['due_date'] ? formatDate($task['due_date']) : 'N/A'; ?></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><strong>Created:</strong></div>
                        <div class="col-sm-9"><?php echo formatDate($task['created_at']); ?></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><strong>Last Updated:</strong></div>
                        <div class="col-sm-9"><?php echo formatDate($task['updated_at']); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Task Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if (hasAnyRole(['admin', 'manager', 'supervisor', 'site_manager'])): ?>
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                                <i class="fas fa-sync-alt"></i> Update Status
                            </button>
                        <?php endif; ?>
                        <?php if ($task['assigned_to'] == getCurrentUserId() || hasAnyRole(['admin', 'manager', 'supervisor', 'site_manager'])): ?>
                            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#assignTaskModal">
                                <i class="fas fa-user-tag"></i> Assign Task
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if (hasAnyRole(['admin', 'manager', 'supervisor'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Admin Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteTaskModal">
                            <i class="fas fa-trash"></i> Delete Task
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (hasAnyRole(['admin', 'manager', 'supervisor', 'site_manager'])): ?>
<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStatusModalLabel">Update Task Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateStatusForm" method="POST" action="<?php echo url('/api/tasks/update_status?id=' . $task['id']); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="pending" <?php echo $task['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in progress" <?php echo $task['status'] === 'in progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo $task['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $task['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
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
                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                    
                    <div class="mb-3">
                        <label for="user_id" class="form-label">Assign To</label>
                        <select class="form-select" id="user_id" name="user_id" required>
                            <option value="">Loading...</option>
                        </select>
                        <div class="form-text">Only approved subcontractors are listed.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="assignTaskBtn">Assign Task</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Task Modal -->
<div class="modal fade" id="deleteTaskModal" tabindex="-1" aria-labelledby="deleteTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteTaskModalLabel">Delete Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this task? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="deleteTaskBtn">Delete Task</button>
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
        const modalEl = document.getElementById('updateStatusModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal?.hide();
        if (data.success) {
            showTaskAlert('success', data.message || 'Status updated.');
            setTimeout(() => { location.reload(); }, 1200);
        } else {
            showTaskAlert('danger', data.message || 'Failed to update status.');
        }
    })
    .catch(error => {
        const modalEl = document.getElementById('updateStatusModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal?.hide();
        showTaskAlert('danger', 'An error occurred while updating the task status.');
    });
});

// Assignment handler (resilient to ID changes)
(function(){
  const primaryBtn = document.getElementById('assignTaskBtn');
  const altBtn = document.getElementById('assignTaskSubmitBtn'); // in case reused markup
  const handler = (ev) => {
    ev.preventDefault();
    const btn = ev.currentTarget;
    if (btn.dataset.submitting === '1') return; // prevent double submit
    btn.dataset.submitting = '1';
    btn.disabled = true;
    const form = document.getElementById('assignTaskForm');
    if (!form) { console.warn('Assign form missing'); return; }
    const formData = new FormData(form);
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        const modalEl = document.getElementById('assignTaskModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal?.hide();
        if (data.success) {
            showTaskAlert('success', data.message || 'Task assigned.');
            setTimeout(() => { location.reload(); }, 1000);
        } else {
            showTaskAlert('danger', data.message || 'Failed to assign task.');
        }
    })
    .catch(err => {
        showTaskAlert('danger', 'Error assigning task.');
    })
    .finally(() => { btn.disabled = false; delete btn.dataset.submitting; });
  };
  if (primaryBtn) primaryBtn.addEventListener('click', handler);
  if (altBtn) altBtn.addEventListener('click', handler);
})();

// Subcontractor loading (reusable)
function loadSubcontractors(selectEl) {
    if (!selectEl) return;
    selectEl.innerHTML = '<option value="">Loading...</option>';
    fetch('<?php echo url('/api/site_manager/subcontractors.php'); ?>?debug=1', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json().catch(()=>({success:false,message:'Invalid JSON'})))
        .then(d => {
            console.log('[assign] subcontractors response', d);
            const list = (d && d.success && Array.isArray(d.data)) ? d.data : [];
            if (!list.length) {
                selectEl.innerHTML = '<option value="">No subcontractors found</option>';
                return;
            }
            selectEl.innerHTML = '<option value="">Select a subcontractor</option>';
            list.forEach(u => {
                const id = u.id;
                const name = u.name || u.email || ('User #' + id);
                const opt = document.createElement('option');
                opt.value = id;
                opt.textContent = name;
                selectEl.appendChild(opt);
            });
        })
        .catch(err => {
            console.warn('[assign] subcontractor load failed', err);
            selectEl.innerHTML = '<option value="">Failed to load subcontractors</option>';
        });
}

// Load on modal show AND eagerly after DOM ready
const assignModalEl = document.getElementById('assignTaskModal');
assignModalEl.addEventListener('show.bs.modal', function() { loadSubcontractors(document.getElementById('user_id')); });
document.addEventListener('DOMContentLoaded', () => {
    // Preload so list ready on first open
    loadSubcontractors(document.getElementById('user_id'));
});

// Capture-phase click debug (in case overlay intercepts)
document.addEventListener('click', (e) => {
    if (e.target && (e.target.id === 'assignTaskBtn' || e.target.id === 'assignTaskSubmitBtn')) {
        console.log('[assign] button click captured');
    }
}, true);

document.getElementById('deleteTaskBtn').addEventListener('click', function() {
    const taskId = <?php echo $task['id']; ?>;
    
    fetch('<?php echo url('/api/tasks/delete?id=' . $task['id']); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            csrf_token: '<?php echo generateCSRFToken(); ?>',
            id: taskId
        })
    })
    .then(response => response.json())
    .then(data => {
        const modalEl = document.getElementById('deleteTaskModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal?.hide();
        if (data.success) {
            showTaskAlert('success', data.message || 'Task deleted.');
            setTimeout(() => { window.location.href = '<?php echo url('/tasks'); ?>'; }, 1200);
        } else {
            showTaskAlert('danger', data.message || 'Failed to delete task.');
        }
    })
    .catch(error => {
        const modalEl = document.getElementById('deleteTaskModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal?.hide();
        showTaskAlert('danger', 'An error occurred while deleting the task.');
    });
});

function showTaskAlert(type, message) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    const container = document.querySelector('.col-md-9') || document.querySelector('.col-lg-10') || document.body;
    const anchor = document.querySelector('.border-bottom') || container.firstChild;
    container.insertBefore(alert, anchor);
    setTimeout(() => { try { new bootstrap.Alert(alert).close(); } catch(e){} }, 3000);
}
</script>
<?php endif; ?>

<?php include_once __DIR__ . '/../../views/layouts/footer.php'; ?>
</content>