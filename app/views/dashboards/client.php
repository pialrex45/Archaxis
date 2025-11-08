<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';

requireAuth();
if (!hasRole('client')) { http_response_code(403); die('Access denied. Clients only.'); }

$dashboardData = [
  'projects' => [],
  'tasks' => [],
  'materials' => [],
  'purchase_orders' => [],
  'reports' => [],
  'finance' => [
    'total_invoiced' => 0,
    'total_paid' => 0,
    'outstanding' => 0,
  ],
  'messages' => []
];

// Try to load optional controller data if available (non-breaking)
@include_once __DIR__ . '/../../controllers/DashboardController.php';
if (class_exists('DashboardController')) {
  $ctl = new DashboardController();
  if (method_exists($ctl, 'getClientDashboard')) {
    $resp = $ctl->getClientDashboard();
    if (is_array($resp) && !empty($resp['success']) && is_array($resp['data'])) {
      $dashboardData = array_merge($dashboardData, $resp['data']);
    }
  }
}

$pageTitle = 'Client Dashboard';
$currentPage = 'dashboard';
// Add a scoped stylesheet and body class for a polished client look
$extraStyles = '<link rel="stylesheet" href="'.htmlspecialchars(url('/assets/css/dashboard-client.css'), ENT_QUOTES).'">';
$bodyClass = 'client-dash';

include_once __DIR__ . '/../../views/layouts/header.php';
?>
<div class="row">
  <div class="col-12">
    <div class="overview-banner">
      <div class="icon"></div>
      <div>
        <div class="title">Welcome back</div>
        <div class="subtitle">Overview of your projects and messages with your Project Manager.</div>
      </div>
    </div>
  </div>
  <div class="col-12"><h1 class="page-head">Client Dashboard</h1></div>
</div>

<!-- Quick actions -->
<div class="row g-3 mb-4">
  <div class="col-12 col-lg-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="kicker">Design & Drawing</div>
        <h5 class="card-title">Sketch and iterate together</h5>
        <p class="card-text">Open the in-browser designer to sketch, iterate, and share with your team.</p>
        <div class="d-flex gap-2 flex-wrap">
          <a class="btn btn-primary btn-gradient open-designer" href="<?php echo url('/diagram45/index.html'); ?>" target="_blank" rel="noopener">Open Designer</a>
          <button class="btn btn-outline-primary" onclick="openSendDesignModal();">Send to engineer</button>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="kicker text-success">Estimate & Tax Calculator</div>
        <h5 class="card-title">Break down and print clean PDFs</h5>
        <p class="card-text">Auto-fill unit prices by supplier and product, apply tax, and export well-formatted output.</p>
  <a class="btn btn-success btn-gradient open-calculator" href="<?php echo url('/estimate-calculator'); ?>" target="_blank" rel="noopener">Open Calculator</a>
      </div>
    </div>
  </div>
</div>

