<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/MaterialController.php';

// Check authentication
requireAuth();

// Get material ID from URL parameter
$materialId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($materialId)) {
    http_response_code(400);
    die('Material ID is required');
}

// Create MaterialController instance
$materialController = new MaterialController();

// Get material details
$result = $materialController->get($materialId);

if (!$result['success']) {
    http_response_code(404);
    die('Material not found');
}

$material = $result['data'];

// Set page title and current page
$pageTitle = 'Material Details: ' . $material['material_name'];
$currentPage = 'materials';
?>

<?php include_once __DIR__ . '/../../views/layouts/header.php'; ?>
<?php include_once __DIR__ . '/../../views/layouts/sidebar.php'; ?>

<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Material: <?php echo htmlspecialchars($material['material_name']); ?></h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo url('/materials'); ?>" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Materials
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Material Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-3"><strong>Material:</strong></div>
                        <div class="col-sm-9"><?php echo htmlspecialchars($material['material_name']); ?></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><strong>Project:</strong></div>
                        <div class="col-sm-9"><?php echo htmlspecialchars($material['project_name']); ?></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><strong>Quantity:</strong></div>
                        <div class="col-sm-9"><?php echo htmlspecialchars($material['quantity']); ?></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><strong>Status:</strong></div>
                        <div class="col-sm-9">
                            <span class="badge bg-<?php 
                                echo $material['status'] === 'approved' ? 'success' : 
                                     ($material['status'] === 'delivered' ? 'primary' : 
                                     ($material['status'] === 'rejected' ? 'danger' : 
                                     ($material['status'] === 'ordered' ? 'info' : 'warning'))); ?>">
                                <?php echo htmlspecialchars($material['status']); ?>
                            </span>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><strong>Requested By:</strong></div>
                        <div class="col-sm-9"><?php echo htmlspecialchars($material['requested_by_name']); ?></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><strong>Requested At:</strong></div>
                        <div class="col-sm-9"><?php echo formatDate($material['created_at']); ?></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><strong>Last Updated:</strong></div>
                        <div class="col-sm-9"><?php echo formatDate($material['updated_at']); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if (hasAnyRole(['admin', 'manager']) && $material['status'] === 'requested'): ?>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                                <i class="fas fa-check"></i> Approve Request
                            </button>
                        <?php endif; ?>
                        <?php if (hasAnyRole(['admin', 'manager']) && $material['status'] === 'approved'): ?>
                            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#markOrderedModal">
                                <i class="fas fa-truck"></i> Mark as Ordered
                            </button>
                        <?php endif; ?>
                        <?php if (hasAnyRole(['admin', 'manager']) && $material['status'] === 'ordered'): ?>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#markDeliveredModal">
                                <i class="fas fa-box"></i> Mark as Delivered
                            </button>
                        <?php endif; ?>
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
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteMaterialModal">
                            <i class="fas fa-trash"></i> Delete Request
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (hasAnyRole(['admin', 'manager'])): ?>
<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approveModalLabel">Approve Material Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to approve this material request?</p>
                <form id="approveForm" method="POST" action="<?php echo url('/api/materials/approve?id=' . $material['id']); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="document.getElementById('approveForm').submit()">Approve</button>
            </div>
        </div>
    </div>
</div>

<!-- Mark Ordered Modal -->
<div class="modal fade" id="markOrderedModal" tabindex="-1" aria-labelledby="markOrderedModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="markOrderedModalLabel">Mark as Ordered</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to mark this material as ordered?</p>
                <form id="markOrderedForm" method="POST" action="<?php echo url('/api/materials/update?id=' . $material['id']); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="status" value="ordered">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info" onclick="document.getElementById('markOrderedForm').submit()">Mark as Ordered</button>
            </div>
        </div>
    </div>
</div>

<!-- Mark Delivered Modal -->
<div class="modal fade" id="markDeliveredModal" tabindex="-1" aria-labelledby="markDeliveredModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="markDeliveredModalLabel">Mark as Delivered</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to mark this material as delivered?</p>
                <form id="markDeliveredForm" method="POST" action="<?php echo url('/api/materials/update?id=' . $material['id']); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="status" value="delivered">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('markDeliveredForm').submit()">Mark as Delivered</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Material Modal -->
<div class="modal fade" id="deleteMaterialModal" tabindex="-1" aria-labelledby="deleteMaterialModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteMaterialModalLabel">Delete Material Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this material request? This action cannot be undone.</p>
                <form id="deleteMaterialForm" method="POST" action="<?php echo url('/api/materials/delete?id=' . $material['id']); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('deleteMaterialForm').submit()">Delete Request</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include_once __DIR__ . '/../../views/layouts/footer.php'; ?>
</content>
<line_count