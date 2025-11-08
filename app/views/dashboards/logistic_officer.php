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
        <h5 class="card-title d-flex align-items-center justify-content-between">
          <span>Deliveries</span>
          <span id="statusDeliveries" class="badge rounded-pill bg-secondary" title="API status">...</span>
        </h5>
        <p class="card-text">Log delivered materials and link them to POs and projects.</p>
        <a class="btn btn-sm btn-primary" href="#" onclick="openLogDeliveryModal(); return false;">Log Delivery</a>
      </div>
    </div>
  </div>

  <!-- PO Coordination -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title d-flex align-items-center justify-content-between">
          <span>Purchase Orders</span>
          <span id="statusPOs" class="badge rounded-pill bg-secondary" title="API status">...</span>
        </h5>
        <p class="card-text">Create purchase orders (go to approval), or mark delivered.</p>
        <div class="d-flex flex-wrap gap-2">
          <a class="btn btn-sm btn-primary" href="#" onclick="openCreatePOModal(); return false;">Create PO</a>
          <a class="btn btn-sm btn-outline-primary" href="#" onclick="openUpdatePOStatusModal(); return false;">Update PO Status</a>
          <a class="btn btn-sm btn-outline-secondary" href="#" onclick="openPOListModal(); return false;">View All POs</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Warehouse -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title d-flex align-items-center justify-content-between">
          <span>Warehouse</span>
          <span id="statusWarehouse" class="badge rounded-pill bg-secondary" title="API status">...</span>
        </h5>
        <p class="card-text">Adjust stock (per project/zone) and log zone transfers.</p>
        <div class="d-flex flex-wrap gap-2">
          <a class="btn btn-sm btn-outline-primary" href="#" onclick="openUpdateStockModal(); return false;">Update Stock</a>
          <a class="btn btn-sm btn-outline-primary" href="#" onclick="openLogTransferModal(); return false;">Log Transfer</a>
          <a class="btn btn-sm btn-outline-primary" href="#" onclick="openInventoryModal(); return false;">Inventory</a>
          <a class="btn btn-sm btn-primary" href="#" onclick="openWarehouseProductsModal(); return false;">Stock Catalog</a>
          <a class="btn btn-sm btn-success" href="#" onclick="openAddProductModal(); return false;">Add Product</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Incidents -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title d-flex align-items-center justify-content-between">
          <span>Incidents</span>
          <span id="statusIncidents" class="badge rounded-pill bg-secondary" title="API status">...</span>
        </h5>
        <p class="card-text">Report delivery issues like delays, damages, shortages.</p>
        <a class="btn btn-sm btn-outline-primary" href="#" onclick="openReportIncidentModal(); return false;">Report Incident</a>
      </div>
    </div>
  </div>

  <!-- Read-only Catalogs (optional quick access) -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title mb-2">Products & Suppliers</h5>
        <p class="card-text text-muted mb-3">View-only references used across logistics.</p>

        <style>
          /* Scoped styles for the segmented control */
          .ref-segmented { display:inline-flex; align-items:center; gap:6px; padding:6px; border-radius:999px; background:#f8fafc; border:1px solid #e6eaf1; }
          .ref-segmented a { text-decoration:none; border:1px solid transparent; padding:8px 14px; border-radius:999px; font-size:0.85rem; font-weight:600; color:#5b6b7b; transition:all .15s ease; }
          .ref-segmented a:hover { background:#f0f4f8; color:#334155; border-color:#e6eaf1; }
          .ref-segmented a.active { background:#e9efff; color:#2b50ff; border-color:#c7d2fe; box-shadow:inset 0 1px 0 rgba(255,255,255,.6); }
        </style>

        <div class="ref-segmented" role="group" aria-label="Products and Suppliers">
          <a class="active" href="#" onclick="openReferenceCatalogsModal('products'); return false;">Products</a>
          <a href="#" onclick="openReferenceCatalogsModal('suppliers'); return false;">Suppliers</a>
        </div>
        <script>
          // Toggle active state locally for nicer feedback (links still open in new tab)
          (function(){
            try {
              var grp = document.currentScript && document.currentScript.previousElementSibling;
              if (!grp || !grp.classList.contains('ref-segmented')) {
                grp = document.querySelector('.ref-segmented');
              }
              if (!grp) return;
              grp.querySelectorAll('a').forEach(function(a){
                a.addEventListener('click', function(){
                  grp.querySelectorAll('a').forEach(function(x){ x.classList.remove('active'); x.setAttribute('aria-pressed','false'); });
                  a.classList.add('active');
                  a.setAttribute('aria-pressed','true');
                });
              });
            } catch(e){}
          })();
        </script>
      </div>
    </div>
  </div>

  <!-- Messaging -->
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Messaging</h5>
        <p class="card-text">Communicate with Procurement, Site Manager, Warehouse.</p>
        <div class="d-flex flex-wrap gap-2">
          <a class="btn btn-sm btn-outline-primary" href="<?= url('/messages') ?>">Open Messages</a>
          <a class="btn btn-sm btn-outline-secondary" href="#" onclick="openCreateSupplierModal(); return false;">Enroll Supplier</a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add Product -->
<div class="modal fade" id="addProductModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Add Product (Supplier)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="addProductForm">
          <div class="mb-2"><label class="form-label">Supplier</label>
            <select name="supplier_id" id="add_product_supplier" class="form-select form-select-sm" required>
              <option value="" disabled selected>Loading suppliers...</option>
            </select>
          </div>
          <div class="mb-2"><label class="form-label">Product Name</label><input type="text" name="name" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">Unit</label><input type="text" name="unit" class="form-control form-control-sm" placeholder="e.g., bag, kg, pcs" value="unit"></div>
          <div class="mb-2"><label class="form-label">Unit Price</label><input type="number" step="0.01" min="0" name="unit_price" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">Initial Stock</label><input type="number" min="0" name="stock" class="form-control form-control-sm" value="0"></div>
          <div class="mb-2"><label class="form-label">Status</label>
            <select name="status" class="form-select form-select-sm">
              <option value="active" selected>Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </form>
        <div id="addProductMsg" class="small"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="submitAddProduct();">Save</button>
      </div>
    </div>
  </div>
  </div>

<!-- Inventory (All warehouse stock) -->
<div class="modal fade" id="inventoryModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Warehouse Inventory</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="inventoryFilters" class="row g-2 align-items-end">
          <div class="col-sm-4">
            <label class="form-label">Search Material</label>
            <input type="text" class="form-control form-control-sm" name="q" placeholder="Material name">
          </div>
          <div class="col-sm-4">
            <label class="form-label">Project</label>
            <select class="form-select form-select-sm" name="project_id" id="inventory_project_filter">
              <option value="">All projects</option>
            </select>
          </div>
          <div class="col-sm-2">
            <label class="form-label">Zone</label>
            <input type="text" class="form-control form-control-sm" name="zone" placeholder="e.g., MAIN">
          </div>
          <div class="col-sm-2 d-grid">
            <button type="button" class="btn btn-sm btn-primary" onclick="loadInventory();">Refresh</button>
          </div>
        </form>
        <div class="table-responsive mt-3">
          <table class="table table-sm table-striped">
            <thead>
              <tr>
                <th>Project</th>
                <th>Material</th>
                <th>Status</th>
                <th>Zone</th>
                <th class="text-end">Quantity</th>
                <th>Updated</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody id="inventoryBody"><tr><td colspan="7" class="text-muted">Loading...</td></tr></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Receive PO to Inventory Modal -->
<div class="modal fade" id="receivePOModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Receive PO to Inventory</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="receivePOForm" class="row g-2">
          <div class="col-sm-4">
            <label class="form-label">PO ID</label>
            <input type="number" class="form-control form-control-sm" name="po_id" id="receive_po_id" readonly>
          </div>
          <div class="col-sm-4">
            <label class="form-label">Project</label>
            <input type="text" class="form-control form-control-sm" id="receive_po_project" readonly>
          </div>
          <div class="col-sm-4">
            <label class="form-label">Zone</label>
            <input type="text" class="form-control form-control-sm" name="zone" id="receive_po_zone" placeholder="MAIN" value="MAIN">
          </div>
        </form>
        <div class="table-responsive mt-2">
          <table class="table table-sm table-striped">
            <thead>
              <tr><th>#</th><th>Product</th><th>Qty</th><th>Unit</th></tr>
            </thead>
            <tbody id="receivePOItemsBody"><tr><td colspan="4" class="text-muted">Loading...</td></tr></tbody>
          </table>
        </div>
        <div id="receivePOMsg" class="small"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="submitReceivePO();">Receive to Inventory</button>
      </div>
    </div>
  </div>
 </div>

<!-- All Purchase Orders Modal -->
<div class="modal fade" id="poListModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">All Purchase Orders</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="poListFilters" class="row g-2 align-items-end">
          <div class="col-sm-3">
            <label class="form-label">Status</label>
            <select class="form-select form-select-sm" name="status" id="po_filter_status">
              <option value="">All</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="ordered">Ordered</option>
              <option value="delivered">Delivered</option>
              <option value="rejected">Rejected</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <div class="col-sm-4">
            <label class="form-label">Project</label>
            <select class="form-select form-select-sm" name="project_id" id="po_filter_project">
              <option value="">All projects</option>
            </select>
          </div>
          <div class="col-sm-4">
            <label class="form-label">Supplier</label>
            <select class="form-select form-select-sm" name="supplier_id" id="po_filter_supplier">
              <option value="">All suppliers</option>
            </select>
          </div>
          <div class="col-sm-1 d-grid">
            <button type="button" class="btn btn-sm btn-primary" onclick="loadPOList();">Go</button>
          </div>
        </form>

        <div class="table-responsive mt-3">
          <table class="table table-sm table-striped">
            <thead>
              <tr>
                <th>#</th>
                <th>Project</th>
                <th>Supplier</th>
                <th>Total</th>
                <th>Status</th>
                <th>Created</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="poListBody"><tr><td colspan="7" class="text-muted">Loading...</td></tr></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Warehouse Products (Products + Supplier + Stock) -->
<div class="modal fade" id="warehouseProductsModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Warehouse Stock Catalog</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="warehouseProductsFilters" class="row g-2 align-items-end">
          <div class="col-sm-4">
            <label class="form-label">Search</label>
            <input type="text" class="form-control form-control-sm" name="q" placeholder="Product or Supplier name">
          </div>
          <div class="col-sm-3">
            <label class="form-label">Supplier</label>
            <select class="form-select form-select-sm" name="supplier_id" id="warehouse_supplier_filter">
              <option value="">All suppliers</option>
            </select>
          </div>
          <div class="col-sm-2">
            <label class="form-label">Min Stock</label>
            <input type="number" class="form-control form-control-sm" name="min_stock" min="0">
          </div>
          <div class="col-sm-2">
            <label class="form-label">Max Stock</label>
            <input type="number" class="form-control form-control-sm" name="max_stock" min="0">
          </div>
          <div class="col-sm-1">
            <label class="form-label">Status</label>
            <select class="form-select form-select-sm" name="status">
              <option value="">All</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="col-12 d-flex gap-2">
            <button type="button" class="btn btn-sm btn-primary" onclick="loadWarehouseProducts();">Search</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetWarehouseFilters();">Reset</button>
            <button type="button" class="btn btn-sm btn-outline-primary ms-auto" onclick="exportWarehouseProductsPDF();">Export PDF</button>
          </div>
        </form>

        <div class="table-responsive mt-3">
          <table class="table table-sm table-striped">
            <thead>
              <tr>
                <th>#</th>
                <th>Product</th>
                <th>Supplier</th>
                <th>Unit</th>
                <th>Unit Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="warehouseProductsBody"><tr><td colspan="8" class="text-muted">Use filters and Search to load results.</td></tr></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
  </div>

<!-- Widgets Row: Recent Deliveries & Low-stock Alerts -->
<style>
  /* Scoped styling for dashboard widgets */
  .ir-widgets .card { border: 1px solid #eef1f6; border-radius: 16px; box-shadow: 0 10px 30px rgba(31,41,55,0.06), 0 1px 3px rgba(31,41,55,0.08); }
  .ir-widgets .card-body { position: relative; border-radius: 16px; background: radial-gradient(180% 100% at 0% 0%, #fafbff 0%, #f6f8fc 60%, #f4f6fb 100%); }
  .ir-widgets h5.card-title { text-transform: uppercase; letter-spacing: .08em; font-weight: 800; color: #364152; }
  .ir-link-refresh { text-transform: uppercase; letter-spacing: .12em; font-weight: 800; font-size: .75rem; color: #6b7a90; text-decoration: none; }
  .ir-link-refresh:hover { color: #2b50ff; }
  .ir-table-wrapper { border-radius: 12px; box-shadow: inset 0 1px 0 rgba(255,255,255,.6); border:1px solid #e9edf5; overflow:hidden; }
  .ir-table-modern.table { margin: 0; }
  .ir-table-modern thead th { background: #f3f6fb; color: #606b85; font-weight: 800; font-size: .8rem; border-bottom: 1px solid #e9edf5; }
  .ir-table-modern tbody tr { background: #ffffff; }
  .ir-table-modern tbody tr + tr td { border-top: 1px solid #f1f4f9; }
  .ir-table-modern tbody tr:hover { background: #f9fbff; }
  .ir-pill { display:inline-block; padding: .25rem .6rem; border-radius: 999px; font-weight: 700; font-size: .75rem; line-height: 1; }
  .ir-pill-zone { background:#eef2ff; color:#3949ab; }
  .ir-badge { display:inline-block; padding:.25rem .6rem; border-radius:999px; font-weight:800; font-size:.75rem; line-height:1; }
  .ir-badge-success { background:#e7f6ee; color:#0f9d58; }
  .ir-badge-warning { background:#fff6e4; color:#b7791f; }
  .ir-badge-danger { background:#fde2e2; color:#d32f2f; }
  .ir-badge-secondary { background:#edf2f7; color:#4a5568; }
  .ir-input-pill { border-radius:10px; border:1px solid #e6eaf1; box-shadow: inset 0 1px 2px rgba(0,0,0,0.03); }
  .ir-muted { color:#6b7a90; }
</style>
<div class="row g-3 mt-1 ir-widgets">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="card-title mb-0">Recent Deliveries</h5>
          <a href="#" class="ir-link-refresh" onclick="loadRecentDeliveries(); return false;">Refresh</a>
        </div>
        <div class="table-responsive ir-table-wrapper">
          <table class="table table-sm ir-table-modern">
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
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="card-title mb-0">Low-stock Alerts</h5>
          <div class="d-flex align-items-center gap-3">
            <label class="form-check form-check-inline mb-0 me-1">
              <input class="form-check-input" type="checkbox" id="lowStockIncludeNonDelivered" title="Include items not yet delivered to site">
              <span class="form-check-label small">Include non-delivered</span>
            </label>
            <input id="lowStockThreshold" type="number" class="form-control form-control-sm d-inline-block ir-input-pill" style="width:90px" value="10" title="Threshold">
            <a href="#" class="ir-link-refresh" onclick="loadLowStock(); return false;">Refresh</a>
          </div>
        </div>
        <div class="table-responsive ir-table-wrapper">
          <table class="table table-sm ir-table-modern">
            <thead><tr><th>Project</th><th>Material</th><th>Status</th><th>Zone</th><th>Qty</th><th>Updated</th></tr></thead>
            <tbody id="lowStockBody"><tr><td colspan="6" class="text-muted">Loading...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Enroll Supplier Modal -->
<div class="modal fade" id="createSupplierModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Enroll Supplier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="createSupplierForm">
          <div class="mb-2"><label class="form-label">Company Name</label><input name="name" type="text" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">Email</label><input name="email" type="email" class="form-control form-control-sm"></div>
          <div class="mb-2"><label class="form-label">Phone</label><input name="phone" type="text" class="form-control form-control-sm"></div>
          <div class="mb-2"><label class="form-label">Address</label><input name="address" type="text" class="form-control form-control-sm"></div>
          <div class="mb-2"><label class="form-label">Rating</label><input name="rating" type="number" step="0.1" min="0" max="5" class="form-control form-control-sm" placeholder="e.g., 4.5"></div>
        </form>
        <div class="form-text">Suppliers can be referenced in Purchase Orders.</div>
        <div id="createSupplierMsg" class="small"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="submitCreateSupplier();">Save</button>
      </div>
    </div>
  </div>
  </div>

<!-- Create PO Modal -->
<div class="modal fade" id="createPOModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Create Purchase Order</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="createPOForm">
          <div class="mb-2"><label class="form-label">Project</label>
            <select name="project_id" id="create_po_project" class="form-select form-select-sm" required>
              <option value="" disabled selected>Loading projects...</option>
            </select>
            <div id="projectHelp" class="form-text"></div>
          </div>
          <div class="mb-2"><label class="form-label">Supplier</label>
            <select name="supplier_id" id="create_po_supplier" class="form-select form-select-sm" required>
              <option value="" disabled selected>Loading suppliers...</option>
            </select>
            <div id="supplierHelp" class="form-text"></div>
          </div>
          <div class="mb-2"><label class="form-label">Total Amount (optional)</label><input name="total_amount" type="number" step="0.01" min="0" class="form-control form-control-sm" placeholder="Will be computed if items are not provided"></div>
          <details class="mb-2">
            <summary class="small">Optional: Add one item</summary>
            <div class="mt-2 p-2 border rounded">
              <div class="mb-2"><label class="form-label">Product</label>
                <select name="item_product_id" id="create_po_item_product" class="form-select form-select-sm">
                  <option value="" selected disabled>Select supplier first</option>
                </select>
                <div id="create_po_item_help" class="form-text"></div>
              </div>
              <div class="mb-2"><label class="form-label">Quantity</label><input name="item_quantity" type="number" min="1" class="form-control form-control-sm"></div>
              <div class="mb-2"><label class="form-label">Unit Price</label><input name="item_unit_price" id="create_po_item_unit_price" type="number" step="0.01" min="0" class="form-control form-control-sm" placeholder="Auto-fills from product"></div>
            </div>
          </details>
        </form>
        <div class="form-text">POs created here start as "pending" and require approval by a Project Manager or Admin.</div>
        <div id="createPOMsg" class="small"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="submitCreatePO();">Create</button>
      </div>
    </div>
  </div>
  </div>
<!-- Lightweight modals (handlers will call APIs) -->
<!-- Reference Catalogs Modal (Products & Suppliers) -->
<div class="modal fade" id="refCatalogsModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Products & Suppliers</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <ul class="nav nav-pills mb-3" id="refTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-products" data-bs-toggle="pill" data-bs-target="#pane-products" type="button" role="tab">Products</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-suppliers" data-bs-toggle="pill" data-bs-target="#pane-suppliers" type="button" role="tab">Suppliers</button>
          </li>
        </ul>
        <div class="tab-content">
          <div class="tab-pane fade show active" id="pane-products" role="tabpanel" aria-labelledby="tab-products">
            <form class="row g-2 align-items-end mb-2">
              <div class="col-sm-4">
                <label class="form-label form-label-sm">Search</label>
                <input type="text" class="form-control form-control-sm" id="refProdSearch" placeholder="name or supplier">
              </div>
              <div class="col-sm-3">
                <label class="form-label form-label-sm">Supplier</label>
                <select class="form-select form-select-sm" id="refProdSupplier">
                  <option value="">All</option>
                </select>
              </div>
              <div class="col-sm-3">
                <label class="form-label form-label-sm">Status</label>
                <select class="form-select form-select-sm" id="refProdStatus">
                  <option value="">Any</option>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
              <div class="col-sm-2 d-flex gap-2">
                <button type="button" class="btn btn-sm btn-primary" onclick="loadRefProducts();">Search</button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="printRefProducts();">Print</button>
              </div>
            </form>
            <div class="table-responsive ir-table-wrapper">
              <table class="table table-sm ir-table-modern">
                <thead><tr><th>#</th><th>Name</th><th>Unit</th><th>Price</th><th>Supplier</th><th>Status</th></tr></thead>
                <tbody id="refProdBody"><tr><td colspan="6" class="text-muted">Use filters and Search to load results.</td></tr></tbody>
              </table>
            </div>
          </div>
          <div class="tab-pane fade" id="pane-suppliers" role="tabpanel" aria-labelledby="tab-suppliers">
            <form class="row g-2 align-items-end mb-2">
              <div class="col-sm-5">
                <label class="form-label form-label-sm">Search</label>
                <input type="text" class="form-control form-control-sm" id="refSupSearch" placeholder="supplier name">
              </div>
              <div class="col-sm-3 d-flex gap-2">
                <button type="button" class="btn btn-sm btn-primary" onclick="loadRefSuppliers();">Search</button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="printRefSuppliers();">Print</button>
              </div>
            </form>
            <div class="table-responsive ir-table-wrapper">
              <table class="table table-sm ir-table-modern">
                <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Rating</th></tr></thead>
                <tbody id="refSupBody"><tr><td colspan="5" class="text-muted">Use search to load results.</td></tr></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
  </div>

<script>
function openReferenceCatalogsModal(tab){
  try {
    // preload suppliers for product filter
    getSuppliersRobust().then(function(list){
      var sel = document.getElementById('refProdSupplier');
      if (sel) {
        var opts = (list||[]).map(function(s){ return '<option value="'+s.id+'">'+(s.name||('Supplier #'+s.id))+'</option>'; }).join('');
        sel.innerHTML = '<option value="">All</option>' + opts;
      }
    });
  } catch(e){}
  var el = document.getElementById('refCatalogsModal');
  var m = new bootstrap.Modal(el);
  m.show();
  if (tab === 'suppliers') {
    document.getElementById('tab-suppliers').click();
    loadRefSuppliers();
  } else {
    document.getElementById('tab-products').click();
    loadRefProducts();
  }
}

async function loadRefProducts(){
  var tbody = document.getElementById('refProdBody');
  tbody.innerHTML = '<tr><td colspan="6" class="text-muted">Loading...</td></tr>';
  var q = document.getElementById('refProdSearch').value.trim();
  var supplierId = document.getElementById('refProdSupplier').value;
  var status = document.getElementById('refProdStatus').value;
  var qs = new URLSearchParams();
  if (q) qs.set('q', q);
  if (supplierId) qs.set('supplier_id', supplierId);
  if (status) qs.set('status', status);
  qs.set('limit','1000');
  try {
    var res = await fetch('<?= url('api/logistic_officer/products.php') ?>' + '?' + qs.toString());
    var j = await res.json();
    if (!j.success) throw new Error(j.message||'Failed');
    var rows = (j.data||[]).map(function(p,i){
      var price = (p.unit_price!=null) ? (Number(p.unit_price).toFixed(2)) : '';
      var status = String(p.status||'').toLowerCase();
      var badge = status==='active' ? '<span class="ir-badge ir-badge-success">active</span>' : '<span class="ir-badge ir-badge-secondary">inactive</span>';
      return '<tr>'+
             '<td>'+(i+1)+'</td>'+
             '<td>'+ (p.name||'') +'</td>'+
             '<td>'+ (p.unit||'') +'</td>'+
             '<td class="text-end">'+ price +'</td>'+
             '<td>'+ (p.supplier_name || ('#'+p.supplier_id)) +'</td>'+
             '<td>'+ badge +'</td>'+
             '</tr>';
    }).join('');
    tbody.innerHTML = rows || '<tr><td colspan="6" class="text-muted">No products</td></tr>';
  } catch(e){ tbody.innerHTML = '<tr><td colspan="6" class="text-danger">'+e.message+'</td></tr>'; }
}

async function loadRefSuppliers(){
  var tbody = document.getElementById('refSupBody');
  tbody.innerHTML = '<tr><td colspan="5" class="text-muted">Loading...</td></tr>';
  var q = document.getElementById('refSupSearch').value.trim();
  var qs = new URLSearchParams(); if (q) qs.set('q', q); qs.set('limit','1000');
  try {
    var res = await fetch('<?= url('api/logistic_officer/suppliers.php') ?>' + '?' + qs.toString());
    var j = await res.json();
    if (!j.success) throw new Error(j.message||'Failed');
    var rows = (j.data||[]).map(function(s,i){
      var rating = (s.rating!=null && s.rating!=='') ? ('⭐ '+Number(s.rating).toFixed(1)) : '';
      return '<tr>'+
             '<td>'+(i+1)+'</td>'+
             '<td>'+ (s.name||'') +'</td>'+
             '<td>'+ (s.email||'') +'</td>'+
             '<td>'+ (s.phone||'') +'</td>'+
             '<td>'+ rating +'</td>'+
             '</tr>';
    }).join('');
    tbody.innerHTML = rows || '<tr><td colspan="5" class="text-muted">No suppliers</td></tr>';
  } catch(e){ tbody.innerHTML = '<tr><td colspan="5" class="text-danger">'+e.message+'</td></tr>'; }
}

function printRefProducts(){
  try {
    var q = document.getElementById('refProdSearch').value.trim();
    var supplierId = document.getElementById('refProdSupplier').value;
    var status = document.getElementById('refProdStatus').value;
    var qs = new URLSearchParams();
    if (q) qs.set('q', q);
    if (supplierId) qs.set('supplier_id', supplierId);
    if (status) qs.set('status', status);
    window.open('<?= url('api/logistic_officer/products_stock_print.php') ?>' + '?' + qs.toString(), '_blank');
  } catch(e){}
}

function printRefSuppliers(){
  // For now, print the current modal content
  try {
    var el = document.getElementById('pane-suppliers');
    var w = window.open('', '_blank');
    w.document.write('<html><head><title>Suppliers</title></head><body>'+ el.innerHTML +'</body></html>');
    w.document.close();
    w.focus();
    w.print();
  } catch(e){}
}
</script>
<div class="modal fade" id="logDeliveryModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Log Delivery</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="logDeliveryForm">
          <div class="mb-2">
            <label class="form-label">PO</label>
            <select name="po_id" id="log_delivery_po" class="form-select form-select-sm" required>
              <option value="" disabled selected>Loading purchase orders...</option>
            </select>
            <div id="logDeliveryPoHelp" class="form-text"></div>
          </div>
          <div class="mb-2">
            <label class="form-label">Project</label>
            <select name="project_id" id="log_delivery_project" class="form-select form-select-sm" required>
              <option value="" disabled selected>Loading projects...</option>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Supplier</label>
            <select name="supplier_id" id="log_delivery_supplier" class="form-select form-select-sm" required>
              <option value="" disabled selected>Loading suppliers...</option>
            </select>
          </div>
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
  <div class="form-text">Tip: Use valid IDs from Purchase Orders, Projects, and Suppliers. Status defaults to "received".</div>
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
  <div class="form-text">Set status to "delivered" when goods arrive. Optionally paste a GRN file URL (if uploaded elsewhere).</div>
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
          <div class="mb-2"><label class="form-label">Project ID</label><input name="project_id" type="number" min="1" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">Material ID</label><input name="material_id" type="number" min="1" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">Zone</label><input name="zone" type="text" class="form-control form-control-sm" value="MAIN" placeholder="e.g., MAIN / YARD / BAY-A"></div>
          <div class="mb-2"><label class="form-label">Quantity (+/-)</label><input name="quantity" type="number" class="form-control form-control-sm" required></div>
        </form>
        <div class="form-text">Enter positive to add stock, negative to deduct. Zone defaults to MAIN.</div>
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
          <div class="mb-2"><label class="form-label">Project ID</label><input name="project_id" type="number" min="1" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">From Zone</label><input name="from_zone" type="text" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">To Zone</label><input name="to_zone" type="text" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">Material ID</label><input name="material_id" type="number" min="1" class="form-control form-control-sm" required></div>
          <div class="mb-2"><label class="form-label">Quantity</label><input name="quantity" type="number" min="1" class="form-control form-control-sm" required></div>
        </form>
        <div class="form-text">Moves quantity from one zone to another within the same project.</div>
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
  <div class="form-text">Describe the issue. You can reference a related PO ID if applicable.</div>
        <div id="reportIncidentMsg" class="small"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="submitReportIncident();">Submit</button>
      </div>
    </div>
  </div>
</div>

<!-- Upload GRN flow removed to simplify: add GRN file URL in "Update PO Status" -->

<script>
  function openLogDeliveryModal(){ preloadLogDeliveryOptions().then(()=>{ new bootstrap.Modal(document.getElementById('logDeliveryModal')).show(); }); }
  function openUpdatePOStatusModal(){ new bootstrap.Modal(document.getElementById('updatePOStatusModal')).show(); }
  function openUpdateStockModal(){ new bootstrap.Modal(document.getElementById('updateStockModal')).show(); }
  function openLogTransferModal(){ new bootstrap.Modal(document.getElementById('logTransferModal')).show(); }
  function openReportIncidentModal(){ new bootstrap.Modal(document.getElementById('reportIncidentModal')).show(); }
  function openCreateSupplierModal(){ new bootstrap.Modal(document.getElementById('createSupplierModal')).show(); }
  function openCreatePOModal(){ preloadCreatePOOptions().then(()=>{ new bootstrap.Modal(document.getElementById('createPOModal')).show(); }); }
  function openWarehouseProductsModal(){ preloadWarehouseSuppliers().then(()=>{ new bootstrap.Modal(document.getElementById('warehouseProductsModal')).show(); }); }
  function openAddProductModal(){ preloadAddProductSuppliers().then(()=>{ new bootstrap.Modal(document.getElementById('addProductModal')).show(); }); }
  function openInventoryModal(){ preloadInventoryFilters().then(()=>{ new bootstrap.Modal(document.getElementById('inventoryModal')).show(); loadInventory(); }); }
  function openPOListModal(){ preloadPOListFilters().then(()=>{ new bootstrap.Modal(document.getElementById('poListModal')).show(); loadPOList(); }); }

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
    if (j.success) {
      const parts = [];
      if (j.project_name) parts.push(`Project: ${j.project_name} (#${j.project_id})`);
      if (j.material_name) parts.push(`Material: ${j.material_name} (#${j.material_id})`);
      parts.push(`Zone: ${j.zone}`);
      parts.push(`Quantity: ${j.previous_quantity ?? '?'} -> ${j.new_quantity ?? '?'}`);
      document.getElementById('updateStockMsg').innerHTML = '<span class="text-success">Applied.</span> ' + parts.join(' | ');
      // Optionally refresh low-stock widget
      loadLowStock();
    } else {
      document.getElementById('updateStockMsg').textContent = j.message || 'Failed';
    }
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

  async function submitCreateSupplier(){
    const data = Object.fromEntries(new FormData(document.getElementById('createSupplierForm')).entries());
    // Coerce rating to number or remove if blank
    if (data.rating === '') delete data.rating;
    const res = await fetch('<?= url('api/logistic_officer/suppliers.php?action=create') ?>', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const j = await res.json();
    document.getElementById('createSupplierMsg').textContent = j.success ? ('Created. ID '+ (j.id||'')) : (j.message||'Failed');
    if (j.success) { preloadCreatePOOptions(); }
  }

  async function submitCreatePO(){
    const f = new FormData(document.getElementById('createPOForm'));
    const data = Object.fromEntries(f.entries());
    // Build items array if provided
    const pid = parseInt(data.item_product_id||'0',10);
    const qty = parseInt(data.item_quantity||'0',10);
    const up  = parseFloat(data.item_unit_price||'0');
    if (pid>0 && qty>0 && up>=0) {
      data.items = [{ product_id: pid, quantity: qty, unit_price: up }];
    }
    delete data.item_product_id; delete data.item_quantity; delete data.item_unit_price;
    if (data.total_amount === '') delete data.total_amount;
    // Coerce numeric
    data.project_id = parseInt(data.project_id,10);
    data.supplier_id = parseInt(data.supplier_id,10);
    if (data.total_amount !== undefined) data.total_amount = parseFloat(data.total_amount);
    const res = await fetch('<?= url('api/logistic_officer/purchase_orders.php?action=create') ?>', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const j = await res.json();
    const msgEl = document.getElementById('createPOMsg');
    msgEl.textContent = j.success ? ('PO Created (pending). ID '+ (j.id||'')) : (j.message||'Failed');
    if (j.success) {
      // Close modal and refresh PO list or page widgets
      const modalEl = document.getElementById('createPOModal');
      const inst = bootstrap.Modal.getInstance(modalEl);
      if (inst) inst.hide();
      // Reset form for next time
      document.getElementById('createPOForm').reset();
      // Refresh PO list if open; otherwise refresh widgets
      const poListEl = document.getElementById('poListModal');
      const isPOListOpen = poListEl && poListEl.classList.contains('show');
      setTimeout(()=>{
        if (isPOListOpen && typeof loadPOList === 'function') {
          loadPOList();
        } else {
          loadRecentDeliveries();
        }
      }, 300);
    }
  }

  // Add Product helpers
  async function preloadAddProductSuppliers(){
    try {
      const list = await getSuppliersRobust();
      const sel = document.getElementById('add_product_supplier');
      if (!sel) return;
      const opts = (list||[]).map(s=>`<option value="${s.id}">${s.name||('Supplier #'+s.id)}</option>`).join('');
      sel.innerHTML = '<option value="" disabled selected>Select supplier</option>' + opts;
    } catch(e){ /* ignore */ }
  }

  async function submitAddProduct(){
    const data = Object.fromEntries(new FormData(document.getElementById('addProductForm')).entries());
    data.supplier_id = parseInt(data.supplier_id, 10);
    data.unit_price = parseFloat(data.unit_price);
    data.stock = parseInt(data.stock||'0', 10);
    const res = await fetch('<?= url('api/logistic_officer/products.php?action=create') ?>', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const j = await res.json();
    document.getElementById('addProductMsg').textContent = j.success ? ('Created. ID '+ (j.id||'')) : (j.message||'Failed');
    // Refresh supplier/product lists used elsewhere
    if (j.success) { preloadWarehouseSuppliers(); loadWarehouseProducts(); }
  }

  // Quick order from products table (prefill Create PO with supplier and single item)
  async function quickOrderProduct(product){
    // product: {id, name, supplier_id, unit_price}
    await preloadCreatePOOptions();
    const supSel = document.getElementById('create_po_supplier');
    if (supSel) {
      supSel.value = String(product.supplier_id);
      // trigger change to load products list for that supplier
      supSel.dispatchEvent(new Event('change'));
    }
    // Prefill single item fields (within details)
    const f = document.getElementById('createPOForm');
    if (f) {
      f.querySelector('[name="item_product_id"]').value = product.id;
      f.querySelector('[name="item_quantity"]').value = 1;
      f.querySelector('[name="item_unit_price"]').value = product.unit_price;
    }
    new bootstrap.Modal(document.getElementById('createPOModal')).show();
  }

  // Build query string from Warehouse filters
  function _warehouseQueryParams(){
    const f = document.getElementById('warehouseProductsFilters');
    const data = Object.fromEntries(new FormData(f).entries());
    const params = new URLSearchParams();
    if (data.q) params.set('q', data.q);
    if (data.supplier_id) params.set('supplier_id', data.supplier_id);
    if (data.min_stock) params.set('min_stock', data.min_stock);
    if (data.max_stock) params.set('max_stock', data.max_stock);
    if (data.status) params.set('status', data.status);
    params.set('limit', '1000');
    return params.toString();
  }

  async function preloadWarehouseSuppliers(){
    try {
      const list = await getSuppliersRobust();
      const sel = document.getElementById('warehouse_supplier_filter');
      if (!sel) return;
      const opts = (list||[]).map(s=>`<option value="${s.id}">${s.name||('Supplier #'+s.id)}</option>`).join('');
      sel.innerHTML = '<option value="">All suppliers</option>' + opts;
    } catch(e){ /* ignore */ }
  }

  async function loadWarehouseProducts(){
    const tbody = document.getElementById('warehouseProductsBody');
  tbody.innerHTML = '<tr><td colspan="8" class="text-muted">Loading...</td></tr>';
    try {
      const qs = _warehouseQueryParams();
      const res = await fetch('<?= url('api/logistic_officer/products_stock.php') ?>' + '?' + qs);
      const j = await res.json();
      if (!j.success) throw new Error(j.message||'Failed');
      const rows = (j.data||[]).map((r,i)=>`
        <tr>
          <td>${i+1}</td>
          <td>${r.name||''}</td>
          <td>${r.supplier_name||''}</td>
          <td>${r.unit||''}</td>
          <td>${Number(r.unit_price||0).toFixed(2)}</td>
          <td>${r.stock||0}</td>
          <td>${r.status||''}</td>
          <td class="text-end"><button class="btn btn-sm btn-outline-primary" onclick='quickOrderProduct(${JSON.stringify({id:"__ID__",supplier_id:"__SUP__",unit_price:"__PRICE__"}).replace("__ID__", String(r.id)).replace("__SUP__", String(r.supplier_id)).replace("__PRICE__", String(r.unit_price||0))})'>Order</button></td>
        </tr>`).join('');
      tbody.innerHTML = rows || '<tr><td colspan="8" class="text-muted">No results</td></tr>';
    } catch(e){ tbody.innerHTML = `<tr><td colspan="7" class="text-danger">${e.message}</td></tr>`; }
  }

  function resetWarehouseFilters(){
    const f = document.getElementById('warehouseProductsFilters');
    f.reset();
    const sel = document.getElementById('warehouse_supplier_filter');
    if (sel) sel.value = '';
  document.getElementById('warehouseProductsBody').innerHTML = '<tr><td colspan="8" class="text-muted">Use filters and Search to load results.</td></tr>';
  }

  function exportWarehouseProductsPDF(){
    const qs = _warehouseQueryParams();
    const urlPrint = '<?= url('api/logistic_officer/products_stock_print.php') ?>' + '?' + qs;
    window.open(urlPrint, '_blank');
  }

  // Inventory helpers
  async function preloadInventoryFilters(){
    try {
      const list = await getProjectsRobust();
      const projSel = document.getElementById('inventory_project_filter');
      if (projSel) {
        const opts = (list||[]).map(p=>`<option value="${p.id}">${p.name||('Project #'+p.id)}</option>`).join('');
        projSel.innerHTML = '<option value="">All projects</option>' + opts;
      }
    } catch(e){ /* ignore */ }
  }

  async function loadInventory(){
    const tbody = document.getElementById('inventoryBody');
    if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="text-muted">Loading...</td></tr>';
    const f = document.getElementById('inventoryFilters');
    const data = Object.fromEntries(new FormData(f).entries());
    const qs = new URLSearchParams();
    qs.set('view','inventory_enriched');
    qs.set('limit','1000');
    if (data.q) qs.set('q', data.q);
    if (data.project_id) qs.set('project_id', data.project_id);
    if (data.zone) qs.set('zone', data.zone);
    try {
      const r = await fetch('<?= url('api/logistic_officer/warehouse.php') ?>' + '?' + qs.toString());
      const j = await r.json();
      if (!j.success) throw new Error(j.message||'Failed');
      const rows = (j.data||[]).map(r => {
        const zoneText = r.zone || 'MAIN';
        const zoneAttr = String(zoneText).replace(/'/g,"&#39;");
        const zoneSafeId = String(zoneText).replace(/[^a-zA-Z0-9_-]/g,'_');
        const inputId = `inv_qty_${r.project_id}_${r.material_id}_${zoneSafeId}`;
        const qtyInput = `<input type=\"number\" id=\"${inputId}\" class=\"form-control form-control-sm d-inline-block me-2\" value=\"1\" min=\"1\" style=\"width:80px\">`;
        const addBtn = `<button class=\"btn btn-sm btn-outline-success\" onclick=\"adjustStockFromRow(${r.project_id},${r.material_id},'${zoneAttr}',true,'${inputId}')\">Add</button>`;
        const remBtn = `<button class=\"btn btn-sm btn-outline-danger\" onclick=\"adjustStockFromRow(${r.project_id},${r.material_id},'${zoneAttr}',false,'${inputId}')\">Remove</button>`;
        return `
          <tr>
            <td>${r.project_name || r.project_id || ''}</td>
            <td>${r.material_name || r.material_id || ''}</td>
            <td>${r.material_status || ''}</td>
            <td>${zoneText}</td>
            <td class=\"text-end\">${r.quantity || 0}</td>
            <td>${r.updated_at || ''}</td>
            <td class=\"text-end\">${qtyInput}${addBtn} ${remBtn}</td>
          </tr>`;
      }).join('');
      if (tbody) tbody.innerHTML = rows || '<tr><td colspan="7" class="text-muted">No inventory records</td></tr>';
    } catch(e){ if (tbody) tbody.innerHTML = `<tr><td colspan="7" class="text-danger">${e.message}</td></tr>`; }
  }

  function adjustStock(projectId, materialId, zone, sign){
    // Reuse Update Stock modal; prefill fields and suggest a delta (1 or -1)
    try {
      const f = document.getElementById('updateStockForm');
      if (f) {
        f.querySelector('[name="project_id"]').value = projectId;
        f.querySelector('[name="material_id"]').value = materialId;
        f.querySelector('[name="zone"]').value = zone || 'MAIN';
        f.querySelector('[name="quantity"]').value = sign >= 0 ? 1 : -1;
      }
      const msg = document.getElementById('updateStockMsg');
      if (msg) msg.textContent = '';
      new bootstrap.Modal(document.getElementById('updateStockModal')).show();
    } catch (e) { /* ignore */ }
  }

  // Populate dropdowns for Log Delivery modal and keep them in sync
  let _dlvPOs = [];
  async function preloadLogDeliveryOptions(){
    try {
      const [poRes] = await Promise.all([
        fetch('<?= url('api/logistic_officer/purchase_orders.php') ?>').then(r=>r.json()).catch(()=>({success:false,data:[]})),
      ]);
      const projList = await getProjectsRobust();
      const supList = await getSuppliersRobust();

      // Cache POs for client-side filtering
      _dlvPOs = Array.isArray(poRes.data) ? poRes.data : [];

      const poSel = document.getElementById('log_delivery_po');
      const projSel = document.getElementById('log_delivery_project');
      const supSel = document.getElementById('log_delivery_supplier');
      const poHelp = document.getElementById('logDeliveryPoHelp');

      // Projects
      if (projSel) {
        const projOpts = (projList||[]).map(p=>`<option value="${p.id}">${p.name || ('Project #'+p.id)}</option>`).join('');
        projSel.innerHTML = '<option value="" disabled selected>Select a project</option>' + projOpts;
        if ((projList||[]).length === 1) { projSel.value = String((projList||[])[0].id); }
      }

      // Suppliers
      if (supSel) {
        const supOpts = (supList||[]).map(s=>`<option value="${s.id}">${s.name || ('Supplier #'+s.id)}</option>`).join('');
        supSel.innerHTML = '<option value="" disabled selected>Select a supplier</option>' + (supOpts || '<option value="" disabled>No suppliers found</option>');
      }

      // POs (render function supports filtering)
      function renderPODropdown(filterProjectId, filterSupplierId){
        if (!poSel) return;
        let list = _dlvPOs;
        if (filterProjectId) list = list.filter(p=>String(p.project_id) === String(filterProjectId));
        if (filterSupplierId) list = list.filter(p=>String(p.supplier_id) === String(filterSupplierId));
        const opts = list.map(po=>{
          const label = `PO #${po.id} — ${po.status||'pending'} (P${po.project_id}/S${po.supplier_id})`;
          return `<option value="${po.id}" data-project="${po.project_id}" data-supplier="${po.supplier_id}">${label}</option>`;
        }).join('');
        if (opts) {
          poSel.innerHTML = '<option value="" disabled selected>Select a purchase order</option>' + opts;
        } else {
          poSel.innerHTML = '<option value="" disabled selected>No matching POs</option>';
        }
        if (poHelp) poHelp.textContent = (_dlvPOs.length === 0) ? 'No purchase orders found. Create one first.' : '';
      }

      renderPODropdown(null, null);

      // When a PO is chosen, auto-fill project and supplier
      if (poSel) {
        poSel.onchange = ()=>{
          const opt = poSel.options[poSel.selectedIndex];
          if (!opt) return;
          const p = opt.getAttribute('data-project');
          const s = opt.getAttribute('data-supplier');
          if (projSel && p) projSel.value = String(p);
          if (supSel && s) supSel.value = String(s);
        };
      }

      // When project/supplier change, filter POs
      if (projSel) projSel.onchange = ()=>{ renderPODropdown(projSel.value||null, supSel ? (supSel.value||null) : null); };
      if (supSel)  supSel.onchange  = ()=>{ renderPODropdown(projSel ? (projSel.value||null) : null, supSel.value||null); };

    } catch(e){ /* ignore */ }
  }

  // Helper: fetch JSON with optional fallback URL
  async function fetchJsonWithFallback(u1, u2){
    try { const r = await fetch(u1); if (!r.ok) throw new Error('HTTP '+r.status); return await r.json(); }
    catch(e){ if (!u2) return {success:false, data:[], error: e.message}; try { const r2 = await fetch(u2); if (!r2.ok) throw new Error('HTTP '+r2.status); return await r2.json(); } catch(e2){ return {success:false, data:[], error: e2.message}; } }
  }

  // Robust projects loader: unions results across endpoints and de-duplicates by id
  async function getProjectsRobust(){
    const lists = [];
    try {
      const a = await fetchJsonWithFallback('/api/logistic_officer/projects.php','<?= url('api/logistic_officer/projects.php') ?>');
      if (a && Array.isArray(a.data)) lists.push(...a.data);
    } catch(_){}
    try {
      const b = await fetchJsonWithFallback('/api/projects/list.php','<?= url('api/projects/list.php') ?>');
      if (b && Array.isArray(b.data)) lists.push(...b.data);
    } catch(_){}
    const map = new Map();
    for (const p of lists) {
      const id = String(p.id);
      if (!map.has(id)) map.set(id, p);
      else {
        const old = map.get(id);
        // prefer entry with a name
        if ((!old.name && p.name) || (old.name && p.name && p.name.length > old.name.length)) map.set(id, {...old, ...p});
      }
    }
    return Array.from(map.values());
  }

  // Robust suppliers loader with retry and cache fallback
  async function getSuppliersRobust(){
    const cacheKey = 'suppliers_cache_v1';
    async function tryFetch(){
      const t = Date.now();
      const res = await fetchJsonWithFallback('/api/logistic_officer/suppliers.php?t='+t, '<?= url('api/logistic_officer/suppliers.php') ?>?t='+t);
      if (res && res.success && Array.isArray(res.data)) return res.data;
      return null;
    }
    let list = await tryFetch();
    if (!list) {
      await new Promise(r=>setTimeout(r, 600));
      list = await tryFetch();
    }
    if (list && list.length) {
      try { localStorage.setItem(cacheKey, JSON.stringify({ ts: Date.now(), data: list })); } catch(_){}
      return list;
    }
    try {
      const raw = localStorage.getItem(cacheKey);
      if (raw) {
        const obj = JSON.parse(raw);
        if (obj && Array.isArray(obj.data)) return obj.data;
      }
    } catch(_){}
    return [];
  }

  // Populate dropdowns for Create PO modal
  async function preloadCreatePOOptions(){
    try {
      const [projList, supList] = await Promise.all([
        getProjectsRobust(),
        getSuppliersRobust(),
      ]);
      const projSel = document.getElementById('create_po_project');
      const supSel = document.getElementById('create_po_supplier');
      const supHelp = document.getElementById('supplierHelp');
      const projHelp = document.getElementById('projectHelp');
      if (projSel) {
        const opts = (projList||[]).map(p=>`<option value="${p.id}">${p.name||('Project #'+p.id)}</option>`).join('');
        projSel.innerHTML = opts ? ('<option value="" disabled selected>Select a project</option>' + opts)
                                 : '<option value="" disabled selected>Failed to load projects</option>';
        if (projHelp) { projHelp.textContent = (projList && projList.length>0) ? '' : 'Could not reach projects API.'; }
        // Auto-select if only one project
        if ((projList||[]).length === 1) { projSel.value = String((projList||[])[0].id); }
      }
      if (supSel) {
        const list = Array.isArray(supList) ? supList : [];
        if (list.length > 0) {
          const opts2 = list.map(s=>`<option value=\"${s.id}\">${s.name||('Supplier #'+s.id)}</option>`).join('');
          supSel.innerHTML = '<option value=\"\" disabled>Select a supplier</option>' + opts2 + '<option value=\"__create__\">+ Enroll new supplier…</option>';
          // Preselect first supplier
          supSel.selectedIndex = 1;
          if (supHelp) supHelp.textContent = '';
          if (supSel.value) { loadProductsForSupplier(supSel.value); }
        } else {
          supSel.innerHTML = '<option value=\"\" disabled selected>No suppliers found</option><option value=\"__create__\">+ Enroll new supplier…</option>';
          if (supHelp) supHelp.textContent = 'No suppliers yet. Enroll one to proceed.';
        }
        // Handle special __create__ option to open modal
        supSel.onchange = (e)=>{
          if (supSel.value === '__create__') {
            openCreateSupplierModal();
            // Reset selection back to placeholder
            supSel.selectedIndex = 0;
          }
          // Load products for selected supplier into item dropdown
          if (supSel.value && supSel.value !== '__create__') {
            loadProductsForSupplier(supSel.value);
          } else {
            const prodSel = document.getElementById('create_po_item_product');
            if (prodSel) prodSel.innerHTML = '<option value="" selected disabled>Select supplier first</option>';
          }
        };
      }
    } catch(e){ /* ignore */ }
  }
  // Load dropdowns once on page load
  preloadCreatePOOptions();

  // Load products dropdown options for a supplier
  async function loadProductsForSupplier(supplierId){
    const prodSel = document.getElementById('create_po_item_product');
    const help = document.getElementById('create_po_item_help');
    if (!prodSel) return;
    prodSel.innerHTML = '<option value="" selected disabled>Loading products...</option>';
    try {
      const j = await fetchJsonWithFallback('/api/logistic_officer/products.php?supplier_id='+encodeURIComponent(supplierId), '<?= url('api/logistic_officer/products.php') ?>?supplier_id='+encodeURIComponent(supplierId));
      if (j && j.success) {
        const list = Array.isArray(j.data)? j.data : [];
        if (list.length > 0) {
          const opts = list.map(p=>`<option value="${p.id}" data-price="${p.unit_price}">${p.name}</option>`).join('');
          prodSel.innerHTML = '<option value="" disabled selected>Select a product</option>' + opts;
          if (help) help.textContent = '';
        } else {
          prodSel.innerHTML = '<option value="" disabled selected>No products for this supplier</option>';
          if (help) help.textContent = 'No products found for the selected supplier.';
        }
      } else {
        prodSel.innerHTML = '<option value="" disabled selected>Failed to load products</option>';
        if (help) help.textContent = 'Could not reach products API.';
      }
    } catch(e){ prodSel.innerHTML = '<option value="" disabled selected>Error loading products</option>'; }

    // When product is chosen, auto-fill unit price
    prodSel.onchange = ()=>{
      const opt = prodSel.options[prodSel.selectedIndex];
      const price = opt ? parseFloat(opt.getAttribute('data-price')||'') : null;
      const priceInput = document.getElementById('create_po_item_unit_price');
      if (priceInput && price !== null && !Number.isNaN(price)) { priceInput.value = price; }
    };
  }

  async function loadRecentDeliveries(){
    const tbody = document.getElementById('recentDeliveriesBody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-muted">Loading...</td></tr>';
    try {
      const res = await fetch('<?= url('api/logistic_officer/deliveries.php') ?>?limit=10');
      const j = await res.json();
      if (!j.success) throw new Error(j.message||'Failed');
      // Build name maps for better labels if available
  const projMap = new Map((_poProjects||[]).map(p=>[String(p.id), p.name||('Project #'+p.id)]));
  const supMap = new Map((_poSuppliers||[]).map(s=>[String(s.id), s.name||('Supplier #'+s.id)]));
      function statusBadge(st){
        const s = String(st||'').toLowerCase();
        if (s==='received' || s==='delivered') return '<span class="ir-badge ir-badge-success">received</span>';
        if (s==='partial') return '<span class="ir-badge ir-badge-warning">partial</span>';
        if (s==='failed' || s==='cancelled') return '<span class="ir-badge ir-badge-danger">'+(s)+'</span>';
        return '<span class="ir-badge ir-badge-secondary">'+(st||'—')+'</span>';
      }
      const rows = (j.data||[]).map(r => {
        const pid = String(r.project_id||'');
        const sid = String(r.supplier_id||'');
        const po = r.purchase_order_id||'';
        const dt = r.delivery_date ? String(r.delivery_date).slice(0,10) : '';
        const projectLabel = r.project_name || projMap.get(pid) || pid;
        const supplierLabel = r.supplier_name || supMap.get(sid) || sid;
        const prodNames = r.product_names || '';
        return `
        <tr>
          <td>${r.id||''}</td>
          <td>${po}</td>
          <td title="${projectLabel}">${projectLabel}</td>
          <td title="${supplierLabel}${prodNames ? ('\nProducts: '+prodNames) : ''}">${supplierLabel}</td>
          <td>${dt}</td>
          <td>${statusBadge(r.status)}</td>
        </tr>`;
      }).join('');
      tbody.innerHTML = rows || '<tr><td colspan="6" class="text-muted">No data</td></tr>';
    } catch(e){ tbody.innerHTML = `<tr><td colspan="6" class="text-danger">${e.message}</td></tr>`; }
  }

  async function loadLowStock(){
    const tbody = document.getElementById('lowStockBody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-muted">Loading...</td></tr>';
    const threshold = parseInt(document.getElementById('lowStockThreshold').value||'10',10);
    try {
      const includeNonDelivered = (document.getElementById('lowStockIncludeNonDelivered') && document.getElementById('lowStockIncludeNonDelivered').checked) ? 1 : 0;
      const deliveredOnly = includeNonDelivered ? 0 : 1;
      const qs = new URLSearchParams({ view:'low_stock', threshold: String(threshold), delivered_only: String(deliveredOnly), limit:'200' });
      const res = await fetch('<?= url('api/logistic_officer/warehouse.php') ?>' + '?' + qs.toString());
      const j = await res.json();
      if (!j.success) throw new Error(j.message||'Failed');
      function qtyBadge(q){
        const n = Number(q||0);
        const cls = n <= 0 ? 'ir-badge-danger' : (n <= threshold ? 'ir-badge-warning' : 'ir-badge-success');
        return `<span class="ir-badge ${cls}">${n}</span>`;
      }
      const rows = (j.data||[]).map(r => {
        const zone = r.zone || 'MAIN';
        return `
        <tr>
          <td>${r.project_name || r.project_id || ''}</td>
          <td>${r.material_name || r.material_id || ''}</td>
          <td>${r.material_status || ''}</td>
          <td><span class="ir-pill ir-pill-zone">${zone}</span></td>
          <td class="text-end">${qtyBadge(r.quantity)}</td>
          <td class="ir-muted">${r.updated_at||''}</td>
        </tr>`;
      }).join('');
      tbody.innerHTML = rows || '<tr><td colspan="6" class="text-muted">All stocks above threshold</td></tr>';
    } catch(e){ tbody.innerHTML = `<tr><td colspan="5" class="text-danger">${e.message}</td></tr>`; }
  }

  // Inline adjust from Inventory table using quantity input
  async function adjustStockFromRow(projectId, materialId, zone, isAdd, inputId){
    try {
      const inp = document.getElementById(inputId);
      let q = inp ? parseInt(inp.value, 10) : 1;
      if (!Number.isFinite(q) || q <= 0) { alert('Enter a positive quantity'); return; }
      const delta = isAdd ? q : -q;
      const payload = { project_id: projectId, material_id: materialId, zone: zone || 'MAIN', quantity: delta };
      const res = await fetch('<?= url('api/logistic_officer/warehouse.php?action=update_stock') ?>', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
      const j = await res.json();
      if (j && j.success) {
        // Reset input to 1 and refresh views
        if (inp) inp.value = 1;
        loadInventory();
        loadLowStock();
      } else {
        alert(j.message || 'Failed to update stock');
      }
    } catch (e) {
      alert('Error: '+ e.message);
    }
  }

  // Initial load for widgets
  loadRecentDeliveries();
  loadLowStock();

  // --- Purchase Orders list helpers ---
  let _poProjects = [];
  let _poSuppliers = [];
  // Track POs received to inventory in this session to hide the Receive button
  const _receivedPOs = new Set();
  // Flag to restore PO list after receiving
  let _poListWasOpenForReceive = false;
  async function preloadPOListFilters(){
    try {
      const projsList = await getProjectsRobust();
      const supList = await getSuppliersRobust();
      _poProjects = Array.isArray(projsList) ? projsList : [];
      _poSuppliers = Array.isArray(supList) ? supList : [];
      const projSel = document.getElementById('po_filter_project');
      const supSel = document.getElementById('po_filter_supplier');
      if (projSel) {
        const opts = _poProjects.map(p=>`<option value="${p.id}">${p.name||('Project #'+p.id)}</option>`).join('');
        projSel.innerHTML = '<option value="">All projects</option>' + opts;
      }
      if (supSel) {
        const opts2 = _poSuppliers.map(s=>`<option value="${s.id}">${s.name||('Supplier #'+s.id)}</option>`).join('');
        supSel.innerHTML = '<option value="">All suppliers</option>' + opts2;
      }
    } catch(e) { /* ignore */ }
  }

  function _nameById(list, id){
    id = String(id);
    const f = list.find(x=>String(x.id)===id);
    return f ? (f.name || ('#'+id)) : ('#'+id);
  }

  async function loadPOList(){
    const tbody = document.getElementById('poListBody');
    if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="text-muted">Loading...</td></tr>';
    const f = document.getElementById('poListFilters');
    const data = Object.fromEntries(new FormData(f).entries());
    const qs = new URLSearchParams();
    if (data.status) qs.set('status', data.status);
    if (data.project_id) qs.set('project_id', data.project_id);
    if (data.supplier_id) qs.set('supplier_id', data.supplier_id);
    qs.set('limit','200');
    try {
      const r = await fetch('<?= url('api/logistic_officer/purchase_orders.php') ?>' + '?' + qs.toString());
      const j = await r.json();
      if (!j.success) throw new Error(j.message||'Failed');
      const rows = (j.data||[]).map(po=>{
        const proj = _nameById(_poProjects, po.project_id);
        const sup  = _nameById(_poSuppliers, po.supplier_id);
        const total = Number(po.total_amount||0).toFixed(2);
        const created = po.created_at || '';
        const printBtn = `<button class=\"btn btn-sm btn-outline-primary\" onclick=\"printPO(${po.id})\">Print</button>`;
        const canReceive = (po.status==='approved'||po.status==='ordered'||po.status==='delivered') && !_receivedPOs.has(po.id);
        const receiveBtn = canReceive ? ` <button id=\"po_receive_btn_${po.id}\" class=\"btn btn-sm btn-success\" onclick=\"openReceivePOModal(${po.id}, ${po.project_id})\">Receive</button>` : '';
        return `<tr>
          <td>${po.id}</td>
          <td>${proj}</td>
          <td>${sup}</td>
          <td>${total}</td>
          <td>${po.status||''}</td>
          <td>${created}</td>
          <td class="text-end">${printBtn}${receiveBtn}</td>
        </tr>`;
      }).join('');
      if (tbody) tbody.innerHTML = rows || '<tr><td colspan="7" class="text-muted">No results</td></tr>';
    } catch(e){ if (tbody) tbody.innerHTML = `<tr><td colspan="7" class="text-danger">${e.message}</td></tr>`; }
  }

  function printPO(id){
    const u = '<?= url('api/purchase_orders/print.php') ?>' + '?id=' + encodeURIComponent(id);
    window.open(u, '_blank');
  }

  // Receive PO to Inventory handlers
  async function openReceivePOModal(poId, projectId){
    try {
      // If PO list modal is open, hide it to avoid stacking issues
      const poListEl = document.getElementById('poListModal');
      _poListWasOpenForReceive = poListEl && poListEl.classList.contains('show');
      if (_poListWasOpenForReceive) {
        const inst = bootstrap.Modal.getInstance(poListEl);
        if (inst) inst.hide();
      }
      // Load PO with items
      const r = await fetch('<?= url('api/logistic_officer/purchase_orders.php') ?>?id='+encodeURIComponent(poId)+'&include_items=1');
      const j = await r.json();
      if (!j.success) throw new Error(j.message||'Failed to load PO');
      const order = j.data.order; const items = j.data.items || [];
      document.getElementById('receive_po_id').value = order.id;
      document.getElementById('receive_po_project').value = _nameById(_poProjects, order.project_id);
      const tbody = document.getElementById('receivePOItemsBody');
      tbody.innerHTML = items.length ? items.map((it,i)=>`<tr><td>${i+1}</td><td>${it.product_name||it.product_id}</td><td>${it.quantity}</td><td>${it.product_unit||''}</td></tr>`).join('') : '<tr><td colspan="4" class="text-muted">No items</td></tr>';
      new bootstrap.Modal(document.getElementById('receivePOModal')).show();
      document.getElementById('receivePOMsg').textContent = '';
    } catch(e){ alert(e.message); }
  }

  async function submitReceivePO(){
    try {
      const poId = parseInt(document.getElementById('receive_po_id').value,10);
      const zone = (document.getElementById('receive_po_zone').value||'MAIN').trim()||'MAIN';
      const res = await fetch('<?= url('api/logistic_officer/purchase_orders.php?action=receive_to_inventory') ?>', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ po_id: poId, zone }) });
      const j = await res.json();
      const msgEl = document.getElementById('receivePOMsg');
      if (j.success) {
        msgEl.innerHTML = '<span class="text-success">Received into inventory.</span> ' + (j.items? ('Items: '+j.items.length):'');
        // Refresh inventory/low stock
        if (typeof loadInventory==='function') loadInventory();
        if (typeof loadLowStock==='function') loadLowStock();
        // Hide the Receive button for this PO (in current view) and remember this PO as received
        _receivedPOs.add(poId);
        const btn = document.getElementById('po_receive_btn_'+poId);
        if (btn) { btn.style.display = 'none'; }
        // Close the receive modal and restore PO list if it was open
        const recvEl = document.getElementById('receivePOModal');
        const inst = bootstrap.Modal.getInstance(recvEl);
        if (inst) inst.hide();
        if (_poListWasOpenForReceive) {
          const poListEl = document.getElementById('poListModal');
          if (poListEl) new bootstrap.Modal(poListEl).show();
          // Also refresh the PO list to reflect hidden button
          if (typeof loadPOList==='function') loadPOList();
        }
      } else {
        msgEl.textContent = j.message || 'Failed to receive';
      }
    } catch(e){
      const msgEl = document.getElementById('receivePOMsg');
      if (msgEl) msgEl.textContent = e.message;
    }
  }

  // Lightweight API health checks for clarity
  (async function pingApis(){
    try {
      const [d, p, w, i] = await Promise.all([
        fetch('<?= url('api/logistic_officer/deliveries.php') ?>?limit=1').then(r=>r.json()).catch(()=>({success:false})),
        fetch('<?= url('api/logistic_officer/purchase_orders.php') ?>?limit=1').then(r=>r.json()).catch(()=>({success:false})),
        fetch('<?= url('api/logistic_officer/warehouse.php') ?>?limit=1').then(r=>r.json()).catch(()=>({success:false})),
        fetch('<?= url('api/logistic_officer/incidents.php') ?>?limit=1').then(r=>r.json()).catch(()=>({success:false})),
      ]);
      const ok = (x)=>x && x.success === true;
      const set = (id, good)=>{
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = good ? 'OK' : 'ERR';
        el.classList.remove('bg-secondary','bg-success','bg-danger');
        el.classList.add(good ? 'bg-success' : 'bg-danger');
        el.title = good ? 'Reachable' : 'Unavailable';
      };
      set('statusDeliveries', ok(d));
      set('statusPOs', ok(p));
      set('statusWarehouse', ok(w));
      set('statusIncidents', ok(i));
    } catch(e){ /* ignore */ }
  })();
</script>
<?php include_once __DIR__ . '/../components/messaging_nav.php'; ?>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
