<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth();
if (!hasRole('admin')) { http_response_code(403); die('Admins only'); }

$pageTitle = 'Products';
$currentPage = 'products';

include_once __DIR__ . '/../layouts/header.php';
include_once __DIR__ . '/../layouts/sidebar.php';
?>
<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Products</h1>
    <a href="<?php echo url('/products/create'); ?>" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Create</a>
  </div>

  <div class="table-responsive">
    <table class="table table-striped table-sm" id="productsTable">
      <thead>
        <tr>
          <th>Name</th><th>Supplier</th><th>Price</th><th>Unit</th><th>Stock</th><th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>
<script>
const apiIndex = '<?php echo url('/api/products/index'); ?>';
fetch(apiIndex, {headers:{'X-Requested-With':'XMLHttpRequest'}})
  .then(r=>r.json()).then(data=>{
    const rows = (data.data||[]).map(p=>`
      <tr>
        <td>${p.name||''}</td>
        <td>${p.supplier_name||''}</td>
        <td>${p.unit_price}</td>
        <td>${p.unit}</td>
        <td>${p.stock}</td>
        <td>${p.status}</td>
        <td>
          <a class="btn btn-sm btn-outline-primary" href="<?php echo url('/products/show?id='); ?>${p.id}">View</a>
          <a class="btn btn-sm btn-outline-secondary" href="<?php echo url('/products/edit?id='); ?>${p.id}">Edit</a>
          <button class="btn btn-sm btn-outline-danger btn-delete-product" data-product-id="${p.id}" data-product-name="${p.name||''}">Delete</button>
        </td>
      </tr>`).join('');
    document.querySelector('#productsTable tbody').innerHTML = rows || '<tr><td colspan=7 class="text-center">No products</td></tr>';
  });
// Delete modal and AJAX
const CSRF_TOKEN = '<?php echo generateCSRFToken(); ?>';
let deleteProduct = { id: null, row: null };

document.addEventListener('click', (e) => {
  const btn = e.target.closest('.btn-delete-product');
  if (!btn) return;
  deleteProduct.id = parseInt(btn.getAttribute('data-product-id'), 10);
  deleteProduct.row = btn.closest('tr');
  const name = btn.getAttribute('data-product-name') || '';
  const txt = document.getElementById('deleteProductText');
  if (txt) txt.textContent = `Are you sure you want to delete product "${name}"? This action cannot be undone.`;
  const modal = new bootstrap.Modal(document.getElementById('deleteProductModal'));
  modal.show();
});

document.getElementById('confirmDeleteProductBtn')?.addEventListener('click', () => {
  if (!deleteProduct.id) return;
  const fd = new FormData();
  fd.append('csrf_token', CSRF_TOKEN);
  fetch(`<?php echo url('/api/products/delete?id='); ?>${deleteProduct.id}`, {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(r => r.json())
  .then(data => {
    const modalEl = document.getElementById('deleteProductModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    modal?.hide();
    if (data.success) {
      if (deleteProduct.row) deleteProduct.row.remove();
      showProductAlert('success', data.message || 'Product deleted successfully.');
    } else {
      showProductAlert('danger', data.message || 'Failed to delete product.');
    }
    deleteProduct = { id: null, row: null };
  })
  .catch(() => {
    const modalEl = document.getElementById('deleteProductModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    modal?.hide();
    showProductAlert('danger', 'An error occurred while deleting the product.');
    deleteProduct = { id: null, row: null };
  });
});

function showProductAlert(type, message) {
  const alert = document.createElement('div');
  alert.className = `alert alert-${type} alert-dismissible fade show`;
  alert.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
  const container = document.querySelector('.col-md-9') || document.querySelector('.col-lg-10') || document.body;
  const anchor = document.querySelector('.border-bottom') || container.firstChild;
  container.insertBefore(alert, anchor);
  setTimeout(() => { try { new bootstrap.Alert(alert).close(); } catch(e){} }, 3000);
}
</script>

<!-- Delete Product Modal -->
<div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteProductModalLabel">Delete Product</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="deleteProductText">Are you sure you want to delete this product? This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteProductBtn">Delete</button>
      </div>
    </div>
  </div>
</div>
</script>
<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
