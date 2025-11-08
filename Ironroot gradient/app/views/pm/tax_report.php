<?php 
// RBAC already checked in router. This view renders filters and consumes /tax/api/breakdown.
?>
<?php include BASE_PATH . '/app/views/layouts/header.php'; ?>

<div class="col-12">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i> Tax Report</h3>
    <div>
      <a class="btn btn-sm btn-outline-secondary" href="<?php echo url('/pm/projects'); ?>"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <form id="taxFilterForm" class="row g-3">
        <div class="col-sm-4">
          <label class="form-label">Role (optional)</label>
          <input type="text" class="form-control" id="roleInput" placeholder="e.g., Logistic Officer">
          <div class="form-text">Leave blank for all roles</div>
        </div>
        <div class="col-sm-3">
          <label class="form-label">From</label>
          <input type="date" class="form-control" id="fromInput">
        </div>
        <div class="col-sm-3">
          <label class="form-label">To</label>
          <input type="date" class="form-control" id="toInput">
        </div>
        <div class="col-sm-2 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i> Load</button>
        </div>
      </form>
    </div>
  </div>

  <div class="row g-3 mb-3" id="summaryCards" style="display:none">
    <div class="col-md-3">
      <div class="card surface-glass border-gradient-primary">
        <div class="card-body">
          <div class="text-muted">Total Payments</div>
          <div class="fs-4 fw-bold" id="sumCount">0</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card surface-glass border-gradient-primary">
        <div class="card-body">
          <div class="text-muted">Total Base Amount</div>
          <div class="fs-4 fw-bold" id="sumBase">0.00</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card surface-glass border-gradient-primary">
        <div class="card-body">
          <div class="text-muted">Total VAT</div>
          <div class="fs-4 fw-bold" id="sumVat">0.00</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card surface-glass border-gradient-primary">
        <div class="card-body">
          <div class="text-muted">Total AIT</div>
          <div class="fs-4 fw-bold" id="sumAit">0.00</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped table-hover" id="taxTable">
          <thead>
            <tr>
              <th>#</th>
              <th>User</th>
              <th>Role</th>
              <th>Project</th>
              <th>Type</th>
              <th class="text-end">Base</th>
              <th class="text-end">VAT (rate)</th>
              <th class="text-end">AIT (rate)</th>
              <th class="text-end">Net Payable</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const form = document.getElementById('taxFilterForm');
  const tbody = document.querySelector('#taxTable tbody');
  const summary = document.getElementById('summaryCards');
  const sumCount = document.getElementById('sumCount');
  const sumBase = document.getElementById('sumBase');
  const sumVat = document.getElementById('sumVat');
  const sumAit = document.getElementById('sumAit');

  function fmt(n){ return (Number(n)||0).toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2}); }

  function todayRange(){
    const d = new Date();
    const y = d.getFullYear();
    const m = d.getMonth();
    const first = new Date(y, m, 1);
    const last = new Date(y, m+1, 0);
    return { from: first.toISOString().slice(0,10), to: last.toISOString().slice(0,10) };
  }

  function load(initial){
    const role = document.getElementById('roleInput').value.trim();
    let from = document.getElementById('fromInput').value;
    let to = document.getElementById('toInput').value;
    if (!from || !to){ const r = todayRange(); from = r.from; to = r.to; }

    const params = new URLSearchParams();
    if (role) params.set('role', role);
    params.set('from', from);
    params.set('to', to);

    tbody.innerHTML = '<tr><td colspan="9">Loading...</td></tr>';
    fetch('<?php echo url('/tax/api/breakdown'); ?>' + '?' + params.toString(), { credentials:'same-origin' })
      .then(r=>r.json())
      .then(j=>{
        if (!j || !j.success){ tbody.innerHTML = '<tr><td colspan="9">Failed to load</td></tr>'; return; }
        const rows = Array.isArray(j.data) ? j.data : [];
        let html = '';
        let totalBase=0, totalVat=0, totalAit=0;
        rows.forEach((row, idx)=>{
          const base = Number(row.base_amount)||0;
          const vat = Number(row.vat_amount)||0;
          const ait = Number(row.ait_amount)||0;
          const net = Number(row.net_payable)||0;
          totalBase += base; totalVat += vat; totalAit += ait;
          html += '<tr>'+
            '<td>'+(idx+1)+'</td>'+
            '<td>'+ (row.user_name||'') +'</td>'+
            '<td>'+ (row.user_role||'') +'</td>'+
            '<td>'+ (row.project_name||'') +'</td>'+
            '<td>'+ (row.project_type||'') +'</td>'+
            '<td class="text-end">'+ fmt(base) +'</td>'+
            '<td class="text-end">'+ fmt(vat) +' ('+ ((Number(row.vat_rate)||0)*100).toFixed(0)+'%)</td>'+
            '<td class="text-end">'+ fmt(ait) +' ('+ ((Number(row.ait_rate)||0)*100).toFixed(0)+'%)</td>'+
            '<td class="text-end">'+ fmt(net) +'</td>'+
          '</tr>';
        });
        tbody.innerHTML = html || '<tr><td colspan="9">No records</td></tr>';
        sumCount.textContent = rows.length;
        sumBase.textContent = fmt(totalBase);
        sumVat.textContent = fmt(totalVat);
        sumAit.textContent = fmt(totalAit);
        summary.style.display = 'block';
      })
      .catch(()=>{ tbody.innerHTML = '<tr><td colspan="9">Error loading data</td></tr>'; });
  }

  form.addEventListener('submit', function(e){ e.preventDefault(); load(); });
  // Initial load for current month
  const r = todayRange();
  document.getElementById('fromInput').value = r.from;
  document.getElementById('toInput').value = r.to;
  load(true);
})();
</script>

<?php include BASE_PATH . '/app/views/layouts/footer.php'; ?>
