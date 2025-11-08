<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth();
if (!hasAnyRole(['project_manager','admin'])) { http_response_code(403); die('Forbidden'); }
$pageTitle = 'Project Workflow';
$currentPage = 'workflow';
// Use the same include path style as other PM views
include_once __DIR__ . '/../layouts/header.php';

$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
?>
<div class="row">
  <div class="col-12 mb-3 d-flex align-items-center">
    <h1 class="h4 mb-0">Workflow Activity</h1>
    <span class="ms-2 text-muted small">Project-wide chronological log</span>
  </div>
</div>
<div class="row mb-3 g-3 align-items-end">
  <div class="col-lg-5">
    <form id="wfProjectForm" class="row g-2">
      <div class="col-7">
        <select id="wfProjectSelect" class="form-select" required>
          <option value="">Select Project...</option>
        </select>
      </div>
      <div class="col-5 d-flex gap-2">
        <input type="number" class="form-control" id="wfProjectId" placeholder="ID" value="<?php echo $projectId ?: ''; ?>" title="Direct project ID override">
        <button class="btn btn-primary flex-shrink-0">Load</button>
      </div>
    </form>
  </div>
  <div class="col-lg-7 text-lg-end d-flex flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2">
      <div class="input-group input-group-sm" style="width:160px;">
        <span class="input-group-text">Auto</span>
        <select id="wfAutoInterval" class="form-select">
          <option value="0">Off</option>
          <option value="15">15s</option>
          <option value="30" selected>30s</option>
          <option value="60">60s</option>
          <option value="120">2m</option>
        </select>
      </div>
      <span id="wfCountdown" class="small text-muted"></span>
    </div>
    <button id="wfRefresh" class="btn btn-outline-secondary btn-sm"><i class="fas fa-rotate"></i> Refresh</button>
  </div>
</div>
<div id="wfResults" class="card shadow-sm d-none">
  <div class="card-header py-2 d-flex justify-content-between align-items-center">
    <strong class="small mb-0">Activity (<span id="wfCount">0</span>)</strong>
    <span class="small text-muted" id="wfStatus"></span>
  </div>
  <div class="card-body p-0">
    <div id="wfEmpty" class="p-3 text-center text-muted small d-none">No activity found yet.</div>
    <div id="wfList" class="list-group list-group-flush small"></div>
    <div class="p-2 text-center"><button id="wfLoadMore" class="btn btn-sm btn-outline-primary d-none">Load More</button></div>
  </div>
