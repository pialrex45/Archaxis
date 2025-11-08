<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/SubContractorController.php';

requireAuth();
if (!hasRole('sub_contractor')) { http_response_code(403); die('Access denied. Sub-Contractors only.'); }

// Get all projects assigned to the sub-contractor
$controller = new SubContractorController();
$projectsResponse = $controller->projectsAssigned(100);
$projects = ($projectsResponse['success'] && isset($projectsResponse['data'])) ? $projectsResponse['data'] : [];

$pageTitle = 'My Projects';
$currentPage = 'projects';

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1>My Projects</h1>
  </div>

  <?php if (empty($projects)): ?>
    <div class="alert alert-info">
      <i class="fas fa-info-circle"></i> You don't have any assigned projects yet.
    </div>
  <?php else: ?>
    <!-- Projects Table -->
    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Projects</h6>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="projectsTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Status</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($projects as $project): ?>
                <tr>
                  <td><?php echo htmlspecialchars($project['id']); ?></td>
                  <td><?php echo htmlspecialchars($project['name']); ?></td>
                  <td>
                    <span class="badge bg-<?php 
                      echo $project['status'] === 'completed' ? 'success' : 
                          ($project['status'] === 'active' ? 'primary' : 
                          ($project['status'] === 'on hold' ? 'warning' : 'secondary')); 
                    ?>">
                      <?php echo htmlspecialchars(ucfirst($project['status'])); ?>
                    </span>
                  </td>
                  <td><?php echo htmlspecialchars($project['start_date'] ?? 'Not set'); ?></td>
                  <td><?php echo htmlspecialchars($project['end_date'] ?? 'Not set'); ?></td>
                  <td>
                    <a href="<?php echo url('/sub-contractor/project/' . $project['id']); ?>" class="btn btn-sm btn-info">
                      <i class="fas fa-eye"></i> View
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Add DataTables for better table functionality -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">

<script>
  $(document).ready(function() {
    $('#projectsTable').DataTable({
      "order": [[0, "desc"]]
    });
  });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
