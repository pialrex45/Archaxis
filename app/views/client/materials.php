<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth();
requireRole('client');

$pageTitle = 'Client Materials';
$currentPage = 'client_materials';

include_once __DIR__ . '/../layouts/header.php';
include_once __DIR__ . '/../layouts/sidebar.php';
?>
<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
  <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Materials</h1>
  </div>

  <div class="table-responsive">
    <table class="table table-striped align-middle" id="clientMaterialsTable">
      <thead>
        <tr>
          <th>Project</th>
          <th>Material</th>
          <th>Supplier</th>
          <th>Quantity</th>
          <th>Delivery Status</th>
          <th>Cost</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>
<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
<script>
const apiClientMaterials = '<?php echo url('/api/client/materials'); ?>';
const statusBadge = (s)=>{
  const map={pending:'bg-warning', shipped:'bg-info', delivered:'bg-success', cancelled:'bg-secondary'};
  const cls = map[(s||'').toString().toLowerCase()]||'bg-light text-dark';
  return `<span class="badge ${cls}">${s||''}</span>`;
};
fetch(apiClientMaterials, {headers:{'X-Requested-With':'XMLHttpRequest'}})
 .then(r=>r.json()).then(json=>{
   const rows = (json.data||[]).map(m=>{
     return `<tr>
       <td>${m.project_name||m.project_id||''}</td>
       <td>${m.name||m.material_name||''}</td>
       <td>${m.supplier_name||m.supplier||''}</td>
       <td>${m.quantity||m.qty||''} ${m.unit||''}</td>
       <td>${statusBadge(m.delivery_status||m.status||'')}</td>
       <td>${m.cost||m.total_cost||m.amount||''}</td>
     </tr>`;
   }).join('');
   document.querySelector('#clientMaterialsTable tbody').innerHTML = rows || `<tr><td colspan="6" class="text-center text-muted">No materials</td></tr>`;
 });
</script>
