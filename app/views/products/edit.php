<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth();
if (!hasRole('admin')) { http_response_code(403); die('Admins only'); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id<=0) { http_response_code(400); die('Invalid ID'); }

$pageTitle = 'Edit Product';
$currentPage = 'products';
include_once __DIR__ . '/../layouts/header.php';
include_once __DIR__ . '/../layouts/sidebar.php';
?>
<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
  <h1 class="h2 pt-3 pb-2 mb-3 border-bottom">Edit Product</h1>
  <form id="productForm" method="POST" action="<?php echo url('/api/products/update?id=' . $id); ?>">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <div class="mb-3"><label class="form-label">Name *</label><input id="name" name="name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Supplier *</label>
      <select name="supplier_id" id="supplier_id" class="form-select" required></select>
    </div>
    <div class="mb-3"><label class="form-label">Description</label><textarea id="description" name="description" class="form-control" rows="3"></textarea></div>
    <div class="mb-3"><label class="form-label">Unit Price *</label><input id="unit_price" name="unit_price" type="number" step="0.01" min="0" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Unit *</label><input id="unit" name="unit" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Stock</label><input id="stock" name="stock" type="number" min="0" class="form-control"></div>
    <div class="mb-3"><label class="form-label">Status</label>
      <select id="status" name="status" class="form-select">
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
    </div>
    <button class="btn btn-primary" type="submit">Update</button>
    <a href="<?php echo url('/products'); ?>" class="btn btn-secondary">Cancel</a>
  </form>
</div>
<script>
// populate suppliers
fetch('<?php echo url('/api/suppliers/index'); ?>')
 .then(r=>r.json()).then(data=>{
   const sel = document.getElementById('supplier_id');
   (data.data||[]).forEach(s=>{ const o=document.createElement('option'); o.value=s.id; o.textContent=s.name; sel.appendChild(o); });
 });

// load product
fetch('<?php echo url('/api/products/show?id='); ?><?php echo $id; ?>')
 .then(r=>r.json()).then(res=>{
   if(res.success){
     const p=res.data; name.value=p.name||''; description.value=p.description||''; unit_price.value=p.unit_price; unit.value=p.unit||''; stock.value=p.stock||0; status.value=p.status||'active';
     document.getElementById('supplier_id').value = p.supplier_id;
   }
 });

document.getElementById('productForm').addEventListener('submit', function(e){
  e.preventDefault();
  const fd = new FormData(this);
  fetch(this.action,{method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'}})
   .then(r=>r.json()).then(res=>{
     if(res.success){ window.location.href = '<?php echo url('/products'); ?>'; }
     else alert(res.message||'Failed');
   });
});
</script>
<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
