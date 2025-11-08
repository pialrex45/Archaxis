<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';

requireAuth();
// Allow site_manager, supervisor, or admin (align with route/API guards)
if (!(hasRole('site_manager') || hasRole('supervisor') || hasRole('admin'))) { http_response_code(403); die('Access denied. Site Managers only.'); }

$pageTitle = 'Site Manager Dashboard';
$currentPage = 'dashboard';
include_once __DIR__ . '/../layouts/header.php';
?>
<div class="row">
  <div class="col-md-12">
    <h1 class="mb-4">Site Manager Dashboard</h1>
    <div class="alert alert-info">Placeholder for Site Manager role dashboard.</div>
  </div>
</div>
<div class="row g-3">
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Tasks</h5>
        <p class="card-text">Create, assign, update tasks and add comments.</p>
        <div class="d-flex gap-2 flex-wrap">
          <a class="btn btn-sm btn-primary" href="#" onclick="openCreateTaskModal(); return false;">Create Task</a>
          <a class="btn btn-sm btn-outline-secondary" href="<?= url('api/tasks/index.php') ?>" target="_blank">All Tasks (API)</a>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Materials</h5>
        <p class="card-text">Request, approve and track project materials.</p>
        <div class="d-flex gap-2 flex-wrap">
          <a class="btn btn-sm btn-primary" href="#" onclick="openRequestMaterialModal(); return false;">Request Material</a>
          <a class="btn btn-sm btn-outline-secondary" href="<?= url('api/materials/index.php') ?>" target="_blank">All Materials (API)</a>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Purchase Orders</h5>
        <p class="card-text">View POs and mark delivery (ordered → delivered).</p>
        <div class="d-flex gap-2 flex-wrap">
          <a class="btn btn-sm btn-outline-secondary" href="<?= url('api/purchase_orders/index.php') ?>" target="_blank">All POs (API)</a>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Projects Overview</h5>
        <p class="card-text">Quick read-only view of a project's tasks and materials.</p>
        <form class="row gy-2 gx-2 align-items-center" onsubmit="openProjectOverview(event);">
          <div class="col">
            <input type="number" min="1" class="form-control form-control-sm" id="sm_project_id" placeholder="Project ID" required>
          </div>
          <div class="col-auto">
            <button class="btn btn-sm btn-primary" type="submit">Open</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Products</h5>
        <p class="card-text">Browse available products (read-only).</p>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('api/site_manager/products.php') ?>" target="_blank">View Products</a>
      </div>
    </div>
  </div>

  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Suppliers</h5>
        <p class="card-text">Browse suppliers (read-only).</p>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('api/site_manager/suppliers.php') ?>" target="_blank">View Suppliers</a>
      </div>
    </div>
  </div>
</div>

<!-- Dynamic data sections (populated via API; non-breaking) -->
<div class="row mt-4">
  <div class="col-md-12">
    <div class="card">
      <div class="card-body">
        <div class="row text-center" id="sm-kpis">
          <div class="col-6 col-md-3 mb-3">
            <div class="text-muted">Total Projects</div>
            <div class="h3 mb-0" id="sm-kpi-projects">—</div>
          </div>
          <div class="col-6 col-md-3 mb-3">
            <div class="text-muted">Open Tasks</div>
            <div class="h3 mb-0" id="sm-kpi-open-tasks">—</div>
          </div>
          <div class="col-6 col-md-3 mb-3">
            <div class="text-muted">Pending Materials</div>
            <div class="h3 mb-0" id="sm-kpi-pending-materials">—</div>
          </div>
          <div class="col-6 col-md-3 mb-3">
            <div class="text-muted">Open POs</div>
            <div class="h3 mb-0" id="sm-kpi-open-pos">—</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row mt-4">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <strong>My Projects</strong>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm">
            <thead><tr><th>#</th><th>Name</th><th>Status</th></tr></thead>
            <tbody id="sm-projects-body">
              <tr><td colspan="3" class="text-muted">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Open Tasks by Project</strong>
      </div>
      <div class="card-body">
        <div id="sm-tasks-accordion" class="accordion"></div>
        <div id="sm-tasks-empty" class="text-muted">Loading…</div>
      </div>
    </div>
  </div>
</div>

