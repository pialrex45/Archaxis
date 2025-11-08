<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/MaterialController.php';

// Check authentication
requireAuth();

// Set page title and current page
$pageTitle = 'Materials';
$currentPage = 'materials';

// Get project ID from URL parameter (optional)
$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

// Create MaterialController instance
$materialController = new MaterialController();

// Get materials
if ($projectId > 0) {
    // Get materials for specific project
    $result = $materialController->getByProject($projectId);
} else {
    // Get all materials
    $result = $materialController->getAll();
}

$materials = $result['success'] ? $result['data'] : [];
?>

<?php include_once __DIR__ . '/../../views/layouts/header.php'; ?>
<?php include_once __DIR__ . '/../../views/layouts/sidebar.php'; ?>

<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Materials</h1>
        <?php if (hasAnyRole(['admin', 'manager', 'supervisor'])): ?>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo url('/materials/request'); ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-plus"></i> Request Material
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
                    <th>Material</th>
                    <th>Project</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Requested By</th>
                    <th>Requested At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($materials)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No materials found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($materials as $material): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($material['material_name']); ?></td>
                            <td><?php echo htmlspecialchars($material['project_name']); ?></td>
                            <td><?php echo htmlspecialchars($material['quantity']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $material['status'] === 'approved' ? 'success' :
                                         ($material['status'] === 'delivered' ? 'primary' :
                                         ($material['status'] === 'rejected' ? 'danger' :
                                         ($material['status'] === 'ordered' ? 'info' : 'warning')));
                                ?>">
                                    <?php echo htmlspecialchars($material['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($material['requested_by_name']); ?></td>
                            <td><?php echo formatDate($material['created_at']); ?></td>
                            <td>
                                <a href="<?php echo url('/materials/show?id=' . $material['id']); ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <?php if (hasAnyRole(['admin', 'manager']) && $material['status'] === 'requested'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $material['id']; ?>">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                <?php endif; ?>
                                <?php if (hasAnyRole(['admin', 'manager'])): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-material" data-material-id="<?php echo (int)$material['id']; ?>" data-material-name="<?php echo htmlspecialchars($material['material_name']); ?>">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        
                        <?php if (hasAnyRole(['admin', 'manager']) && $material['status'] === 'requested'): ?>
                        <!-- Approve Modal -->
                        <div class="modal fade" id="approveModal<?php echo $material['id']; ?>" tabindex="-1" aria-labelledby="approveModalLabel<?php echo $material['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="approveModalLabel<?php echo $material['id']; ?>">Approve Material Request</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Are you sure you want to approve this material request?</p>
                                        <form id="approveForm<?php echo $material['id']; ?>" method="POST" action="<?php echo url('/api/materials/approve?id=' . $material['id']); ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-success" onclick="document.getElementById('approveForm<?php echo $material['id']; ?>').submit()">Approve</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (hasAnyRole(['admin', 'manager'])): ?>
<!-- Delete Material Modal -->
<div class="modal fade" id="deleteMaterialModal" tabindex="-1" aria-labelledby="deleteMaterialModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteMaterialModalLabel">Delete Material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="deleteMaterialText">Are you sure you want to delete this material request? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteMaterialBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
const MAT_CSRF = '<?php echo generateCSRFToken(); ?>';
let deleteMaterial = { id: null, row: null };

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-delete-material');
    if (!btn) return;
    deleteMaterial.id = parseInt(btn.getAttribute('data-material-id'), 10);
    deleteMaterial.row = btn.closest('tr');
    const name = btn.getAttribute('data-material-name') || '';
    const txt = document.getElementById('deleteMaterialText');
    if (txt) txt.textContent = `Are you sure you want to delete material "${name}"? This action cannot be undone.`;
    const modal = new bootstrap.Modal(document.getElementById('deleteMaterialModal'));
    modal.show();
});

document.getElementById('confirmDeleteMaterialBtn')?.addEventListener('click', function() {
    if (!deleteMaterial.id) return;
    fetch('<?php echo url('/api/materials/delete'); ?>?id=' + deleteMaterial.id, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ csrf_token: MAT_CSRF, id: deleteMaterial.id })
    })
    .then(r => r.json())
    .then(data => {
        const modalEl = document.getElementById('deleteMaterialModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal?.hide();
        if (data.success) {
            if (deleteMaterial.row) deleteMaterial.row.remove();
            showMaterialAlert('success', data.message || 'Material deleted successfully.');
        } else {
            showMaterialAlert('danger', data.message || 'Failed to delete material.');
        }
        deleteMaterial = { id: null, row: null };
    })
    .catch(() => {
        const modalEl = document.getElementById('deleteMaterialModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal?.hide();
        showMaterialAlert('danger', 'An error occurred while deleting the material.');
        deleteMaterial = { id: null, row: null };
    });
});

function showMaterialAlert(type, message) {
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