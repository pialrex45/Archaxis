<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth(); if (!hasRole('project_manager')) { http_response_code(403); die('Access denied.'); }
include_once __DIR__ . '/../layouts/header.php';
$csrf = generateCSRFToken();
?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Material Requests</h2>
    <div class="small text-muted">Approve or reject pending requests.</div>
  </div>
  <div class="card">
    <div class="card-body">
      <div id="mrTable">Loading...</div>
    </div>
  </div>
</div>
<script>
const csrf = '<?= $csrf ?>';
function loadMR(){
  fetch('<?= url('/api/pm/material_requests/list') ?>', {headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json())
    .then(d=>{
      const wrap = document.getElementById('mrTable');
      if (!d.success || !Array.isArray(d.data) || d.data.length===0){ wrap.innerHTML = '<div class="text-muted">No pending requests.</div>'; return; }
      let html = '<div class="table-responsive"><table class="table table-striped table-sm"><thead><tr><th>#</th><th>Material</th><th>Qty</th><th>Project</th><th>Status</th><th>Action</th></tr></thead><tbody>';
      d.data.forEach(m=>{
        html += `<tr>
          <td>${m.id ?? ''}</td>
          <td>${m.material_name ?? ''}</td>
          <td>${m.quantity ?? ''}</td>
          <td>${m.project_name ?? ''}</td>
          <td><span class="badge bg-warning text-dark">${m.status ?? ''}</span></td>
          <td>
            <button class="btn btn-success btn-sm me-2" onclick="act(${m.id},'approve')">Approve</button>
            <button class="btn btn-outline-danger btn-sm" onclick="act(${m.id},'reject')">Reject</button>
          </td>
        </tr>`;
      });
      html += '</tbody></table></div>';
      wrap.innerHTML = html;
    })
    .catch(()=>{ document.getElementById('mrTable').innerHTML = '<div class="text-danger">Failed to load.</div>'; });
}
function act(id,action){
  const url = action==='approve' ? '<?= url('/api/pm/material_requests/approve') ?>' : '<?= url('/api/pm/material_requests/reject') ?>';
  const fd = new FormData(); fd.append('id', id); fd.append('csrf_token', csrf);
  fetch(url, {method:'POST', body: fd, headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{ if(d.success){ loadMR(); } else { alert(d.message||'Action failed'); } })
    .catch(()=>alert('Server error'));
}
loadMR();
</script>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
