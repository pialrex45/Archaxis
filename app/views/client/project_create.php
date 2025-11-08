<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth();
requireRole('client');

$pageTitle = 'Create Project';
$currentPage = 'client_projects';
$csrf = function_exists('generate_csrf_token') ? generate_csrf_token() : '';

include_once __DIR__ . '/../layouts/header.php';
include_once __DIR__ . '/../layouts/sidebar.php';
?>
<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
  <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">New Project</h1>
    <a href="<?php echo url('/client/projects'); ?>" class="btn btn-sm btn-outline-secondary">Back</a>
  </div>

  <div class="row">
    <div class="col-md-8 col-lg-7">
      <div class="card">
        <div class="card-body">
          <form id="createProjectForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
            <div class="mb-3">
              <label class="form-label">Project Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="name" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description" rows="4"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">Deadline</label>
              <input type="date" class="form-control" name="deadline">
            </div>
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">Create</button>
              <a href="<?php echo url('/client/projects'); ?>" class="btn btn-light">Cancel</a>
            </div>
          </form>
          <div id="createProjectAlert" class="alert mt-3 d-none" role="alert"></div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
<script>
const createUrl = '<?php echo url('/api/client/project_create'); ?>';
const form = document.getElementById('createProjectForm');
const alertBox = document.getElementById('createProjectAlert');

function showAlert(type, msg){
  alertBox.className = 'alert mt-3 alert-' + type;
  alertBox.textContent = msg;
  alertBox.classList.remove('d-none');
}

form.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const data = new FormData(form);
  try {
    const res = await fetch(createUrl, { method: 'POST', body: data, headers: {'X-Requested-With':'XMLHttpRequest'} });
    const json = await res.json();
    if(json.success){
      showAlert('success', 'Project created successfully. Redirecting...');
      setTimeout(()=>{ window.location.href = '<?php echo url('/client/projects'); ?>'; }, 1000);
    } else {
      showAlert('danger', json.message || 'Failed to create project');
    }
  } catch(err){
    showAlert('danger', 'Network error: ' + err.message);
  }
});
</script>
