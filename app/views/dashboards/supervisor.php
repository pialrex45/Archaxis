<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/DashboardController.php';
requireAuth();
if(!hasRole('supervisor')) { http_response_code(403); die('Access denied'); }
$dashboardController = new DashboardController();
$dashboardData = $dashboardController->getSupervisorDashboard();
$pageTitle='Supervisor'; $currentPage='dashboard';
include_once __DIR__.'/../../views/layouts/header.php';
?>

<!-- Modern Messaging System for Supervisor -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-comments me-2"></i>Team Communications
                </h5>
                <span class="badge bg-primary" id="totalUnreadMessages">0</span>
            </div>
            <div class="card-body p-0">
                <!-- Simple Messaging Button -->
                <div class="text-center p-4">
                    <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#messagesModal">
                        <i class="fas fa-comments me-2"></i>Open Messages
                    </button>
                    <p class="text-muted mt-2 mb-0">Communicate with your team and project stakeholders</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Simple Messaging Component -->
<?php include __DIR__ . '/../components/simple_messaging.php'; ?>

<style>
  .sup-dashboard h1{font-size:1.55rem;font-weight:600;}
  .metric-card{border:none;border-radius:10px;position:relative;overflow:hidden;background:linear-gradient(135deg,#f8f9fa,#ffffff);box-shadow:0 1px 3px rgba(0,0,0,0.08);} 
  .metric-card .value{font-size:1.9rem;font-weight:600;}
  .metric-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:0.75rem;}
  .tasks-table th{font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:#6c757d;}
  .tasks-table td{vertical-align:middle;}
  .task-actions .btn{padding:.25rem .55rem;font-size:.7rem;border-radius:6px;}
  .section-card{border:none;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.06);} 
  .section-card>.card-header{background:#fff;border-bottom:1px solid #e9ecef;font-weight:600;}
  .inline-loader{width:14px;height:14px;border:2px solid #ccc;border-top-color:#0d6efd;border-radius:50%;animation:spin .65s linear infinite;display:inline-block;}
  @keyframes spin{to{transform:rotate(360deg)}}
  .over-alloc{background:#dc3545!important;color:#fff!important;}
  .no-workers{background:#f1f3f5;color:#6c757d;}
  .assign-badge{cursor:pointer;}
  .auto-refresh-indicator{font-size:.7rem;color:#6c757d;}
  @media (max-width: 900px){.hide-mobile{display:none!important}}
</style>

<div class="sup-dashboard container-fluid px-2 px-md-3">
  <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
    <div>
      <h1 class="mb-2">Supervisor Dashboard</h1>
      <p class="text-muted mb-0">Manage your team and tasks • Workers now managed as supervised team members</p>
    </div>
    <div class="d-flex gap-2 small">
      <button id="reloadAllBtn" class="btn btn-sm btn-outline-secondary">Reload</button>
      <div class="form-check form-switch m-0 pt-1">
        <input class="form-check-input" type="checkbox" id="autoRefreshToggle" checked>
        <label class="form-check-label" for="autoRefreshToggle" style="cursor:pointer">Auto</label>
      </div>
      <span class="auto-refresh-indicator" id="autoRefreshIndicator">refreshing...</span>
    </div>
  </div>

  <?php if($dashboardData['success']): $t=$dashboardData['data']['tasks']; ?>
  <div class="metric-grid mb-4">
    <div class="metric-card p-3"><div class="text-muted small mb-1">Total</div><div class="value" id="metricTotal"><?php echo (int)$t['total']; ?></div></div>
    <div class="metric-card p-3"><div class="text-muted small mb-1">Completed</div><div class="value text-success" id="metricCompleted"><?php echo (int)$t['completed']; ?></div></div>
    <div class="metric-card p-3"><div class="text-muted small mb-1">In Progress</div><div class="value text-primary" id="metricInProgress"><?php echo (int)$t['in_progress']; ?></div></div>
    <div class="metric-card p-3"><div class="text-muted small mb-1">Pending</div><div class="value text-warning" id="metricPending"><?php echo (int)$t['pending']; ?></div></div>
  </div>

  <div class="row g-3">
    <div class="col-12 col-xl-8">
      <div class="card section-card h-100">
        <div class="card-header flex-column flex-md-row d-flex justify-content-between align-items-md-center gap-2">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="fw-semibold">Tasks</span>
            <span class="text-muted small" id="tasksHelpHint" style="cursor:help" title="Click 'Asg' or the team members badge to assign / unassign team members; click 'Log' to open logbook.">?</span>
            <select id="taskStatusFilter" class="form-select form-select-sm" style="width:auto;min-width:130px">
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="in progress">In Progress</option>
              <option value="completed">Completed</option>
            </select>
            <input type="text" id="taskSearch" class="form-control form-control-sm" style="width:180px" placeholder="Search task/project" />
          </div>
          <div class="small text-muted" id="tasksLastLoaded"></div>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm mb-0 tasks-table" id="tasksTable">
              <thead><tr>
                <th data-sort="title" class="sortable">Task</th>
                <th data-sort="project" class="hide-mobile sortable">Project</th>
                <th data-sort="workers" class="sortable">Team Members</th>
                <th data-sort="status" class="sortable">Status</th>
                <th data-sort="due" class="hide-mobile sortable">Due</th>
                <th>Actions</th></tr></thead>
              <tbody id="tasksTbody">
                <?php foreach($dashboardData['data']['tasks']['recent'] as $task): ?>
                  <tr data-task-row="<?php echo (int)$task['id']; ?>">
                    <td class="fw-semibold text-truncate" style="max-width:220px" title="<?php echo htmlspecialchars($task['title']); ?>"><?php echo htmlspecialchars($task['title']); ?></td>
                    <td class="hide-mobile text-muted small"><?php echo htmlspecialchars($task['project_name']); ?></td>
                    <td><button type="button" class="badge rounded-pill bg-secondary border-0 assign-badge js-assigned-count" data-open-assign="1" aria-label="Open assignment for this task" data-task-id="<?php echo (int)$task['id']; ?>">--</button></td>
                    <td><span class="badge bg-<?php echo $task['status']==='completed'?'success':($task['status']==='in progress'?'primary':'warning'); ?>"><?php echo htmlspecialchars($task['status']); ?></span></td>
                    <td class="hide-mobile small text-muted"><?php echo $task['due_date']? date('M j',strtotime($task['due_date'])):'—'; ?></td>
                    <td class="task-actions">
                      <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary js-update-task-btn" data-task-id="<?php echo (int)$task['id']; ?>" data-status="<?php echo htmlspecialchars($task['status']); ?>" title="Update">Upd</button>
                        <button class="btn btn-outline-secondary js-assign-task-btn" data-task-id="<?php echo (int)$task['id']; ?>" data-task-title="<?php echo htmlspecialchars($task['title']); ?>" title="Assignments">Asg</button>
                        <button class="btn btn-dark js-logbook-btn" data-log-task-id="<?php echo (int)$task['id']; ?>" data-log-task-title="<?php echo htmlspecialchars($task['title']); ?>" title="Logbook">Log</button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <?php /* Replacing right side panels with Attendance Log */ ?>
    <div class="col-12 col-xl-4">
      <div class="card section-card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>Attendance / Assignment Log</span>
        <div class="d-flex gap-1 align-items-center">
          <select id="attExportGroup" class="form-select form-select-sm" style="width:auto">
            <option value="raw">Raw</option>
            <option value="day">Day</option>
            <option value="month">Month</option>
          </select>
          <button class="btn btn-sm btn-outline-secondary" id="attReloadBtn">Reload</button>
          <button class="btn btn-sm btn-primary" id="attExportBtn" title="Export to printable PDF (browser print)">Export</button>
        </div>
      </div>
        <div class="card-body p-2">
          <form id="attAddForm" class="row g-2 mb-2">
            <div class="col-6">
              <?php
                // Server-side fallback: load all users for worker dropdown
                try {
                  $pdoPrefill = Database::getConnection();
                  $prefillWorkers = $pdoPrefill->query("SELECT id,name FROM users ORDER BY name, id LIMIT 2000")->fetchAll(PDO::FETCH_ASSOC);
                } catch (Throwable $e) { $prefillWorkers = []; }
              ?>
              <select class="form-select form-select-sm" id="attWorkerSelect">
                <?php if(empty($prefillWorkers)): ?>
                  <option value="">(No workers yet)</option>
                <?php else: foreach($prefillWorkers as $w): ?>
                  <option value="<?php echo (int)$w['id']; ?>"><?php echo htmlspecialchars($w['name']!==''?$w['name']:('User #'.$w['id'])); ?></option>
                <?php endforeach; endif; ?>
              </select>
            </div>
            <div class="col-6">
              <select class="form-select form-select-sm" id="attTaskSelect">
                <option value="">(Task opt)</option>
                <?php foreach($dashboardData['data']['tasks']['recent'] as $task): ?>
                  <option value="<?php echo (int)$task['id']; ?>"><?php echo htmlspecialchars(substr($task['title'],0,30)); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6">
              <input type="date" class="form-control form-control-sm" id="attDate" value="<?php echo date('Y-m-d'); ?>" />
            </div>
            <div class="col-6">
              <input type="number" step="0.25" min="0" class="form-control form-control-sm" id="attHours" placeholder="Hours" />
            </div>
            <div class="col-12">
              <input type="text" class="form-control form-control-sm" id="attNote" placeholder="Note (optional)" />
            </div>
            <div class="col-12 d-grid">
              <button class="btn btn-sm btn-primary">Add</button>
            </div>
            <div class="col-12"><div id="attAlert" class="alert d-none p-2 mb-0"></div></div>
          </form>
          <div class="d-flex gap-2 align-items-center mb-2 small">
            <input type="date" id="attFilterFrom" class="form-control form-control-sm" value="<?php echo date('Y-m-01'); ?>" />
            <input type="date" id="attFilterTo" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>" />
            <button class="btn btn-sm btn-outline-secondary" id="attFilterApply">Go</button>
          </div>
          <div id="attList" style="max-height:300px;overflow:auto" class="small border rounded p-2">Loading...</div>
        </div>
      </div>
    </div>
  </div>
  <?php else: ?>
    <div class="alert alert-danger">Failed to load dashboard.</div>
  <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../components/messaging_nav.php'; ?>
<?php include_once __DIR__ . '/../../views/layouts/footer.php'; ?>

<!-- Task Assignment Modal -->
<div class="modal fade" id="taskAssignModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Task Assignments</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
        <div id="assignTaskAlert" class="alert d-none"></div>
        <h6 class="mb-2" id="assignTaskTitle">&nbsp;</h6>
        <input type="hidden" id="assign_task_id" />
        <div class="row g-3">
          <div class="col-12 col-md-6"><div class="card h-100"><div class="card-header py-2"><strong>Currently Assigned</strong></div><div class="card-body p-2"><ul id="currentAssignmentsList" class="list-group small"></ul></div></div></div>
          <div class="col-12 col-md-6"><div class="card h-100"><div class="card-header py-2 d-flex justify-content-between align-items-center"><strong>Available Team Members</strong><button id="refreshAvailableBtn" type="button" class="btn btn-sm btn-outline-secondary">Refresh</button></div><div class="card-body p-2"><div class="mb-2"><label class="form-label small">Select Team Members</label><select multiple size="10" class="form-select" id="assignWorkersSelect"></select><div class="form-text">Ctrl / Cmd to multi-select.</div></div><div class="d-grid gap-2"><button id="bulkAssignBtn" type="button" class="btn btn-primary btn-sm">Assign Selected</button></div></div></div></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
    </div>
  </div>
</div>

<!-- Update Task Modal -->
<div class="modal fade" id="updateTaskModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Task</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="updateTaskForm">
          <input type="hidden" name="task_id" id="task_id" />
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status" id="status">
              <option value="pending">Pending</option>
              <option value="in progress">In Progress</option>
              <option value="completed">Completed</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Note (optional)</label>
            <textarea class="form-control" name="note" id="note" rows="3"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Photo (optional)</label>
            <input class="form-control" type="file" name="photo" id="photo" accept="image/*" />
          </div>
        </form>
        <div class="alert d-none" id="updateTaskAlert"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-outline-primary" id="addProgressBtn">Add Progress</button>
        <button type="button" class="btn btn-primary" id="saveStatusBtn">Save Status</button>
      </div>
    </div>
  </div>
  </div>

<script>
// Ensure correct base path even when app is served from a subdirectory
var BASE_URL = <?php echo json_encode(rtrim(url('/'), '/').'/'); ?>;
document.addEventListener('DOMContentLoaded', function() {
  const modalEl = document.getElementById('updateTaskModal');
  const alertEl = document.getElementById('updateTaskAlert');
  const taskIdInput = document.getElementById('task_id');
  const statusSel = document.getElementById('status');
  const noteEl = document.getElementById('note');
  const photoEl = document.getElementById('photo');
  let bsModal;
  if (window.bootstrap && window.bootstrap.Modal) {
    bsModal = new bootstrap.Modal(modalEl);
  }

  function showAlert(msg, type='success') {
    alertEl.classList.remove('d-none', 'alert-success', 'alert-danger');
    alertEl.classList.add('alert', type === 'success' ? 'alert-success' : 'alert-danger');
    alertEl.textContent = msg;
  }

  // Wire buttons
  document.querySelectorAll('.js-update-task-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const taskId = btn.getAttribute('data-task-id');
      const status = btn.getAttribute('data-status');
      taskIdInput.value = taskId;
      statusSel.value = status && ['pending','in progress','completed'].includes(status) ? status : 'pending';
      noteEl.value = '';
      photoEl.value = '';
      alertEl.classList.add('d-none');
      if (bsModal) bsModal.show(); else modalEl.style.display = 'block';
    });
  });

  async function postForm(action) {
    const fd = new FormData();
    fd.append('task_id', taskIdInput.value);
    if (statusSel.value) fd.append('status', statusSel.value);
    if (noteEl.value) fd.append('note', noteEl.value);
    if (photoEl.files && photoEl.files[0]) fd.append('photo', photoEl.files[0]);
    const url = `${BASE_URL}api/supervisor/tasks.php?action=${encodeURIComponent(action)}`;
    const res = await fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' });
    return res.json();
  }

  document.getElementById('saveStatusBtn').addEventListener('click', async () => {
    try {
      const data = await postForm('update_status');
      if (data.success) {
        showAlert('Status updated successfully', 'success');
        setTimeout(() => { window.location.reload(); }, 800);
      } else { showAlert(data.message || 'Update failed', 'danger'); }
    } catch (e) { showAlert('Server error', 'danger'); }
  });

  document.getElementById('addProgressBtn').addEventListener('click', async () => {
    try {
      const data = await postForm('add_progress');
      if (data.success) {
        showAlert('Progress added successfully', 'success');
        setTimeout(() => { window.location.reload(); }, 800);
      } else { showAlert(data.message || 'Update failed', 'danger'); }
    } catch (e) { showAlert('Server error', 'danger'); }
  });
});

// ----- Team management (Teams + Enroll + List + Remove + Attendance) -----
const teamListEl = document.getElementById('teamList');
const enrollForm = document.getElementById('enrollWorkerForm');
const enrollAlert = document.getElementById('enrollAlert');
const teamSelect = document.getElementById('teamSelect');
const createTeamForm = document.getElementById('createTeamForm');
const teamsAlert = document.getElementById('teamsAlert');
const openAddMembersBtn = document.getElementById('openAddMembersBtn');
let currentTeamId = 0;

function showEnrollAlert(msg, type='success') {
  if (!enrollAlert) return;
  enrollAlert.classList.remove('d-none', 'alert-success', 'alert-danger');
  enrollAlert.classList.add('alert', type === 'success' ? 'alert-success' : 'alert-danger');
  enrollAlert.textContent = msg;
}

function showTeamsAlert(msg, type='success') {
  if (!teamsAlert) return;
  teamsAlert.classList.remove('d-none', 'alert-success', 'alert-danger');
  teamsAlert.classList.add('alert', type === 'success' ? 'alert-success' : 'alert-danger');
  teamsAlert.textContent = msg;
}

async function loadAssignedTeam() {
  if (!teamListEl) return;
  teamListEl.innerHTML = '<li class="list-group-item">Loading...</li>';
  try {
    const res = await fetch(`${BASE_URL}api/supervisor/team.php`, { credentials: 'same-origin' });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Failed to load');
    const rows = data.data || [];
    if (rows.length === 0) {
      teamListEl.innerHTML = '<li class="list-group-item text-muted">No team members yet. Enroll a new team member or assign an existing user by email.</li>';
      return;
    }
    teamListEl.innerHTML = rows.map(u => `
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <div>
          <strong>${u.name ? u.name.replace(/</g,'&lt;') : 'Team Member #'+u.id}</strong>
          <div class="small text-muted">${u.email ? u.email.replace(/</g,'&lt;') : ''}</div>
          <div class="small">Approved: ${u.approved ? 'Yes' : 'No'}</div>
        </div>
        <div class="btn-group">
          <button class="btn btn-sm btn-outline-secondary" data-action="attendance" data-id="${u.id}">Attendance</button>
          <button class="btn btn-sm btn-outline-primary" data-action="payment" data-id="${u.id}" data-name="${(u.name||'').replace(/"/g,'&quot;')}">Payment</button>
          <button class="btn btn-sm btn-outline-danger" data-action="remove" data-id="${u.id}">Remove</button>
        </div>
      </li>`).join('');
  } catch (e) {
    teamListEl.innerHTML = '<li class="list-group-item text-danger">Failed to load team</li>';
  }
}

async function loadTeams() {
  if (!teamSelect) return;
  try {
    const res = await fetch(`${BASE_URL}api/supervisor/teams.php?action=list`, { credentials: 'same-origin' });
    const data = await res.json();
    const rows = (data && data.success && Array.isArray(data.data)) ? data.data : [];
    const options = ['<option value="0">-- Select a Team --</option>'].concat(rows.map(t => `<option value="${t.id}">${(t.name||'').replace(/</g,'&lt;')}</option>`));
    teamSelect.innerHTML = options.join('');
    if (currentTeamId) teamSelect.value = String(currentTeamId);
  } catch (e) {}
}

async function loadTeamMembers(teamId) {
  if (!teamListEl) return;
  teamListEl.innerHTML = '<li class="list-group-item">Loading...</li>';
  try {
    const res = await fetch(`${BASE_URL}api/supervisor/teams.php?action=members&team_id=${encodeURIComponent(teamId)}`, { credentials: 'same-origin' });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Failed to load');
    const rows = data.data || [];
    if (rows.length === 0) {
      teamListEl.innerHTML = '<li class="list-group-item text-muted">No members in this team yet. Click "Add Members".</li>';
      return;
    }
    teamListEl.innerHTML = rows.map(u => `
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <div>
          <strong>${u.name ? u.name.replace(/</g,'&lt;') : 'Team Member #'+u.id}</strong>
          <div class="small text-muted">${u.email ? u.email.replace(/</g,'&lt;') : ''}</div>
          <div class="small">Approved: ${u.approved ? 'Yes' : 'No'}</div>
        </div>
        <div class="btn-group">
          <button class="btn btn-sm btn-outline-secondary" data-action="attendance" data-id="${u.id}">Attendance</button>
          <button class="btn btn-sm btn-outline-primary" data-action="payment" data-id="${u.id}" data-name="${(u.name||'').replace(/"/g,'&quot;')}">Payment</button>
          <button class="btn btn-sm btn-outline-danger" data-action="remove" data-id="${u.id}">Remove</button>
        </div>
      </li>`).join('');
  } catch (e) {
    teamListEl.innerHTML = '<li class="list-group-item text-danger">Failed to load team</li>';
  }
}

if (enrollForm) {
  enrollForm.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    showEnrollAlert('', 'success'); enrollAlert.classList.add('d-none');
    const fd = new FormData(enrollForm);
    const payload = {
      name: fd.get('name'),
      email: fd.get('email'),
      password: fd.get('password'),
      confirm_password: fd.get('confirm_password')
    };
    try {
      const res = await fetch(`${BASE_URL}api/supervisor/team.php?action=enroll`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
        credentials: 'same-origin'
      });
      const data = await res.json();
      if (data.success) {
        showEnrollAlert('Worker enrolled. Awaiting approval.', 'success');
        enrollForm.reset();
        loadTeam();
      } else {
        showEnrollAlert(data.message || 'Enrollment failed', 'danger');
      }
    } catch (e) {
      showEnrollAlert('Server error', 'danger');
    }
  });
}

