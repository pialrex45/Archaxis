<?php
// Marketplace view - basic product list using shared layout
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth();

$pageTitle = 'Marketplace';
$currentPage = 'marketplace';
?>

<?php include_once __DIR__ . '/../../views/layouts/header.php'; ?>
<?php include_once __DIR__ . '/../../views/layouts/sidebar.php'; ?>

<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Marketplace</h1>
        <div>
            <label for="supplierFilter" class="form-label me-2">Filter by supplier:</label>
            <select id="supplierFilter" class="form-select d-inline-block" style="width:auto;">
                <option value="">All</option>
            </select>
        </div>
    </div>

    <div id="products" class="row g-3"></div>
</div>

<?php include_once __DIR__ . '/../../views/layouts/footer.php'; ?>

<script>
const apiSuppliers = '<?php echo url('/api/suppliers/index'); ?>';
const apiProducts  = '<?php echo url('/api/products/index'); ?>';

async function fetchSuppliers() {
    const res = await fetch(apiSuppliers);
    const json = await res.json();
    return json.data || [];
}
async function fetchProducts(supplierId) {
    const url = supplierId ? `${apiProducts}?supplier_id=${encodeURIComponent(supplierId)}` : apiProducts;
    const res = await fetch(url);
    const json = await res.json();
    return json.data || [];
}
function renderProducts(list) {
    const el = document.getElementById('products');
    el.innerHTML = list.map(p => `
        <div class="col-sm-6 col-md-4 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">${p.name}</h5>
                    <h6 class="card-subtitle mb-2 text-muted">${p.supplier_name || ''}</h6>
                    <p class="card-text">${p.description || ''}</p>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <span class="fw-bold">${p.unit_price} / ${p.unit}</span>
                    <span class="text-muted">Stock: ${p.stock}</span>
                </div>
            </div>
        </div>
    `).join('');
}
async function init() {
    const supplierSelect = document.getElementById('supplierFilter');
    const suppliers = await fetchSuppliers();
    suppliers.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = s.name;
        supplierSelect.appendChild(opt);
    });
    supplierSelect.addEventListener('change', async () => {
        const products = await fetchProducts(supplierSelect.value);
        renderProducts(products);
    });
    const products = await fetchProducts('');
    renderProducts(products);
}
init();
</script>
