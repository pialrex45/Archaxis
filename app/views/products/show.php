<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth();
if (!hasRole('admin')) { http_response_code(403); die('Admins only'); }
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0; if($id<=0){ http_response_code(400); die('Invalid ID'); }

$pageTitle = 'Product Details';
$currentPage = 'products';
include_once __DIR__ . '/../layouts/header.php';
include_once __DIR__ . '/../layouts/sidebar.php';
?>
<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
  <h1 class="h2 pt-3 pb-2 mb-3 border-bottom">Product Details</h1>
  <div id="details"></div>
  <div class="mt-3">
    <a class="btn btn-secondary" href="<?php echo url('/products'); ?>">Back</a>
    <a class="btn btn-primary" href="<?php echo url('/products/edit?id='); ?><?php echo $id; ?>">Edit</a>
  </div>
</div>
<script>
fetch('<?php echo url('/api/products/show?id='); ?><?php echo $id; ?>')
 .then(r=>r.json()).then(res=>{
   if(!res.success){ document.getElementById('details').innerHTML = '<div class="alert alert-danger">Not found</div>'; return; }
   const p=res.data;
   document.getElementById('details').innerHTML = `
     <div class="card">
       <div class="card-body">
         <h5 class="card-title">${p.name||''}</h5>
         <p class="card-text"><strong>Supplier:</strong> ${p.supplier_name||''}<br>
         <strong>Description:</strong> ${p.description||''}<br>
         <strong>Unit Price:</strong> ${p.unit_price} / ${p.unit}<br>
         <strong>Stock:</strong> ${p.stock}<br>
         <strong>Status:</strong> ${p.status}</p>
       </div>
     </div>`;
 });
</script>
<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
