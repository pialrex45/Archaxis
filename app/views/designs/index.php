<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth();
$pageTitle = 'Designs';
$currentPage = 'designs';
$canApproveDesigns = hasAnyRole(['admin','site_engineer']);
$isElevated = $canApproveDesigns; // non-elevated users see only approved
include __DIR__ . '/../layouts/header.php';
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h1 class="h4 mb-0">Designs</h1>
    <div class="d-flex gap-2">
  <a class="btn btn-sm btn-outline-primary" href="<?php echo url('/diagram45/index.html'); ?>" target="_blank" rel="noopener">Open Designer</a>
      <a class="btn btn-sm btn-outline-secondary" href="<?php echo url('/designs'); ?>">Refresh</a>
    </div>
  </div>
  <form class="row g-2 align-items-end mb-3">
    <div class="col-sm-4">
      <label class="form-label form-label-sm">Project</label>
      <select class="form-select form-select-sm" id="designListProject"></select>
    </div>
    <?php if ($isElevated): ?>
    <div class="col-sm-3">
      <label class="form-label form-label-sm">Status</label>
      <select class="form-select form-select-sm" id="designListStatus">
        <option value="">All</option>
        <option value="approved">Approved</option>
        <option value="submitted">Submitted</option>
        <option value="rejected">Rejected</option>
      </select>
    </div>
    <?php endif; ?>
    <div class="col-sm-2">
      <button type="button" class="btn btn-sm btn-primary" onclick="loadDesigns();">Filter</button>
    </div>
  </form>
  <div class="table-responsive ir-table-wrapper">
    <table class="table table-sm ir-table-modern">
      <thead>
        <tr>
          <th>#</th>
          <th>Project</th>
          <th>Title</th>
          <th>Status</th>
          <th>Client File</th>
          <th>Engineer PDF</th>
          <?php if ($canApproveDesigns): ?><th>Actions</th><?php endif; ?>
        </tr>
      </thead>
      <tbody id="designsBody"><tr><td colspan="7" class="text-muted">Loading...</td></tr></tbody>
    </table>
  </div>
</div>
<script>
(function initFlags(){
  window.CAN_APPROVE = <?php echo $canApproveDesigns ? 'true' : 'false'; ?>;
})();
(function initBase(){
  window.APP_BASE = <?php echo json_encode(url('')); ?>;
})();
function prefixBase(p){
  if(!p) return p;
  const base = window.APP_BASE || '';
  if (!base) return p; // app at web root
  if (p.startsWith(base + '/')) return p;
  if (p.startsWith('/')) return base + p;
  return p;
}
(async function preload(){
  try{
    const res = await fetch('<?php echo url('api/projects/list.php'); ?>');
    const j = await res.json();
    const sel = document.getElementById('designListProject');
    sel.innerHTML = '<option value="">All</option>' + (j.data||[]).map(p=>`<option value="${p.id}">${p.name||('Project #'+p.id)}</option>`).join('');
  }catch(e){}
  loadDesigns();
})();

