<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/MaterialController.php';
require_once __DIR__ . '/../../controllers/ProjectController.php';

// Check authentication and role
requireAuth();
if (!hasRole('worker')) {
    http_response_code(403);
    die('Access denied. Workers only.');
}

// Get material requests data
$materialController = new MaterialController();
$userId = getCurrentUserId();
$materialsData = $materialController->getRequestsByUser($userId);

// Get projects for requesting materials
$projectController = new ProjectController();
$projectsData = $projectController->getAll();

$pageTitle = 'Material Requests';
$currentPage = 'materials';
?>

<?php include_once __DIR__ . '/../../views/layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Material Requests</h1>
        </div>
        <div class="col-md-4 text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                <i class="bi bi-plus-lg"></i> New Material Request
            </button>
        </div>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Material request submitted successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">My Material Requests</h5>
                    <div>
                        <button class="btn btn-sm btn-primary" onclick="filterRequests('all')">All</button>
                        <button class="btn btn-sm btn-warning" onclick="filterRequests('pending')">Pending</button>
                        <button class="btn btn-sm btn-success" onclick="filterRequests('approved')">Approved</button>
                        <button class="btn btn-sm btn-info" onclick="filterRequests('delivered')">Delivered</button>
                        <button class="btn btn-sm btn-danger" onclick="filterRequests('rejected')">Rejected</button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($materialsData['data'])): ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="materialsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Project</th>
                                        <th>Material</th>
                                        <th>Quantity</th>
                                        <th>Status</th>
                                        <th>Requested On</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($materialsData['data'] as $material): ?>
                                        <tr class="material-row" data-status="<?php echo htmlspecialchars($material['status']); ?>">
                                            <td><?php echo htmlspecialchars($material['id']); ?></td>
                                            <td><?php echo htmlspecialchars($material['project_name']); ?></td>
                                            <td><?php echo htmlspecialchars($material['material_name']); ?></td>
                                            <td><?php echo htmlspecialchars($material['quantity']); ?> <?php echo htmlspecialchars($material['unit'] ?? ''); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $material['status'] === 'approved' ? 'success' : 
                                                         ($material['status'] === 'delivered' ? 'info' : 
                                                         ($material['status'] === 'rejected' ? 'danger' : 'warning')); ?>">
                                                    <?php echo htmlspecialchars($material['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($material['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($material['notes'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No material requests found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Material Request Modal -->
<div class="modal fade" id="newRequestModal" tabindex="-1" aria-labelledby="newRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newRequestModalLabel">Request Materials</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="materialRequestForm">
                    <div class="mb-3">
                        <label for="project_id" class="form-label">Project</label>
                        <select class="form-select" id="project_id" name="project_id" required>
                            <option value="" selected disabled>Select a project</option>
                            <?php if (!empty($projectsData['data'])): ?>
                                <?php foreach ($projectsData['data'] as $project): ?>
                                    <option value="<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['name']); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="material_name" class="form-label">Material Name</label>
                        <input type="text" class="form-control" id="material_name" name="material_name" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="1" required>
                        </div>
                        <div class="col-md-4">
                            <label for="unit" class="form-label">Unit</label>
                            <select class="form-select" id="unit" name="unit">
                                <option value="pcs">pcs</option>
                                <option value="kg">kg</option>
                                <option value="ltr">ltr</option>
                                <option value="m">m</option>
                                <option value="m²">m²</option>
                                <option value="m³">m³</option>
                                <option value="bags">bags</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes/Description</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="required_by" class="form-label">Required By</label>
                        <input type="date" class="form-control" id="required_by" name="required_by">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitMaterialRequest()">Submit Request</button>
            </div>
        </div>
    </div>
</div>

<script>
function submitMaterialRequest() {
    const form = document.getElementById('materialRequestForm');
    const formData = new FormData(form);
    
    fetch('../api/materials/request.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'material_requests.php?success=1';
        } else {
            window.location.href = 'material_requests.php?error=' + encodeURIComponent(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        window.location.href = 'material_requests.php?error=Failed to submit material request';
    });
}

function filterRequests(status) {
    const rows = document.querySelectorAll('.material-row');
    
    rows.forEach(row => {
        if (status === 'all' || row.dataset.status === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php include_once __DIR__ . '/../../views/layouts/footer.php'; ?>
