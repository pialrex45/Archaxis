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
            <select class="form-select form-select-sm" id="sm_project_select" required>
              <option value="" selected disabled>Loading projects...</option>
            </select>
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

<!-- Flat Tasks List (all accessible tasks) -->
<div class="row mt-4">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header d-flex flex-wrap gap-2 align-items-center">
        <strong id="sm-tasks-flat-title">All Open Tasks</strong>
        <div class="ms-auto btn-group btn-group-sm" role="group" aria-label="Task view mode">
          <button type="button" id="smModeFlat" class="btn btn-outline-primary active" onclick="setTaskDisplayMode('flat')">Flat</button>
          <button type="button" id="smModeGrouped" class="btn btn-outline-primary" onclick="setTaskDisplayMode('grouped')">Grouped</button>
        </div>
        <button class="btn btn-sm btn-outline-secondary" onclick="reloadTasksMode()">Refresh</button>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm align-middle" id="sm-flat-tasks-table">
            <thead><tr><th>#</th><th>Title</th><th>Project</th><th>Status</th><th>Assignee</th><th>Due</th><th>Actions</th></tr></thead>
            <tbody id="sm-flat-tasks-body"><tr><td colspan="7" class="text-muted">Loading…</td></tr></tbody>
          </table>
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
          <div class="mb-2"><label class="form-label">Project</label>
            <select name="project_id" id="smTaskProjectSelect" class="form-select form-select-sm" required>
              <option value="" selected disabled>Loading...</option>
            </select>
          </div>
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

<!-- Assign Subcontractor Modal -->
<div class="modal fade" id="assignTaskModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Assign Subcontractor</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
        <form id="assignTaskForm">
          <input type="hidden" name="task_id" id="assignTaskId">
          <input type="hidden" name="project_id" id="assignTaskProjectId">
          <div class="mb-2"><label class="form-label">Subcontractor</label>
            <select name="subcontractor_id" id="assignSubcontractorSelect" class="form-select form-select-sm" required>
              <option value="" disabled selected>Loading...</option>
            </select>
          </div>
        </form>
        <div id="assignTaskMsg" class="small"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="submitAssignTask();">Assign</button>
      </div>
    </div>
  </div>
</div>

