<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth();
if (!hasRole('admin')) { http_response_code(403); die('Admins only'); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id<=0) { http_response_code(400); die('Invalid ID'); }

$pageTitle = 'Edit Supplier';
$currentPage = 'suppliers';
include_once __DIR__ . '/../layouts/header.php';
include_once __DIR__ . '/../layouts/sidebar.php';
?>
<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
  <h1 class="h2 pt-3 pb-2 mb-3 border-bottom">Edit Supplier</h1>
  <form id="supplierForm" method="POST" action="<?php echo url('/api/suppliers/update?id=' . $id); ?>">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <div class="mb-3"><label class="form-label">Name *</label><input id="name" name="name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Email</label><input id="email" name="email" type="email" class="form-control"></div>
    <div class="mb-3"><label class="form-label">Phone</label><input id="phone" name="phone" class="form-control"></div>
    <div class="mb-3"><label class="form-label">Address</label><textarea id="address" name="address" class="form-control" rows="3"></textarea></div>
    <div class="mb-3"><label class="form-label">Rating</label><input id="rating" name="rating" type="number" step="0.1" min="0" max="5" class="form-control"></div>
    <button class="btn btn-primary" type="submit">Update</button>
    <a href="<?php echo url('/suppliers'); ?>" class="btn btn-secondary">Cancel</a>
  </form>
</div>
<script>
fetch('<?php echo url('/api/suppliers/show?id='); ?><?php echo $id; ?>')
 .then(r=>r.json()).then(res=>{
   if(res.success){
     const s=res.data; name.value=s.name||''; email.value=s.email||''; phone.value=s.phone||''; address.value=s.address||''; rating.value=s.rating??'';
   }
 });

document.getElementById('supplierForm').addEventListener('submit', function(e){
  e.preventDefault();
  const fd = new FormData(this);
  fetch(this.action,{method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'}})
   .then(r=>r.json()).then(res=>{
     if(res.success){ window.location.href = '<?php echo url('/suppliers'); ?>'; }
     else alert(res.message||'Failed');
   });
});
</script>
<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