<div class="row mt-4">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header d-flex flex-wrap gap-2 align-items-center">
        <strong>Pending Material Requests</strong>
        <form class="ms-auto d-flex gap-2 align-items-center" onsubmit="loadPendingMaterials(event);">
          <label for="sm_pending_project_id" class="col-form-label col-form-label-sm">Project ID</label>
          <input id="sm_pending_project_id" type="number" min="1" class="form-control form-control-sm" style="width:120px" required>
          <button class="btn btn-sm btn-outline-primary" type="submit">Load</button>
        </form>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>#</th><th>Material</th><th>Qty</th><th>Project</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="sm-pending-materials-body">
              <tr><td colspan="6" class="text-muted">Enter a Project ID above and click Load.</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  
</div>

<!-- Lightweight modals (no backend views added) -->
<div class="modal fade" id="createTaskModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Create Task</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
        <form id="createTaskForm">
          <div class="mb-2"><label class="form-label">Project ID</label><input name="project_id" type="number" min="1" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">Title</label><input name="title" type="text" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">Description</label><textarea name="description" class="form-control form-control-sm" rows="3"></textarea></div>
          <div class="mb-2"><label class="form-label">Assigned To (User ID)</label><input name="assigned_to" type="number" min="1" class="form-control form-control-sm"></div>
          <div class="mb-2"><label class="form-label">Due Date</label><input name="due_date" type="date" class="form-control form-control-sm"></div>
          <div class="mb-2"><label class="form-label">Instructions</label><textarea name="instructions" class="form-control form-control-sm" rows="2"></textarea></div>
          <div class="mb-2"><label class="form-label">Priority</label>
            <select name="priority" class="form-select form-select-sm">
              <option value="normal" selected>Normal</option>
              <option value="high">High</option>
              <option value="low">Low</option>
            </select>
          </div>
        </form>
        <div id="createTaskMsg" class="small"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="submitCreateTask();">Create</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="requestMaterialModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Request Material</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
        <form id="requestMaterialForm">
          <div class="mb-2"><label class="form-label">Project ID</label><input name="project_id" type="number" min="1" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">Material Name</label><input name="material_name" type="text" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">Quantity</label><input name="quantity" type="number" min="1" class="form-control form-control-sm" required></div>
        </form>
        <div id="requestMaterialMsg" class="small"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="submitRequestMaterial();">Request</button>
      </div>
    </div>
  </div>
</div>

