<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';

requireAuth();
// Allow admin and logistic_officer (align with router)
if (!function_exists('hasAnyRole')) {
  $allowed = hasRole('admin') || hasRole('logistic_officer');
} else {
  $allowed = hasAnyRole(['admin','logistic_officer']);
}
if (!$allowed) { http_response_code(403); die('Access denied. Logistic Officers only.'); }

$pageTitle = 'Logistic Officer Dashboard';
$currentPage = 'dashboard';

include_once __DIR__ . '/../layouts/header.php';
?>
<div class="row">
  <div class="col-md-12">
    <h1 class="mb-4">Logistic Officer Dashboard</h1>
    <div class="alert alert-info">Track deliveries, update warehouse stock, coordinate POs, and report incidents.</div>
  </div>
</div>

<div class="row g-3">
  <!-- Material Deliveries -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Material Delivery</h5>
        <p class="card-text">Log deliveries and link to POs and projects.</p>
        <a class="btn btn-sm btn-primary" href="#" onclick="openLogDeliveryModal(); return false;">Log Delivery</a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('api/logistic_officer/deliveries.php') ?>" target="_blank">View Deliveries (API)</a>
      </div>
    </div>
  </div>

  <!-- PO Coordination -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">PO Coordination</h5>
        <p class="card-text">Update PO delivery status and upload GRNs.</p>
        <a class="btn btn-sm btn-outline-primary" href="#" onclick="openUpdatePOStatusModal(); return false;">Update PO Status</a>
        <a class="btn btn-sm btn-primary" href="#" onclick="openUploadGRNModal(); return false;">Upload GRN</a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('api/logistic_officer/purchase_orders.php') ?>" target="_blank">POs (API)</a>
      </div>
    </div>
  </div>

  <!-- Warehouse -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Warehouse</h5>
        <p class="card-text">Update stock and log transfers between zones.</p>
        <a class="btn btn-sm btn-outline-primary" href="#" onclick="openUpdateStockModal(); return false;">Update Stock</a>
        <a class="btn btn-sm btn-outline-primary" href="#" onclick="openLogTransferModal(); return false;">Log Transfer</a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('api/logistic_officer/warehouse.php') ?>" target="_blank">Inventory (API)</a>
      </div>
    </div>
  </div>

  <!-- Incidents -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Delivery Incidents</h5>
        <p class="card-text">Report delivery-related incidents.</p>
        <a class="btn btn-sm btn-outline-primary" href="#" onclick="openReportIncidentModal(); return false;">Report Incident</a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('api/logistic_officer/incidents.php') ?>" target="_blank">Incidents (API)</a>
      </div>
    </div>
  </div>

  <!-- Read-only Catalogs -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Products & Suppliers</h5>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('api/site_engineer/products.php') ?>" target="_blank">Products</a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('api/site_engineer/suppliers.php') ?>" target="_blank">Suppliers</a>
      </div>
    </div>
  </div>

  <!-- Messaging -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Messaging</h5>
        <p class="card-text">Communicate with Procurement, Site Manager, Warehouse.</p>
        <a class="btn btn-sm btn-outline-primary" href="<?= url('/messages') ?>">Open Messages</a>
      </div>
    </div>
  </div>
</div>

<!-- Widgets Row: Recent Deliveries & Low-stock Alerts -->
<div class="row g-3 mt-1">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="card-title mb-0">Recent Deliveries</h5>
          <button class="btn btn-sm btn-outline-secondary" onclick="loadRecentDeliveries();">Refresh</button>
        </div>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead><tr><th>ID</th><th>PO</th><th>Project</th><th>Supplier</th><th>Date</th><th>Status</th></tr></thead>
            <tbody id="recentDeliveriesBody"><tr><td colspan="6" class="text-muted">Loading...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="card-title mb-0">Low-stock Alerts</h5>
          <div>
            <input id="lowStockThreshold" type="number" class="form-control form-control-sm d-inline-block" style="width:100px" value="10" title="Threshold">
            <button class="btn btn-sm btn-outline-secondary" onclick="loadLowStock();">Refresh</button>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead><tr><th>Project</th><th>Material</th><th>Zone</th><th>Qty</th><th>Updated</th></tr></thead>
            <tbody id="lowStockBody"><tr><td colspan="5" class="text-muted">Loading...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Lightweight modals (handlers will call APIs) -->