// Assign existing worker by email
const assignForm = document.getElementById('assignWorkerForm');
const assignAlert = document.getElementById('assignAlert');
function showAssignAlert(msg, type='success') {
  if (!assignAlert) return;
  assignAlert.classList.remove('d-none', 'alert-success', 'alert-danger');
  assignAlert.classList.add('alert', type === 'success' ? 'alert-success' : 'alert-danger');
  assignAlert.textContent = msg;
}
if (assignForm) {
  assignForm.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    showAssignAlert('', 'success'); if (assignAlert) assignAlert.classList.add('d-none');
    const fd = new FormData(assignForm);
    const payload = { email: fd.get('email') };
    try {
      const res = await fetch(`${BASE_URL}api/supervisor/team.php?action=assign_existing`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
        credentials: 'same-origin'
      });
      const data = await res.json();
      if (data.success) { showAssignAlert('Worker assigned to your team.', 'success'); assignForm.reset(); loadTeam(); }
      else { showAssignAlert(data.message || 'Assignment failed', 'danger'); }
    } catch (e) { showAssignAlert('Server error', 'danger'); }
  });
}

if (teamListEl) {
  teamListEl.addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-action]');
    if (!btn) return;
    const action = btn.getAttribute('data-action');
    const id = btn.getAttribute('data-id');
    if (action === 'remove') {
      if (!confirm('Remove this worker from your team?')) return;
      try {
        if (currentTeamId > 0) {
          const res = await fetch(`${BASE_URL}api/supervisor/teams.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'remove_member', team_id: currentTeamId, worker_id: parseInt(id,10) }),
            credentials: 'same-origin'
          });
          const data = await res.json();
          if (data.success) { loadTeamMembers(currentTeamId); }
        } else {
          const res = await fetch(`${BASE_URL}api/supervisor/team.php?action=remove`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ worker_id: parseInt(id,10) }),
            credentials: 'same-origin'
          });
          const data = await res.json();
          if (data.success) { loadAssignedTeam(); }
        }
      } catch (e) {}
    } else if (action === 'attendance') {
      // Open a simple inline modal with recent attendance
      try {
        const res = await fetch(`${BASE_URL}api/supervisor/attendance.php?user_id=${encodeURIComponent(id)}&limit=10`, { credentials: 'same-origin' });
        const data = await res.json();
        let html = '';
        if (data.success && data.data && data.data.length) {
          html = '<ul class="list-group">' + data.data.map(a => `
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <div><strong>${a.date}</strong></div>
                  <div class="small text-muted">Status: ${(a.status||'').toUpperCase()}</div>
                </div>
                ${a.status !== 'approved' ? `<button class="btn btn-sm btn-outline-success" data-approve="${a.id}">Approve</button>` : ''}
              </li>`).join('') + '</ul>';
        } else { html = '<div class="text-muted">No attendance records</div>'; }
        const container = document.getElementById('attendanceModalBody');
        if (container) { container.innerHTML = html; }
        const attModalEl = document.getElementById('attendanceModal');
        if (window.bootstrap && window.bootstrap.Modal && attModalEl) {
          new bootstrap.Modal(attModalEl).show();
        }
        // Add event listener for approve buttons
        document.querySelectorAll('#attendanceModalBody button[data-approve]').forEach(approveBtn => {
          approveBtn.addEventListener('click', async () => {
            const attendanceId = approveBtn.getAttribute('data-approve');
            try {
              const res = await fetch(`${BASE_URL}api/supervisor/attendance.php?action=approve`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ attendance_id: parseInt(attendanceId,10) }),
                credentials: 'same-origin'
              });
              const data = await res.json();
              if (data.success) {
                // Reload attendance records
                const res = await fetch(`${BASE_URL}api/supervisor/attendance.php?user_id=${encodeURIComponent(id)}&limit=10`, { credentials: 'same-origin' });
                const data = await res.json();
                let html = '';
                if (data.success && data.data && data.data.length) {
                  html = '<ul class="list-group">' + data.data.map(a => `
                      <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                          <div><strong>${a.date}</strong></div>
                          <div class="small text-muted">Status: ${(a.status||'').toUpperCase()}</div>
                        </div>
                        ${a.status !== 'approved' ? `<button class="btn btn-sm btn-outline-success" data-approve="${a.id}">Approve</button>` : ''}
                      </li>`).join('') + '</ul>';
                } else { html = '<div class="text-muted">No attendance records</div>'; }
                const container = document.getElementById('attendanceModalBody');
                if (container) { container.innerHTML = html; }
              } else {
                alert('Failed to approve attendance');
              }
            } catch (e) {
              alert('Server error');
            }
          });
        });
      } catch (e) {}
    } else if (action === 'payment') {
      const workerId = parseInt(id, 10);
      const workerName = btn.getAttribute('data-name') || '';
      const titleEl = document.getElementById('paymentModalTitle');
      const widEl = document.getElementById('payment_worker_id');
      const nameEl = document.getElementById('payment_worker_name');
      const listEl = document.getElementById('paymentList');
      if (titleEl) titleEl.textContent = `Add Payment - ${workerName || ('Worker #' + workerId)}`;
      if (nameEl) nameEl.textContent = workerName || ('Worker #' + workerId);
      if (widEl) widEl.value = workerId;
      if (listEl) { listEl.innerHTML = '<li class="list-group-item">Loading...</li>'; }
      // Load recent payments
      try {
        const res = await fetch(`${BASE_URL}api/supervisor/payments.php?worker_id=${encodeURIComponent(workerId)}`, { credentials: 'same-origin' });
        const data = await res.json();
        if (listEl) {
          if (data.success && data.data && data.data.length) {
            listEl.innerHTML = data.data.map(p => `
              <li class="list-group-item d-flex justify-content-between">
                <span>${new Date(p.created_at).toLocaleString()}</span>
                <strong>$${Number(p.amount).toFixed(2)}</strong>
              </li>`).join('');
          } else {
            listEl.innerHTML = '<li class="list-group-item text-muted">No payments recorded</li>';
          }
        }
      } catch (e) { if (listEl) listEl.innerHTML = '<li class="list-group-item text-danger">Payments not available (run migration)</li>'; }
      const payModal = document.getElementById('paymentModal');
      if (window.bootstrap && window.bootstrap.Modal && payModal) {
        new bootstrap.Modal(payModal).show();
      }
    }
  });
}

// Teams UI wiring
if (createTeamForm) {
  createTeamForm.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    showTeamsAlert('', 'success'); if (teamsAlert) teamsAlert.classList.add('d-none');
    const fd = new FormData(createTeamForm);
    const name = (fd.get('name')||'').toString().trim();
    if (!name) { showTeamsAlert('Team name required', 'danger'); return; }
    try {
      const res = await fetch(`${BASE_URL}api/supervisor/teams.php?action=create`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin',
        body: JSON.stringify({ name })
      });
      const data = await res.json();
      if (data.success && data.team_id) {
        showTeamsAlert('Team created', 'success');
        currentTeamId = parseInt(data.team_id, 10);
        await loadTeams();
        if (teamSelect) teamSelect.value = String(currentTeamId);
        loadTeamMembers(currentTeamId);
        createTeamForm.reset();
      } else { showTeamsAlert(data.message || 'Create failed', 'danger'); }
    } catch (e) { showTeamsAlert('Server error', 'danger'); }
  });
}

if (teamSelect) {
  teamSelect.addEventListener('change', () => {
    currentTeamId = parseInt(teamSelect.value || '0', 10) || 0;
    if (currentTeamId > 0) { loadTeamMembers(currentTeamId); }
    else { loadAssignedTeam(); }
  });
}

// Add members modal
const addMembersModalEl = document.getElementById('addMembersModal');
const addMembersListEl = document.getElementById('addMembersList');
if (openAddMembersBtn && addMembersModalEl && addMembersListEl) {
  openAddMembersBtn.addEventListener('click', async () => {
    if (!currentTeamId) { showTeamsAlert('Select a team first', 'danger'); return; }
    addMembersListEl.innerHTML = '<li class="list-group-item">Loading...</li>';
    try {
      const res = await fetch(`${BASE_URL}api/supervisor/teams.php?action=available_workers&team_id=${encodeURIComponent(currentTeamId)}`, { credentials: 'same-origin' });
      const data = await res.json();
      const rows = (data && data.success && Array.isArray(data.data)) ? data.data : [];
      if (!rows.length) { addMembersListEl.innerHTML = '<li class="list-group-item text-muted">No available workers</li>'; }
      else {
        addMembersListEl.innerHTML = rows.map(u => `
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <strong>${(u.name||('Worker #'+u.id)).replace(/</g,'&lt;')}</strong>
              <div class="small text-muted">${(u.email||'').replace(/</g,'&lt;')}</div>
            </div>
            <button class="btn btn-sm btn-outline-primary" data-add-worker="${u.id}">Add</button>
          </li>`).join('');
        // wire add buttons
        addMembersListEl.querySelectorAll('button[data-add-worker]').forEach(btn => {
          btn.addEventListener('click', async () => {
            const wid = parseInt(btn.getAttribute('data-add-worker'), 10);
            try {
              const res = await fetch(`${BASE_URL}api/supervisor/teams.php`, {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin',
                body: JSON.stringify({ action: 'add_member', team_id: currentTeamId, worker_id: wid })
              });
              const data = await res.json();
              if (data && data.success) {
                btn.disabled = true; btn.textContent = 'Added';
                loadTeamMembers(currentTeamId);
              }
            } catch (e) {}
          });
        });
      }
      if (window.bootstrap && window.bootstrap.Modal) {
        new bootstrap.Modal(addMembersModalEl).show();
      }
    } catch (e) { addMembersListEl.innerHTML = '<li class="list-group-item text-danger">Failed to load</li>'; }
  });
}

// Initial load
loadTeams();
loadAssignedTeam();
});

// Payment modal
const paymentForm = document.getElementById('paymentForm');
const paymentAlert = document.getElementById('paymentAlert');

function showPaymentAlert(msg, type='success') {
  if (!paymentAlert) return;
  paymentAlert.classList.remove('d-none', 'alert-success', 'alert-danger');
  paymentAlert.classList.add('alert', type === 'success' ? 'alert-success' : 'alert-danger');
  paymentAlert.textContent = msg;
}

if (paymentForm) {
  paymentForm.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    showPaymentAlert('', 'success'); paymentAlert.classList.add('d-none');
    const fd = new FormData(paymentForm);
    const payload = {
      worker_id: fd.get('worker_id'),
      amount: fd.get('amount'),
      note: fd.get('note')
    };
    try {
      const res = await fetch(`${BASE_URL}api/supervisor/payments.php?action=add`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
        credentials: 'same-origin'
      });
      const data = await res.json();
      if (data.success) {
        showPaymentAlert('Payment recorded successfully', 'success');
        paymentForm.reset();
        const workerId = fd.get('worker_id');
        const listEl = document.getElementById('paymentList');
        if (listEl) { listEl.innerHTML = '<li class="list-group-item">Loading...</li>'; }
        // Load recent payments
        try {
          const res = await fetch(`${BASE_URL}api/supervisor/payments.php?worker_id=${encodeURIComponent(workerId)}`, { credentials: 'same-origin' });
          const data = await res.json();
          if (listEl) {
            if (data.success && data.data && data.data.length) {
              listEl.innerHTML = data.data.map(p => `
                <li class="list-group-item d-flex justify-content-between">
                  <span>${new Date(p.created_at).toLocaleString()}</span>
                  <strong>$${Number(p.amount).toFixed(2)}</strong>
                </li>`).join('');
            } else {
              listEl.innerHTML = '<li class="list-group-item text-muted">No payments recorded</li>';
            }
          }
        } catch (e) { if (listEl) listEl.innerHTML = '<li class="list-group-item text-danger">Payments not available (run migration)</li>'; }
      } else {
        showPaymentAlert(data.message || 'Payment failed', 'danger');
      }
    } catch (e) {
      showPaymentAlert('Server error', 'danger');
    }
  });
}
</script>

<script>
// Lightweight tab toggle for Supervisor dashboard
document.addEventListener('DOMContentLoaded', function() {
  const tabs = document.getElementById('supTabs');
  const panes = {
    overview: document.getElementById('tab-overview'),
    team: document.getElementById('tab-team')
  };
  if (!tabs) return;
  tabs.addEventListener('click', function(e) {
    const link = e.target.closest('a[data-tab]');
    if (!link) return;
    e.preventDefault();
    const target = link.getAttribute('data-tab');
    // toggle active class
    tabs.querySelectorAll('a[data-tab]').forEach(a => a.classList.remove('active'));
    link.classList.add('active');
    // show/hide panes
    Object.keys(panes).forEach(k => {
      if (panes[k]) panes[k].classList.toggle('d-none', k !== target);
    });
  });
});
</script>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="paymentModalTitle">Add Payment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="paymentForm" class="mb-3">
          <input type="hidden" id="payment_worker_id" name="worker_id" />
          <div class="mb-2"><strong id="payment_worker_name"></strong></div>
          <div class="mb-3">
            <label class="form-label">Amount</label>
            <input type="number" step="0.01" min="0" class="form-control" id="payment_amount" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Note (optional)</label>
            <input type="text" class="form-control" id="payment_note" />
          </div>
          <div id="paymentAlert" class="alert d-none"></div>
          <button type="submit" class="btn btn-primary">Record Payment</button>
        </form>
        <h6>Recent Payments</h6>
        <ul id="paymentList" class="list-group"></ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
 </div>

<!-- Team Management Section -->
<div id="tab-team" class="tab-pane d-none">
<div class="row g-3 mt-2">
  <div class="col-12">
    <div class="card">
      <div class="card-header"><h5 class="mb-0">Teams</h5></div>
      <div class="card-body">
        <div id="teamsAlert" class="alert d-none"></div>
        <div class="row g-2 align-items-end">
          <div class="col-12 col-md-6">
            <form id="createTeamForm" class="row g-2">
              <div class="col-8">
                <label class="form-label">New Team Name</label>
                <input type="text" class="form-control" name="name" placeholder="e.g., Masonry Team" />
              </div>
              <div class="col-4 d-grid">
                <button type="submit" class="btn btn-primary mt-md-4">Create</button>
              </div>
            </form>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Select Team</label>
            <div class="input-group">
              <select id="teamSelect" class="form-select">
                <option value="0">-- Select a Team --</option>
              </select>
              <button id="openAddMembersBtn" type="button" class="btn btn-outline-primary">Add Members</button>
            </div>
            <div class="form-text">If no team is selected, the list shows your legacy assigned workers.</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-6">
    <div class="card">
      <div class="card-header"><h5 class="mb-0">Enroll Worker</h5></div>
      <div class="card-body">
        <div id="enrollAlert" class="alert d-none"></div>
        <form id="enrollWorkerForm">
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" name="name" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" minlength="6" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <input type="password" class="form-control" name="confirm_password" minlength="6" required />
          </div>
          <button type="submit" class="btn btn-primary">Enroll</button>
          <div class="form-text">New worker will require admin/manager approval before login.</div>
        </form>
        <hr/>
        <h6 class="mb-2">Assign Existing Worker</h6>
        <div id="assignAlert" class="alert d-none"></div>
        <form id="assignWorkerForm" class="row g-2">
          <div class="col-12 col-md-8">
            <label class="form-label visually-hidden">Worker Email</label>
            <input type="email" class="form-control" name="email" placeholder="worker@email.com" required />
          </div>
          <div class="col-12 col-md-4 d-grid">
            <button type="submit" class="btn btn-outline-primary">Assign</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-6">
    <div class="card">
      <div class="card-header"><h5 class="mb-0">Your Team</h5></div>
      <div class="card-body">
        <ul id="teamList" class="list-group"></ul>
      </div>
    </div>
  </div>
</div>
</div>

<!-- Add Members Modal -->
<div class="modal fade" id="addMembersModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Members to Team</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <ul id="addMembersList" class="list-group"></ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
 </div>

<script>
// ---- Task Assignment Modal Logic (isolated, safe) ----
(function(){
  const BASE_URL = <?php echo json_encode(rtrim(url('/'), '/').'/'); ?>;
  const OVER_ALLOC_THRESHOLD = 3; // simple heuristic for highlighting
  let bsAssignModal = null;
  const modalEl = document.getElementById('taskAssignModal');
  const taskIdInput = document.getElementById('assign_task_id');
  const titleEl = document.getElementById('assignTaskTitle');
  const listEl = document.getElementById('currentAssignmentsList');
  const selectEl = document.getElementById('assignWorkersSelect');
  const alertEl = document.getElementById('assignTaskAlert');
  const bulkBtn = document.getElementById('bulkAssignBtn');
  const refreshAvailBtn = document.getElementById('refreshAvailableBtn');

  function safeText(t){ return (t||'').replace(/[<>]/g, s=> s==='<'?'&lt;':'&gt;'); }
  function showAlert(msg,type='success'){
    if(!alertEl) return; alertEl.className='alert alert-'+(type==='success'?'success':'danger'); alertEl.textContent=msg; }
  function clearAlert(){ if(alertEl){ alertEl.className='alert d-none'; alertEl.textContent=''; } }

  async function loadAssignments(taskId){
    if(!listEl) return; listEl.innerHTML='<li class="list-group-item">Loading...</li>';
    try{
      const res = await fetch(`${BASE_URL}api/supervisor/assignments.php?task_id=${encodeURIComponent(taskId)}`, {credentials:'same-origin'});
      const data = await res.json();
      if(!data.success){ listEl.innerHTML='<li class="list-group-item text-danger">Failed</li>'; return; }
      const rows = data.data||[];
      if(!rows.length){ listEl.innerHTML='<li class="list-group-item text-muted">No workers assigned</li>'; return; }
      listEl.innerHTML = rows.map(r=>`<li class="list-group-item d-flex justify-content-between align-items-center">
         <span>${safeText(r.name || ('Worker #'+r.worker_id))}<br><small class="text-muted">${safeText(r.email||'')}</small></span>
         <button class="btn btn-sm btn-outline-danger" data-unassign="${r.worker_id}">Remove</button>
       </li>`).join('');
    }catch(e){ listEl.innerHTML='<li class="list-group-item text-danger">Error</li>'; }
  }

  async function loadAvailable(taskId){
    if(!selectEl) return; selectEl.innerHTML='<option>Loading...</option>';
    try{
      const res = await fetch(`${BASE_URL}api/supervisor/workers.php?action=available_for_task&task_id=${encodeURIComponent(taskId)}`, {credentials:'same-origin'});
      const data = await res.json();
      if(!data.success){ selectEl.innerHTML='<option value="">Failed to load</option>'; return; }
      const rows = data.data||[];
      if(!rows.length){ selectEl.innerHTML='<option value="">No available workers</option>'; return; }
      selectEl.innerHTML = rows.map(w=>`<option value="${w.id}">${safeText(w.name||('Worker #'+w.id))}  (${safeText(w.email||'')})${w.active_tasks? ' - '+w.active_tasks+' tasks':''}</option>`).join('');
    }catch(e){ selectEl.innerHTML='<option value="">Error loading</option>'; }
  }

  async function bulkAssign(){
    clearAlert();
    const taskId = parseInt(taskIdInput.value,10)||0; if(!taskId) return;
    const selected = Array.from(selectEl.selectedOptions).map(o=>parseInt(o.value,10)).filter(v=>v>0);
    if(!selected.length){ showAlert('Select at least one worker','danger'); return; }
    bulkBtn.disabled=true; bulkBtn.textContent='Assigning...';
    try{
      const res = await fetch(`${BASE_URL}api/supervisor/assignments.php?action=bulk_assign`, {
        method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin',
        body: JSON.stringify({ task_id: taskId, worker_ids: selected })
      });
      const data = await res.json();
      if(data.success){ showAlert('Assigned successfully','success'); await loadAssignments(taskId); await loadAvailable(taskId); }
      else { showAlert(data.message||'Assign failed','danger'); }
    }catch(e){ showAlert('Server error','danger'); }
    finally { bulkBtn.disabled=false; bulkBtn.textContent='Assign Selected'; }
  }

  function wireUnassign(){
    if(!listEl) return;
    listEl.addEventListener('click', async (ev)=>{
      const btn = ev.target.closest('button[data-unassign]'); if(!btn) return;
      const wid = parseInt(btn.getAttribute('data-unassign'),10); const taskId=parseInt(taskIdInput.value,10)||0; if(!wid||!taskId) return;
      btn.disabled=true; btn.textContent='...';
      try {
        const res = await fetch(`${BASE_URL}api/supervisor/assignments.php?action=unassign`, {
          method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin',
          body: JSON.stringify({ task_id: taskId, worker_id: wid })
        });
        const data = await res.json();
        if(data.success){ showAlert('Worker unassigned','success'); await loadAssignments(taskId); await loadAvailable(taskId); }
        else { showAlert(data.message||'Unassign failed','danger'); }
      }catch(e){ showAlert('Server error','danger'); }
    });
  }

  function openModal(taskId, title){
    taskIdInput.value = taskId; titleEl.textContent = 'Task: '+ title;
    clearAlert(); loadAssignments(taskId); loadAvailable(taskId);
    if(window.bootstrap && window.bootstrap.Modal){
      if(!bsAssignModal) bsAssignModal=new bootstrap.Modal(modalEl);
      bsAssignModal.show();
    } else { modalEl.style.display='block'; }
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.js-assign-task-btn').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const taskId = parseInt(btn.getAttribute('data-task-id'),10);
        const title = btn.getAttribute('data-task-title') || ('#'+taskId);
        openModal(taskId, title);
      });
    });
    // Load inline assignment counts
    (async function loadCounts(){
      const badges = document.querySelectorAll('.js-assigned-count');
      for(const b of badges){
        const tid = b.getAttribute('data-task-id'); if(!tid) continue;
        try {
          const res = await fetch(`${BASE_URL}api/supervisor/assignments.php?task_id=${encodeURIComponent(tid)}`, {credentials:'same-origin'});
          const data = await res.json();
          if(data.success){
            const count = (data.data||[]).length; b.textContent = count; b.classList.remove('bg-secondary');
            if(count===0) { b.classList.add('bg-light','text-muted'); }
            else if(count>=OVER_ALLOC_THRESHOLD) { b.classList.add('bg-danger'); b.title='High load'; }
            else { b.classList.add('bg-info'); }
          } else { b.textContent='!'; b.classList.add('bg-warning'); }
        } catch(e){ b.textContent='?'; b.classList.add('bg-warning'); }
      }
    })();
    if(bulkBtn) bulkBtn.addEventListener('click', bulkAssign);
    if(refreshAvailBtn) refreshAvailBtn.addEventListener('click', ()=>{
      const t = parseInt(taskIdInput.value,10)||0; if(t) loadAvailable(t);
    });
    wireUnassign();
  });
})();
</script>

<script>
// ---- Attendance Log panel logic ----
(function(){
 const BASE_URL=BASE_URL||'';
 const workerSel=document.getElementById('attWorkerSelect');
 const taskSel=document.getElementById('attTaskSelect');
 const dateInput=document.getElementById('attDate');
 const hoursInput=document.getElementById('attHours');
 const noteInput=document.getElementById('attNote');
 const form=document.getElementById('attAddForm');
 const alertBox=document.getElementById('attAlert');
 const listBox=document.getElementById('attList');
 const reloadBtn=document.getElementById('attReloadBtn');
 const exportBtn=document.getElementById('attExportBtn'); const exportGroup=document.getElementById('attExportGroup');
 const fFrom=document.getElementById('attFilterFrom');
 const fTo=document.getElementById('attFilterTo');
 const fApply=document.getElementById('attFilterApply');
 function showAlert(m,t='success'){alertBox.className='alert p-2 mb-0 alert-'+(t==='success'?'success':'danger');alertBox.textContent=m;}
 function clearAlert(){alertBox.className='alert d-none p-2 mb-0';alertBox.textContent='';}
 async function loadWorkers(){workerSel.innerHTML='<option>Loading...</option>';try{const r=await fetch(`${BASE_URL}api/supervisor/attendance_log.php?action=workers`,{credentials:'same-origin'});const d=await r.json();if(!d.success){workerSel.innerHTML='<option value="">Failed</option>';return;}const rows=d.data||[];if(!rows.length){workerSel.innerHTML='<option value="">No workers found</option>';return;}workerSel.innerHTML=rows.map(w=>`<option value='${w.id}'>${(w.name||('Worker #'+w.id)).replace(/[<>]/g,'')}</option>`).join('');}catch(e){workerSel.innerHTML='<option value="">Error</option>';}}
 async function loadList(){const df=fFrom.value;const dt=fTo.value;listBox.innerHTML='<div><span class="inline-loader"></span> Loading...</div>';try{const r=await fetch(`${BASE_URL}api/supervisor/attendance_log.php?action=list&date_from=${encodeURIComponent(df)}&date_to=${encodeURIComponent(dt)}`,{credentials:'same-origin'});const d=await r.json();if(!d.success){listBox.innerHTML='<div class="text-danger">Failed</div>';return;}const rows=d.data||[];if(!rows.length){listBox.innerHTML='<div class="text-muted">No entries</div>';return;}listBox.innerHTML=rows.map(r=>`<div class='border-bottom py-1'><strong>${r.log_date}</strong> ${(r.worker_name||('#'+r.worker_user_id))}${r.task_title?` <span class='text-muted'>[${r.task_title.replace(/[<>]/g,'')}]</span>`:''}${r.hours?` <span class='badge bg-info'>${Number(r.hours).toFixed(2)}h</span>`:''}${r.note?`<div>${r.note.replace(/[<>]/g,'')}</div>`:''}</div>`).join('');}catch(e){listBox.innerHTML='<div class="text-danger">Error</div>';}}
 async function submitForm(ev){
  ev.preventDefault();
  clearAlert();
  const payload={worker_id:parseInt(workerSel.value,10),task_id:taskSel.value?parseInt(taskSel.value,10):null,date:dateInput.value,hours:hoursInput.value||null,note:noteInput.value.trim()||null};
  if(!payload.worker_id){showAlert('Worker required','danger');return;}
  try{
    const r=await fetch(`${BASE_URL}api/supervisor/attendance_log.php?action=add`,{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify(payload)});
    const d=await r.json();
    if(d.success){
      showAlert(d.message||'Added','success');
      hoursInput.value='';
      noteInput.value='';
      loadList();
    } else {
      showAlert(d.message||'Failed','danger');
    }
  }catch(e){
    showAlert('Server error','danger');
  }
 }
 async function exportData(){const df=fFrom.value;const dt=fTo.value;const grp=exportGroup.value;window.open(`${BASE_URL}api/supervisor/attendance_log.php?action=export&date_from=${encodeURIComponent(df)}&date_to=${encodeURIComponent(dt)}&group=${encodeURIComponent(grp)}`,'_blank');}
 form.addEventListener('submit',submitForm); reloadBtn.addEventListener('click',loadList); fApply.addEventListener('click',loadList); exportBtn.addEventListener('click',exportData);
 function initLoad(){
   const prefilled = workerSel.options.length;
   if(prefilled){ console.log('[Attendance] Prefilled workers:', prefilled); }
   loadWorkers().then(()=>{
     console.log('[Attendance] After AJAX workers:', workerSel.options.length);
     if(!workerSel.options.length){ console.warn('[Attendance] No workers after fetch, retrying...'); setTimeout(()=>loadWorkers().then(()=>console.log('[Attendance] Retry workers:', workerSel.options.length)),1500); }
   });
   loadList();
 }
 if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initLoad); else initLoad();
})();
</script>

<!-- Attendance Modal -->
<div class="modal fade" id="attendanceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Recent Attendance</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="attendanceModalBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>