<script>
  function openCreateTaskModal(){ var m = new bootstrap.Modal(document.getElementById('createTaskModal')); m.show(); }
  function openRequestMaterialModal(){ var m = new bootstrap.Modal(document.getElementById('requestMaterialModal')); m.show(); }
  function openProjectOverview(e){ e.preventDefault(); var id = document.getElementById('sm_project_id').value; if(!id) return; window.open('<?= url('api/site_manager/projects.php') ?>?project_id='+encodeURIComponent(id), '_blank'); }
  async function submitCreateTask(){
    const form = document.getElementById('createTaskForm');
    const data = Object.fromEntries(new FormData(form).entries());
    const res = await fetch('<?= url('api/site_manager/tasks.php?action=create') ?>', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const j = await res.json();
    document.getElementById('createTaskMsg').textContent = j.success ? 'Created' : (j.message||'Failed');
    if (j.success) setTimeout(()=>bootstrap.Modal.getInstance(document.getElementById('createTaskModal')).hide(), 600);
  }
  async function submitRequestMaterial(){
    const form = document.getElementById('requestMaterialForm');
    const data = Object.fromEntries(new FormData(form).entries());
    const res = await fetch('<?= url('api/site_manager/materials.php?action=request') ?>', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const j = await res.json();
    document.getElementById('requestMaterialMsg').textContent = j.success ? 'Requested' : (j.message||'Failed');
    if (j.success) setTimeout(()=>bootstrap.Modal.getInstance(document.getElementById('requestMaterialModal')).hide(), 600);
  }

  // Dashboard data loading (non-breaking)
  document.addEventListener('DOMContentLoaded', loadSiteManagerDashboard);

  async function loadSiteManagerDashboard(){
    try {
      const base = '<?= url('api/site_manager/projects.php') ?>';
      // 1) Fetch assigned projects
      const pr = await fetch(base);
      const pj = await pr.json();
      const projects = (pj && pj.projects) ? pj.projects : [];
      updateProjectsTable(projects);
      // KPIs default values
      let kpiProjects = projects.length;
      let kpiOpenTasks = 0;
      let kpiPendingMaterials = 0;
      let kpiOpenPOs = 0;

      // 2) For each project, fetch overview to build grouped tasks and aggregate KPIs
      const accordion = document.getElementById('sm-tasks-accordion');
      const empty = document.getElementById('sm-tasks-empty');
      accordion.innerHTML = '';
      let panelIndex = 0;
      for (const p of projects.slice(0, 6)) { // limit to 6 projects to keep it light
        const pid = p.id || p.project_id;
        if (!pid) continue;
        try {
          const r = await fetch(base + '?project_id=' + encodeURIComponent(pid));
          const j = await r.json();
          if (!j || !j.success || !j.data) continue;
          const name = (j.data.project && (j.data.project.name || j.data.project.project_name)) || ('Project #' + pid);
          const tasks = (j.data.tasks && Array.isArray(j.data.tasks.recent)) ? j.data.tasks.recent : [];
          const counts = (j.data.tasks && j.data.tasks.counts) ? j.data.tasks.counts : {};
          const materialsCounts = (j.data.materials && j.data.materials.counts) ? j.data.materials.counts : {};
          const posRecent = (j.data.purchase_orders && Array.isArray(j.data.purchase_orders.recent)) ? j.data.purchase_orders.recent : [];

          // Aggregate KPIs
          const openCount = tasks.filter(t => {
            const s = (t.status||'').toString().toLowerCase();
            return s !== 'completed' && s !== 'cancelled';
          }).length;
          kpiOpenTasks += (counts.pending || 0) + (counts.in_progress || counts.inprogress || 0);
          kpiPendingMaterials += (materialsCounts.requested || 0);
          kpiOpenPOs += posRecent.filter(po => (po.status||'').toString().toLowerCase() !== 'delivered').length;

          // Build accordion section per project
          panelIndex++;
          const collapseId = 'smProjTasks' + panelIndex;
          const rows = tasks.map(t => {
            const ts = (t.status||'').toString().toLowerCase();
            if (ts === 'completed' || ts === 'cancelled') return '';
            const badge = ts.includes('progress') ? 'primary' : (ts==='pending'?'warning':'secondary');
            const due = t.due_date || '';
            const ass = t.assigned_to_name || '';
            const tid = t.id || '';
            const title = t.title || '';
            return `<tr><td>${escapeHtml(tid)}</td><td>${escapeHtml(title)}</td><td><span class="badge bg-${badge}">${escapeHtml(t.status||'')}</span></td><td>${escapeHtml(ass)}</td><td>${escapeHtml(due)}</td></tr>`;
          }).join('');

          const table = rows ? `
            <div class="table-responsive">
              <table class="table table-sm">
                <thead><tr><th>#</th><th>Task</th><th>Status</th><th>Assignee</th><th>Due</th></tr></thead>
                <tbody>${rows}</tbody>
              </table>
            </div>` : '<div class="text-muted">No open tasks.</div>';

          const item = document.createElement('div');
          item.className = 'accordion-item';
          item.innerHTML = `
            <h2 class="accordion-header" id="heading${panelIndex}">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="false" aria-controls="${collapseId}">
                ${escapeHtml(name)} <span class="badge bg-primary ms-2">${openCount}</span>
              </button>
            </h2>
            <div id="${collapseId}" class="accordion-collapse collapse" aria-labelledby="heading${panelIndex}" data-bs-parent="#sm-tasks-accordion">
              <div class="accordion-body">${table}</div>
            </div>`;
          accordion.appendChild(item);
        } catch (_) { /* ignore per-project error */ }
      }
      if (accordion.children.length > 0) { empty.style.display = 'none'; } else { empty.textContent = 'No open tasks.'; }

      // 3) Update KPIs
      document.getElementById('sm-kpi-projects').textContent = kpiProjects;
      document.getElementById('sm-kpi-open-tasks').textContent = kpiOpenTasks;
      document.getElementById('sm-kpi-pending-materials').textContent = kpiPendingMaterials;
      document.getElementById('sm-kpi-open-pos').textContent = kpiOpenPOs;
    } catch (e) {
      // Graceful fallback
      const body = document.getElementById('sm-projects-body');
      if (body) body.innerHTML = '<tr><td colspan="3" class="text-danger">Failed to load</td></tr>';
      const empty = document.getElementById('sm-tasks-empty');
      if (empty) empty.textContent = 'Failed to load tasks.';
    }
  }

  function updateProjectsTable(projects){
    const body = document.getElementById('sm-projects-body');
    if (!body) return;
    if (!projects || projects.length === 0){ body.innerHTML = '<tr><td colspan="3" class="text-muted">No projects found.</td></tr>'; return; }
    body.innerHTML = projects.map(p => {
      const id = p.id || p.project_id || '';
      const name = p.name || p.project_name || '';
      const status = (p.status||'').toString();
      const s = status.toLowerCase();
      const badge = s==='active'?'primary':(s==='completed'?'success':(s==='on hold'||s==='on_hold'?'warning':(s==='cancelled'?'danger':'secondary')));
      return `<tr><td>${escapeHtml(id)}</td><td>${escapeHtml(name)}</td><td><span class="badge bg-${badge}">${escapeHtml(status)}</span></td></tr>`;
    }).join('');
  }

  function escapeHtml(x){ return String(x).replace(/[&<>"]+/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s])); }

  // Pending Materials (per project) using existing project overview API
  async function loadPendingMaterials(e){
    if (e) e.preventDefault();
    const pidEl = document.getElementById('sm_pending_project_id');
    const pid = pidEl && pidEl.value ? pidEl.value : '';
    const body = document.getElementById('sm-pending-materials-body');
    if (!pid) { if (body) body.innerHTML = '<tr><td colspan="6" class="text-danger">Project ID required.</td></tr>'; return; }
    try {
      body.innerHTML = '<tr><td colspan="6" class="text-muted">Loading…</td></tr>';
      const urlBase = '<?= url('api/site_manager/projects.php') ?>';
      const res = await fetch(urlBase + '?project_id=' + encodeURIComponent(pid));
      const j = await res.json();
      if (!j || !j.success || !j.data) { body.innerHTML = '<tr><td colspan="6" class="text-danger">Failed to load.</td></tr>'; return; }
      const projectName = (j.data.project && (j.data.project.name||j.data.project.project_name)) || ('Project #' + pid);
      const materials = Array.isArray(j.data.materials && j.data.materials.recent) ? j.data.materials.recent : [];
      const pending = materials.filter(m => (String(m.status||'').toLowerCase() === 'requested'));
      if (pending.length === 0) { body.innerHTML = '<tr><td colspan="6" class="text-muted">No pending material requests.</td></tr>'; return; }
      body.innerHTML = pending.map(m => {
        const id = m.id || m.material_id || '';
        const name = m.material_name || m.name || '';
        const qty = m.quantity || m.qty || '';
        return `<tr id="sm-mat-row-${id}">`+
               `<td>${escapeHtml(id)}</td>`+
               `<td>${escapeHtml(name)}</td>`+
               `<td>${escapeHtml(qty)}</td>`+
               `<td>${escapeHtml(projectName)}</td>`+
               `<td><span class="badge bg-warning">Requested</span></td>`+
               `<td class="d-flex gap-2">`+
                 `<button class="btn btn-success btn-sm" onclick="approveMaterial(${Number(id)})">Approve</button>`+
                 `<button class="btn btn-danger btn-sm" onclick="rejectMaterial(${Number(id)})">Reject</button>`+
               `</td>`+
               `</tr>`;
      }).join('');
    } catch (_) {
      body.innerHTML = '<tr><td colspan="6" class="text-danger">Failed to load.</td></tr>';
    }
  }

  async function approveMaterial(id){ await updateMaterialStatus(id, 'approve'); }
  async function rejectMaterial(id){ await updateMaterialStatus(id, 'reject'); }

  async function updateMaterialStatus(id, action){
    if (!id) return;
    const body = { id: Number(id) };
    try {
      const res = await fetch('<?= url('api/site_manager/materials.php') ?>?action='+encodeURIComponent(action), {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body)
      });
      const j = await res.json();
      if (!j || !j.success) { alert(j && j.message ? j.message : 'Failed'); return; }
      // Update row UI
      const row = document.getElementById('sm-mat-row-'+id);
      if (row) {
        const statusCell = row.querySelector('td:nth-child(5)');
        if (statusCell) statusCell.innerHTML = action === 'approve' ? '<span class="badge bg-primary">Approved</span>' : '<span class="badge bg-danger">Rejected</span>';
        const actionsCell = row.querySelector('td:nth-child(6)');
        if (actionsCell) actionsCell.innerHTML = '<span class="text-muted">Updated</span>';
      }
      // Decrement KPI for pending materials if present
      const kpi = document.getElementById('sm-kpi-pending-materials');
      if (kpi) { const v = parseInt(kpi.textContent||'0',10)||0; kpi.textContent = v>0 ? (v-1) : 0; }
    } catch (e) {
      alert('Request failed.');
    }
  }
</script>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
