<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';

requireAuth();
// Allow admin and site_engineer (align with router)
if (!function_exists('hasAnyRole')) {
  // Fallback if helper missing
  $allowed = hasRole('admin') || hasRole('site_engineer');
} else {
  $allowed = hasAnyRole(['admin','site_engineer']);
}
if (!$allowed) { http_response_code(403); die('Access denied. Site Engineers only.'); }

$pageTitle = 'Site Engineer Dashboard';
$currentPage = 'dashboard';

include_once __DIR__ . '/../layouts/header.php';
?>
<div class="row">
  <div class="col-md-12">
    <h1 class="mb-4">Site Engineer Dashboard</h1>
    <div class="alert alert-info">Quick tools for inspections, drawings, incidents, and supplier/product lookups.</div>
  </div>
</div>

<div class="row g-3">
  <!-- Inspections -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Inspections</h5>
        <p class="card-text">Create or update task inspections; list inspections by task.</p>
        <form class="row gy-2 gx-2 align-items-center" onsubmit="openInspections(event);">
          <div class="col">
            <input type="number" min="1" class="form-control form-control-sm" id="se_task_id" placeholder="Task ID" required>
          </div>
          <div class="col-auto">
            <button class="btn btn-sm btn-outline-secondary" type="submit">List</button>
          </div>
        </form>
        <hr class="my-3"/>
        <form id="createInspectionForm">
          <div class="mb-2"><label class="form-label">Task ID</label><input name="task_id" type="number" min="1" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">Zone</label><input name="zone_id" type="text" class="form-control form-control-sm" placeholder="e.g. A1"></div>
          <div class="mb-2"><label class="form-label">Status</label>
            <select name="status" class="form-select form-select-sm">
              <option value="in_review">In Review</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
          <div class="mb-2"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control form-control-sm" rows="2"></textarea></div>
        </form>
        <div class="d-flex gap-2 flex-wrap">
          <button class="btn btn-sm btn-primary" onclick="submitCreateInspection();">Create Inspection</button>
        </div>
        <div id="createInspectionMsg" class="small mt-2"></div>
      </div>
    </div>
  </div>

  <!-- Drawings -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Drawings</h5>
        <p class="card-text">Browse available drawings (read-only).</p>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('api/site_engineer/drawings.php') ?>" target="_blank">View Drawings (API)</a>
      </div>
    </div>
  </div>

  <!-- Incidents -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Incidents</h5>
        <p class="card-text">Report or view incidents (API-based).</p>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('api/site_engineer/incidents.php') ?>" target="_blank">Open Incidents (API)</a>
      </div>
    </div>
  </div>

  <!-- Products -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Products</h5>
        <p class="card-text">Browse available products (read-only).</p>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('api/site_engineer/products.php') ?>" target="_blank">View Products</a>
      </div>
    </div>
  </div>

  <!-- Suppliers -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Suppliers</h5>
        <p class="card-text">Browse suppliers (read-only).</p>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('api/site_engineer/suppliers.php') ?>" target="_blank">View Suppliers</a>
      </div>
    </div>
  </div>

  <!-- Purchase Orders -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Purchase Orders</h5>
        <p class="card-text">Read PO data relevant to site operations (read-only).</p>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('api/site_engineer/purchase_orders.php') ?>" target="_blank">View POs</a>
      </div>
    </div>
  </div>

  <!-- Materials (read-only link) -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Materials</h5>
        <p class="card-text">View project materials (read-only).</p>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('api/site_engineer/materials.php') ?>" target="_blank">View Materials</a>
      </div>
    </div>
  </div>

  <!-- Messages (API view) -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Messages</h5>
        <p class="card-text">Open messages feed (API).</p>
        <a class="btn btn-sm btn-outline-primary" href="<?= url('api/site_engineer/messages.php') ?>" target="_blank">Open Messages</a>
      </div>
    </div>
  </div>
</div>

<script>
  function openInspections(e){
    e.preventDefault();
    var id = document.getElementById('se_task_id').value;
    if(!id) return;
    window.open('<?= url('api/site_engineer/inspections.php') ?>?task_id='+encodeURIComponent(id), '_blank');
  }
  async function submitCreateInspection(){
    const form = document.getElementById('createInspectionForm');
    const data = Object.fromEntries(new FormData(form).entries());
    const res = await fetch('<?= url('api/site_engineer/inspections.php?action=create_inspection') ?>', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const j = await res.json();
    document.getElementById('createInspectionMsg').textContent = j.success ? 'Inspection created' : (j.message||'Failed');
  }
</script>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
