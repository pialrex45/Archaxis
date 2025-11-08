<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth();

$pageTitle = 'Purchase Order Details';
$currentPage = 'purchase_orders';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
?>
<?php include_once __DIR__ . '/../../views/layouts/header.php'; ?>
<?php include_once __DIR__ . '/../../views/layouts/sidebar.php'; ?>
<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
  <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Purchase Order #<?php echo htmlspecialchars($id); ?></h1>
  </div>
  <div id="poContainer"></div>
</div>
<?php include_once __DIR__ . '/../../views/layouts/footer.php'; ?>
<script>
const apiPOShow = '<?php echo url('/api/purchase_orders/show'); ?>' + '?id=' + encodeURIComponent(<?php echo (int)$id; ?>);
const apiPOUpdate = '<?php echo url('/api/purchase_orders/update_status'); ?>';

const statusBadge = (s)=>{
  const map = {pending:'bg-warning', approved:'bg-primary', rejected:'bg-danger', ordered:'bg-info', delivered:'bg-success', cancelled:'bg-secondary', draft:'bg-light text-dark'};
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
  if(j.success){ loadPO(); } else { alert(j.message||'Failed'); }
}

async function loadPO(){
  const res = await fetch(apiPOShow); const j = await res.json();
  if(!j.data){ document.getElementById('poContainer').innerHTML = '<div class="alert alert-danger">Not found</div>'; return; }
  const o = j.data.order; const items = j.data.items||[];
  const actions = nextActions(o.status).map(a=>`<button class='btn btn-sm btn-outline-secondary me-1' onclick=\"doUpdate(${o.id},'${a}')\">${a}</button>`).join('');
  document.getElementById('poContainer').innerHTML = `
    <div class="card mb-3">
      <div class="card-body">
        <div class="row">
          <div class="col-md-3"><strong>PO ID:</strong> #${o.id}</div>
          <div class="col-md-3"><strong>Project:</strong> ${o.project_name||o.project_id}</div>
          <div class="col-md-3"><strong>Supplier:</strong> ${o.supplier_name||o.supplier_id}</div>
          <div class="col-md-3"><strong>Status:</strong> ${statusBadge(o.status)}</div>
        </div>
        <div class="row mt-2 align-items-center">
          <div class="col-md-3"><strong>Total:</strong> ${o.total_amount}</div>
          <div class="col-md-3"><strong>Created:</strong> ${o.created_at}</div>
          <div class="col-md-6 text-md-end mt-2 mt-md-0">${actions}</div>
        </div>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-striped">
        <thead><tr><th>#</th><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
        <tbody>
          ${items.map((it,idx)=>`<tr><td>${idx+1}</td><td>${it.product_name||it.product_id}</td><td>${it.quantity}</td><td>${it.unit_price}</td><td>${it.total}</td></tr>`).join('')}
        </tbody>
      </table>
    </div>`;
}
loadPO();
</script>
