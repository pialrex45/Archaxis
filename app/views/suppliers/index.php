<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth();
if (!hasRole('admin')) { http_response_code(403); die('Admins only'); }

$pageTitle = 'Suppliers';
$currentPage = 'suppliers';

include_once __DIR__ . '/../layouts/header.php';
include_once __DIR__ . '/../layouts/sidebar.php';
?>
<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Suppliers</h1>
    <a href="<?php echo url('/suppliers/create'); ?>" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Create</a>
  </div>

  <div id="alertBox"></div>

  <div class="table-responsive">
    <table class="table table-striped table-sm" id="suppliersTable">
      <thead>
        <tr>
          <th>Name</th><th>Email</th><th>Phone</th><th>Rating</th><th>Actions</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>
<script>
fetch('<?php echo url('/api/suppliers/index'); ?>', {headers: {'X-Requested-With':'XMLHttpRequest'}})
  .then(r=>r.json()).then(data=>{
    const rows = (data.data||[]).map(s=>`
      <tr>
        <td>${s.name||''}</td>
        <td>${s.email||''}</td>
        <td>${s.phone||''}</td>
        <td>${s.rating??''}</td>
        <td>
          <a class="btn btn-sm btn-outline-primary" href="<?php echo url('/suppliers/show?id='); ?>${s.id}">View</a>
          <a class="btn btn-sm btn-outline-secondary" href="<?php echo url('/suppliers/edit?id='); ?>${s.id}">Edit</a>
          <button class="btn btn-sm btn-outline-danger btn-delete-supplier" data-supplier-id="${s.id}" data-supplier-name="${s.name||''}">Delete</button>
        </td>
      </tr>`).join('');
    document.querySelector('#suppliersTable tbody').innerHTML = rows || '<tr><td colspan=5 class="text-center">No suppliers</td></tr>';
  });

const SUP_CSRF = '<?php echo generateCSRFToken(); ?>';
let deleteSupplier = { id: null, row: null };

document.addEventListener('click', (e) => {
  const btn = e.target.closest('.btn-delete-supplier');
  if (!btn) return;
  deleteSupplier.id = parseInt(btn.getAttribute('data-supplier-id'), 10);
  deleteSupplier.row = btn.closest('tr');
  const name = btn.getAttribute('data-supplier-name') || '';
  const txt = document.getElementById('deleteSupplierText');
  if (txt) txt.textContent = `Are you sure you want to delete supplier "${name}"? This action cannot be undone.`;
  const modal = new bootstrap.Modal(document.getElementById('deleteSupplierModal'));
  modal.show();
});

document.getElementById('confirmDeleteSupplierBtn')?.addEventListener('click', () => {
  if (!deleteSupplier.id) return;
  const fd = new FormData();
  fd.append('csrf_token', SUP_CSRF);
  fetch(`<?php echo url('/api/suppliers/delete?id='); ?>${deleteSupplier.id}`, {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(r => r.json())
  .then(data => {
    const modalEl = document.getElementById('deleteSupplierModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    modal?.hide();
    if (data.success) {
      if (deleteSupplier.row) deleteSupplier.row.remove();
      showSupplierAlert('success', data.message || 'Supplier deleted successfully.');
    } else {
      showSupplierAlert('danger', data.message || 'Failed to delete supplier.');
    }
    deleteSupplier = { id: null, row: null };
  })
  .catch(() => {
    const modalEl = document.getElementById('deleteSupplierModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    modal?.hide();
    showSupplierAlert('danger', 'An error occurred while deleting the supplier.');
    deleteSupplier = { id: null, row: null };
  });
});

function showSupplierAlert(type, message) {
  const alert = document.createElement('div');
  alert.className = `alert alert-${type} alert-dismissible fade show`;
  alert.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
  const container = document.querySelector('.col-md-9') || document.querySelector('.col-lg-10') || document.body;
  const anchor = document.querySelector('.border-bottom') || container.firstChild;
  container.insertBefore(alert, anchor);
  setTimeout(() => { try { new bootstrap.Alert(alert).close(); } catch(e){} }, 3000);
}
</script>

<!-- Delete Supplier Modal -->
<div class="modal fade" id="deleteSupplierModal" tabindex="-1" aria-labelledby="deleteSupplierModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteSupplierModalLabel">Delete Supplier</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="deleteSupplierText">Are you sure you want to delete this supplier? This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteSupplierBtn">Delete</button>
      </div>
    </div>
  </div>
</div>
</script>
<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
