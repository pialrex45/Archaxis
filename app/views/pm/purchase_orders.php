<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth(); if (!hasRole('project_manager')) { http_response_code(403); die('Access denied.'); }
include_once __DIR__ . '/../layouts/header.php';
$csrf = generateCSRFToken();
?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Purchase Orders</h2>
    <div class="small text-muted">Create from approved requests, track statuses.</div>
  </div>
  <div class="card">
    <div class="card-body">
      <div id="poTable">Loading...</div>
    </div>
  </div>
</div>
<script>
const csrf = '<?= $csrf ?>';
function loadPO(){
  fetch('<?= url('/api/pm/purchase_orders/list') ?>', {headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json())
    .then(d=>{
      const wrap = document.getElementById('poTable');
      if (!d.success || !Array.isArray(d.data) || d.data.length===0){ wrap.innerHTML = '<div class="text-muted">No purchase orders.</div>'; return; }
      let html = '<div class="table-responsive"><table class="table table-striped table-sm"><thead><tr><th>#</th><th>Vendor</th><th>Project</th><th>Total</th><th>Status</th><th>Action</th></tr></thead><tbody>';
      d.data.forEach(po=>{
        const id = po.id ?? '';
        const status = (po.status ?? '').toLowerCase();
        html += `<tr>
          <td>${id}</td>
          <td>${po.vendor_name ?? ''}</td>
          <td>${po.project_name ?? ''}</td>
          <td>${po.total_cost ?? ''}</td>
          <td><span class="badge ${status==='approved'?'bg-success':(status==='pending'?'bg-warning text-dark':'bg-secondary')}">${po.status ?? ''}</span></td>
          <td>
            <div class="btn-group btn-group-sm">
              <button class="btn btn-outline-secondary" onclick="setStatus(${id},'pending')">Pending</button>
              <button class="btn btn-outline-primary" onclick="setStatus(${id},'processing')">Processing</button>
              <button class="btn btn-outline-success" onclick="setStatus(${id},'approved')">Approved</button>
              <button class="btn btn-outline-dark" onclick="setStatus(${id},'completed')">Completed</button>
            </div>
          </td>
        </tr>`;
      });
      html += '</tbody></table></div>';
      wrap.innerHTML = html;
    })
    .catch(()=>{ document.getElementById('poTable').innerHTML = '<div class="text-danger">Failed to load.</div>'; });
}
function setStatus(id, status){
  const fd = new FormData(); fd.append('id', id); fd.append('status', status); fd.append('csrf_token', csrf);
  fetch('<?= url('/api/pm/purchase_orders/status') ?>', {method:'POST', body: fd, headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(d=>{ if(d.success){ loadPO(); } else { alert(d.message||'Update failed'); } })
    .catch(()=>alert('Server error'));
}
loadPO();
</script>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
