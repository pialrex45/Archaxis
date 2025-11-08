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
          <th>Manager</th>
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
  const pmName = p.site_manager_name || '';
  const pmBtn = pmName ? `<span class='small text-success me-1 pm-name' data-pm-name="${pmName.replace(/"/g,'&quot;')}">${pmName.replace(/</g,'&lt;')}</span><button class=\"btn btn-sm btn-outline-secondary btn-select-pm\" data-project-id=\"${p.id}\" data-current-pm-name=\"${pmName.replace(/"/g,'&quot;')}\">Change</button><button class=\"btn btn-sm btn-outline-danger ms-1 btn-unassign-pm\" data-project-id=\"${p.id}\">Unassign</button>` : `<button class=\"btn btn-sm btn-outline-primary btn-select-pm\" data-project-id=\"${p.id}\">Select</button>`;
      return `<tr>
        <td>${p.name||p.title||''}</td>
        <td><span class="badge bg-secondary">${status}</span></td>
        <td id="pm-cell-${p.id}">${pmBtn}</td>
        <td>${milestones||'<span class="text-muted">-</span>'}</td>
        <td>${deadline? new Date(deadline).toLocaleDateString(): ''}</td>
        <td>${progressBar(prog)}</td>
      </tr>`;
    }).join('');
    document.querySelector('#clientProjectsTable tbody').innerHTML = rows || `<tr><td colspan="6" class="text-center text-muted">No projects</td></tr>`;
  });
</script>

<!-- Manager Selection Modal -->
<div class="modal fade" id="pmSelectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Select Project Manager</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
        <div id="pmSelectAlert" class="alert d-none p-2"></div>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <small class="text-muted">Select a manager</small>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="pmRefreshBtn">Refresh</button>
        </div>
  <div class="table-responsive" style="max-height:400px; overflow-y:auto;">
          <table class="table table-sm align-middle"><thead><tr><th style=\"width:25%\">Name</th><th style=\"width:50%\">Ongoing Projects</th><th style=\"width:25%\">Action</th></tr></thead><tbody id="pmListBody"><tr><td colspan="3" class="text-center text-muted">Loading...</td></tr></tbody></table>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
    </div>
  </div>
</div>
<script>
(function(){
  const listBody=document.getElementById('pmListBody');
  const alertBox=document.getElementById('pmSelectAlert');
  const refreshBtn=document.getElementById('pmRefreshBtn');
  const modalEl=document.getElementById('pmSelectModal');
  let currentProjectId=null; let bsModal=null; let currentAssignedId=null;
  function showAlert(msg,type='success'){alertBox.className='alert alert-'+(type==='success'?'success':'danger')+' p-2';alertBox.textContent=msg;alertBox.classList.remove('d-none');}
  function clearAlert(){alertBox.className='alert d-none p-2';alertBox.textContent='';}
  async function loadManagers(){
  listBody.innerHTML='<tr><td colspan="3" class="text-center text-muted">Loading...</td></tr>';
  try{const r=await fetch('<?php echo url('/api/client/project_managers.php?action=list'); ?>',{credentials:'same-origin'});const d=await r.json();if(!d.success){listBody.innerHTML='<tr><td colspan="3" class="text-danger">Failed</td></tr>';return;}const rows=d.data||[];if(!rows.length){listBody.innerHTML='<tr><td colspan="3" class="text-muted">No managers</td></tr>';return;}listBody.innerHTML=rows.map(m=>{const highlighted=currentAssignedId && Number(currentAssignedId)===Number(m.id);const projects=(m.ongoing_projects||'').replace(/[<>]/g,'');const short=projects.length>120?projects.slice(0,117)+'...':projects;return `<tr class='${highlighted?"table-success":""}'><td>${(m.name||'Unnamed').replace(/[<>]/g,'')}</td><td title='${projects}'>${short||'<span class=\"text-muted\">-</span>'}</td><td>${highlighted?'<span class=\"badge bg-success\">Current</span>':'<button class=\'btn btn-sm btn-primary\' data-pm-id=\''+m.id+"'>Select</button>"}</td></tr>`}).join('');}
  catch(e){listBody.innerHTML='<tr><td colspan="3" class="text-danger">Error</td></tr>';}    
  }
  async function assign(pmId, pmName){
    clearAlert(); if(!currentProjectId) return;
    try{const r=await fetch('<?php echo url('/api/client/project_managers.php?action=assign'); ?>',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify({project_id:currentProjectId,manager_user_id:pmId})});const d=await r.json();if(d.success){showAlert('Assigned','success');const cell=document.getElementById('pm-cell-'+currentProjectId);if(cell){const safeName=(pmName||'').replace(/</g,'&lt;');cell.innerHTML=`<span class='small text-success me-1 pm-name' data-pm-name="${safeName}">${safeName}</span><button class="btn btn-sm btn-outline-secondary btn-select-pm" data-project-id="${currentProjectId}" data-current-pm-name="${safeName}" data-current-pm-id="${pmId}">Change</button><button class="btn btn-sm btn-outline-danger ms-1 btn-unassign-pm" data-project-id="${currentProjectId}">Unassign</button>`;}currentAssignedId=pmId;loadManagers();} else {showAlert(d.message||'Failed','danger');}}catch(e){showAlert('Server error','danger');}
  }
  async function unassign(){
    clearAlert(); if(!currentProjectId) return;
    try{const r=await fetch('<?php echo url('/api/client/project_managers.php?action=unassign'); ?>',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify({project_id:currentProjectId})});const d=await r.json();if(d.success){showAlert('Unassigned','success');const cell=document.getElementById('pm-cell-'+currentProjectId);if(cell){cell.innerHTML=`<button class="btn btn-sm btn-outline-primary btn-select-pm" data-project-id="${currentProjectId}">Select</button>`;}currentAssignedId=null;loadManagers();} else {showAlert(d.message||'Failed','danger');}}catch(e){showAlert('Server error','danger');}
  }
  document.addEventListener('click',e=>{
    const sel=e.target.closest('.btn-select-pm'); if(sel){currentProjectId=parseInt(sel.getAttribute('data-project-id'),10);currentAssignedId=sel.getAttribute('data-current-pm-id')||null; if(window.bootstrap&&!bsModal) bsModal=new bootstrap.Modal(modalEl); if(bsModal) bsModal.show(); loadManagers(); return;}
    const un=e.target.closest('.btn-unassign-pm'); if(un){ currentProjectId=parseInt(un.getAttribute('data-project-id'),10); unassign(); }
  });
  listBody.addEventListener('click',e=>{const b=e.target.closest('button[data-pm-id]'); if(!b) return; const tr=b.closest('tr'); const nameCell=tr?tr.children[0].textContent.trim():''; const pmId=parseInt(b.getAttribute('data-pm-id'),10); if(pmId) assign(pmId,nameCell);});
  refreshBtn.addEventListener('click',loadManagers);
})();
</script>
</script>
