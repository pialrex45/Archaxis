<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth();
requireRole('client');

$pageTitle = 'Client Tasks';
$currentPage = 'client_tasks';

include_once __DIR__ . '/../layouts/header.php';
include_once __DIR__ . '/../layouts/sidebar.php';
?>
<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
  <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Tasks</h1>
    <div>
      <select id="statusFilter" class="form-select form-select-sm" style="width:auto;">
        <option value="">All</option>
        <option>pending</option>
        <option>in progress</option>
        <option>completed</option>
      </select>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-striped align-middle" id="clientTasksTable">
      <thead>
        <tr>
          <th>Title</th>
          <th>Phase/Team</th>
          <th>Assignee</th>
          <th>Deadline</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>
<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
<script>
const apiClientTasks = '<?php echo url('/api/client/tasks'); ?>';
const statusBadge = (s)=>{
  const map={pending:'bg-warning', 'in progress':'bg-primary', completed:'bg-success'};
  const cls = map[(s||'').toString().toLowerCase()]||'bg-secondary';
  return `<span class="badge ${cls}">${s||''}</span>`;
};
let allTasks = [];
function renderTasks(list){
  const rows = (list||[]).map(t=>{
    const phase = t.phase || t.team || '';
    const who = t.assignee_name || t.assigned_to_name || t.assigned_to || '';
    const deadline = t.deadline || t.due_date || t.end_date || '';
    const status = (t.status||'').toString();
    return `<tr>
      <td>${t.title||t.name||''}</td>
      <td>${phase}</td>
      <td>${who}</td>
      <td>${deadline? new Date(deadline).toLocaleDateString(): ''}</td>
      <td>${statusBadge(status)}</td>
    </tr>`;
  }).join('');
  document.querySelector('#clientTasksTable tbody').innerHTML = rows || `<tr><td colspan="5" class="text-center text-muted">No tasks</td></tr>`;
}
fetch(apiClientTasks, {headers:{'X-Requested-With':'XMLHttpRequest'}})
 .then(r=>r.json()).then(json=>{ allTasks = json.data||[]; renderTasks(allTasks); });

document.getElementById('statusFilter').addEventListener('change', (e)=>{
  const v = (e.target.value||'').toString().toLowerCase();
  if(!v) return renderTasks(allTasks);
  renderTasks(allTasks.filter(t => (t.status||'').toString().toLowerCase()===v));
});
</script>
