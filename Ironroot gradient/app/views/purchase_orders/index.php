<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth();

$pageTitle = 'Purchase Orders';
$currentPage = 'purchase_orders';
?>
<?php include_once __DIR__ . '/../../views/layouts/header.php'; ?>
<?php include_once __DIR__ . '/../../views/layouts/sidebar.php'; ?>
<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
  <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Purchase Orders</h1>
    <a href="<?php echo url('/purchase-orders/create'); ?>" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> New PO</a>
  </div>
  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th>ID</th>
          <th>Project</th>
          <th>Supplier</th>
          <th>Status</th>
          <th>Total</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="poRows"></tbody>
    </table>
  </div>
</div>
<?php include_once __DIR__ . '/../../views/layouts/footer.php'; ?>
<script>
const apiPOIndex = '<?php echo url('/api/purchase_orders/index'); ?>';
const apiPOUpdate = '<?php echo url('/api/purchase_orders/update_status'); ?>';

const statusBadge = (s)=>{
  const map = {pending:'bg-warning', approved:'bg-success', rejected:'bg-danger', ordered:'bg-primary', delivered:'bg-info', cancelled:'bg-secondary', draft:'bg-light text-dark'};
  const cls = map[s] || 'bg-secondary';
  return `<span class="badge ${cls}">${s}</span>`;
};

const nextActions = (s)=>{
  switch(s){
    case 'pending': return ['approved','rejected','cancelled'];
    case 'approved': return ['ordered','cancelled'];
    case 'ordered': return ['delivered','cancelled'];
    case 'draft': return ['pending','cancelled'];
    default: return [];
  }
};

async function doUpdate(id, status){
  if(!confirm(`Change status to ${status}?`)) return;
  const res = await fetch(apiPOUpdate,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id, status})});
  const j = await res.json();
  if(j.success){ loadPOs(); } else { alert(j.message||'Failed'); }
}
async function loadPOs(){
  const res = await fetch(apiPOIndex);
  const json = await res.json();
  const rows = (json.data||[]).map(o=>{
    const actions = nextActions(o.status).map(a=>`<button class='btn btn-sm btn-outline-secondary me-1' onclick=\"doUpdate(${o.id},'${a}')\">${a}</button>`).join('');
    return `
    <tr>
      <td>#${o.id}</td>
      <td>${o.project_name||o.project_id}</td>
      <td>${o.supplier_name||o.supplier_id}</td>
      <td>${statusBadge(o.status)}</td>
      <td>${o.total_amount}</td>
      <td>${o.created_at}</td>
      <td>
        <a href='${'<?php echo url('/purchase-orders/show'); ?>'}?id=${o.id}' class='btn btn-sm btn-outline-primary me-2'>View</a>
        ${actions}
      </td>
    </tr>`;
  }).join('');
  document.getElementById('poRows').innerHTML = rows || '<tr><td colspan="7" class="text-muted">No purchase orders yet.</td></tr>';
}
loadPOs();
</script>
