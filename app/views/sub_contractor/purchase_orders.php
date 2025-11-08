<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/SubContractorController.php';
require_once __DIR__ . '/../../models/Project.php';
require_once __DIR__ . '/../../models/Supplier.php';

requireAuth();
if (!hasRole('sub_contractor')) { http_response_code(403); die('Access denied. Sub-Contractors only.'); }

// Get all purchase orders for projects assigned to the sub-contractor
$controller = new SubContractorController();
$posResponse = $controller->purchaseOrdersForAssignedProjects(100);
$purchaseOrders = ($posResponse['success'] && isset($posResponse['data'])) ? $posResponse['data'] : [];

// Load projects and suppliers for display
$projectModel = new Project();
$supplierModel = new Supplier();
$projects = [];
$suppliers = [];

// Load data for all projects and suppliers referenced in POs
if (!empty($purchaseOrders)) {
    $projectIds = array_unique(array_column($purchaseOrders, 'project_id'));
    $supplierIds = array_unique(array_column($purchaseOrders, 'supplier_id'));
    
    foreach ($projectIds as $pid) {
        $project = $projectModel->getById($pid);
        if ($project) {
            $projects[$pid] = $project;
        }
    }
    
    foreach ($supplierIds as $sid) {
        $supplier = $supplierModel->getById($sid);
        if ($supplier) {
            $suppliers[$sid] = $supplier;
        }
    }
}

$pageTitle = 'Purchase Orders';
$currentPage = 'purchase_orders';

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Purchase Orders</h1>
  </div>

  <!-- Status Counts -->
  <div class="row mb-4">
    <?php
      $pendingCount = 0;
      $approvedCount = 0;
      $rejectedCount = 0;
      $deliveredCount = 0;
      
      foreach ($purchaseOrders as $po) {
        if ($po['status'] === 'pending') {
          $pendingCount++;
        } elseif ($po['status'] === 'approved') {
          $approvedCount++;
        } elseif ($po['status'] === 'rejected') {
          $rejectedCount++;
        } elseif ($po['status'] === 'delivered') {
          $deliveredCount++;
        }
      }
    ?>
    
    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card border-left-warning shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pendingCount; ?></div>
            </div>
            <div class="col-auto">
              <i class="fas fa-clock fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card border-left-primary shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Approved</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $approvedCount; ?></div>
            </div>
            <div class="col-auto">
              <i class="fas fa-thumbs-up fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card border-left-danger shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Rejected</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $rejectedCount; ?></div>
            </div>
            <div class="col-auto">
              <i class="fas fa-thumbs-down fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card border-left-success shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Delivered</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $deliveredCount; ?></div>
            </div>
            <div class="col-auto">
              <i class="fas fa-truck fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if (empty($purchaseOrders)): ?>
    <div class="alert alert-info">
      <i class="fas fa-info-circle"></i> No purchase orders found.
    </div>
  <?php else: ?>
    <!-- Purchase Orders Table -->
    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Purchase Orders</h6>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="purchaseOrdersTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>PO #</th>
                <th>Project</th>
                <th>Supplier</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($purchaseOrders as $po): ?>
                <tr>
                  <td><?php echo htmlspecialchars($po['id']); ?></td>
                  <td><?php echo htmlspecialchars($projects[$po['project_id']]['name'] ?? 'Unknown Project'); ?></td>
                  <td><?php echo htmlspecialchars($suppliers[$po['supplier_id']]['name'] ?? 'Unknown Supplier'); ?></td>
                  <td>$<?php echo htmlspecialchars(number_format($po['total_amount'], 2)); ?></td>
                  <td>
                    <span class="badge bg-<?php 
                      echo $po['status'] === 'delivered' ? 'success' : 
                          ($po['status'] === 'approved' ? 'primary' : 
                          ($po['status'] === 'rejected' ? 'danger' :
                          ($po['status'] === 'pending' ? 'warning' : 'secondary'))); 
                    ?>">
                      <?php echo htmlspecialchars(ucfirst($po['status'])); ?>
                    </span>
                  </td>
                  <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($po['created_at']))); ?></td>
                  <td>
                    <button class="btn btn-sm btn-info view-po-btn" data-po-id="<?php echo htmlspecialchars($po['id']); ?>">
                      <i class="fas fa-eye"></i> View
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- View Purchase Order Modal -->
<div class="modal fade" id="viewPurchaseOrderModal" tabindex="-1" aria-labelledby="viewPurchaseOrderModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewPurchaseOrderModalLabel">Purchase Order Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="poDetailsContent">
          <div class="text-center">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p>Loading purchase order details...</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Add DataTables for better table functionality -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">

