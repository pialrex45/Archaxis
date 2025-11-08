<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth();

$pageTitle = 'Create Purchase Order';
$currentPage = 'purchase_orders';
?>
<?php include_once __DIR__ . '/../../views/layouts/header.php'; ?>
<?php include_once __DIR__ . '/../../views/layouts/sidebar.php'; ?>
<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
  <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Create Purchase Order</h1>
  </div>
  <form id="poForm" class="mb-4">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Project ID</label>
        <input type="number" class="form-control" id="projectId" required />
      </div>
      <div class="col-md-4">
        <label class="form-label">Supplier</label>
        <select id="supplierId" class="form-select" required></select>
      </div>
    </div>
    <hr />
    <h5>Items</h5>
    <div id="items" class="mb-2"></div>
    <button type="button" class="btn btn-sm btn-outline-secondary" id="addItemBtn"><i class="fas fa-plus"></i> Add Item</button>
    <hr />
    <button type="submit" class="btn btn-primary">Create PO</button>
  </form>
  <div id="resultMsg"></div>
</div>
<?php include_once __DIR__ . '/../../views/layouts/footer.php'; ?>
<script>
const apiSuppliers = '<?php echo url('/api/suppliers/index'); ?>';
const apiProducts  = '<?php echo url('/api/products/index'); ?>';
const apiPOCreate  = '<?php echo url('/api/purchase_orders/create'); ?>';

function itemRowTemplate(idx){
  return `<div class="row g-2 align-items-end mb-2" data-idx="${idx}">
    <div class="col-md-6">
      <label class="form-label">Product</label>
      <select class="form-select productSelect" required></select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Qty</label>
      <input type="number" min="1" class="form-control qtyInput" required />
    </div>
    <div class="col-md-3">
      <label class="form-label">Unit Price (optional)</label>
      <input type="number" step="0.01" class="form-control priceInput" />
    </div>
  </div>`;
}

async function loadSuppliers(){
  const res = await fetch(apiSuppliers); const j = await res.json();
  const sel = document.getElementById('supplierId');
  sel.innerHTML = '<option value="">Select supplier</option>' + (j.data||[]).map(s=>`<option value="${s.id}">${s.name}</option>`).join('');
}

async function loadProductsForSupplier(sid){
  const res = await fetch(`${apiProducts}?supplier_id=${encodeURIComponent(sid)}`); const j = await res.json();
  return j.data || [];
}

function refreshProductsInRows(products){
  document.querySelectorAll('.productSelect').forEach(sel=>{
    const value = sel.value;
    sel.innerHTML = '<option value="">Select product</option>' + products.map(p=>`<option value="${p.id}">${p.name} - ${p.unit_price}/${p.unit}</option>`).join('');
    if (products.find(p=>String(p.id)===value)) sel.value = value;
  });
}

let rowIdx = 0;
function addItemRow(){
  const container = document.getElementById('items');
  container.insertAdjacentHTML('beforeend', itemRowTemplate(rowIdx++));
}

document.getElementById('addItemBtn').addEventListener('click', addItemRow);

document.getElementById('supplierId').addEventListener('change', async (e)=>{
  const sid = e.target.value; if(!sid) return;
  const products = await loadProductsForSupplier(sid);
  refreshProductsInRows(products);
});

// initial
loadSuppliers();
addItemRow();

// submit
document.getElementById('poForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const project_id = parseInt(document.getElementById('projectId').value,10);
  const supplier_id = parseInt(document.getElementById('supplierId').value,10);
  const items = Array.from(document.querySelectorAll('#items [data-idx]')).map(row=>{
    const product_id = parseInt(row.querySelector('.productSelect').value,10);
    const quantity = parseInt(row.querySelector('.qtyInput').value,10);
    const up = row.querySelector('.priceInput').value;
    const unit_price = up ? parseFloat(up) : undefined;
    return unit_price? {product_id, quantity, unit_price} : {product_id, quantity};
  }).filter(i=>i.product_id>0 && i.quantity>0);

  const res = await fetch(apiPOCreate, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({project_id, supplier_id, items})});
  const j = await res.json();
  const msg = document.getElementById('resultMsg');
  if(j.success){
    msg.innerHTML = `<div class='alert alert-success'>Created PO #${j.purchase_order_id} (Total ${j.total_amount}). <a href='${'<?php echo url('/purchase-orders/show'); ?>'}?id=${j.purchase_order_id}'>View</a></div>`;
  } else {
    msg.innerHTML = `<div class='alert alert-danger'>${j.message||'Failed to create PO'}</div>`;
  }
});
</script>
