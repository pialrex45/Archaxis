<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';

requireAuth();
// Allow admin and site_engineer (align with router)
if (!function_exists('hasAnyRole')) {
  // Fallback if helper missing
  $allowed = hasRole('admin') || hasRole('site_engineer');
} else {
  $allowed = hasAnyRole(['admin','site_engineer']);
}
if (!$allowed) { http_response_code(403); die('Access denied. Site Engineers only.'); }

$pageTitle = 'Site Engineer Dashboard';
$currentPage = 'dashboard';

include_once __DIR__ . '/../layouts/header.php';
?>
<style>
  /* Scoped modern styling for Site Engineer dashboard */
  .se-modern .hero {
    background: linear-gradient(135deg, #f5f7ff 0%, #eef2ff 50%, #ffffff 100%);
    border-radius: 14px;
    padding: 18px 20px;
    margin-bottom: 18px;
    border: 1px solid #eef0f7;
  }
  .se-modern .hero h2 { font-weight: 600; margin: 0; }
  .se-modern .stat { background: #fff; border: 1px solid #eef0f7; border-radius: 12px; padding: 14px; display:flex; align-items:center; gap:12px; box-shadow: 0 4px 12px rgba(16,24,40,0.04); }
  .se-modern .stat .num { font-size: 22px; font-weight: 700; }
  .se-modern .stat .label { font-size: 12px; color: #6b7280; }
  .se-modern .stat .icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; background:#eef2ff; color:#4f46e5; }
  .se-modern .card { border-radius: 14px; border: 1px solid #eef0f7; box-shadow: 0 6px 18px rgba(16,24,40,0.05); }
  .se-modern .card-header { background:#fafbff; border-bottom:1px solid #eef0f7; }
  .se-modern .surface { background:#ffffff; border:1px solid #eef0f7; border-radius:12px; }
  .se-modern .section-title { font-weight:600; }
  .se-modern .mini { font-size: 12px; color:#6b7280; }
  .se-modern .searchbox { position: relative; }
  .se-modern .searchbox input { padding-left: 32px; }
  .se-modern .searchbox .ico { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#9ca3af; }
  /* Compact buttons to avoid wrapping */
  .btn-compact { white-space: nowrap; min-width: 92px; padding: .45rem .7rem; border-radius: 10px; display: inline-flex; align-items: center; gap: .35rem; text-transform: none; letter-spacing: 0; }
  @media (max-width: 991px){ .se-modern .stat { margin-bottom:10px; } }
</style>

<div class="se-modern">
  <div class="hero d-flex justify-content-between align-items-center flex-wrap mb-2">
    <div>
      <h2>Hello <?php echo htmlspecialchars($_SESSION['username'] ?? 'Engineer'); ?> <span class="mini">👋</span></h2>
      <div class="mini">Stay on top of approvals, tasks, and site activities.</div>
    </div>
  </div>
  <div class="row g-3 mb-2 align-items-stretch">
    <div class="col-12 col-md-3">
      <div class="stat h-100 d-flex flex-column justify-content-center">
        <div class="icon"><i class="fa-regular fa-file-lines"></i></div>
        <div>
          <div id="se_stat_designs" class="num">0</div>
          <div class="label">Pending Design Approvals</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="stat h-100 d-flex flex-column justify-content-center">
        <div class="icon" style="background:#ecfdf5;color:#047857"><i class="fa-solid fa-list-check"></i></div>
        <div>
          <div id="se_stat_tasks" class="num">0</div>
          <div class="label">Assigned Tasks</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="stat h-100 d-flex flex-column justify-content-center">
        <div class="icon" style="background:#fef3c7;color:#b45309"><i class="fa-solid fa-diagram-project"></i></div>
        <div>
          <div id="se_stat_projects" class="num">0</div>
          <div class="label">Active Projects</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card h-100 d-flex flex-column justify-content-center">
        <div class="card-body d-flex flex-column justify-content-center align-items-start">
          <h5 class="card-title">Designer</h5>
          <p class="card-text">Open the in-browser design tool to collaborate with clients.</p>
          <a class="btn btn-sm btn-primary mt-auto" href="<?= url('/diagram45/index.html'); ?>" target="_blank" rel="noopener">Open Designer</a>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">

  <!-- Designs Queue (Submitted) -->
  <div class="col-md-12">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 section-title">Designs Awaiting Approval</h5>
        <button class="btn btn-sm btn-soft-secondary btn-compact ms-2" id="collapse-designs-btn" type="button" onclick="toggleSectionCollapse('designs')">
          <i class="fa fa-minus"></i>
        </button>
        <div class="d-flex gap-2 align-items-center">
          <div class="searchbox">
            <i class="fa fa-search ico"></i>
            <input id="se_designs_search" type="text" class="form-control form-control-sm" placeholder="Search designs..." oninput="filterDesignsTable()" />
          </div>
          <a class="btn btn-sm btn-soft-primary" href="<?= url('/designs'); ?>">Open Designs</a>
        </div>
      </div>
  <div class="card-body" id="designs-section-body">
        <form class="row g-2 align-items-end mb-2">
          <div class="col-sm-4">
            <label class="form-label">Project</label>
            <select class="form-select form-select-sm" id="se_designs_project"></select>
          </div>
          <div class="col-sm-2">
            <button type="button" class="btn btn-sm btn-soft-primary" onclick="loadSubmittedDesigns();">Filter</button>
          </div>
        </form>
        <div class="table-responsive surface p-2">
          <table class="table table-sm align-middle" id="se_designs_table">
            <thead><tr><th>#</th><th>Project</th><th>Title</th><th>Status</th><th>Client File</th><th>Action</th></tr></thead>
            <tbody id="se_designs_tbody"><tr><td colspan="6" class="text-muted">Loading...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Designs Activity Log -->
  <div class="col-md-12">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 section-title">Recent Design Activities</h5>
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-sm btn-soft-secondary btn-compact" onclick="loadDesignLogs();"><i class="fa fa-rotate-right"></i><span>Refresh</span></button>
          <button class="btn btn-sm btn-soft-secondary btn-compact ms-2" id="collapse-logs-btn" type="button" onclick="toggleSectionCollapse('logs')">
            <i class="fa fa-minus"></i>
          </button>
        </div>
      </div>
      <div class="card-body" id="logs-section-body">
        <div class="table-responsive surface p-2">
          <table class="table table-sm">
            <thead><tr><th>When</th><th>Project</th><th>Title</th><th>Action</th><th>By</th><th>Details</th></tr></thead>
            <tbody id="se_dlogs_tbody"><tr><td colspan="6" class="text-muted">Loading...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <!-- Inspections -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Inspections</h5>
        <p class="card-text">Create or update task inspections; quickly pick a task and view its history.</p>
        <form class="row gy-2 gx-2 align-items-center" onsubmit="openInspections(event);">
          <div class="col">
            <select class="form-select form-select-sm" id="se_task_select"></select>
          </div>
          <div class="col-auto">
            <button class="btn btn-sm btn-soft-secondary btn-compact" type="submit"><i class="fa fa-list-ul"></i><span>List</span></button>
          </div>
        </form>
        <hr class="my-3"/>
        <form id="createInspectionForm">
          <div class="mb-2"><label class="form-label">Task</label>
            <select name="task_id" class="form-select form-select-sm" id="se_task_select2" required></select>
          </div>
          <div class="mb-2"><label class="form-label">Zone</label><input name="zone_id" type="text" class="form-control form-control-sm" placeholder="e.g. A1"></div>
          <div class="mb-2"><label class="form-label">Status</label>
            <select name="status" class="form-select form-select-sm">
              <option value="in_review">In Review</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
          <div class="mb-2"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control form-control-sm" rows="2"></textarea></div>
        </form>
        <div class="d-flex gap-2 flex-wrap">
          <button class="btn btn-sm btn-primary" onclick="submitCreateInspection();">Create Inspection</button>
        </div>
        <div id="createInspectionMsg" class="small mt-2"></div>
      </div>
    </div>
  </div>

  <!-- Drawings -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Drawings</h5>
        <p class="card-text">Browse available drawings.</p>
        <div class="d-flex gap-2 align-items-end mb-2">
          <div class="flex-grow-1">
            <label class="form-label">Project</label>
            <select id="se_drawings_project" class="form-select form-select-sm"></select>
          </div>
          <div>
            <a class="btn btn-sm btn-soft-secondary" id="se_open_drawings" href="<?= url('/designs'); ?>">Open Designs</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Incidents -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Incidents</h5>
        <p class="card-text">Report or view incidents.</p>
        <div class="row g-2 align-items-end">
          <div class="col-8">
            <label class="form-label">Task (optional)</label>
            <select id="se_incident_task" class="form-select form-select-sm"></select>
          </div>
          <div class="col-4">
            <button class="btn btn-sm btn-soft-secondary w-100" onclick="openIncidents();">Open</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Products -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Products</h5>
  <p class="card-text">Browse available products.</p>
        <button class="btn btn-sm btn-soft-secondary" onclick="openSECatalog('products')">View Products</button>
      </div>
    </div>
  </div>

  <!-- Suppliers -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Suppliers</h5>
  <p class="card-text">Browse suppliers.</p>
        <button class="btn btn-sm btn-soft-secondary" onclick="openSECatalog('suppliers')">View Suppliers</button>
      </div>
    </div>
  </div>

  <!-- Purchase Orders -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Purchase Orders</h5>
  <p class="card-text">Read PO data relevant to site operations.</p>
        <button class="btn btn-sm btn-soft-secondary" onclick="openSECatalog('purchase_orders')">View POs</button>
      </div>
    </div>
  </div>

  <!-- Materials (read-only link) -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Materials</h5>
  <p class="card-text">View project materials.</p>
        <button class="btn btn-sm btn-soft-secondary" onclick="openSECatalog('materials')">View Materials</button>
      </div>
    </div>
  </div>

  <!-- Messages (API view) -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Messages</h5>
        <p class="card-text">Open messages feed (API).</p>
        <a class="btn btn-sm btn-soft-secondary" href="<?= url('/messages'); ?>">Open Messages</a>
      </div>
    </div>
  </div>

  <!-- Project Chat -->
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Project Chat</h5>
        <div class="d-flex gap-2 align-items-center">
          <select id="se_chat_project" class="form-select form-select-sm" style="min-width:220px"></select>
          <button class="btn btn-sm btn-soft-secondary btn-compact" onclick="seChatLoad()"><i class="fa fa-rotate-right"></i><span>Refresh</span></button>
        </div>
      </div>
      <div class="card-body">
        <style>
          /* Chat bubbles */
          .se-chat .chat-line { margin:10px 0; }
          .se-chat .chat-bubble { display:inline-block; padding:10px 14px; border-radius:14px; max-width:78%; white-space:pre-wrap; text-align:left; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
          .se-chat .chat-bubble.me { background:#e6f4ff; color:#0b4f79; border: 1px solid #cfe8ff; }
          .se-chat .chat-bubble.them { background:#f3f4f6; color:#333; border: 1px solid #e7e8ea; }
          .se-chat .chat-bubble a { color:inherit; text-decoration:underline; }

          /* Messages viewport */
          .se-chat .messages {
            max-height: 320px; overflow: auto; border: 1px solid #eef0f7; border-radius: 12px; padding: 10px; background: #fff;
            box-shadow: inset 0 1px 3px rgba(16,24,40,0.04);
          }

          /* Composer */
          .se-chat .composer { margin-top: 10px; background: #fafbff; border: 1px solid #eef0f7; border-radius: 14px; padding: 8px; box-shadow: 0 6px 16px rgba(16,24,40,0.05); }
          .se-chat .chat-input { border: none; background: #fff; border-radius: 12px; padding: .6rem .85rem; box-shadow: inset 0 0 0 1px #e9edf5; }
          .se-chat .chat-input:focus { box-shadow: 0 0 0 .2rem rgba(101,54,255,.15); }

          /* File button */
          .se-chat .file-label { display: inline-flex; align-items: center; gap: .4rem; background: #eef2ff; color: #3849eb; border: 1px solid #dfe3ff; border-radius: 10px; padding: .5rem .75rem; cursor: pointer; white-space: nowrap; }
          .se-chat .file-label:hover { filter: brightness(0.99); }
          .se-chat .file-info { min-width: 110px; color: #6b7280; }

          /* Send button (fix wrapping) */
          .se-chat .btn-send { white-space: nowrap; min-width: 92px; border-radius: 10px; padding: .55rem .9rem; }
        </style>
        <div id="se_chat_messages" class="se-chat messages">
          <div class="text-muted">Select a project to view chat</div>
        </div>
        <form id="se_chat_form" class="se-chat composer d-flex align-items-center gap-2" onsubmit="seChatSend(event)">
          <input type="text" id="se_chat_text" class="form-control chat-input" placeholder="Type a message..." />
          <input type="file" id="se_chat_file" class="d-none" multiple />
          <label for="se_chat_file" class="btn file-label"><i class="fa fa-paperclip"></i> Choose files</label>
          <span id="se_chat_file_info" class="small file-info">No file</span>
          <button class="btn btn-primary btn-send"><i class="fa fa-paper-plane me-1"></i> Send</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  // Filter designs table by search term (client-side only)
  function filterDesignsTable(){
    const q = (document.getElementById('se_designs_search')?.value || '').trim().toLowerCase();
    const tbody = document.getElementById('se_designs_tbody');
    if (!tbody) return;
    Array.from(tbody.querySelectorAll('tr')).forEach(tr => {
      if (tr.children.length < 3) return; // skip placeholders
      const text = tr.innerText.toLowerCase();
      tr.style.display = q ? (text.includes(q) ? '' : 'none') : '';
    });
  }

  function openInspections(e){
    e.preventDefault();
    var id = document.getElementById('se_task_select').value;
    if(!id) return;
    window.open('<?= url('api/site_engineer/inspections.php') ?>?task_id='+encodeURIComponent(id), '_blank');
  }
  async function submitCreateInspection(){
    const form = document.getElementById('createInspectionForm');
    const data = Object.fromEntries(new FormData(form).entries());
    const res = await fetch('<?= url('api/site_engineer/inspections.php?action=create_inspection') ?>', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const j = await res.json();
    document.getElementById('createInspectionMsg').textContent = j.success ? 'Inspection created' : (j.message||'Failed');
  }

  // --- Designs queue ---
  (async function preloadSE(){
    try {
      // Try minimal, role-aware list first
      let res = await fetch('<?= url('api/projects/mine_min.php'); ?>');
      let j = await res.json();
      // Fallback to generic list (all projects) if none found
      if (!j || !j.success || !(j.data||[]).length) {
        res = await fetch('<?= url('api/projects/list.php'); ?>');
        j = await res.json();
      }
      // Stats: projects count
      try { document.getElementById('se_stat_projects').textContent = Array.isArray(j.data) ? j.data.length : 0; } catch(_e) {}
      const sel = document.getElementById('se_designs_project');
      if (sel) sel.innerHTML = '<option value="">All</option>' + (j.data||[]).map(p=>`<option value="${p.id}">${p.name||('Project #'+p.id)}</option>`).join('');
      // drawings project list
      const selDraw = document.getElementById('se_drawings_project');
      if (selDraw) selDraw.innerHTML = '<option value="">All</option>' + (j.data||[]).map(p=>`<option value="${p.id}">${p.name||('Project #'+p.id)}</option>`).join('');
      // chat project list
      const selChat = document.getElementById('se_chat_project');
      if (selChat) {
        selChat.innerHTML = '<option value="">Select project…</option>' + (j.data||[]).map(p=>`<option value="${p.id}">${p.name||('Project #'+p.id)}</option>`).join('');
        if (!selChat.value && selChat.options.length > 1) { selChat.selectedIndex = 1; }
        selChat.addEventListener('change', seChatLoad);
      }
    } catch(e) {}
    // tasks dropdowns for inspections/incidents
    try {
      const rt = await fetch('<?= url('api/site_engineer/tasks.php'); ?>');
      const tj = await rt.json();
      // Stat: tasks count
      try { document.getElementById('se_stat_tasks').textContent = Array.isArray(tj.data) ? tj.data.length : 0; } catch(_e) {}
      const options = (tj.data||[]).map(t=>`<option value="${t.id}">${t.project_name?('['+t.project_name+'] '):''}${t.title||('Task #'+t.id)}</option>`).join('');
      const s1 = document.getElementById('se_task_select'); if (s1) s1.innerHTML = '<option value="">Select task…</option>'+options;
      const s2 = document.getElementById('se_task_select2'); if (s2) s2.innerHTML = '<option value="">Select task…</option>'+options;
      const s3 = document.getElementById('se_incident_task'); if (s3) s3.innerHTML = '<option value="">(Optional) Any task</option>'+options;
    } catch(e) {}
    // Initial auto-refresh on page load
    await loadSubmittedDesigns();
    await loadDesignLogs();
  // If chat project preselected, load messages once
  if (document.getElementById('se_chat_project')?.value) { await seChatLoad(); }
    // Keep data fresh while the dashboard is open
    if (!window.seAutoRefreshInterval) {
      window.seAutoRefreshInterval = setInterval(()=>{
        loadSubmittedDesigns();
        loadDesignLogs();
      }, 60000); // 60s
      window.addEventListener('beforeunload', ()=>{
        clearInterval(window.seAutoRefreshInterval);
        window.seAutoRefreshInterval = null;
      });
    }
  })();

  async function seChatLoad(){
    const pid = document.getElementById('se_chat_project')?.value;
    const box = document.getElementById('se_chat_messages');
    if (!pid){ box.innerHTML = '<div class="text-muted">Select a project to view chat</div>'; return; }
    box.innerHTML = '<div>Loading…</div>';
    try{
      const u = new URL('<?= url('api/projects/messages/list.php'); ?>', window.location.origin);
      u.searchParams.set('project_id', pid);
      const res = await fetch(u.toString());
      const j = await res.json();
      if (!j.success) throw new Error(j.message||'Failed');
      const me = window.__CURRENT_USER_ID;
      box.innerHTML = (j.data||[]).map(m=>{
        const isMe = (me && String(m.sender_id)===String(me));
        const side = isMe ? 'text-end' : 'text-start';
        const who = m.sender_name ? `<small class=\"text-muted\">${escapeHtml(m.sender_name)}</small><br>` : '';
        let files = '';
        const meta = typeof m.metadata === 'string' ? (function(){try{return JSON.parse(m.metadata)}catch{return null}})() : m.metadata;
        if (meta && meta.attachments && meta.attachments.length){
          files = '<div class="mt-1">' + meta.attachments.map(f=>{
            const token = encodeURIComponent(f.token||'');
            const href = '<?= url('api/projects/messages/download.php'); ?>' + '?token=' + token;
            return `<a href="${href}" target="_blank" rel="noopener">${escapeHtml(f.name||'file')}</a>`;
          }).join('<br>') + '</div>';
        }
        const time = m.created_at ? new Date(m.created_at).toLocaleString() : '';
        const meClass = isMe ? 'me' : 'them';
        return `<div class=\"${side} chat-line ${meClass}\">${who}<span class=\"chat-bubble ${meClass}\">${escapeHtml(m.body||'')}${files}</span><div><small class=\"text-muted\">${escapeHtml(time)}</small></div></div>`;
      }).join('') || '<div class="text-muted">No messages yet.</div>';
      box.scrollTop = box.scrollHeight;
    }catch(e){ box.innerHTML = '<div class="text-danger">'+escapeHtml(e.message)+'</div>'; }
  }

  async function seChatSend(e){
    e.preventDefault();
    const pid = document.getElementById('se_chat_project')?.value;
    if (!pid){ alert('No accessible project selected. Please choose a project.'); return; }
    const txt = document.getElementById('se_chat_text');
    const file = document.getElementById('se_chat_file');
    const body = (txt.value||'').trim();
    const files = file && file.files ? file.files : null;
    if (!body && (!files || !files.length)) return;
    const fd = new FormData();
    fd.append('project_id', pid);
    fd.append('body', body);
    if (files && files.length){ Array.from(files).forEach(f=> fd.append('attachments[]', f)); }
    try {
      const res = await fetch('<?= url('api/projects/messages/send.php'); ?>', { method:'POST', body: fd });
      const j = await res.json();
      if (!j.success) throw new Error(j.message||'Failed');
      txt.value = ''; if (file) file.value='';
      try { document.getElementById('se_chat_file_info').textContent = 'No file'; } catch(_e) {}
      seChatLoad();
    } catch(e){ alert(e.message); }
  }

  function openIncidents(){
    var tid = document.getElementById('se_incident_task').value;
    const url = '<?= url('api/site_engineer/incidents.php'); ?>' + (tid ? ('?task_id='+encodeURIComponent(tid)) : '');
    window.open(url, '_blank');
  }

  async function loadSubmittedDesigns(){
    const tbody = document.getElementById('se_designs_tbody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6" class="text-muted">Loading...</td></tr>';
    const pid = document.getElementById('se_designs_project')?.value || '';
    const qs = new URLSearchParams();
    if (pid) qs.set('project_id', pid);
    qs.set('status', 'submitted');
    qs.set('limit', '200');
    try {
      const res = await fetch('<?= url('api/designs.php'); ?>' + '?' + qs.toString());
      const j = await res.json();
      if (!j.success) throw new Error(j.message||'Failed');
      // Update pending designs stat count
      try { document.getElementById('se_stat_designs').textContent = Array.isArray(j.data) ? j.data.length : 0; } catch(_e) {}
      const downloadUrl = (id)=> '<?= url('api/designs.php'); ?>' + '?action=download&file=client&id=' + encodeURIComponent(id);
      const rows = (j.data||[]).map((d,i)=>{
        const client = d.client_file ? `<a href="${downloadUrl(d.id)}" target="_blank">Download</a>` : '';
        const approve = `<button class=\"btn btn-sm btn-success\" data-id=\"${String(d.id)}\" data-title=\"${escapeHtml(d.title||'')}\" data-project=\"${escapeHtml(d.project_name||('#'+d.project_id))}\" onclick=\"openSEApproveFromBtn(this)\">Approve</button>`;
        return `<tr>
          <td>${i+1}</td>
          <td>${d.project_name || d.project_id}</td>
          <td>${d.title||''}</td>
          <td><span class="badge bg-warning">submitted</span></td>
          <td>${client}</td>
          <td>${approve}</td>
        </tr>`;
      }).join('');
      tbody.innerHTML = rows || '<tr><td colspan="6" class="text-muted">No submitted designs</td></tr>';
    } catch(e) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-danger">'+e.message+'</td></tr>';
    }
  }

  async function loadDesignLogs(){
    const tbody = document.getElementById('se_dlogs_tbody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6" class="text-muted">Loading...</td></tr>';
    try {
      const res = await fetch('<?= url('api/designs.php'); ?>?action=logs&limit=200');
      const j = await res.json();
      if (!j.success) throw new Error(j.message||'Failed');
      const rows = (j.data||[]).map(l=>{
        const when = l.created_at ? new Date(l.created_at).toLocaleString() : '';
        return `<tr>
          <td>${when}</td>
          <td>${l.project_name || l.project_id}</td>
          <td>${l.title||''}</td>
          <td>${l.action}</td>
          <td>${l.actor_name||l.actor_id||''}</td>
          <td>${l.details||''}</td>
        </tr>`;
      }).join('');
      tbody.innerHTML = rows || '<tr><td colspan="6" class="text-muted">No recent activity</td></tr>';
    } catch(e) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-danger">'+e.message+'</td></tr>';
    }
  }

  function escapeHtml(s){
    return String(s).replace(/[&<>"']/g, function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#039;'}[c]);});
  }

  function openSEApproveFromBtn(btn){
    const payload = { id: btn.dataset.id, title: btn.dataset.title, project_name: btn.dataset.project };
    openSEApproveModal(payload);
  }

  function openSEApproveModal(payload){
    const m = document.getElementById('seApproveModal');
    m.querySelector('[data-field="design-id"]').value = payload.id;
    m.querySelector('[data-field="design-title"]').textContent = payload.title||'';
    m.querySelector('[data-field="design-project"]').textContent = payload.project_name||'';
    new bootstrap.Modal(m).show();
  }

  // Chat file chooser feedback
  (function(){
    const f = document.getElementById('se_chat_file');
    const info = document.getElementById('se_chat_file_info');
    if (f && info) {
      f.addEventListener('change', ()=>{
        if (!f.files || !f.files.length) { info.textContent = 'No file'; return; }
        if (f.files.length === 1) { info.textContent = f.files[0].name; }
        else { info.textContent = f.files.length + ' files'; }
      });
    }
  })();

  async function submitSEApprove(){
    const btn = document.getElementById('seApproveSubmitBtn');
    const form = document.getElementById('seApproveForm');
    const fd = new FormData(form);
    if(!fd.get('pdf') || !fd.get('pdf').name){ alert('Please choose a PDF file.'); return; }
    btn.disabled = true; btn.textContent = 'Uploading...';
    try{
      const res = await fetch('<?= url('api/designs.php?action=engineer_approve'); ?>', { method: 'POST', body: fd });
      const j = await res.json();
      if (!j.success) throw new Error(j.message||'Approval failed');
      bootstrap.Modal.getInstance(document.getElementById('seApproveModal')).hide();
      form.reset();
      loadSubmittedDesigns();
      loadDesignLogs();
    }catch(e){ alert(e.message); }
    finally{ btn.disabled = false; btn.textContent = 'Approve & Upload PDF'; }
  }

// --- Collapse/minimize logic for dashboard sections ---
function toggleSectionCollapse(section) {
  let body, btn, icon, collapsed;
  if (section === 'designs') {
    body = document.getElementById('designs-section-body');
    btn = document.getElementById('collapse-designs-btn');
  } else if (section === 'logs') {
    body = document.getElementById('logs-section-body');
    btn = document.getElementById('collapse-logs-btn');
  }
  if (!body || !btn) return;
  collapsed = body.style.display === 'none';
  if (collapsed) {
    body.style.display = '';
    btn.querySelector('i').classList.remove('fa-plus');
    btn.querySelector('i').classList.add('fa-minus');
    localStorage.setItem('se_dash_'+section+'_collapsed', '0');
  } else {
    body.style.display = 'none';
    btn.querySelector('i').classList.remove('fa-minus');
    btn.querySelector('i').classList.add('fa-plus');
    localStorage.setItem('se_dash_'+section+'_collapsed', '1');
  }
}

// On page load, restore collapse state
document.addEventListener('DOMContentLoaded', function() {
  ['designs','logs'].forEach(function(section) {
    let collapsed = localStorage.getItem('se_dash_'+section+'_collapsed') === '1';
    let body = document.getElementById(section+'-section-body');
    let btn = document.getElementById('collapse-'+section+'-btn');
    if (body && btn) {
      if (collapsed) {
        body.style.display = 'none';
        btn.querySelector('i').classList.remove('fa-minus');
        btn.querySelector('i').classList.add('fa-plus');
      } else {
        body.style.display = '';
        btn.querySelector('i').classList.remove('fa-plus');
        btn.querySelector('i').classList.add('fa-minus');
      }
    }
  });
});
</script>

<!-- Approve Design Modal (SE Dashboard) -->
<div class="modal fade" id="seApproveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Approve Design</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><strong>Project:</strong> <span data-field="design-project"></span></div>
        <div class="mb-3"><strong>Title:</strong> <span data-field="design-title"></span></div>
        <form id="seApproveForm" onsubmit="event.preventDefault(); submitSEApprove();">
          <input type="hidden" name="id" data-field="design-id" />
          <label class="form-label">Approved PDF</label>
          <input class="form-control" type="file" name="pdf" accept="application/pdf,.pdf" required />
        </form>
        <small class="text-muted">Only PDF files are accepted.</small>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-soft-secondary" data-bs-dismiss="modal">Cancel</button>
        <button id="seApproveSubmitBtn" type="button" class="btn btn-success" onclick="submitSEApprove()">Approve & Upload PDF</button>
      </div>
    </div>
  </div>
</div>
<!-- SE Reference Catalog Modal -->
<div class="modal fade" id="seCatalogModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="seCatalogTitle">Catalog</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex gap-2 mb-2" id="seCatalogFilters"></div>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead id="seCatalogHead"></thead>
            <tbody id="seCatalogBody"><tr><td class="text-muted">Loading...</td></tr></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-outline-secondary" onclick="printSECatalog()">Print</button>
      </div>
    </div>
  </div>
 </div>

<script>
  function openSECatalog(kind){
    const title = {
      products: 'Products', suppliers: 'Suppliers', materials: 'Materials', purchase_orders: 'Purchase Orders'
    }[kind] || 'Catalog';
    document.getElementById('seCatalogTitle').textContent = title;
    document.getElementById('seCatalogFilters').innerHTML = '';
    document.getElementById('seCatalogHead').innerHTML = '';
    document.getElementById('seCatalogBody').innerHTML = '<tr><td class="text-muted">Loading...</td></tr>';
    new bootstrap.Modal(document.getElementById('seCatalogModal')).show();
    if (kind==='products') return loadSEProducts();
    if (kind==='suppliers') return loadSESuppliers();
    if (kind==='materials') return loadSEMaterials();
    if (kind==='purchase_orders') return loadSEPOs();
  }

  async function loadSEProducts(){
    try{
      const res = await fetch('<?= url('api/site_engineer/products.php'); ?>');
      const j = await res.json();
      document.getElementById('seCatalogHead').innerHTML = '<tr><th>#</th><th>Name</th><th>Supplier</th><th>Unit</th><th>Price</th><th>Status</th></tr>';
      const rows = (j.data||j||[]).map((p,i)=>`<tr><td>${i+1}</td><td>${p.name||''}</td><td>${p.supplier_name||p.supplier_id||''}</td><td>${p.unit||''}</td><td>${p.unit_price||''}</td><td>${p.status||''}</td></tr>`).join('');
      document.getElementById('seCatalogBody').innerHTML = rows || '<tr><td class="text-muted">No products</td></tr>';
    }catch(e){ document.getElementById('seCatalogBody').innerHTML = '<tr><td class="text-danger">'+e.message+'</td></tr>'; }
  }
  async function loadSESuppliers(){
    try{
      const res = await fetch('<?= url('api/site_engineer/suppliers.php'); ?>');
      const j = await res.json();
      document.getElementById('seCatalogHead').innerHTML = '<tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Rating</th></tr>';
      const rows = (j.data||j||[]).map((s,i)=>`<tr><td>${i+1}</td><td>${s.name||''}</td><td>${s.email||''}</td><td>${s.phone||''}</td><td>${s.rating||''}</td></tr>`).join('');
      document.getElementById('seCatalogBody').innerHTML = rows || '<tr><td class="text-muted">No suppliers</td></tr>';
    }catch(e){ document.getElementById('seCatalogBody').innerHTML = '<tr><td class="text-danger">'+e.message+'</td></tr>'; }
  }
  async function loadSEMaterials(){
    try{
      const res = await fetch('<?= url('api/site_engineer/materials.php'); ?>');
      const j = await res.json();
      document.getElementById('seCatalogHead').innerHTML = '<tr><th>#</th><th>Project</th><th>Material</th><th>Qty</th><th>Status</th></tr>';
      const rows = (j.data||j||[]).map((m,i)=>`<tr><td>${i+1}</td><td>${m.project_name||m.project_id||''}</td><td>${m.material_name||m.name||''}</td><td>${m.quantity||''}</td><td>${m.status||''}</td></tr>`).join('');
      document.getElementById('seCatalogBody').innerHTML = rows || '<tr><td class="text-muted">No materials</td></tr>';
    }catch(e){ document.getElementById('seCatalogBody').innerHTML = '<tr><td class="text-danger">'+e.message+'</td></tr>'; }
  }
  async function loadSEPOs(){
    try{
      const res = await fetch('<?= url('api/site_engineer/purchase_orders.php'); ?>');
      const j = await res.json();
      document.getElementById('seCatalogHead').innerHTML = '<tr><th>#</th><th>Project</th><th>Supplier</th><th>Status</th><th>Total</th></tr>';
      const rows = (j.data||j||[]).map((p,i)=>`<tr><td>${i+1}</td><td>${p.project_name||p.project_id||''}</td><td>${p.supplier_name||p.supplier_id||''}</td><td>${p.status||''}</td><td>${p.total_amount||p.total||''}</td></tr>`).join('');
      document.getElementById('seCatalogBody').innerHTML = rows || '<tr><td class="text-muted">No purchase orders</td></tr>';
    }catch(e){ document.getElementById('seCatalogBody').innerHTML = '<tr><td class="text-danger">'+e.message+'</td></tr>'; }
  }
  function printSECatalog(){
    const w = window.open('', '_blank');
    const title = document.getElementById('seCatalogTitle').textContent;
    const head = document.getElementById('seCatalogHead').innerHTML;
    const body = document.getElementById('seCatalogBody').innerHTML;
    w.document.write('<html><head><title>'+title+'</title><style>table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:6px;font-size:12px}th{background:#f8f9fa}</style></head><body>');
    w.document.write('<h3>'+title+'</h3><table><thead>'+head+'</thead><tbody>'+body+'</tbody></table>');
    w.document.write('</body></html>');
    w.document.close(); w.focus(); w.print();
  }
</script>
<?php include_once __DIR__ . '/../components/messaging_nav.php'; ?>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
