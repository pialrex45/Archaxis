<?php
// Estimate & Tax Calculator - self-contained UI page
// Uses existing /api/products and /api/suppliers endpoints (no new tables)
// Provides: add line items (labor/material/equipment), select supplier & product, auto-fill unit price, tax 10-15%, print to PDF

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/helpers.php';

// Allow guest usage (read-only calculator) — hide project/import/export for unauthenticated users
$isGuest = function_exists('isAuthenticated') ? !isAuthenticated() : true;

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Estimate & Tax Calculator</title>
  <link rel="stylesheet" href="./style.css" />
</head>
<body>
  <div class="container">
    <header class="page-header">
      <div class="page-title">
        <h1>Estimate & Tax Calculator</h1>
        <p class="muted">Build itemized estimates by project—auto-fill from products or plan, apply tax, export to finance, and print.</p>
      </div>
      <div class="header-actions">
        <button id="printBtn" class="btn primary">Print / Save as PDF</button>
        <button id="clearBtn" class="btn danger-outline">Clear Items</button>
      </div>
    </header>

    <section class="controls card">
      <div class="row grid-7">
        <div class="field" id="projectField">
          <label for="projectSelect">Project</label>
          <select id="projectSelect">
            <option value="">Loading projects...</option>
          </select>
        </div>
        <div class="field">
          <label for="supplierSelect">Supplier</label>
          <select id="supplierSelect">
            <option value="">Loading suppliers...</option>
          </select>
        </div>
        <div class="field">
          <label for="productSelect">Product</label>
          <select id="productSelect">
            <option value="">Select supplier first</option>
          </select>
        </div>
        <div class="field">
          <label for="categorySelect">Category</label>
          <select id="categorySelect">
            <option value="materials">Materials</option>
            <option value="labor">Labor</option>
            <option value="equipment">Equipment</option>
          </select>
        </div>
        <div class="field">
          <label for="descriptionInput">Description</label>
          <input id="descriptionInput" placeholder="e.g., 1000 bricks / Mason hours" />
        </div>
        <div class="field">
          <label for="unitPriceInput">Unit Price</label>
          <input id="unitPriceInput" type="number" step="0.01" placeholder="Auto from product" />
        </div>
        <div class="field field-qty">
          <label for="quantityInput">Quantity</label>
          <input id="quantityInput" type="number" step="0.01" value="1" />
        </div>
      </div>
      <div class="toolbar-row">
        <div class="left">
          <button id="addItemBtn" class="btn primary">Add Item</button>
          <button id="planBtn" class="btn">Estimate from Plan</button>
          <button id="importBtn" class="btn">Import last estimate</button>
        </div>
        <div class="right">
          <button id="exportBtn" class="btn success">Export to Finance (project)</button>
        </div>
      </div>
    </section>

    <section class="table-wrap card" id="printArea">
      <h2>Estimate Details</h2>
      <div class="estimate-meta">
        <div>Project: <strong id="projectNameDisplay">—</strong></div>
        <div>Date: <span id="printDateDisplay"></span></div>
      </div>
      <table id="itemsTable">
        <thead>
          <tr>
            <th>#</th>
            <th>Category</th>
            <th>Description</th>
            <th>Supplier</th>
            <th>Product</th>
            <th>Unit</th>
            <th>Unit Price</th>
            <th>Qty</th>
            <th>Subtotal</th>
            <th class="actions-col"></th>
          </tr>
        </thead>
        <tbody id="itemsBody"></tbody>
        <tfoot>
          <tr>
            <td colspan="8" class="right">Materials Total</td>
            <td id="materialsTotal">0.00</td>
            <td></td>
          </tr>
          <tr>
            <td colspan="8" class="right">Labor Total</td>
            <td id="laborTotal">0.00</td>
            <td></td>
          </tr>
          <tr>
            <td colspan="8" class="right">Equipment Total</td>
            <td id="equipmentTotal">0.00</td>
            <td></td>
          </tr>
          <tr>
            <td colspan="8" class="right">Subtotal</td>
            <td id="subTotal">0.00</td>
            <td></td>
          </tr>
          <tr>
            <td colspan="7"></td>
            <td class="right">
              Tax (%)
              <input id="taxInput" type="number" min="10" max="15" step="0.1" value="10" class="tax-input" />
            </td>
            <td id="taxAmount">0.00</td>
            <td></td>
          </tr>
          <tr>
            <td colspan="8" class="right total">Grand Total</td>
            <td class="total" id="grandTotal">0.00</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </section>

    <section class="summary-bar">
      <div class="chip muted">Materials: <span id="materialsTotalChip">0.00</span></div>
      <div class="chip muted">Labor: <span id="laborTotalChip">0.00</span></div>
      <div class="chip muted">Equipment: <span id="equipmentTotalChip">0.00</span></div>
      <div class="chip">Subtotal: <strong id="subTotalChip">0.00</strong></div>
      <div class="chip">Tax: <strong id="taxAmountChip">0.00</strong></div>
      <div class="chip primary">Grand Total: <strong id="grandTotalChip">0.00</strong></div>
    </section>
  </div>

  <script>
    window.APP_BASE = '<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>';
    window.IS_GUEST = <?php echo $isGuest ? 'true' : 'false'; ?>;
  </script>
  <script src="./script.js"></script>
</body>
</html>