<script>
  $(document).ready(function() {
    $('#purchaseOrdersTable').DataTable({
      "order": [[0, "desc"]]
    });
    
    // Handle view purchase order button clicks
    $('.view-po-btn').on('click', function() {
      const poId = $(this).data('po-id');
      
      // Show the modal
      $('#viewPurchaseOrderModal').modal('show');
      
      // Load purchase order details
      $.ajax({
        url: `<?php echo url('/api/sub_contractor/purchase_orders'); ?>/${poId}`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
          if (response.success && response.data) {
            const po = response.data;
            const projectName = '<?php echo addslashes(json_encode($projects)); ?>';
            const supplierName = '<?php echo addslashes(json_encode($suppliers)); ?>';
            const projects = JSON.parse(projectName);
            const suppliers = JSON.parse(supplierName);
            
            let statusClass = '';
            switch (po.status) {
              case 'delivered': statusClass = 'success'; break;
              case 'approved': statusClass = 'primary'; break;
              case 'rejected': statusClass = 'danger'; break;
              case 'pending': statusClass = 'warning'; break;
              default: statusClass = 'secondary';
            }
            
            let html = `
              <div class="row mb-3">
                <div class="col-md-6">
                  <h6>Purchase Order #${po.id}</h6>
                  <p><strong>Date:</strong> ${new Date(po.created_at).toLocaleDateString()}</p>
                  <p><strong>Status:</strong> <span class="badge bg-${statusClass}">${po.status.charAt(0).toUpperCase() + po.status.slice(1)}</span></p>
                </div>
                <div class="col-md-6">
                  <p><strong>Project:</strong> ${projects[po.project_id]?.name || 'Unknown Project'}</p>
                  <p><strong>Supplier:</strong> ${suppliers[po.supplier_id]?.name || 'Unknown Supplier'}</p>
                  <p><strong>Total Amount:</strong> $${parseFloat(po.total_amount).toFixed(2)}</p>
                </div>
              </div>
            `;
            
            if (po.items && po.items.length > 0) {
              html += `
                <div class="table-responsive mt-4">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                      </tr>
                    </thead>
                    <tbody>
              `;
              
              po.items.forEach(item => {
                html += `
                  <tr>
                    <td>${item.name}</td>
                    <td>${item.quantity}</td>
                    <td>$${parseFloat(item.unit_price).toFixed(2)}</td>
                    <td>$${parseFloat(item.quantity * item.unit_price).toFixed(2)}</td>
                  </tr>
                `;
              });
              
              html += `
                    </tbody>
                  </table>
                </div>
              `;
            } else {
              html += `<div class="alert alert-info mt-3">No item details available for this purchase order.</div>`;
            }
            
            $('#poDetailsContent').html(html);
          } else {
            $('#poDetailsContent').html(`
              <div class="alert alert-danger">
                Failed to load purchase order details. ${response.message || ''}
              </div>
            `);
          }
        },
        error: function() {
          $('#poDetailsContent').html(`
            <div class="alert alert-danger">
              An error occurred while trying to load the purchase order details.
            </div>
          `);
        }
      });
    });
  });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
