<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth();
if (!hasRole('admin')) { http_response_code(403); die('Admins only'); }

$pageTitle = 'Create Product';
$currentPage = 'products';
include_once __DIR__ . '/../layouts/header.php';
include_once __DIR__ . '/../layouts/sidebar.php';
?>
<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
  <h1 class="h2 pt-3 pb-2 mb-3 border-bottom">Create Product</h1>
  <div id="productAlertAnchor"></div>
  <form id="productForm" method="POST" action="<?php echo url('/api/products/create'); ?>">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <div class="mb-3"><label class="form-label">Name *</label><input name="name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Supplier *</label>
      <select name="supplier_id" id="supplier_id" class="form-select" required></select>
    </div>
    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
    <div class="mb-3"><label class="form-label">Unit Price *</label><input name="unit_price" type="number" step="0.01" min="0" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Unit *</label><input name="unit" class="form-control" required placeholder="e.g., kg, piece"></div>
    <div class="mb-3"><label class="form-label">Stock</label><input name="stock" type="number" min="0" class="form-control" value="0"></div>
    <div class="mb-3"><label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="active" selected>Active</option>
        <option value="inactive">Inactive</option>
      </select>
    </div>
    <button class="btn btn-primary" type="submit">Create</button>
    <a href="<?php echo url('/products'); ?>" class="btn btn-secondary">Cancel</a>
  </form>
</div>
<script>
// Load suppliers for select
fetch('<?php echo url('/api/suppliers/index'); ?>', { headers: {'X-Requested-With':'XMLHttpRequest'} })
 .then(async r => {
   if (!r.ok) {
     const msg = r.status === 401 ? 'Please login to load suppliers.' : 'Failed to load suppliers.';
     showFormAlert('danger', msg, 'productAlertAnchor');
     return { data: [] };
   }
   return r.json().catch(() => ({ data: [] }));
 })
 .then(data=>{
   const sel = document.getElementById('supplier_id');
   (data.data||[]).forEach(s=>{ const o=document.createElement('option'); o.value=s.id; o.textContent=s.name; sel.appendChild(o); });
 });

document.getElementById('productForm').addEventListener('submit', function(e){
  e.preventDefault();
  const fd = new FormData(this);
  const submitBtn = this.querySelector('button[type="submit"]');
  submitBtn.disabled = true;
  fetch(this.action,{method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'}})
   .then(async r=>{
      let payload = null;
      try { payload = await r.json(); } catch(e) { payload = {success:false, message:'Invalid server response'}; }
      if (!r.ok) {
        const msg = payload.message || (r.status===403 ? 'You do not have permission to create products.' : 'Request failed.');
        return { success:false, message: msg };
      }
      return payload;
   })
   .then(res=>{
     if(res.success){
       showFormAlert('success', res.message || 'Product created successfully.', 'productAlertAnchor');
       setTimeout(()=>{ window.location.href = '<?php echo url('/products'); ?>'; }, 800);
     } else {
       showFormAlert('danger', res.message || 'Failed to create product.', 'productAlertAnchor');
     }
   })
   .catch(()=>{
     showFormAlert('danger', 'Network error creating product.', 'productAlertAnchor');
   })
   .finally(()=>{ submitBtn.disabled = false; });
});

function showFormAlert(type, message, anchorId){
  const anchor = document.getElementById(anchorId) || document.body;
  const el = document.createElement('div');
  el.className = `alert alert-${type} alert-dismissible fade show`;
  el.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
  anchor.innerHTML = '';
  anchor.appendChild(el);
  setTimeout(()=>{ try { new bootstrap.Alert(el).close(); } catch(e){} }, 4000);
}
</script>
<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