<!-- Project Chat -->
<div class="row mb-4">
  <div class="col-12">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Project Chat</h5>
        <div class="d-flex gap-2 align-items-center">
          <select id="client_chat_project" class="form-select form-select-sm" style="min-width:220px"></select>
          <button class="btn btn-sm btn-soft-secondary btn-compact" onclick="clientChatLoad()"><i class="fa fa-rotate-right"></i><span>Refresh</span></button>
        </div>
      </div>
      <div class="card-body">
        <style>
          .client-chat .chat-line { margin:10px 0; }
          .client-chat .chat-bubble { display:inline-block; padding:10px 14px; border-radius:14px; max-width:78%; white-space:pre-wrap; text-align:left; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
          .client-chat .chat-bubble.me { background:#e6f4ff; color:#0b4f79; border: 1px solid #cfe8ff; }
          .client-chat .chat-bubble.them { background:#f3f4f6; color:#333; border: 1px solid #e7e8ea; }
          .client-chat .chat-bubble a { color:inherit; text-decoration:underline; }

          .client-chat .messages { max-height:320px; overflow:auto; border:1px solid #eef0f7; border-radius:12px; padding:10px; background:#fff; box-shadow: inset 0 1px 3px rgba(16,24,40,0.04); }
          .client-chat .composer { margin-top:10px; background:#fafbff; border:1px solid #eef0f7; border-radius:14px; padding:8px; box-shadow:0 6px 16px rgba(16,24,40,0.05); }
          .client-chat .chat-input { border:none; background:#fff; border-radius:12px; padding:.6rem .85rem; box-shadow: inset 0 0 0 1px #e9edf5; }
          .client-chat .chat-input:focus { box-shadow: 0 0 0 .2rem rgba(101,54,255,.15); }
          .client-chat .file-label { display:inline-flex; align-items:center; gap:.4rem; background:#eef2ff; color:#3849eb; border:1px solid #dfe3ff; border-radius:10px; padding:.5rem .75rem; cursor:pointer; white-space:nowrap; }
          .client-chat .file-label:hover { filter: brightness(0.99); }
          .client-chat .file-info { min-width:110px; color:#6b7280; }
          .client-chat .btn-send { white-space:nowrap; min-width:92px; border-radius:10px; padding:.55rem .9rem; }
        </style>
        <div id="client_chat_messages" class="client-chat messages">
          <div class="text-muted">Select a project to view chat</div>
        </div>
        <form id="client_chat_form" class="client-chat composer d-flex align-items-center gap-2" onsubmit="clientChatSend(event)">
          <input type="text" id="client_chat_text" class="form-control chat-input" placeholder="Type a message..." />
          <input type="file" id="client_chat_file" class="d-none" multiple />
          <label for="client_chat_file" class="btn file-label"><i class="fa fa-paperclip"></i> Choose files</label>
          <span id="client_chat_file_info" class="small file-info">No file</span>
          <button class="btn btn-primary btn-send"><i class="fa fa-paper-plane me-1"></i> Send</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Send Design to Engineer Modal -->
<div class="modal fade" id="sendDesignModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Send design to Site Engineer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="sendDesignForm">
          <div class="mb-2"><label class="form-label">Project</label>
            <select class="form-select form-select-sm" name="project_id" id="design_project_select" required></select>
          </div>
          <div class="mb-2"><label class="form-label">Title</label>
            <input type="text" class="form-control form-control-sm" name="title" placeholder="e.g., Ground floor v1" required>
          </div>
          <div class="mb-2"><label class="form-label">Attach file (PDF or ZIP)</label>
            <input type="file" class="form-control form-control-sm" name="file" accept="application/pdf,application/zip" required>
          </div>
          <div class="form-text">Your engineer will review and approve, then upload final PDF for Site Manager.</div>
        </form>
        <div id="sendDesignMsg" class="small mt-2"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="submitSendDesign();">Send</button>
      </div>
    </div>
  </div>
</div>

<script>
function openSendDesignModal(){
  // populate projects with only this client's projects
  fetch('<?php echo url('api/projects/list.php'); ?>?mine=1').then(r=>r.json()).then(j=>{
    var sel = document.getElementById('design_project_select');
    var opts = (j.data||[]).map(p=>'<option value="'+p.id+'">'+(p.name||('Project #'+p.id))+'</option>').join('');
    sel.innerHTML = opts;
  }).catch(()=>{});
  new bootstrap.Modal(document.getElementById('sendDesignModal')).show();
}
async function submitSendDesign(){
  const f = document.getElementById('sendDesignForm');
  const fd = new FormData(f);
  const res = await fetch('<?php echo url('api/designs.php?action=client_submit'); ?>', { method:'POST', body: fd });
  const j = await res.json();
  document.getElementById('sendDesignMsg').textContent = j.success ? 'Sent for engineering review.' : (j.message||'Failed');
  if (j.success) setTimeout(()=>{ bootstrap.Modal.getInstance(document.getElementById('sendDesignModal')).hide(); }, 600);
}

// --- Project Chat (client) ---
document.addEventListener('DOMContentLoaded', ()=>{
  // Only client's own projects
  fetch('<?php echo url('api/projects/list.php'); ?>?mine=1').then(r=>r.json()).then(j=>{
    var sel = document.getElementById('client_chat_project');
    if (sel) sel.innerHTML = '<option value="">Select project…</option>' + (j.data||[]).map(p=>`<option value="${p.id}">${p.name||('Project #'+p.id)}</option>`).join('');
  }).catch(()=>{});
});

async function clientChatLoad(){
  const pid = document.getElementById('client_chat_project')?.value;
  const box = document.getElementById('client_chat_messages');
  if (!pid){ box.innerHTML = '<div class="text-muted">Select a project to view chat</div>'; return; }
  box.innerHTML = '<div>Loading…</div>';
  try{
    const u = new URL('<?php echo url('api/projects/messages/list.php'); ?>', window.location.origin);
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
          const href = '<?php echo url('api/projects/messages/download.php'); ?>' + '?token=' + token;
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

async function clientChatSend(e){
  e.preventDefault();
  const pid = document.getElementById('client_chat_project')?.value;
  if (!pid){ alert('Select a project'); return; }
  const txt = document.getElementById('client_chat_text');
  const file = document.getElementById('client_chat_file');
  const body = (txt.value||'').trim();
  const files = file && file.files ? file.files : null;
  if (!body && (!files || !files.length)) return;
  const fd = new FormData();
  fd.append('project_id', pid);
  fd.append('body', body);
  if (files && files.length){ Array.from(files).forEach(f=> fd.append('attachments[]', f)); }
  try {
    const res = await fetch('<?php echo url('api/projects/messages/send.php'); ?>', { method:'POST', body: fd });
    const j = await res.json();
    if (!j.success) throw new Error(j.message||'Failed');
    txt.value=''; if (file) file.value='';
    try { document.getElementById('client_chat_file_info').textContent = 'No file'; } catch(_e) {}
    clientChatLoad();
  } catch(e){ alert(e.message); }
}

function escapeHtml(s){
  return String(s||'').replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c]));
}

// Chat file chooser feedback (client)
document.addEventListener('DOMContentLoaded', ()=>{
  const f = document.getElementById('client_chat_file');
  const info = document.getElementById('client_chat_file_info');
  if (f && info) {
    f.addEventListener('change', ()=>{
      if (!f.files || !f.files.length) { info.textContent = 'No file'; return; }
      if (f.files.length === 1) { info.textContent = f.files[0].name; }
      else { info.textContent = f.files.length + ' files'; }
    });
  }
});
</script>

<script>
  // Ensure designer and calculator buttons open reliably even with overlay/pointer quirks
  (function(){
    function openHref(e){
      var href = this.getAttribute('href');
      if (!href) return;
      e.preventDefault();
      try {
        var w = window.open(href, '_blank');
        if (!w) { window.location.href = href; }
      } catch(err){ window.location.href = href; }
    }
    document.querySelectorAll('.open-designer, .open-calculator').forEach(function(el){
      el.addEventListener('click', openHref);
    });
  })();
</script>

<!-- Project design saves & estimates (auto) -->
<div class="row mb-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Design Saves & Estimates</h5>
        <div>
          <a class="btn btn-sm btn-outline-primary" href="<?php echo url('/diagram45/index.html'); ?>" target="_blank">Open Designer</a>
        </div>
      </div>
      <div class="card-body" id="client_design_summaries">
        <div class="text-muted">Loading…</div>
      </div>
    </div>
  </div>
  <script>
  (function(){
    fetch('<?php echo url('api/designs_summary.php'); ?>?mine=1', { credentials:'include' })
      .then(r=>r.json()).then(j=>{
        const box = document.getElementById('client_design_summaries');
        if (!box) return;
        if (!j || !j.success || !(j.data||[]).length) { box.innerHTML = '<div class="text-muted">No saved designs yet.</div>'; return; }
        const rows = (j.data||[]).map(x=>{
          const est = x.estimates||{}; const wall = est.wall||{}; const rcc = est.rcc||{};
          function nf(v, d){ v = Number(v); if (!isFinite(v)) return '-'; return v.toFixed(d); }
          const time = x.saved_at? new Date(x.saved_at).toLocaleString() : '';
          const title = escapeHtml(x.project_name||'Project');
          const sub = escapeHtml(x.title||'');
          return `
          <div class="ds-card">
            <div class="ds-header">
              <div>
                <div class="ds-title">${title}</div>
                <div class="ds-sub">${sub}</div>
              </div>
              <div class="ds-chip">${time}</div>
            </div>
            <div class="row ds-sections">
              <div class="col-md-6 ds-section">
                <div class="ds-section-title">Walls</div>
                <ul class="ds-kv">
                  <li><span>Area</span><span>${nf(wall.areaM2,2)} m²</span></li>
                  <li><span>Bricks</span><span>${nf(wall.bricks,0)} pcs</span></li>
                  <li><span>Grand total</span><span>${nf(wall.grandTotalBDT,2)} BDT</span></li>
                </ul>
              </div>
              <div class="col-md-6 ds-section">
                <div class="ds-section-title">RCC Roof</div>
                <ul class="ds-kv">
                  <li><span>Volume</span><span>${nf(rcc.volumeM3,3)} m³</span></li>
                  <li><span>Steel</span><span>${nf(rcc.steelKg,1)} kg</span></li>
                  <li><span>Grand total</span><span>${nf(rcc.grandTotalBDT,2)} BDT</span></li>
                </ul>
              </div>
            </div>
          </div>`;
        }).join('');
        box.innerHTML = rows;
      }).catch(()=>{
        const box = document.getElementById('client_design_summaries');
        if (box) box.innerHTML = '<div class="text-danger">Failed to load.</div>';
      });
  })();
  </script>

<!-- Design analytics charts -->
<div class="row mb-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Design Analytics</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-12 col-lg-7 mb-3 mb-lg-0">
            <div style="height:260px">
              <canvas id="client_chart_estimates_line"></canvas>
            </div>
          </div>
          <div class="col-12 col-lg-5">
            <div style="height:260px">
              <canvas id="client_chart_costs_pie"></canvas>
            </div>
          </div>
        </div>
        <div id="client_charts_no_data" class="text-muted d-none">No design data to chart.</div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
  // Fetch latest design snapshots per project and render charts
  fetch('<?php echo url('api/designs_summary.php'); ?>?mine=1', { credentials:'include' })
    .then(r=>r.json())
    .then(j=>{
      const data = (j && j.success && Array.isArray(j.data)) ? j.data : [];
      const elLine = document.getElementById('client_chart_estimates_line');
      const elPie = document.getElementById('client_chart_costs_pie');
      if (!data.length || !elLine || !elPie) {
        try { document.getElementById('client_charts_no_data').classList.remove('d-none'); } catch(_e) {}
        return;
      }

      const labels = data.map(d => (d.project_name || ('Project #'+(d.project_id||''))));
      const totals = data.map(d => {
        const e = d.estimates || {}; const w = e.wall || {}; const r = e.rcc || {};
        return (Number(w.grandTotalBDT)||0) + (Number(r.grandTotalBDT)||0);
      });

      const ctx1 = elLine.getContext('2d');
      // eslint-disable-next-line no-undef
      new Chart(ctx1, {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'Estimated total cost (BDT)',
            data: totals,
            backgroundColor: '#4f46e5',
            borderColor: '#4f46e5',
            borderWidth: 1,
            borderRadius: 6,
            maxBarThickness: 44
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: true },
            tooltip: {
              callbacks: {
                label: (ctx) => ' ' + formatBDT(ctx.parsed.y) + ' BDT'
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: { callback: (v) => formatBDT(v) }
            },
            x: {
              ticks: { autoSkip: true, maxRotation: 0 }
            }
          }
        }
      });

      // Aggregate cost components across all projects for pie chart
      let wallMaterials=0, wallLabor=0, rccMaterials=0, rccLabor=0, rccShuttering=0;
      data.forEach(d => {
        const e = d.estimates || {}; const w = e.wall || {}; const r = e.rcc || {};
        wallMaterials += Number(w.materialsCostBDT)||0;
        wallLabor += Number(w.laborCostBDT)||0;
        rccMaterials += Number(r.materialsCostBDT)||0;
        rccLabor += Number(r.laborCostBDT)||0;
        rccShuttering += Number(r.shutteringCostBDT)||0;
      });
      const pieValues = [wallMaterials, wallLabor, rccMaterials, rccLabor, rccShuttering];

      const ctx2 = elPie.getContext('2d');
      // eslint-disable-next-line no-undef
      new Chart(ctx2, {
        type: 'pie',
        data: {
          labels: ['Wall materials','Wall labor','RCC materials','RCC labor','RCC shuttering'],
          datasets: [{
            data: pieValues,
            backgroundColor: ['#38bdf8','#64748b','#22c55e','#f59e0b','#ef4444']
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom' },
            tooltip: {
              callbacks: {
                label: (ctx) => ' ' + ctx.label + ': ' + formatBDT(ctx.parsed) + ' BDT'
              }
            }
          }
        }
      });
    })
    .catch(() => {
      try { document.getElementById('client_charts_no_data').classList.remove('d-none'); } catch(_e) {}
    });

  function formatBDT(v){
    try { return (Number(v)||0).toLocaleString(undefined, { maximumFractionDigits: 0 }); } catch(_e) { return String(v); }
  }
})();
</script>


<?php if (!empty($dashboardData['statistics'])): ?>
  <!-- Admin-like Statistics Cards -->
  <div class="row">
    <div class="col-md-4 mb-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Total Projects</h5>
          <p class="card-text display-6 mb-0"><?php echo htmlspecialchars($dashboardData['statistics']['total_projects']); ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Open Tasks</h5>
          <p class="card-text display-6 mb-0"><?php echo htmlspecialchars($dashboardData['statistics']['open_tasks']); ?></p>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if (!empty($dashboardData['recent_projects_client']) || !empty($dashboardData['recent_activities'])): ?>
  <div class="row">
    <!-- Recent Projects (client) -->
    <div class="col-md-6 mb-4">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Recent Projects</h5>
        </div>
        <div class="card-body">
          <?php if (!empty($dashboardData['recent_projects_client'])): ?>
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Created</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($dashboardData['recent_projects_client'] as $project): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($project['name']); ?></td>
                      <td>
                        <span class="badge bg-<?php 
                          echo ($project['status'] ?? '') === 'active' ? 'success' :
                               (($project['status'] ?? '') === 'completed' ? 'primary' :
                               (($project['status'] ?? '') === 'on hold' ? 'warning' : 'secondary')); ?>">
                          <?php echo htmlspecialchars($project['status'] ?? 'n/a'); ?>
                        </span>
                      </td>
                      <td><?php echo !empty($project['created_at']) ? date('M j, Y', strtotime($project['created_at'])) : '-'; ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <p class="mb-0">No recent projects found.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Recent Activities -->
    <div class="col-md-6 mb-4">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Recent Activities</h5>
        </div>
        <div class="card-body">
          <?php if (!empty($dashboardData['recent_activities'])): ?>
            <ul class="list-group">
              <?php foreach ($dashboardData['recent_activities'] as $activity): ?>
                <li class="list-group-item">
                  <div class="d-flex justify-content-between">
                    <div>
                      <strong><?php echo htmlspecialchars($activity['description'] ?? ''); ?></strong><br>
                      <small class="text-muted"><?php echo htmlspecialchars(($activity['type'] ?? '') . ' ' . ($activity['action'] ?? '')); ?></small>
                    </div>
                    <small class="text-muted"><?php echo !empty($activity['timestamp']) ? date('M j, Y g:i A', strtotime($activity['timestamp'])) : ''; ?></small>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="mb-0">No recent activities found.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Project Overview -->
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Project Overview</h5>
        <a class="btn btn-sm btn-outline-primary" href="<?php echo url('/projects'); ?>">View All Projects</a>
      </div>
      <div class="card-body p-0">
        <?php if (!empty($dashboardData['projects'])): ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Project</th>
                  <th>Status</th>
                  <th>Deadline</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($dashboardData['projects'] as $p): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($p['name'] ?? ''); ?></td>
                    <td><span class="badge bg-<?php echo ($p['status'] ?? 'secondary') === 'on-track' ? 'success' : (($p['status'] ?? '') === 'at-risk' ? 'warning' : 'secondary'); ?>">
                      <?php echo htmlspecialchars(ucfirst($p['status'] ?? 'n/a')); ?></span></td>
                    <td><?php echo htmlspecialchars($p['deadline'] ?? ''); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="p-3">
            <div class="alert alert-secondary mb-0">No project data available yet.</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <!-- Task Overview removed by request -->

  <!-- Material Overview -->
  <div class="col-12 col-lg-6">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Material Overview</h5>
        <a class="btn btn-sm btn-outline-primary" href="<?php echo url('/materials'); ?>">View All Materials</a>
      </div>
      <div class="card-body p-0">
        <?php if (!empty($dashboardData['materials'])): ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($dashboardData['materials'] as $m): ?>
              <li class="list-group-item">
                <div class="d-flex justify-content-between">
                  <strong><?php echo htmlspecialchars($m['name'] ?? ''); ?></strong>
                  <small class="text-muted"><?php echo htmlspecialchars($m['quantity'] ?? ''); ?></small>
                </div>
                <div class="text-muted small">Unit Price: <?php echo htmlspecialchars($m['unit_price'] ?? ''); ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="p-3">
            <div class="alert alert-secondary mb-0">No material data available yet.</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Purchase Order Overview -->
  <div class="col-12 col-lg-6">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Purchase Order Overview</h5>
        <a class="btn btn-sm btn-outline-primary" href="<?php echo url('/purchase-orders'); ?>">View All Purchase Orders</a>
      </div>
      <div class="card-body p-0">
        <?php if (!empty($dashboardData['purchase_orders'])): ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($dashboardData['purchase_orders'] as $po): ?>
              <li class="list-group-item">
                <div class="d-flex justify-content-between">
                  <strong><?php echo htmlspecialchars($po['number'] ?? ''); ?></strong>
                  <small class="text-muted"><?php echo htmlspecialchars($po['status'] ?? ''); ?></small>
                </div>
                <div class="text-muted small">Total: <?php echo htmlspecialchars($po['total'] ?? ''); ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="p-3">
            <div class="alert alert-secondary mb-0">No purchase order data available yet.</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Report Overview removed by request -->

  <!-- Recent Design Saves and Materials Cost Summary -->
  <div class="col-12 col-xxl-6">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Design Saves</h5>
      </div>
      <div class="card-body">
        <div id="client_recent_design_saves">
          <div class="text-muted">Loading…</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-xxl-6">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Materials Cost Summary</h5>
      </div>
      <div class="card-body">
        <div id="client_materials_cost_summary">
          <div class="text-muted">Loading…</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Finance Summary removed by request -->

  
</div>

<!-- Old Finance and Messages sections removed by request (duplicate finance) -->
<?php include_once __DIR__ . '/../components/messaging_nav.php'; ?>
<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>

<script>
// Unified loader for design summaries (cached between consumers)
const loadDesignSummaries = (function(){
  let cache=null, inflight=null;
  return function(){
    if (cache) return Promise.resolve(cache);
    if (inflight) return inflight;
    inflight = fetch('<?php echo url('api/designs_summary.php'); ?>?mine=1', { credentials:'include' })
      .then(r=>r.json())
      .then(j=>{ cache = (j && j.success && Array.isArray(j.data)) ? j.data : []; return cache; })
      .finally(()=>{ inflight = null; });
    return inflight;
  };
})();

// Recent Design Saves: build from summaries (fast + single request)
(function(){
  const box = document.getElementById('client_recent_design_saves');
  if (!box) return;
  loadDesignSummaries()
    .then(arr=>{
      if (!arr.length) { box.innerHTML = '<div class="text-muted">No design saves yet.</div>'; return; }
      const rows = arr
        .slice() // copy
        .sort((a,b)=> new Date(b.saved_at||0) - new Date(a.saved_at||0))
        .slice(0,10)
        .map(d=>{
          const pname = d.project_name || ('Project #'+(d.project_id||''));
          const saved = d.saved_at ? new Date(d.saved_at).toLocaleString() : '';
          const dl = '<?php echo url('api/designs.php'); ?>' + '?action=download&file=client&id=' + encodeURIComponent(d.design_id||'');
          return '<tr>'+
            '<td>'+escapeHtml(pname)+'</td>'+
            '<td>'+escapeHtml(d.title||('Design #'+(d.design_id||'')))+'</td>'+
            '<td><span class="badge bg-secondary">JSON</span></td>'+
            '<td>'+escapeHtml(saved)+'</td>'+
            '<td>-</td>'+
            '<td><a class="btn btn-sm btn-outline-primary" href="'+dl+'" target="_blank" rel="noopener">Download</a></td>'+
          '</tr>';
        }).join('');
      box.innerHTML = '<div class="table-responsive">\
        <table class="table table-hover mb-0">\
          <thead><tr>\
            <th>Project</th><th>Title</th><th>Type</th><th>Saved</th><th>By</th><th></th>\
          </tr></thead>\
          <tbody>'+rows+'</tbody>\
        </table></div>';
    })
    .catch(()=>{ box.innerHTML = '<div class="text-danger">Failed to load.</div>'; });
})();

// Materials Cost Summary: latest per project material costs from design summaries
(function(){
  const box = document.getElementById('client_materials_cost_summary');
  if (!box) return;
  loadDesignSummaries()
    .then(arr=>{
      if (!arr.length) { box.innerHTML = '<div class="text-muted">No design summaries yet.</div>'; return; }
      function fmt(n){ try { return (Number(n)||0).toLocaleString(undefined,{maximumFractionDigits:0}); } catch(_){ return String(n); } }
      const rows = arr.map(x=>{
        const e = x.estimates||{}; const w=e.wall||{}; const r=e.rcc||{};
        const wm = Number(w.materialsCostBDT)||0; const rm = Number(r.materialsCostBDT)||0; const tot = wm+rm;
        return '<tr>'+
          '<td>'+escapeHtml(x.project_name||('Project #'+(x.project_id||'')))+'</td>'+
          '<td class="text-end">'+fmt(wm)+'</td>'+
          '<td class="text-end">'+fmt(rm)+'</td>'+
          '<td class="text-end fw-semibold">'+fmt(tot)+'</td>'+
        '</tr>';
      }).join('');
      const totalAll = arr.reduce((s,x)=>{
        const e=x.estimates||{}; const w=e.wall||{}; const r=e.rcc||{}; return s + (Number(w.materialsCostBDT)||0) + (Number(r.materialsCostBDT)||0);
      },0);
      box.innerHTML = '<div class="table-responsive">\
        <table class="table mb-0">\
          <thead><tr>\
            <th>Project</th><th class="text-end">Wall materials (BDT)</th><th class="text-end">RCC materials (BDT)</th><th class="text-end">Total (BDT)</th>\
          </tr></thead>\
          <tbody>'+rows+'</tbody>\
          <tfoot><tr>\
            <th colspan="3" class="text-end">Grand total</th><th class="text-end">'+fmt(totalAll)+'</th>\
          </tr></tfoot>\
        </table></div>';
    })
    .catch(()=>{ box.innerHTML = '<div class="text-danger">Failed to load.</div>'; });
})();
</script>