<div class="modal fade" id="logDeliveryModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Log Delivery</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="logDeliveryForm">
          <div class="mb-2"><label class="form-label">PO ID</label><input name="po_id" type="number" min="1" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">Project ID</label><input name="project_id" type="number" min="1" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">Supplier ID</label><input name="supplier_id" type="number" min="1" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">Delivery Date</label><input name="delivery_date" type="date" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">Status</label>
            <select name="status" class="form-select form-select-sm">
              <option value="received">Received</option>
              <option value="partial">Partial</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
          <div class="mb-2"><label class="form-label">Paid</label>
            <select name="paid" class="form-select form-select-sm">
              <option value="0" selected>No</option>
              <option value="1">Yes</option>
            </select>
          </div>
        </form>
        <div id="logDeliveryMsg" class="small"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="submitLogDelivery();">Save</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="updatePOStatusModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Update PO Status</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="updatePOStatusForm">
          <div class="mb-2"><label class="form-label">PO ID</label><input name="po_id" type="number" min="1" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">Status</label>
            <select name="status" class="form-select form-select-sm">
              <option value="ordered">Ordered</option>
              <option value="delivered">Delivered</option>
            </select>
          </div>
          <div class="mb-2"><label class="form-label">GRN File URL (optional)</label><input name="grn_file" type="text" class="form-control form-control-sm" placeholder="/uploads/grn/grn-po1-20250101-120000-abcd1234.pdf"></div>
        </form>
        <div id="updatePOStatusMsg" class="small"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="submitUpdatePOStatus();">Update</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="updateStockModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Update Stock</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="updateStockForm">
          <div class="mb-2"><label class="form-label">Material ID</label><input name="material_id" type="number" min="1" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">Quantity (+/-)</label><input name="quantity" type="number" class="form-control form-control-sm" required></div>
        </form>
        <div id="updateStockMsg" class="small"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="submitUpdateStock();">Apply</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="logTransferModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Log Warehouse Transfer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="logTransferForm">
          <div class="mb-2"><label class="form-label">From Zone</label><input name="from_zone" type="text" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">To Zone</label><input name="to_zone" type="text" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">Material ID</label><input name="material_id" type="number" min="1" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">Quantity</label><input name="quantity" type="number" min="1" class="form-control form-control-sm" required></div>
        </form>
        <div id="logTransferMsg" class="small"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="submitLogTransfer();">Log</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="reportIncidentModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Report Delivery Incident</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="reportIncidentForm">
          <div class="mb-2"><label class="form-label">Type</label><input name="type" type="text" class="form-control form-control-sm" placeholder="delay/damage/shortage" required></div>
          <div class="mb-2"><label class="form-label">Description</label><textarea name="description" class="form-control form-control-sm" rows="2" required></textarea></div>
          <div class="mb-2"><label class="form-label">Related PO ID (optional)</label><input name="related_po_id" type="number" min="1" class="form-control form-control-sm"></div>
        </form>
        <div id="reportIncidentMsg" class="small"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="submitReportIncident();">Submit</button>
      </div>
    </div>
  </div>
</div>

<!-- Upload GRN Modal (multipart) -->
<div class="modal fade" id="uploadGRNModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Upload GRN</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="uploadGRNForm" enctype="multipart/form-data">
          <div class="mb-2"><label class="form-label">PO ID</label><input name="po_id" type="number" min="1" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">GRN File</label><input name="grn_file" type="file" accept=".pdf,.jpg,.jpeg,.png" class="form-control form-control-sm" required></div>
        </form>
        <div id="uploadGRNMsg" class="small"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="submitUploadGRN();">Upload</button>
      </div>
    </div>
  </div>
</div>

