<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth(); if (!hasRole('project_manager')) { http_response_code(403); die('Access denied.'); }
$csrf = generate_csrf_token();
include_once __DIR__ . '/../layouts/header.php';
?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Tasks</h2>
  <button class="btn btn-primary btn-sm" id="btnNewTask" type="button">New Task</button>
  </div>

  <!-- Project-wise task list -->
  <div class="card mb-4"><div class="card-body">
    <div id="tasksContainer" class="text-muted">Loading...</div>
  </div></div>

  <!-- New Task Modal -->
  <div class="modal fade" id="taskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Create Task</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="taskForm">
            <div class="mb-3">
              <label for="task_project" class="form-label">Project</label>
              <select id="task_project" class="form-select" name="project_id" required></select>
            </div>
            <div class="mb-3">
              <label for="task_title" class="form-label">Title</label>
              <input id="task_title" name="title" class="form-control" required />
            </div>
            <div class="mb-3">
              <label for="task_description" class="form-label">Description</label>
              <textarea id="task_description" name="description" class="form-control" rows="3"></textarea>
            </div>
            <div class="row g-3">
              <div class="col-md-6">
                <label for="task_due" class="form-label">Due Date</label>
                <input type="date" id="task_due" name="due_date" class="form-control" />
              </div>
              <div class="col-md-6">
                <label for="task_status" class="form-label">Status</label>
                <select id="task_status" name="status" class="form-select">
                  <option value="pending">Pending</option>
                  <option value="in progress">In Progress</option>
                  <option value="completed">Completed</option>
                  <option value="cancelled">Cancelled</option>
                </select>
              </div>
            </div>
          </form>
          <div id="taskAlert" class="alert d-none mt-2" role="alert"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" id="btnSaveTask">Save Task</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  const CSRF_TOKEN = "<?= $csrf ?>";
  const API_LIST_PROJECTS = "<?= url('/api/pm/projects/list.php') ?>";
  const API_LIST_TASKS    = "<?= url('/api/pm/tasks/list.php') ?>";
  const API_CREATE_TASK   = "<?= url('/api/pm/tasks/create.php') ?>";

  function candidates(urlStr) {
    // Try given URL, then without leading slash, then relative paths
    const c = [urlStr];
    if (urlStr.startsWith('/')) c.push(urlStr.substring(1));
    c.push('../' + (urlStr.startsWith('/') ? urlStr.substring(1) : urlStr));
    c.push('../../' + (urlStr.startsWith('/') ? urlStr.substring(1) : urlStr));
    return Array.from(new Set(c));
  }

  async function fetchWithFallback(urlStr, opts = {}) {
    const list = candidates(urlStr);
    let lastErr;
    for (const u of list) {
      try {
        const res = await fetch(u, opts);
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res;
      } catch (e) { lastErr = e; }
    }
    throw lastErr || new Error('All fetch attempts failed');
  }

  async function fetchJson(url, opts = {}) {
    try {
      const res = await fetchWithFallback(url, opts);
      const text = await res.text();
      let data;
      try { data = text ? JSON.parse(text) : {}; } catch (e) { console.error('Invalid JSON from', url, text); throw e; }
      if (!res.ok) { console.error('HTTP error', res.status, data); throw new Error(data && data.message || 'Request failed'); }
      return data;
    } catch (err) {
      console.error('fetchJson error for', url, err);
      throw err;
    }
  }

  function statusBadge(status) {
    const s = (status || '').toLowerCase();
    let cls = 'secondary';
    if (s === 'in progress') cls = 'primary';
    else if (s === 'pending') cls = 'warning';
    else if (s === 'completed') cls = 'success';
    else if (s === 'cancelled') cls = 'danger';
    return `<span class="badge bg-${cls}">${status || ''}</span>`;
  }

  async function loadProjectsIntoSelect() {
    const sel = document.getElementById('task_project');
    sel.innerHTML = '<option value="">Loading...</option>';
    const data = await fetchJson(API_LIST_PROJECTS + '?_=' + Date.now());
    const items = (data && data.data) || [];
    if (!items.length) { sel.innerHTML = '<option value="">No projects</option>'; return; }
    sel.innerHTML = items.map(p => `<option value="${p.id}">${p.name}</option>`).join('');
  }

  async function loadTasksGrouped() {
    const container = document.getElementById('tasksContainer');
    container.textContent = 'Loading...';
    let data;
    try {
      data = await fetchJson(API_LIST_TASKS + '?_=' + Date.now());
    } catch (e) {
      console.error('Failed to load tasks', e);
      container.innerHTML = `<div class="text-danger">Failed to load tasks: ${e.message || e}</div>`;
      return;
    }
    const tasks = (data && data.data) || [];
    console.debug('Tasks API data', tasks);
    // Group by project
    const grouped = {};
    for (const t of tasks) {
      const s = (t.status || '').toLowerCase();
      if (s === 'completed' || s === 'cancelled') continue; // show open by default
      const pid = t.project_id; if (!pid) continue;
      if (!grouped[pid]) grouped[pid] = { project_name: t.project_name || `Project #${pid}`, items: [] };
      grouped[pid].items.push(t);
    }
    const projectIds = Object.keys(grouped);
    if (!projectIds.length) { container.textContent = 'No open tasks.'; return; }
    let html = '<div class="accordion" id="pmTasks">';
    let i = 0;
    for (const pid of projectIds) {
      const g = grouped[pid]; i++; const cid = `taskgrp${i}`;
      html += `
        <div class="accordion-item">
          <h2 class="accordion-header" id="h${cid}">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#${cid}">
              ${g.project_name} <span class="badge bg-primary ms-2">${g.items.length}</span>
            </button>
          </h2>
          <div id="${cid}" class="accordion-collapse collapse" data-bs-parent="#pmTasks">
            <div class="accordion-body">
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead><tr><th>#</th><th>Task</th><th>Status</th><th>Assignee</th><th>Due</th></tr></thead>
                  <tbody>
                    ${g.items.map(t => `
                      <tr>
                        <td>${t.id || ''}</td>
                        <td>${(t.title || '').replace(/</g,'&lt;')}</td>
                        <td>${statusBadge(t.status)}</td>
                        <td>${(t.assigned_to_name || '').replace(/</g,'&lt;')}</td>
                        <td>${t.due_date || ''}</td>
                      </tr>
                    `).join('')}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>`;
    }
    html += '</div>';
    container.innerHTML = html;
  }

  async function createTask() {
    const form = document.getElementById('taskForm');
    const payload = {
      project_id: form.project_id.value,
      title: form.title.value,
      description: form.description.value,
      due_date: form.due_date.value || null,
      status: form.status.value || 'pending',
    };
    const resp = await fetchWithFallback(API_CREATE_TASK, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
      body: JSON.stringify(payload)
    });
    const data = await resp.json();
    const alertBox = document.getElementById('taskAlert');
    if (data && data.success) {
      alertBox.className = 'alert alert-success';
      alertBox.textContent = 'Task created successfully';
      alertBox.classList.remove('d-none');
      form.reset();
      loadTasksGrouped();
      // Auto-close modal after short delay
      setTimeout(() => {
        const modalEl = document.getElementById('taskModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal && modal.hide();
        alertBox.classList.add('d-none');
      }, 800);
    } else {
      alertBox.className = 'alert alert-danger';
      alertBox.textContent = (data && data.message) || 'Failed to create task';
      alertBox.classList.remove('d-none');
    }
  }

  function ensureModalParent(id){
    const el = document.getElementById(id);
    if (el && el.parentNode !== document.body) document.body.appendChild(el);
  }
  function forceModalInteraction(id){
    setTimeout(()=>{
      const el = document.getElementById(id); if (!el) return;
      el.style.zIndex='5001'; el.style.pointerEvents='auto';
      const back = document.querySelector('.modal-backdrop');
      if (back){ back.style.zIndex='5000'; back.style.pointerEvents='auto'; }
      document.body.classList.add('modal-open');
    },30);
  }
  function openTaskModal(){
    ensureModalParent('taskModal');
    const m = new bootstrap.Modal(document.getElementById('taskModal'));
    m.show();
    forceModalInteraction('taskModal');
  }
  document.getElementById('btnNewTask').addEventListener('click', openTaskModal);
  document.getElementById('btnSaveTask').addEventListener('click', createTask);
  // Load data on page ready
  document.addEventListener('DOMContentLoaded', () => {
    loadProjectsIntoSelect();
    loadTasksGrouped();
  });
</script>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
