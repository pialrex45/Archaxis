<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';

requireAuth();
// Allow general contractor or admin
if (!(hasRole('general_contractor') || hasRole('admin'))) { http_response_code(403); die('Access denied. General Contractor only.'); }

$pageTitle = 'General Contractor Dashboard';
$currentPage = 'dashboard';

include_once __DIR__ . '/../../views/layouts/header.php';
?>
<div class="row">
  <div class="col-md-12">
    <h1 class="mb-4">General Contractor Dashboard</h1>
    <div class="alert alert-info">Read-only access to projects, tasks, POs, products & suppliers. You can request materials.</div>
  </div>
</div>



<div class="row g-3">
  <div class="col-md-6 col-xl-4">
    <div class="card h-100"><div class="card-body">
      <h5 class="card-title">Projects</h5>
      <p class="card-text">View projects and open an overview.</p>
      <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('api/general_contractor/projects.php') ?>" target="_blank">Recent Projects (API)</a>
        <form class="d-flex gap-2" onsubmit="openGcProjectOverview(event);">
          <input type="number" min="1" class="form-control form-control-sm" id="gc_project_id" placeholder="Project ID" required>
          <button class="btn btn-sm btn-primary" type="submit">Open</button>
        </form>
      </div>
    </div></div>
  </div>

  <div class="col-md-6 col-xl-4">
    <div class="card h-100"><div class="card-body">
      <h5 class="card-title">Tasks</h5>
      <p class="card-text">View tasks related to your scope.</p>
      <a class="btn btn-sm btn-outline-secondary" href="<?= url('api/general_contractor/tasks.php') ?>" target="_blank">All Tasks (API)</a>
    </div></div>
  </div>

  <div class="col-md-6 col-xl-4">
    <div class="card h-100"><div class="card-body">
      <h5 class="card-title">Materials</h5>
      <p class="card-text">View and request materials. Approval handled by site/pm.</p>
      <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-sm btn-primary" href="#" onclick="openGcRequestMaterialModal(); return false;">Request Material</a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('api/general_contractor/materials.php') ?>" target="_blank">All Materials (API)</a>
      </div>
    </div></div>
  </div>

  <div class="col-md-6 col-xl-4">
    <div class="card h-100"><div class="card-body">
      <h5 class="card-title">Purchase Orders</h5>
      <p class="card-text">View POs status linked to material requests.</p>
      <a class="btn btn-sm btn-outline-secondary" href="<?= url('api/general_contractor/purchase_orders.php') ?>" target="_blank">All POs (API)</a>
    </div></div>
  </div>

  <div class="col-md-6 col-xl-4">
    <div class="card h-100"><div class="card-body">
      <h5 class="card-title">Products</h5>
      <p class="card-text">View product specifications.</p>
      <a class="btn btn-sm btn-outline-secondary" href="<?= url('api/general_contractor/products.php') ?>" target="_blank">View Products</a>
    </div></div>
  </div>

  <div class="col-md-6 col-xl-4">
    <div class="card h-100"><div class="card-body">
      <h5 class="card-title">Suppliers</h5>
      <p class="card-text">View supplier profiles and ratings.</p>
      <a class="btn btn-sm btn-outline-secondary" href="<?= url('api/general_contractor/suppliers.php') ?>" target="_blank">View Suppliers</a>
    </div></div>
  </div>
</div>

<!-- Request Material Modal -->
<div class="modal fade" id="gcRequestMaterialModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Request Material</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
        <form id="gcRequestMaterialForm">
          <div class="mb-2"><label class="form-label">Project ID</label><input name="project_id" type="number" min="1" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">Material Name</label><input name="material_name" type="text" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">Quantity</label><input name="quantity" type="number" min="1" step="0.01" class="form-control form-control-sm" required></div>
        </form>
        <div id="gcRequestMaterialMsg" class="small"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="submitGcRequestMaterial();">Request</button>
      </div>
    </div>
  </div>
</div>

<script>
  function openGcProjectOverview(e){ e.preventDefault(); var id = document.getElementById('gc_project_id').value; if(!id) return; window.open('<?= url('api/general_contractor/projects.php') ?>?project_id='+encodeURIComponent(id), '_blank'); }
  function openGcRequestMaterialModal(){ var m = new bootstrap.Modal(document.getElementById('gcRequestMaterialModal')); m.show(); }
  async function submitGcRequestMaterial(){
    const form = document.getElementById('gcRequestMaterialForm');
    const data = Object.fromEntries(new FormData(form).entries());
    const res = await fetch('<?= url('api/general_contractor/materials.php?action=request') ?>', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const j = await res.json();
    document.getElementById('gcRequestMaterialMsg').textContent = j.success ? 'Requested' : (j.message||'Failed');
    if (j.success) setTimeout(()=>bootstrap.Modal.getInstance(document.getElementById('gcRequestMaterialModal')).hide(), 600);
  }
</script>
<?php include_once __DIR__ . '/../components/messaging_nav.php'; ?>
<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>
