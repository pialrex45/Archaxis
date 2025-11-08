<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/SubContractorController.php';
// Ensure models are available (avoid relying on autoload side-effects)
require_once __DIR__ . '/../../models/Task.php';
require_once __DIR__ . '/../../models/Material.php';

// Provide a local flash helper fallback if global one isn't loaded yet
if (!function_exists('setFlashMessage')) {
  function setFlashMessage($msg, $type='info') { if(session_status()!==PHP_SESSION_ACTIVE){@session_start();} $_SESSION['flash'][]=['message'=>$msg,'type'=>$type]; }
}
if (!function_exists('redirect')) {
  function redirect($path){ header('Location: ' . $path); exit; }
}

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

// Get tasks for this project (filter to those assigned to current user or all if none found)
$taskModel = new Task();
$allProjectTasks = $taskModel->getByProject($projectId) ?: [];
$currentUserId = getCurrentUserId();
$tasks = array_values(array_filter($allProjectTasks, function($t) use ($currentUserId){
  return isset($t['assigned_to']) && (int)$t['assigned_to'] === (int)$currentUserId;
}));
if (empty($tasks)) { $tasks = $allProjectTasks; } // fallback: show all project tasks if none specifically assigned

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
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Project Information</h6>
            <button type="button" id="scProjectRefresh" class="btn btn-sm btn-outline-secondary"><i class="fas fa-rotate"></i> Refresh</button>
          </div>
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
          <h6 class="m-0 font-weight-bold text-primary mb-0">Tasks</h6>
          <div class="d-flex gap-2">
            <button type="button" id="scTasksRefresh" class="btn btn-sm btn-outline-secondary"><i class="fas fa-rotate"></i> Refresh</button>
          </div>
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
          <h6 class="m-0 font-weight-bold text-primary mb-0">Materials</h6>
          <div class="d-flex gap-2">
            <button type="button" id="scMaterialsRefresh" class="btn btn-sm btn-outline-secondary"><i class="fas fa-rotate"></i> Refresh</button>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#requestMaterialModal">
              <i class="fas fa-plus"></i> Request Material
            </button>
          </div>
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

    // --- Dynamic Refresh Logic (basic old-style approach) ---
    const projectId = <?php echo (int)$project['id']; ?>;
    const baseUrl = '<?php echo rtrim(url('/api/sub_contractor/projects'),'/'); ?>';
    const refreshBtn = document.getElementById('scProjectRefresh');
    const tasksBtn = document.getElementById('scTasksRefresh');
    const materialsBtn = document.getElementById('scMaterialsRefresh');
    let tasksDT = $.fn.DataTable.isDataTable('#tasksTable') ? $('#tasksTable').DataTable() : null;
    let materialsDT = $.fn.DataTable.isDataTable('#materialsTable') ? $('#materialsTable').DataTable() : null;

    function badge(status){
      const s = (status||'').toLowerCase();
      let cls='secondary';
      if(s==='completed'||s==='delivered') cls='success';
      else if(s==='active'||s==='approved'||s==='in progress') cls='primary';
      else if(s==='pending'||s==='requested'||s==='on hold') cls='warning';
      return '<span class="badge bg-'+cls+'">'+(s.replace(/\b\w/g,c=>c.toUpperCase())||'—')+'</span>';
    }

    function loadProject(part){
      const url = baseUrl + '/' + projectId;
      (part==='tasks'?tasksBtn: part==='materials'?materialsBtn:refreshBtn)?.classList.add('disabled');
      fetch(url).then(r=>r.json()).then(j=>{
        if(!j||!j.success){ alert('Refresh failed'); return; }
        const d = j.data||{};
        if(!part||part==='tasks'){
          if(tasksDT){ tasksDT.clear(); }
          (d.tasks||[]).forEach(t=>{
            const row=[t.id, $('<div/>').text(t.title||'').html(), badge(t.status), (t.due_date||'Not set'), '<a href="<?php echo url('/sub-contractor/task/'); ?>'+t.id+'" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> View</a>'];
            tasksDT?tasksDT.row.add(row):null;
          });
          tasksDT && tasksDT.draw(false);
        }
        if(!part||part==='materials'){
          if(materialsDT){ materialsDT.clear(); }
          (d.materials||[]).forEach(m=>{
            const row=[m.id, $('<div/>').text(m.material_name||'').html(), $('<div/>').text(m.quantity||'').html(), badge(m.status), (m.created_at?m.created_at.substr(0,10):'')];
            materialsDT?materialsDT.row.add(row):null;
          });
          materialsDT && materialsDT.draw(false);
        }
      }).catch(()=>alert('Network error refreshing')).finally(()=>{
        refreshBtn?.classList.remove('disabled');
        tasksBtn?.classList.remove('disabled');
        materialsBtn?.classList.remove('disabled');
      });
    }
    refreshBtn && refreshBtn.addEventListener('click', ()=>loadProject());
    tasksBtn && tasksBtn.addEventListener('click', ()=>loadProject('tasks'));
    materialsBtn && materialsBtn.addEventListener('click', ()=>loadProject('materials'));
  });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
