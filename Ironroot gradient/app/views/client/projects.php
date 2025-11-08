<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth();
requireRole('client');

$pageTitle = 'Client Projects';
$currentPage = 'client_projects';

include_once __DIR__ . '/../layouts/header.php';
include_once __DIR__ . '/../layouts/sidebar.php';
?>
<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
  <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Projects</h1>
    <a href="<?php echo url('/client/projects/create'); ?>" class="btn btn-sm btn-primary">
      <i class="fas fa-plus"></i> New Project
    </a>
  </div>

  <div class="table-responsive">
    <table class="table table-striped align-middle" id="clientProjectsTable">
      <thead>
        <tr>
          <th>Name</th>
          <th>Status</th>
          <th>Milestones</th>
          <th>Deadline</th>
          <th>Progress</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>
<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
<script>
const apiClientProjects = '<?php echo url('/api/client/projects'); ?>';
const progressBar = (p)=>{
  const val = Math.max(0, Math.min(100, Number(p||0)));
  return `<div class="progress" style="height: 8px;">
    <div class="progress-bar" role="progressbar" style="width:${val}%" aria-valuenow="${val}" aria-valuemin="0" aria-valuemax="100"></div>
  </div>`;
};
fetch(apiClientProjects, {headers: {'X-Requested-With':'XMLHttpRequest'}})
  .then(r=>r.json()).then(json=>{
    const rows = (json.data||[]).map(p=>{
      const milestones = (p.milestones||[]).map(m=>m.title||m.name||'').slice(0,3).join(', ');
      const deadline = p.deadline || p.due_date || '';
      const status = (p.status||'').toString();
      const prog = p.progress ?? p.completion ?? null;
      return `<tr>
        <td>${p.name||p.title||''}</td>
        <td><span class="badge bg-secondary">${status}</span></td>
        <td>${milestones||'<span class="text-muted">-</span>'}</td>
        <td>${deadline? new Date(deadline).toLocaleDateString(): ''}</td>
        <td>${progressBar(prog)}</td>
      </tr>`;
    }).join('');
    document.querySelector('#clientProjectsTable tbody').innerHTML = rows || `<tr><td colspan="5" class="text-center text-muted">No projects</td></tr>`;
  });
</script>
