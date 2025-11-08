<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth();
if (!hasRole('admin')) { http_response_code(403); die('Admins only'); }

$pageTitle = 'Create Supplier';
$currentPage = 'suppliers';
include_once __DIR__ . '/../layouts/header.php';
include_once __DIR__ . '/../layouts/sidebar.php';
?>
<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
  <h1 class="h2 pt-3 pb-2 mb-3 border-bottom">Create Supplier</h1>
  <div id="supplierAlertAnchor"></div>
  <form id="supplierForm" method="POST" action="<?php echo url('/api/suppliers/create'); ?>">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <div class="mb-3"><label class="form-label">Name *</label><input name="name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control"></div>
    <div class="mb-3"><label class="form-label">Phone</label><input name="phone" class="form-control"></div>
    <div class="mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="3"></textarea></div>
    <div class="mb-3"><label class="form-label">Rating</label><input name="rating" type="number" step="0.1" min="0" max="5" class="form-control"></div>
    <button class="btn btn-primary" type="submit">Create</button>
    <a href="<?php echo url('/suppliers'); ?>" class="btn btn-secondary">Cancel</a>
  </form>
</div>
<script>
document.getElementById('supplierForm').addEventListener('submit', function(e){
  e.preventDefault();
  const fd = new FormData(this);
  const submitBtn = this.querySelector('button[type="submit"]');
  submitBtn.disabled = true;
  fetch(this.action,{method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'}})
   .then(async r=>{
      let payload = null;
      try { payload = await r.json(); } catch(e) { payload = {success:false, message:'Invalid server response'}; }
      if (!r.ok) {
        const msg = payload.message || (r.status===403 ? 'You do not have permission to create suppliers.' : 'Request failed.');
        return { success:false, message: msg };
      }
      return payload;
   })
   .then(res=>{
     if(res.success){
       showFormAlert('success', res.message || 'Supplier created successfully.', 'supplierAlertAnchor');
       setTimeout(()=>{ window.location.href = '<?php echo url('/suppliers'); ?>'; }, 800);
     } else {
       showFormAlert('danger', res.message || 'Failed to create supplier.', 'supplierAlertAnchor');
     }
   })
   .catch(()=>{
     showFormAlert('danger', 'Network error creating supplier.', 'supplierAlertAnchor');
   })
   .finally(()=>{ submitBtn.disabled = false; });
});

function showFormAlert(type, message, anchorId){
  const anchor = document.getElementById(anchorId) || document.body;
  const el = document.createElement('div');
  el.className = `alert alert-${type} alert-dismissible fade show`;
  el.innerHTML = `${message}<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>`;
  anchor.innerHTML = '';
  anchor.appendChild(el);
  setTimeout(()=>{ try { new bootstrap.Alert(el).close(); } catch(e){} }, 4000);
}
</script>
<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
