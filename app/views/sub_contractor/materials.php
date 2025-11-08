<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/SubContractorController.php';
require_once __DIR__ . '/../../models/Project.php';

requireAuth();
if (!hasRole('sub_contractor')) { http_response_code(403); die('Access denied. Sub-Contractors only.'); }

// Get all materials for projects assigned to the sub-contractor
$controller = new SubContractorController();
$materialsResponse = $controller->materialsList();
$materials = ($materialsResponse['success'] && isset($materialsResponse['data'])) ? $materialsResponse['data'] : [];

// Get assigned projects for the dropdown
$projectsResponse = $controller->projectsAssigned(100);
$projects = ($projectsResponse['success'] && isset($projectsResponse['data'])) ? $projectsResponse['data'] : [];

$pageTitle = 'Materials';
$currentPage = 'materials';

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Materials</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#requestMaterialModal">
      <i class="fas fa-plus"></i> Request Material
    </button>
  </div>

  <!-- Status Counts -->
  <div class="row mb-4">
    <?php
      $requestedCount = 0;
      $approvedCount = 0;
      $rejectedCount = 0;
      $deliveredCount = 0;
      
      foreach ($materials as $material) {
        if ($material['status'] === 'requested') {
          $requestedCount++;
        } elseif ($material['status'] === 'approved') {
          $approvedCount++;
        } elseif ($material['status'] === 'rejected') {
          $rejectedCount++;
        } elseif ($material['status'] === 'delivered') {
          $deliveredCount++;
        }
      }
    ?>
    
    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card border-left-warning shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Requested</div>
              <div id="requestedCount" class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $requestedCount; ?></div>
            </div>
            <div class="col-auto">
              <i class="fas fa-clock fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card border-left-primary shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Approved</div>
              <div id="approvedCount" class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $approvedCount; ?></div>
            </div>
            <div class="col-auto">
              <i class="fas fa-thumbs-up fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card border-left-danger shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Rejected</div>
              <div id="rejectedCount" class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $rejectedCount; ?></div>
            </div>
            <div class="col-auto">
              <i class="fas fa-thumbs-down fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card border-left-success shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Delivered</div>
              <div id="deliveredCount" class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $deliveredCount; ?></div>
            </div>
            <div class="col-auto">
              <i class="fas fa-truck fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if (empty($materials)): ?>
    <div class="alert alert-info">
      <i class="fas fa-info-circle"></i> No material requests found.
    </div>
  <?php else: ?>
    <!-- Materials Table -->
    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Material Requests</h6>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="materialsTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Material</th>
                <th>Project</th>
                <th>Quantity</th>
                <th>Status</th>
                <th>Requested Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($materials as $material): 
                // Get project name
                $projectName = "";
                foreach ($projects as $project) {
                  if ($project['id'] == $material['project_id']) {
                    $projectName = $project['name'];
                    break;
                  }
                }
              ?>
                <tr>
                  <td><?php echo htmlspecialchars($material['id']); ?></td>
                  <td><?php echo htmlspecialchars($material['material_name']); ?></td>
                  <td><?php echo htmlspecialchars($projectName); ?></td>
                  <td><?php echo htmlspecialchars($material['quantity']); ?></td>
                  <td>
                    <span class="badge bg-<?php 
                      echo $material['status'] === 'delivered' ? 'success' : 
                          ($material['status'] === 'approved' ? 'primary' : 
                          ($material['status'] === 'rejected' ? 'danger' :
                          ($material['status'] === 'requested' ? 'warning' : 'secondary'))); 
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
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Request Material Modal -->
<div class="modal fade" id="requestMaterialModal" tabindex="-1" aria-labelledby="requestMaterialModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="requestMaterialModalLabel">Request Material</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="requestMaterialForm" action="<?php echo url('/api/sub_contractor/materials'); ?>" method="POST" onsubmit="return submitSCMaterialRequest(event);">
        <div class="modal-body">
          <input type="hidden" name="action" value="request">
          <div class="mb-3">
            <label for="project_id" class="form-label">Project</label>
            <select class="form-select" id="project_id" name="project_id" required>
              <option value="">Select Project</option>
              <?php foreach ($projects as $project): ?>
                <option value="<?php echo htmlspecialchars($project['id']); ?>">
                  <?php echo htmlspecialchars($project['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
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

<!-- Add DataTables for better table functionality -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">

<script>
  $(document).ready(function() {
    var materialsDataTable = $('#materialsTable').length ? $('#materialsTable').DataTable({
      order: [[0, 'desc']]
    }) : null;

    // Build a project ID -> name map for quick client lookup
    window.scProjectNameMap = <?php echo json_encode(array_reduce($projects, function($acc, $p){ $acc[$p['id']] = $p['name']; return $acc; }, [])); ?>;
  });

  // Plain JS submit handler to avoid navigating to API even if jQuery isn't available/binding fails
  async function submitSCMaterialRequest(e){
    if (e) e.preventDefault();
    try {
      var formEl = document.getElementById('requestMaterialForm');
      var action = formEl.getAttribute('action');
      var fd = new FormData(formEl);
      // Ensure action=request present
      if (!fd.get('action')) fd.set('action', 'request');
      const res = await fetch(action, { method: 'POST', body: fd });
      const j = await res.json();
      if (!j || !j.success){ alert('Error: ' + ((j && j.message) || 'Failed to submit material request')); return false; }

      // Update KPIs and table if DataTable exists
      var rqEl = document.getElementById('requestedCount');
      if (rqEl) { var v = parseInt(rqEl.textContent || '0', 10) || 0; rqEl.textContent = v + 1; }

      if (window.jQuery && jQuery.fn && jQuery.fn.dataTable) {
        var dt = jQuery('#materialsTable').length ? jQuery('#materialsTable').DataTable() : null;
        if (dt) {
          var pid = formEl.querySelector('#project_id').value;
          var pname = (window.scProjectNameMap && window.scProjectNameMap[pid]) ? window.scProjectNameMap[pid] : pid;
          var mname = formEl.querySelector('#material_name').value;
          var qty = formEl.querySelector('#quantity').value;
          var id = j.material_id || j.id || '—';
          var today = new Date();
          var created = today.getFullYear() + '-' + String(today.getMonth()+1).padStart(2,'0') + '-' + String(today.getDate()).padStart(2,'0');
          dt.row.add([
            id,
            escapeHtml(mname),
            escapeHtml(pname),
            escapeHtml(qty),
            '<span class="badge bg-warning">Requested</span>',
            escapeHtml(created)
          ]).draw(false);
        }
      }

      // Close modal and reset
      var modalEl = document.getElementById('requestMaterialModal');
      if (modalEl) { var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl); modal.hide(); }
      formEl.reset();
      return false;
    } catch (err) {
      alert('An error occurred while processing your request');
      return false;
    }
  }

  function escapeHtml(x){ return String(x).replace(/[&<>"]+/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s])); }
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
