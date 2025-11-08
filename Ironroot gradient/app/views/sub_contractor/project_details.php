<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/SubContractorController.php';

requireAuth();
if (!hasRole('sub_contractor')) { http_response_code(403); die('Access denied. Sub-Contractors only.'); }

// Get the project ID from the URL
$parts = explode('/', $_SERVER['REQUEST_URI']);
$projectId = (int)end($parts);

if (!$projectId) {
    setFlashMessage('Invalid project ID', 'danger');
    redirect('/sub-contractor/projects');
    exit;
}

// Get project details
$controller = new SubContractorController();
$projectResponse = $controller->projectSnapshot($projectId);

if (!$projectResponse['success'] || !isset($projectResponse['data'])) {
    setFlashMessage('Project not found or you do not have access', 'danger');
    redirect('/sub-contractor/projects');
    exit;
}

$project = $projectResponse['data'];

// Get tasks for this project
$taskModel = new Task();
$tasks = $taskModel->getByProjectForUser($projectId, getCurrentUserId());

// Get materials for this project
$materialModel = new Material();
$materials = $materialModel->getByProject($projectId);

$pageTitle = 'Project Details: ' . htmlspecialchars($project['name']);
$currentPage = 'projects';

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Project: <?php echo htmlspecialchars($project['name']); ?></h1>
    <a href="<?php echo url('/sub-contractor/projects'); ?>" class="btn btn-outline-primary">
      <i class="fas fa-arrow-left"></i> Back to Projects
    </a>
  </div>

  <!-- Project Information -->
  <div class="row mb-4">
    <div class="col-lg-12">
      <div class="card shadow">
        <div class="card-header py-3">
          <h6 class="m-0 font-weight-bold text-primary">Project Information</h6>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <p><strong>ID:</strong> <?php echo htmlspecialchars($project['id']); ?></p>
              <p><strong>Name:</strong> <?php echo htmlspecialchars($project['name']); ?></p>
              <p><strong>Status:</strong> 
                <span class="badge bg-<?php 
                  echo $project['status'] === 'completed' ? 'success' : 
                      ($project['status'] === 'active' ? 'primary' : 
                      ($project['status'] === 'on hold' ? 'warning' : 'secondary')); 
                ?>">
                  <?php echo htmlspecialchars(ucfirst($project['status'])); ?>
                </span>
              </p>
            </div>
            <div class="col-md-6">
              <p><strong>Start Date:</strong> <?php echo htmlspecialchars($project['start_date'] ?? 'Not set'); ?></p>
              <p><strong>End Date:</strong> <?php echo htmlspecialchars($project['end_date'] ?? 'Not set'); ?></p>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-12">
              <h6 class="font-weight-bold">Description</h6>
              <p><?php echo nl2br(htmlspecialchars($project['description'] ?? 'No description available.')); ?></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tasks Section -->
  <div class="row mb-4">
    <div class="col-lg-12">
      <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
          <h6 class="m-0 font-weight-bold text-primary">Tasks</h6>
        </div>
        <div class="card-body">
          <?php if (empty($tasks)): ?>
            <p class="text-center">No tasks assigned for this project.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-bordered" id="tasksTable" width="100%" cellspacing="0">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Due Date</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($tasks as $task): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($task['id']); ?></td>
                      <td><?php echo htmlspecialchars($task['title']); ?></td>
                      <td>
                        <span class="badge bg-<?php 
                          echo $task['status'] === 'completed' ? 'success' : 
                              ($task['status'] === 'in progress' ? 'primary' : 
                              ($task['status'] === 'pending' ? 'warning' : 'secondary')); 
                        ?>">
                          <?php echo htmlspecialchars(ucfirst($task['status'])); ?>
                        </span>
                      </td>
                      <td><?php echo htmlspecialchars($task['due_date'] ?? 'Not set'); ?></td>
                      <td>
                        <a href="<?php echo url('/sub-contractor/task/' . $task['id']); ?>" class="btn btn-sm btn-info">
                          <i class="fas fa-eye"></i> View
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Materials Section -->
  <div class="row mb-4">
    <div class="col-lg-12">
      <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
          <h6 class="m-0 font-weight-bold text-primary">Materials</h6>
          <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#requestMaterialModal">
            <i class="fas fa-plus"></i> Request Material
          </button>
        </div>
        <div class="card-body">
          <?php if (empty($materials)): ?>
            <p class="text-center">No materials requested for this project.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-bordered" id="materialsTable" width="100%" cellspacing="0">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Material</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Requested At</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($materials as $material): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($material['id']); ?></td>
                      <td><?php echo htmlspecialchars($material['material_name']); ?></td>
                      <td><?php echo htmlspecialchars($material['quantity']); ?></td>
                      <td>
                        <span class="badge bg-<?php 
                          echo $material['status'] === 'delivered' ? 'success' : 
                              ($material['status'] === 'approved' ? 'primary' : 
                              ($material['status'] === 'requested' ? 'warning' : 'secondary')); 
                        ?>">
                          <?php echo htmlspecialchars(ucfirst($material['status'])); ?>
                        </span>
                      </td>
                      <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($material['created_at']))); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Request Material Modal -->
<div class="modal fade" id="requestMaterialModal" tabindex="-1" aria-labelledby="requestMaterialModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="requestMaterialModalLabel">Request Material</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="requestMaterialForm" action="<?php echo url('/api/sub_contractor/materials'); ?>" method="POST">
        <div class="modal-body">
          <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($project['id']); ?>">
          <div class="mb-3">
            <label for="material_name" class="form-label">Material Name</label>
            <input type="text" class="form-control" id="material_name" name="material_name" required>
          </div>
          <div class="mb-3">
            <label for="quantity" class="form-label">Quantity</label>
            <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="1" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Submit Request</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- DataTables Script -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">

<script>
  $(document).ready(function() {
    // Initialize DataTables
    $('#tasksTable').DataTable({
      "order": [[0, "desc"]]
    });
    
    $('#materialsTable').DataTable({
      "order": [[0, "desc"]]
    });
    
    // Handle material request form submission
    $('#requestMaterialForm').on('submit', function(e) {
      e.preventDefault();
      
      $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            alert('Material request submitted successfully');
            location.reload();
          } else {
            alert('Error: ' + (response.message || 'Failed to submit material request'));
          }
        },
        error: function() {
          alert('An error occurred while processing your request');
        }
      });
    });
  });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
