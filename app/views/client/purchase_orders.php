<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth();
requireRole('client');

$pageTitle = 'Client Purchase Orders';
$currentPage = 'client_purchase_orders';

include_once __DIR__ . '/../layouts/header.php';
include_once __DIR__ . '/../layouts/sidebar.php';
?>
<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
  <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Purchase Orders</h1>
  </div>

  <div class="table-responsive">
    <table class="table table-striped align-middle" id="clientPOsTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Project</th>
          <th>Vendor</th>
          <th>Status</th>
          <th>Total</th>
          <th>Created</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>
<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
<script>
const apiClientPOs = '<?php echo url('/api/client/purchase_orders'); ?>';
const statusBadge = (s)=>{
  const map={pending:'bg-warning', approved:'bg-primary', rejected:'bg-danger', ordered:'bg-info', delivered:'bg-success', cancelled:'bg-secondary', draft:'bg-light text-dark'};
  const cls = map[(s||'').toString().toLowerCase()]||'bg-secondary';
  return `<span class="badge ${cls}">${s||''}</span>`;
};
fetch(apiClientPOs, {headers:{'X-Requested-With':'XMLHttpRequest'}})
 .then(r=>r.json()).then(json=>{
   const rows = (json.data||[]).map(o=>{
     return `<tr>
       <td>#${o.id||''}</td>
       <td>${o.project_name||o.project_id||''}</td>
       <td>${o.supplier_name||o.vendor||''}</td>
       <td>${statusBadge(o.status||'')}</td>
       <td>${o.total_amount||o.amount||''}</td>
       <td>${o.created_at||''}</td>
     </tr>`;
   }).join('');
   document.querySelector('#clientPOsTable tbody').innerHTML = rows || `<tr><td colspan="6" class="text-center text-muted">No purchase orders</td></tr>`;
 });
</script>