<script>
  function openLogDeliveryModal(){ new bootstrap.Modal(document.getElementById('logDeliveryModal')).show(); }
  function openUpdatePOStatusModal(){ new bootstrap.Modal(document.getElementById('updatePOStatusModal')).show(); }
  function openUpdateStockModal(){ new bootstrap.Modal(document.getElementById('updateStockModal')).show(); }
  function openLogTransferModal(){ new bootstrap.Modal(document.getElementById('logTransferModal')).show(); }
  function openReportIncidentModal(){ new bootstrap.Modal(document.getElementById('reportIncidentModal')).show(); }
  function openUploadGRNModal(){ new bootstrap.Modal(document.getElementById('uploadGRNModal')).show(); }

  async function submitLogDelivery(){
    const data = Object.fromEntries(new FormData(document.getElementById('logDeliveryForm')).entries());
    const res = await fetch('<?= url('api/logistic_officer/deliveries.php?action=log_delivery') ?>', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const j = await res.json();
    document.getElementById('logDeliveryMsg').textContent = j.success ? 'Saved' : (j.message||'Failed');
  }
  async function submitUpdatePOStatus(){
    const data = Object.fromEntries(new FormData(document.getElementById('updatePOStatusForm')).entries());
    const res = await fetch('<?= url('api/logistic_officer/purchase_orders.php?action=update_status') ?>', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const j = await res.json();
    document.getElementById('updatePOStatusMsg').textContent = j.success ? 'Updated' : (j.message||'Failed');
  }
  async function submitUpdateStock(){
    const data = Object.fromEntries(new FormData(document.getElementById('updateStockForm')).entries());
    const res = await fetch('<?= url('api/logistic_officer/warehouse.php?action=update_stock') ?>', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const j = await res.json();
    document.getElementById('updateStockMsg').textContent = j.success ? 'Applied' : (j.message||'Failed');
  }
  async function submitLogTransfer(){
    const data = Object.fromEntries(new FormData(document.getElementById('logTransferForm')).entries());
    const res = await fetch('<?= url('api/logistic_officer/warehouse.php?action=log_transfer') ?>', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const j = await res.json();
    document.getElementById('logTransferMsg').textContent = j.success ? 'Logged' : (j.message||'Failed');
  }
  async function submitReportIncident(){
    const data = Object.fromEntries(new FormData(document.getElementById('reportIncidentForm')).entries());
    const res = await fetch('<?= url('api/logistic_officer/incidents.php?action=report') ?>', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const j = await res.json();
    document.getElementById('reportIncidentMsg').textContent = j.success ? 'Submitted' : (j.message||'Failed');
  }
  async function submitUploadGRN(){
    const form = document.getElementById('uploadGRNForm');
    const fd = new FormData(form);
    const res = await fetch('<?= url('api/logistic_officer/purchase_orders.php?action=upload_grn') ?>', { method:'POST', body: fd });
    const j = await res.json();
    document.getElementById('uploadGRNMsg').textContent = j.success ? ('Uploaded: ' + (j.file||'')) : (j.message||'Failed');
  }

  async function loadRecentDeliveries(){
    const tbody = document.getElementById('recentDeliveriesBody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-muted">Loading...</td></tr>';
    try {
      const res = await fetch('<?= url('api/logistic_officer/deliveries.php') ?>?limit=10');
      const j = await res.json();
      if (!j.success) throw new Error(j.message||'Failed');
      const rows = (j.data||[]).map(r => `
        <tr>
          <td>${r.id||''}</td>
          <td>${r.purchase_order_id||''}</td>
          <td>${r.project_id||''}</td>
          <td>${r.supplier_id||''}</td>
          <td>${r.delivery_date||''}</td>
          <td>${r.status||''}</td>
        </tr>`).join('');
      tbody.innerHTML = rows || '<tr><td colspan="6" class="text-muted">No data</td></tr>';
    } catch(e){ tbody.innerHTML = `<tr><td colspan="6" class="text-danger">${e.message}</td></tr>`; }
  }

  async function loadLowStock(){
    const tbody = document.getElementById('lowStockBody');
    tbody.innerHTML = '<tr><td colspan="5" class="text-muted">Loading...</td></tr>';
    const threshold = parseInt(document.getElementById('lowStockThreshold').value||'10',10);
    try {
      const res = await fetch('<?= url('api/logistic_officer/warehouse.php') ?>?limit=200');
      const j = await res.json();
      if (!j.success) throw new Error(j.message||'Failed');
      const rows = (j.data||[]).filter(r => (parseInt(r.quantity||0,10) < threshold)).map(r => `
        <tr>
          <td>${r.project_id||''}</td>
          <td>${r.material_id||''}</td>
          <td>${r.zone||''}</td>
          <td class="text-danger fw-bold">${r.quantity||0}</td>
          <td>${r.updated_at||''}</td>
        </tr>`).join('');
      tbody.innerHTML = rows || '<tr><td colspan="5" class="text-muted">All stocks above threshold</td></tr>';
    } catch(e){ tbody.innerHTML = `<tr><td colspan="5" class="text-danger">${e.message}</td></tr>`; }
  }

  // Initial load for widgets
  loadRecentDeliveries();
  loadLowStock();
</script>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
