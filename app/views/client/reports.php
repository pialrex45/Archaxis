<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth();
requireRole('client');

$pageTitle = 'Client Reports';
$currentPage = 'client_reports';

include_once __DIR__ . '/../layouts/header.php';
include_once __DIR__ . '/../layouts/sidebar.php';
?>
<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
  <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Reports & Analytics</h1>
  </div>

  <div class="row g-3">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">Available Reports</h5>
            <div>
              <select id="reportType" class="form-select form-select-sm" style="width:auto;">
                <option value="">All</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="custom">Custom</option>
              </select>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-striped align-middle" id="clientReportsTable">
              <thead>
                <tr>
                  <th>Title</th>
                  <th>Type</th>
                  <th>Project</th>
                  <th>Date</th>
                  <th>Budget vs Actual</th>
                  <th>Download</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
<script>
const apiClientReports = '<?php echo url('/api/client/reports'); ?>';
let allReports = [];
function renderReports(list){
  const rows = (list||[]).map(r=>{
    const type = (r.type||'').toString().toLowerCase();
    const proj = r.project_name||r.project||r.project_id||'';
    const date = r.generated_at||r.created_at||r.date||'';
    const budget = (r.budget||0);
    const actual = (r.actual||r.actual_spend||0);
    const delta = Number(budget) ? Math.round((Number(actual)/Number(budget))*100) : null;
    const progress = Number.isFinite(delta) ? `<div class=\"progress\" style=\"height:8px;\"><div class=\"progress-bar ${delta>100?'bg-danger':'bg-success'}\" style=\"width:${Math.min(100,delta)}%\"></div></div>` : '';
    const pdf = r.pdf_url||''; const xls = r.xls_url||r.xlsx_url||'';
    const dl = [pdf?`<a href=\"${pdf}\" class=\"btn btn-sm btn-outline-primary me-1\">PDF</a>`:'', xls?`<a href=\"${xls}\" class=\"btn btn-sm btn-outline-secondary\">Excel</a>`:''].join('');
    return `<tr>
      <td>${r.title||r.name||''}</td>
      <td><span class=\"badge bg-secondary\">${type||'n/a'}</span></td>
      <td>${proj}</td>
      <td>${date? new Date(date).toLocaleDateString(): ''}</td>
      <td>${progress}</td>
      <td>${dl||'<span class=\"text-muted\">N/A</span>'}</td>
    </tr>`;
  }).join('');
  document.querySelector('#clientReportsTable tbody').innerHTML = rows || `<tr><td colspan=\"6\" class=\"text-center text-muted\">No reports</td></tr>`;
}
fetch(apiClientReports, {headers:{'X-Requested-With':'XMLHttpRequest'}})
 .then(r=>r.json()).then(json=>{ allReports = json.data||[]; renderReports(allReports); });

document.getElementById('reportType').addEventListener('change', (e)=>{
  const v=(e.target.value||'').toString().toLowerCase();
  if(!v) return renderReports(allReports);
  renderReports(allReports.filter(r => (r.type||'').toString().toLowerCase()===v));
});
</script>