</div>
<script>
(function(){
  const form = document.getElementById('wfProjectForm');
  const list = document.getElementById('wfList');
  const wrap = document.getElementById('wfResults');
  const countEl = document.getElementById('wfCount');
  const moreBtn = document.getElementById('wfLoadMore');
  const refreshBtn = document.getElementById('wfRefresh');
  const emptyEl = document.getElementById('wfEmpty');
  const statusEl = document.getElementById('wfStatus');
  let projectId = <?php echo $projectId ?: 0; ?>;
  let offset = 0; const limit = 25; let eof=false; let loading=false;
  let timer=null; let remaining=0; let interval=30; // default 30s (matches selected)

  function fmt(ts){ return ts ? ts.replace('T',' ').substring(0,19) : ''; }
  function escapeHtml(s){ return (s||'').replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[c])); }
  function line(item){
    const sum = escapeHtml(item.summary||'');
    const actor = escapeHtml(item.actor_name||'');
    const when = escapeHtml(fmt(item.created_at||''));
    return `<div class="list-group-item">
      <div class="d-flex justify-content-between">
        <div><span class="badge bg-secondary me-1">${escapeHtml(item.action||'')}</span> ${sum}</div>
        <div class="text-muted">${when}</div>
      </div>
      <div class="text-muted mt-1">${actor?('<i class=\"fas fa-user\"></i> '+actor):''}</div>
    </div>`;
  }
  function setStatus(msg,type='muted'){ if(statusEl){ statusEl.textContent = msg||''; statusEl.className = 'small text-'+(type==='error'?'danger':'muted'); } }
  function render(items,append){
    if(!append){ list.innerHTML=''; offset=0; eof=false; }
    items.forEach(it=>{ list.insertAdjacentHTML('beforeend', line(it)); });
    countEl.textContent = list.children.length;
    const hasItems = list.children.length>0;
    emptyEl.classList.toggle('d-none', hasItems);
    if(!hasItems) { emptyEl.textContent = eof? 'No activity found yet.' : 'Loading...'; }
    moreBtn.classList.toggle('d-none', eof || !hasItems);
    wrap.classList.remove('d-none');
  }
  function load(reset=false){
    if(loading||!projectId) return; loading=true;
    if(reset){ offset=0; eof=false; setStatus('Loading...'); list.innerHTML=''; emptyEl.classList.add('d-none'); wrap.classList.remove('d-none'); }
    fetch(`<?php echo url('/api/pm/workflow.php'); ?>?project_id=${projectId}&limit=${limit}&offset=${offset}`)
      .then(r=>r.json()).then(j=>{ 
        if(!j.success){ setStatus(j.message||'Error', 'error'); emptyEl.classList.remove('d-none'); emptyEl.textContent = j.message||'Error loading activity.'; wrap.classList.remove('d-none'); return; }
        const items = j.data||[];
        if(items.length===0){ if(reset){ eof=true; render([],true); } else { eof=true; }
        } else { offset += items.length; if(items.length < limit) eof=true; render(items,true); }
        setStatus(eof && list.children.length>0 ? 'End of results' : '');
      }).catch(()=>{ setStatus('Network error','error'); emptyEl.classList.remove('d-none'); emptyEl.textContent='Network error fetching activity.'; wrap.classList.remove('d-none'); })
      .finally(()=>{ loading=false; });
  }
  function syncInputs(fromSelect=false){
    const sel = document.getElementById('wfProjectSelect');
    const idInput = document.getElementById('wfProjectId');
    if(fromSelect){ idInput.value = sel.value; }
    projectId = parseInt(idInput.value,10)||0; return projectId;
  }
  form.addEventListener('submit', function(e){ e.preventDefault(); syncInputs(); if(projectId){ list.innerHTML=''; offset=0; eof=false; load(true); restartTimer(); } });
  document.getElementById('wfProjectSelect').addEventListener('change', ()=>{ syncInputs(true); if(projectId){ list.innerHTML=''; offset=0; eof=false; load(true); restartTimer(); } });
  moreBtn.addEventListener('click', ()=>load());
  refreshBtn.addEventListener('click', ()=> { load(true); restartTimer(); });

  // Auto-refresh logic
  const intervalSel = document.getElementById('wfAutoInterval');
  const countdownEl = document.getElementById('wfCountdown');
  intervalSel.addEventListener('change', ()=>{ interval = parseInt(intervalSel.value,10)||0; restartTimer(); });
  function tick(){ if(interval===0){ countdownEl.textContent=''; return; } remaining--; if(remaining<=0){ load(true); remaining=interval; } countdownEl.textContent = remaining+'s'; }
  function restartTimer(){ if(timer){ clearInterval(timer); timer=null;} if(interval>0){ remaining=interval; countdownEl.textContent=remaining+'s'; timer=setInterval(tick,1000);} }

  // Populate project dropdown (lightweight reuse of existing projects index endpoint)
  function loadProjects(){
    fetch(`<?php echo url('/api/pm/projects_min.php'); ?>`)
      .then(r=>r.json())
      .then(j=>{ 
        const sel=document.getElementById('wfProjectSelect');
        if(j.success && Array.isArray(j.data) && j.data.length){
          sel.innerHTML='<option value="">Select Project...</option>' + j.data.map(p=>`<option value="${p.id}">${p.id} - ${escapeHtml(p.name||('Project '+p.id))}</option>`).join('');
          if(projectId){ sel.value=projectId; }
        } else {
          sel.innerHTML='<option value="">(No projects)</option>';
        }
      })
      .catch(()=>{/* ignore */});
  }

  loadProjects();
  if(projectId){ load(true); }
  restartTimer();
})();
</script>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