async function loadDesigns(){
  const tbody = document.getElementById('designsBody');
  tbody.innerHTML = '<tr><td colspan="7" class="text-muted">Loading...</td></tr>';
  const pid = document.getElementById('designListProject').value;
  const st = <?php echo $isElevated ? 'document.getElementById("designListStatus").value' : '"approved"'; ?>;
  const params = new URLSearchParams();
  if (pid) params.set('project_id', pid);
  if (st) params.set('status', st);
  const qs = params.toString() ? ('?'+params.toString()) : '';
  try{
    const res = await fetch('<?php echo url('api/designs.php'); ?>'+qs);
    const j = await res.json();
    if(!j.success) throw new Error(j.message||'Failed');
    const rows = (j.data||[]).map((d,i)=>{
      const status = String(d.status||'').toLowerCase();
      const badge = status==='approved' ? '<span class="ir-badge ir-badge-success">approved</span>' : (status==='rejected' ? '<span class="ir-badge ir-badge-danger">rejected</span>' : '<span class="ir-badge ir-badge-warning">submitted</span>');
  const clientUrl = '<?php echo url('api/designs.php'); ?>' + '?action=download&file=client&id=' + encodeURIComponent(d.id);
  const pdfUrl = '<?php echo url('api/designs.php'); ?>' + '?action=download&file=approved&id=' + encodeURIComponent(d.id);
  const client = d.client_file ? `<a href="${clientUrl}" target="_blank">Download</a>` : '';
  const pdf = d.engineer_pdf ? `<a href="${pdfUrl}" target="_blank">View PDF</a>` : '';
      let actions = '';
      if (window.CAN_APPROVE && status === 'submitted') {
        const proj = d.project_name || ("#"+d.project_id);
        actions = `<button class="btn btn-sm btn-success" data-id="${String(d.id)}" data-title="${escapeHtml(d.title||'')}" data-project="${escapeHtml(proj)}" onclick="openApproveFromBtn(this)">Approve</button>`;
      }
      return `<tr>
        <td>${i+1}</td>
        <td>${d.project_name || d.project_id}</td>
        <td>${d.title||''}</td>
        <td>${badge}</td>
        <td>${client}</td>
        <td>${pdf}</td>
        ${window.CAN_APPROVE ? `<td>${actions}</td>` : ''}
      </tr>`;
    }).join('');
    tbody.innerHTML = rows || `<tr><td colspan="${window.CAN_APPROVE?7:6}" class="text-muted">No designs</td></tr>`;
  }catch(e){ tbody.innerHTML = '<tr><td colspan="6" class="text-danger">'+e.message+'</td></tr>'; }
}

function escapeHtml(s){
  return String(s).replace(/[&<>"']/g, function(c){
    return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#039;'}[c]);
  });
}

function openApproveFromBtn(btn){
  const payload = { id: btn.dataset.id, title: btn.dataset.title, project_name: btn.dataset.project };
  openApproveModal(payload);
}

function openApproveModal(payload){
  const modalEl = document.getElementById('approveDesignModal');
  modalEl.querySelector('[data-field="design-id"]').value = payload.id;
  modalEl.querySelector('[data-field="design-title"]').textContent = payload.title || '';
  modalEl.querySelector('[data-field="design-project"]').textContent = payload.project_name || '';
  const bsModal = new bootstrap.Modal(modalEl);
  bsModal.show();
}

async function submitApprove(){
  const btn = document.getElementById('approveSubmitBtn');
  const form = document.getElementById('approveForm');
  const fd = new FormData(form);
  if(!fd.get('pdf') || !fd.get('pdf').name){
    alert('Please choose a PDF file.');
    return;
  }
  btn.disabled = true; btn.textContent = 'Uploading...';
  try{
    const res = await fetch('<?php echo url('api/designs.php?action=engineer_approve'); ?>', { method: 'POST', body: fd });
    const j = await res.json();
    if(!j.success){ throw new Error(j.message||'Approval failed'); }
    bootstrap.Modal.getInstance(document.getElementById('approveDesignModal')).hide();
    form.reset();
    loadDesigns();
  }catch(e){
    alert(e.message);
  }finally{
    btn.disabled = false; btn.textContent = 'Approve & Upload PDF';
  }
}
</script>
<?php if ($canApproveDesigns): ?>
<!-- Approve Design Modal -->
<div class="modal fade" id="approveDesignModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Approve Design</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><strong>Project:</strong> <span data-field="design-project"></span></div>
        <div class="mb-3"><strong>Title:</strong> <span data-field="design-title"></span></div>
        <form id="approveForm" onsubmit="event.preventDefault(); submitApprove();">
          <input type="hidden" name="id" data-field="design-id" />
          <label class="form-label">Approved PDF</label>
          <input class="form-control" type="file" name="pdf" accept="application/pdf,.pdf" required />
        </form>
        <small class="text-muted">Only PDF files are accepted.</small>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button id="approveSubmitBtn" type="button" class="btn btn-success" onclick="submitApprove()">Approve & Upload PDF</button>
      </div>
    </div>
  </div>
 </div>
<?php endif; ?>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
