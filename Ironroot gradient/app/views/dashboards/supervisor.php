<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/DashboardController.php';

// Check authentication and role
requireAuth();
if (!hasRole('supervisor')) {
    http_response_code(403);
    die('Access denied. Super Visors only.');
}

// Get dashboard data
$dashboardController = new DashboardController();
$dashboardData = $dashboardController->getSupervisorDashboard();

$pageTitle = 'Super Visor Dashboard';
$currentPage = 'dashboard';
?>

<?php include_once __DIR__ . '/../../views/layouts/header.php'; ?>

<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">Super Visor Dashboard</h1>
    </div>
</div>

<ul class="nav nav-tabs mb-3" id="supTabs">
  <li class="nav-item">
    <a href="#" class="nav-link active" data-tab="overview">Overview</a>
  </li>
  <li class="nav-item">
    <a href="#" class="nav-link" data-tab="team">Team</a>
  </li>
  <!-- Future: <li class="nav-item"><a href="#" class="nav-link" data-tab="payments">Payments</a></li> -->
  <!-- Keep minimal for now -->
 </ul>

<style>
  /* Team tab visual tweaks (non-invasive) */
  #tab-team .card { height: 100%; }
  #tab-team .card-header { background: #f8f9fa; }
  #teamList { min-height: 220px; }
  @media (max-width: 767.98px) {
    #enrollWorkerForm button[type="submit"] { width: 100%; }
  }
</style>

<?php if ($dashboardData['success']): ?>
    <div id="tab-overview" class="tab-pane">
    <div class="row">
        <!-- Stats Cards -->
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Tasks</h5>
                    <p class="card-text display-4"><?php echo htmlspecialchars($dashboardData['data']['tasks']['total']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Completed</h5>
                    <p class="card-text display-4"><?php echo htmlspecialchars($dashboardData['data']['tasks']['completed']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">In Progress</h5>
                    <p class="card-text display-4"><?php echo htmlspecialchars($dashboardData['data']['tasks']['in_progress']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Pending</h5>
                    <p class="card-text display-4"><?php echo htmlspecialchars($dashboardData['data']['tasks']['pending']); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Today's Tasks -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Your Assigned Tasks</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboardData['data']['tasks']['recent'])): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Project</th>
                                        <th>Status</th>
                                        <th>Due Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dashboardData['data']['tasks']['recent'] as $task): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($task['title']); ?></td>
                                            <td><?php echo htmlspecialchars($task['project_name']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $task['status'] === 'completed' ? 'success' : 
                                                         ($task['status'] === 'in progress' ? 'primary' : 'warning'); ?>">
                                                    <?php echo htmlspecialchars($task['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $task['due_date'] ? date('M j, Y', strtotime($task['due_date'])) : 'N/A'; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary js-update-task-btn" 
                                                        data-task-id="<?php echo (int)$task['id']; ?>"
                                                        data-status="<?php echo htmlspecialchars($task['status']); ?>">
                                                    Update
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No assigned tasks found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Attendance Status -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Today's Attendance</h5>
                </div>
                <div class="card-body">
                    <?php if ($dashboardData['data']['attendance']): ?>
                        <div class="text-center">
                            <h3 class="display-6">Checked In</h3>
                            <p class="text-muted">
                                <?php echo date('g:i A', strtotime($dashboardData['data']['attendance']['check_in_time'])); ?>
                            </p>
                            <?php if ($dashboardData['data']['attendance']['check_out_time']): ?>
                                <p>Checked Out: <?php echo date('g:i A', strtotime($dashboardData['data']['attendance']['check_out_time'])); ?></p>
                            <?php else: ?>
                                <button class="btn btn-primary">Check Out</button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center">
                            <h3 class="display-6">Not Checked In</h3>
                            <button class="btn btn-success">Check In</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Messages -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Recent Messages</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboardData['data']['recent_messages'])): ?>
                        <ul class="list-group">
                            <?php foreach ($dashboardData['data']['recent_messages'] as $message): ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong><?php echo htmlspecialchars(substr($message['message_text'], 0, 30)); ?>...</strong>
                                        </div>
                                        <small class="text-muted"><?php echo date('M j, Y', strtotime($message['created_at'])); ?></small>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No recent messages.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    </div>
<?php else: ?>
    <div class="alert alert-danger">
        Error loading dashboard data: <?php echo htmlspecialchars($dashboardData['message']); ?>
    </div>
<?php endif; ?>

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
      teamListEl.innerHTML = '<li class="list-group-item text-muted">No team members yet. Enroll a new worker or assign an existing worker by email.</li>';
      return;
    }
    teamListEl.innerHTML = rows.map(u => `
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <div>
          <strong>${u.name ? u.name.replace(/</g,'&lt;') : 'Worker #'+u.id}</strong>
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
          <strong>${u.name ? u.name.replace(/</g,'&lt;') : 'Worker #'+u.id}</strong>
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

<?php include_once __DIR__ . '/../../views/layouts/footer.php'; ?>

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