<!-- Project Overview Modal -->
<div class="modal fade" id="projectOverviewModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="projectOverviewTitle">Project Overview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="projectOverviewBody">
        <!-- Filled dynamically -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
  function ensureModalParent(id){
    var el = document.getElementById(id);
    if (el && el.parentNode !== document.body) { document.body.appendChild(el); }
  }
  function forceModalInteraction(id){
    setTimeout(function(){
      var el = document.getElementById(id);
      if (!el) return;
      el.style.zIndex = '5001';
      el.style.pointerEvents = 'auto';
      var backdrop = document.querySelector('.modal-backdrop');
      if (backdrop) { backdrop.style.zIndex = '5000'; backdrop.style.pointerEvents='auto'; }
      document.body.classList.add('modal-open');
    }, 30);
  }
  function openCreateTaskModal(){ ensureProjectOptionsLoaded(); ensureModalParent('createTaskModal'); var m = new bootstrap.Modal(document.getElementById('createTaskModal')); m.show(); forceModalInteraction('createTaskModal'); }
  function openRequestMaterialModal(){ ensureModalParent('requestMaterialModal'); var m = new bootstrap.Modal(document.getElementById('requestMaterialModal')); m.show(); forceModalInteraction('requestMaterialModal'); }
  // Open Project Overview (modal rendering)
  function openProjectOverview(e){
    if (e) e.preventDefault();
    var sel = document.getElementById('sm_project_select');
    if(!sel) return; var id = sel.value; if(!id) return;
    ensureModalParent('projectOverviewModal');
    var modalEl = document.getElementById('projectOverviewModal');
    var body = document.getElementById('projectOverviewBody');
    var title = document.getElementById('projectOverviewTitle');
    title.textContent = 'Project Overview (#'+id+')';
    body.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm" role="status"></div> Loading…</div>';
    var m = new bootstrap.Modal(modalEl);
    m.show();
    // Fetch overview
    fetch('<?= url('api/site_manager/projects.php') ?>?project_id='+encodeURIComponent(id))
      .then(r=>r.json())
      .then(j=>{
        if(!j || !j.success || !j.data){
          body.innerHTML = '<div class="text-danger">Failed to load project overview.</div>';
          return;
        }
        renderProjectOverview(j.data, body);
      })
      .catch(()=>{ body.innerHTML = '<div class="text-danger">Request failed.</div>'; });
  }

  function renderProjectOverview(data, container){
    var p = data.project || {};
    var name = p.name || p.project_name || ('Project #'+(p.id||''));
    var status = (p.status||'').toString();
  var assignments = data.assignments || {};
  var owner = assignments.owner || {};
  var siteManager = assignments.site_manager || {};
  var otherMembers = assignments.other_members || [];
  var tasks = (data.tasks && data.tasks.recent) ? data.tasks.recent : [];
  var taskCounts = (data.tasks && data.tasks.counts) ? data.tasks.counts : {};
  var progressPercent = (data.tasks && typeof data.tasks.progress_percent !== 'undefined') ? data.tasks.progress_percent : null;
    var materials = (data.materials && data.materials.recent) ? data.materials.recent : [];
    var materialCounts = (data.materials && data.materials.counts) ? data.materials.counts : {};
    var finance = data.finance || {};
    var pos = (data.purchase_orders && data.purchase_orders.recent) ? data.purchase_orders.recent : [];
    var posTotal = (data.purchase_orders && data.purchase_orders.total) ? data.purchase_orders.total : pos.length;

    function countsTable(counts){
      var keys = Object.keys(counts);
      if(!keys.length) return '<span class="text-muted">None</span>';
      return '<div class="small d-flex flex-wrap gap-2">'+keys.map(k=>'<span class="badge bg-secondary">'+escapeHtml(k)+': '+escapeHtml(counts[k])+'</span>').join('')+'</div>';
    }

    function tasksTable(list){
      if(!list.length) return '<div class="text-muted">No recent tasks.</div>';
      return '<div class="table-responsive"><table class="table table-sm mb-0">\n<thead><tr><th>#</th><th>Title</th><th>Status</th><th>Assignee</th><th>Due</th></tr></thead><tbody>'+
        list.slice(0,5).map(t=>{ var s=(t.status||'').toLowerCase(); var badge=s==='pending'?'warning':(s.includes('progress')?'primary':(s==='completed'?'success':'secondary')); return '<tr><td>'+escapeHtml(t.id||'')+'</td><td>'+escapeHtml(t.title||'')+'</td><td><span class="badge bg-'+badge+'">'+escapeHtml(t.status||'')+'</span></td><td>'+escapeHtml(t.assigned_to_name||'—')+'</td><td>'+escapeHtml(t.due_date||'')+'</td></tr>'; }).join('')+
        '</tbody></table></div>';
    }

    function materialsTable(list){
      if(!list.length) return '<div class="text-muted">No recent materials.</div>';
      return '<div class="table-responsive"><table class="table table-sm mb-0">\n<thead><tr><th>#</th><th>Name</th><th>Qty</th><th>Status</th></tr></thead><tbody>'+
        list.slice(0,5).map(m=>{ var s=(m.status||'').toLowerCase(); var badge = s==='requested'?'warning':(s==='approved'?'primary':(s==='delivered'?'success':(s==='rejected'?'danger':'secondary'))); return '<tr><td>'+escapeHtml(m.id||m.material_id||'')+'</td><td>'+escapeHtml(m.material_name||m.name||'')+'</td><td>'+escapeHtml(m.quantity||m.qty||'')+'</td><td><span class="badge bg-'+badge+'">'+escapeHtml(m.status||'')+'</span></td></tr>'; }).join('')+
        '</tbody></table></div>';
    }

    function poTable(list){
      if(!list.length) return '<div class="text-muted">No recent purchase orders.</div>';
      return '<div class="table-responsive"><table class="table table-sm mb-0">\n<thead><tr><th>#</th><th>Supplier</th><th>Status</th><th>Total</th></tr></thead><tbody>'+
        list.slice(0,5).map(po=>{ var s=(po.status||'').toLowerCase(); var badge = s==='ordered'?'primary':(s==='delivered'?'success':'secondary'); return '<tr><td>'+escapeHtml(po.id||'')+'</td><td>'+escapeHtml(po.supplier_name||'')+'</td><td><span class="badge bg-'+badge+'">'+escapeHtml(po.status||'')+'</span></td><td>'+escapeHtml(po.total_amount||po.total||'')+'</td></tr>'; }).join('')+
        '</tbody></table></div>';
    }

    container.innerHTML = `
      <div class="mb-3">
        <h5 class="mb-1">${escapeHtml(name)}</h5>
        <div class="small text-muted mb-1">Status: ${escapeHtml(status||'')}</div>
        <div class="small">
          <strong>Owner:</strong> ${escapeHtml(owner.name||('#'+(owner.id||'?')))}
          ${ siteManager && siteManager.id ? ' | <strong>Site Manager:</strong> '+escapeHtml(siteManager.name||('#'+siteManager.id)) : '' }
        </div>
        ${ otherMembers.length ? ('<div class="small mt-1"><strong>Team:</strong> '+otherMembers.slice(0,8).map(m=>escapeHtml(m.name||('#'+m.id))).join(', ')+(otherMembers.length>8?'…':'')+'</div>') : '' }
      </div>
      <div class="mb-3">
        <h6 class="mb-1">Task Counts</h6>
        ${countsTable(taskCounts)}
      </div>
      ${ progressPercent!==null ? `<div class=\"mb-3\"><h6 class=\"mb-1\">Progress</h6><div class=\"progress\" style=\"height:8px;\"><div class=\"progress-bar bg-success\" role=\"progressbar\" style=\"width:${progressPercent}%;\" aria-valuenow=\"${progressPercent}\" aria-valuemin=\"0\" aria-valuemax=\"100\"></div></div><div class=\"small text-muted mt-1\">${progressPercent}% completed</div></div>` : '' }
      <div class="mb-3">
        <h6 class="mb-1">Recent Tasks</h6>
        ${tasksTable(tasks)}
      </div>
      <div class="mb-3">
        <h6 class="mb-1">Material Counts</h6>
        ${countsTable(materialCounts)}
      </div>
      <div class="mb-3">
        <h6 class="mb-1">Recent Materials</h6>
        ${materialsTable(materials)}
      </div>
      <div class="mb-3">
        <h6 class="mb-1">Finance Summary</h6>
        <div class="small d-flex flex-wrap gap-2">${finance ? (
          ['income','expense','balance'].filter(k=>finance[k]!==undefined).map(k=>'<span class="badge bg-secondary">'+escapeHtml(k)+': '+escapeHtml(finance[k])+'</span>').join('') || '<span class="text-muted">No data</span>'
        ) : '<span class="text-muted">No data</span>'}</div>
      </div>
      <div class="mb-1 d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Recent Purchase Orders</h6>
        <span class="badge bg-secondary">Total: ${escapeHtml(posTotal)}</span>
      </div>
      ${poTable(pos)}
      <div class="text-muted small mt-3">Showing up to 5 recent items per category.</div>
    `;
  }
  async function submitCreateTask(){
    const form = document.getElementById('createTaskForm');
    const data = Object.fromEntries(new FormData(form).entries());
    const msgEl = document.getElementById('createTaskMsg');
    msgEl.textContent = 'Saving...';
    try {
      const res = await fetch('<?= url('api/site_manager/tasks.php?action=create') ?>', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data) });
      const j = await res.json();
      msgEl.textContent = j.success ? 'Created' : (j.message||'Failed');
      if (j.success) {
        form.reset();
        ensureProjectOptionsLoaded();
        // Refresh dashboard tasks list
        loadSiteManagerDashboard();
        setTimeout(()=>bootstrap.Modal.getInstance(document.getElementById('createTaskModal')).hide(), 800);
      }
    } catch (e) { msgEl.textContent = 'Request failed'; }
  }

  let smProjectsCache = null;
  async function ensureProjectOptionsLoaded(){
    const sel = document.getElementById('smTaskProjectSelect');
    if (!sel) return;
    if (smProjectsCache === null) {
      try {
        const res = await fetch('<?= url('api/site_manager/my_projects.php') ?>');
        const j = await res.json();
        smProjectsCache = (j && j.projects) ? j.projects : [];
      } catch (e) { smProjectsCache = []; }
    }
    sel.innerHTML = '';
    if (!smProjectsCache.length) { sel.innerHTML = '<option value="" disabled>No projects</option>'; return; }
    sel.innerHTML = '<option value="" disabled selected>Select project</option>' + smProjectsCache.map(p => {
      const id = p.id || p.project_id; const name = (p.name||p.project_name||('Project #'+id));
      return `<option value="${id}">${escapeHtml(name)} (#${id})</option>`;
    }).join('');
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
  document.addEventListener('DOMContentLoaded', () => { loadSiteManagerDashboard(); populateOverviewSelect(); });

  async function populateOverviewSelect(){
    const sel = document.getElementById('sm_project_select');
    if(!sel) return;
    // Reuse cache if already loaded
    if (smProjectsCache === null){
      try {
        const res = await fetch('<?= url('api/site_manager/my_projects.php') ?>');
        const j = await res.json();
        smProjectsCache = (j && j.projects) ? j.projects : [];
      } catch(_){ smProjectsCache = []; }
    }
    if(!smProjectsCache.length){
      sel.innerHTML = '<option value="" disabled selected>No projects available</option>';
      return;
    }
    sel.innerHTML = '<option value="" disabled selected>Select a project...</option>' + smProjectsCache.map(p=>{
      const id = p.id || p.project_id; const name = p.name || p.project_name || ('Project #'+id);
      return `<option value="${id}">${escapeHtml(name)} (#${id})</option>`;
    }).join('');
  }

  async function loadSiteManagerDashboard(){
    try {
  const base = '<?= url('api/site_manager/projects.php') ?>';
  const myProjectsEndpoint = '<?= url('api/site_manager/my_projects.php') ?>';
  // 1) Fetch assigned projects via simplified endpoint
  const pr = await fetch(myProjectsEndpoint);
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
          let tasks = [];
          if (j.data.tasks) {
            if (Array.isArray(j.data.tasks.recent)) tasks = j.data.tasks.recent;
            else if (Array.isArray(j.data.tasks.open)) tasks = j.data.tasks.open;
            else if (Array.isArray(j.data.tasks.list)) tasks = j.data.tasks.list;
            else if (Array.isArray(j.data.tasks)) tasks = j.data.tasks;
          }
          if (!Array.isArray(tasks)) tasks = [];
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
            return `<tr><td>${escapeHtml(tid)}</td><td>${escapeHtml(title)}</td><td><span class="badge bg-${badge}">${escapeHtml(t.status||'')}</span></td><td>${escapeHtml(ass)}</td><td>${escapeHtml(due)}</td><td><button class='btn btn-sm btn-outline-primary' onclick='openAssignTaskModal(${Number(tid)}, ${Number(pid)})'>Assign</button></td></tr>`;
          }).join('');

          const table = rows ? `
            <div class="table-responsive">
              <table class="table table-sm">
                <thead><tr><th>#</th><th>Task</th><th>Status</th><th>Assignee</th><th>Due</th><th>Actions</th></tr></thead>
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
      // Load flat tasks after grouped load
      loadFlatTasks();
    } catch (e) {
      // Graceful fallback
      const body = document.getElementById('sm-projects-body');
      if (body) body.innerHTML = '<tr><td colspan="3" class="text-danger">Failed to load</td></tr>';
      const empty = document.getElementById('sm-tasks-empty');
      if (empty) empty.textContent = 'Failed to load tasks.';
    }
  }

  let smTaskDisplayMode = 'flat';
  function setTaskDisplayMode(mode){
    if (mode !== 'flat' && mode !== 'grouped') mode = 'flat';
    smTaskDisplayMode = mode;
    document.getElementById('smModeFlat').classList.toggle('active', mode==='flat');
    document.getElementById('smModeGrouped').classList.toggle('active', mode==='grouped');
    reloadTasksMode();
  }
  function reloadTasksMode(){ loadFlatTasks(); }

  async function loadFlatTasks(){
    const body = document.getElementById('sm-flat-tasks-body');
    if (!body) return;
    body.innerHTML = '<tr><td colspan="7" class="text-muted">Loading…</td></tr>';
    try {
      const res = await fetch('<?= url('api/site_manager/tasks.php?action=list') ?>');
      const j = await res.json();
      if (!j || !j.success) { body.innerHTML = '<tr><td colspan="7" class="text-danger">Failed to load</td></tr>'; return; }
      let rows = (j.data||[]).filter(t => {
        const s = (t.status||'').toLowerCase();
        return s !== 'completed' && s !== 'cancelled';
      });
      if (!rows.length) { body.innerHTML = '<tr><td colspan="7" class="text-muted">No open tasks</td></tr>'; return; }
      if (smTaskDisplayMode === 'grouped') {
        const groups = {};
        rows.forEach(t => { const pid = t.project_id; if (!groups[pid]) groups[pid] = []; groups[pid].push(t); });
        let html = '';
        Object.keys(groups).forEach(pid => {
          const list = groups[pid];
          html += `<tr class='table-active'><td colspan='7'><strong>${escapeHtml(list[0].project_name||('Project #'+pid))}</strong> <span class='badge bg-primary ms-2'>${list.length}</span></td></tr>`;
          html += list.slice(0,100).map(t => taskRowHtml(t)).join('');
        });
        body.innerHTML = html;
      } else {
        body.innerHTML = rows.slice(0,50).map(t => taskRowHtml(t)).join('');
      }
    } catch (e) {
      body.innerHTML = '<tr><td colspan="7" class="text-danger">Load error</td></tr>';
    }
  }

  function taskRowHtml(t){
    const s = (t.status||'').toLowerCase();
    const badge = s==='pending'?'warning':(s.includes('progress')?'primary':(s==='completed'?'success':(s==='cancelled'?'danger':'secondary')));
    const ass = t.assigned_to_name || '—';
    const due = t.due_date || '';
    return `<tr><td>${escapeHtml(t.id||'')}</td><td>${escapeHtml(t.title||'')}</td><td>${escapeHtml(t.project_name||'')}</td><td><span class='badge bg-${badge}'>${escapeHtml(t.status||'')}</span></td><td>${escapeHtml(ass)}</td><td>${escapeHtml(due)}</td><td><button class='btn btn-sm btn-outline-primary' onclick='openAssignTaskModal(${Number(t.id)}, ${Number(t.project_id)})'>Assign</button></td></tr>`;
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

  // Assignment helpers
  function openAssignTaskModal(taskId, projectId){
    document.getElementById('assignTaskId').value = taskId;
    document.getElementById('assignTaskProjectId').value = projectId;
    loadSubcontractors(projectId);
    ensureModalParent('assignTaskModal');
    const m = new bootstrap.Modal(document.getElementById('assignTaskModal'));
    m.show();
    forceModalInteraction('assignTaskModal');
  }

  async function loadSubcontractors(projectId){
    const sel = document.getElementById('assignSubcontractorSelect');
    sel.innerHTML = '<option disabled selected>Loading...</option>';
    try {
      const res = await fetch('<?= url('api/site_manager/subcontractors.php') ?>?project_id='+encodeURIComponent(projectId));
      const j = await res.json();
      const list = (j && j.data) ? j.data : [];
      // If fallback (not project scoped), still allow selection
      if (!list.length){
        let detail = '';
        if (j && j.debug && j.debug.reason === 'none_found') {
          const a = j.debug.approved_subcontractors ?? '?';
            const p = j.debug.potential_subcontractors ?? '?';
          detail = ` (Approved: ${a} / Potential: ${p}. Need at least one approved=1)`;
        }
        sel.innerHTML = '<option disabled selected>No subcontractors'+detail+'</option>';
        return;
      }
      sel.innerHTML = '<option disabled selected>Select subcontractor</option>' + list.map(s => `<option value="${s.id}">${escapeHtml(s.name||('User #'+s.id))}</option>`).join('');
    } catch (e) {
      sel.innerHTML = '<option disabled selected>Error loading</option>';
    }
  }

  async function submitAssignTask(){
    const form = document.getElementById('assignTaskForm');
    const msg = document.getElementById('assignTaskMsg');
    const data = Object.fromEntries(new FormData(form).entries());
    if (!data.task_id || !data.subcontractor_id){ msg.textContent='Missing fields'; return; }
    msg.textContent = 'Saving...';
    try {
      const res = await fetch('<?= url('api/site_manager/tasks.php?action=assign_subcontractor') ?>', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ task_id: Number(data.task_id), subcontractor_id: Number(data.subcontractor_id) }) });
      const j = await res.json();
      msg.textContent = j.success ? 'Assigned' : (j.message||'Failed');
      if (j.success){
        loadSiteManagerDashboard();
        setTimeout(()=>{ const inst = bootstrap.Modal.getInstance(document.getElementById('assignTaskModal')); if (inst) inst.hide(); }, 700);
      }
    } catch (e) {
      msg.textContent = 'Request failed';
    }
  }

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
<?php include_once __DIR__ . '/../components/messaging_nav.php'; ?>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
