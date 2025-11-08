<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';

// Check authentication and role
requireAuth();
// Broaden access so relevant roles can use embedded widgets
if (!hasAnyRole(['admin', 'manager', 'supervisor', 'site_manager', 'project_manager', 'general_manager', 'client', 'site_engineer'])) {
    http_response_code(403);
    die('Access denied.');
}

$pageTitle = 'Attendance';
$currentPage = 'attendance';
$csrf = generate_csrf_token();
// Expose role to JS for role-aware UI
$currentRole = strtolower(getCurrentUserRole() ?? '');
?>

<?php include_once __DIR__ . '/../layouts/header.php'; ?>
<?php include_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Attendance Management</h1>
    </div>

    <!-- KPI Header styled like the reference screenshot -->
    <div class="row g-3 mb-3">
      <div class="col-sm-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100 bg-gradient-success text-white">
          <div class="card-body py-3">
            <div class="small fw-semibold">Present (Today)</div>
            <div id="kpi_present" class="display-6 fw-bold">0</div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100 bg-gradient-warning text-white">
          <div class="card-body py-3">
            <div class="small fw-semibold">Late (Today)</div>
            <div id="kpi_late" class="display-6 fw-bold">0</div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100 bg-gradient-danger text-white">
          <div class="card-body py-3">
            <div class="small fw-semibold">Absent (Today)</div>
            <div id="kpi_absent" class="display-6 fw-bold">0</div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100 bg-gradient-primary text-white">
          <div class="card-body py-3">
            <div class="small fw-semibold">Pending Approvals</div>
            <div id="kpi_pending" class="display-6 fw-bold">0</div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <!-- Embedded widgets -->
                    <?php if (hasAnyRole(['admin','supervisor','site_manager'])): ?>
                    <div class="mb-4">
                        <h5 class="mb-2">Approvals Queue</h5>
                        <div class="d-flex gap-2 align-items-end mb-2 flex-wrap toolbar">
                            <div>
                                <label class="form-label">From</label>
                                <input type="date" id="ap_from" class="form-control form-control-sm">
                            </div>
                            <div>
                                <label class="form-label">To</label>
                                <input type="date" id="ap_to" class="form-control form-control-sm">
                            </div>
                            <div>
                                <label class="form-label">Status</label>
                                <select id="ap_status" class="form-select form-select-sm">
                                    <option value="">Any</option>
                                    <option value="present">Present</option>
                                    <option value="late">Late</option>
                                    <option value="manual">Manual</option>
                                    <option value="absent">Absent</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Approval</label>
                                <select id="ap_approval" class="form-select form-select-sm">
                                    <option value="">Any</option>
                                    <option value="pending">Pending</option>
                                    <option value="supervisor_approved">Supervisor Approved</option>
                                    <option value="site_manager_approved">Final Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <button id="ap_load" class="btn btn-sm btn-secondary">Load</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="ap_sel_all"></th>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Project</th>
                                        <th>Check-in</th>
                                        <th>Check-out</th>
                                        <th>Status</th>
                                        <th>Approval</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="ap_rows">
                                    <tr><td colspan="9">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-2">
                          <button id="ap_bulk_approve" class="btn btn-sm btn-success">Approve Selected</button>
                          <button id="ap_bulk_reject" class="btn btn-sm btn-danger">Reject Selected</button>
                          <button id="ap_bulk_final" class="btn btn-sm btn-primary">Final Approve Selected</button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (hasAnyRole(['admin','site_manager','project_manager','general_manager','client','site_engineer'])): ?>
                    <div class="mb-2">
                        <h5 class="mb-2">Attendance Reports</h5>
                        <div class="d-flex gap-2 align-items-end mb-2 flex-wrap">
                            <div>
                                <label class="form-label">From</label>
                                <input type="date" id="rp_from" class="form-control form-control-sm">
                            </div>
                            <div>
                                <label class="form-label">To</label>
                                <input type="date" id="rp_to" class="form-control form-control-sm">
                            </div>
                            <div>
                                <label class="form-label">User</label>
                                <input type="number" id="rp_user" class="form-control form-control-sm" placeholder="User ID">
                            </div>
                            <div>
                                <label class="form-label">Project</label>
                                <input type="number" id="rp_project" class="form-control form-control-sm" placeholder="Project ID">
                            </div>
                            <div>
                                <label class="form-label">Status</label>
                                <select id="rp_status" class="form-select form-select-sm">
                                    <option value="">Any</option>
                                    <option value="present">Present</option>
                                    <option value="late">Late</option>
                                    <option value="manual">Manual</option>
                                    <option value="absent">Absent</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Approval</label>
                                <select id="rp_approval" class="form-select form-select-sm">
                                    <option value="">Any</option>
                                    <option value="pending">Pending</option>
                                    <option value="supervisor_approved">Supervisor Approved</option>
                                    <option value="site_manager_approved">Final Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <button id="rp_apply" class="btn btn-sm btn-secondary">Apply</button>
                            <button id="rp_export" class="btn btn-sm btn-outline-primary">Export CSV</button>
                            <button id="rp_print" class="btn btn-sm btn-outline-secondary">Print</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Project</th>
                                        <th>Check-in</th>
                                        <th>Check-out</th>
                                        <th>Status</th>
                                        <th>Approval</th>
                                    </tr>
                                </thead>
                                <tbody id="rp_rows">
                                    <tr><td colspan="7">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Weekly Calendar View -->
    <div class="row mt-3">
      <div class="col-md-12">
        <div class="card">
          <div class="card-body">
            <h5 class="mb-2">Weekly Calendar</h5>
            <div class="d-flex gap-2 align-items-end mb-2 flex-wrap">
              <div>
                <label class="form-label">Week start (Mon)</label>
                <input type="date" id="wk_start" class="form-control form-control-sm">
              </div>
              <div>
                <label class="form-label">User</label>
                <input type="number" id="wk_user" class="form-control form-control-sm" placeholder="User ID (optional)">
              </div>
              <div>
                <label class="form-label">Shift Start</label>
                <input type="time" id="wk_shift_start" value="09:00" class="form-control form-control-sm">
              </div>
              <div>
                <label class="form-label">Shift End</label>
                <input type="time" id="wk_shift_end" value="18:00" class="form-control form-control-sm">
              </div>
              <div>
                <label class="form-label">Grace (min)</label>
                <input type="number" id="wk_grace" value="10" class="form-control form-control-sm" style="width:100px">
              </div>
              <button id="wk_load" class="btn btn-sm btn-secondary">Load Week</button>
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-striped align-middle">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Hours</th>
                    <th>Flags</th>
                  </tr>
                </thead>
                <tbody id="wk_rows"><tr><td colspan="5">Loading...</td></tr></tbody>
                <tfoot>
                  <tr>
                    <th colspan="3">Weekly Total</th>
                    <th id="wk_total">0</th>
                    <th id="wk_overtime">Overtime: 0</th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
</div>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>

<style>
/* Additive minor UI polish for attendance page */
.card .table thead th { position: sticky; top: 0; background: #fff; z-index: 1; }
.btn-xs { padding: .15rem .35rem; font-size: .75rem; line-height: 1.2; }
.toolbar { gap: .5rem; }
.toolbar .form-control-sm, .toolbar .form-select-sm { min-width: 110px; }
.table td, .table th { vertical-align: middle; }
/* Subtle card polish */
.card { border-radius: .5rem; }
.card > .card-body > h5 { font-weight: 600; }
.kpi-card .display-6 { font-size: 2rem; }
.badge-soft { background: #f2f6ff; color: #1b4dd7; border: 1px solid #dbe6ff; }
</style>

<script>
(function(){
  const apiBase = "<?= url('/api/attendance') ?>";
  const csrf = "<?= $csrf ?>";
  const currentRole = "<?= $currentRole ?>";
  // Simple in-page reminder: if checked-in > 9h and no checkout, show alert banner
  let reminderShown = false;

  // Build a robust URL and try fallback to path-only if absolute fails (handles subdir/public quirks)
  function makePrimary(urlPath) { return `${apiBase}${urlPath}`; }
  function makeFallback(urlPath) {
    try {
      const pathOnly = "<?= parse_url(url('/api/attendance'), PHP_URL_PATH) ?>"; // e.g. /Ironroot/api/attendance
      return `${pathOnly}${urlPath}`;
    } catch(e) { return urlPath; }
  }
  // Add light cache-busting to avoid stale responses after approve actions
  function withCacheBust(path){
    try {
      const u = new URL(path, window.location.origin);
      u.searchParams.set('_cb', Date.now().toString());
      return u.pathname + '?' + u.searchParams.toString();
    } catch(e){
      const j = path.includes('?') ? '&' : '?';
      return path + j + '_cb=' + Date.now();
    }
  }
  async function fetchJsonWithFallback(urlPath, options = {}) {
    // Ensure cookies/session are sent to PHP for auth/CSRF
    options.credentials = options.credentials || 'same-origin';
    const isGet = !options.method || String(options.method).toUpperCase() === 'GET';
    // Add cache-busting param for GET requests
    let path = urlPath;
    if (isGet) {
      const sep = urlPath.includes('?') ? '&' : '?';
      path = `${urlPath}${sep}_cb=${Date.now()}`;
    }
    // Ensure no-cache header on all requests
    const headers = Object.assign({}, options.headers || {}, { 'Cache-Control': 'no-cache' });
    let res;
    try { res = await fetch(makePrimary(path), Object.assign({}, options, { headers })); } catch(e) { /* network error */ }
    if (!res || !res.ok) {
      try { res = await fetch(makeFallback(path), Object.assign({}, options, { headers })); } catch(e2) { /* ignore */ }
    }
    return res;
  }

  // Helpers to get last 7 days in yyyy-mm-dd
  function fmt(d){ return d.toISOString().slice(0,10); }
  function setDefaultDates(prefix){
    const to = document.getElementById(prefix + '_to');
    const from = document.getElementById(prefix + '_from');
    if (to && !to.value){ const d = new Date(); to.value = fmt(d); }
    if (from && !from.value){ const d = new Date(); d.setDate(d.getDate()-7); from.value = fmt(d); }
  }

  // Approvals widget
  const apRows = document.getElementById('ap_rows');
  const apLoadBtn = document.getElementById('ap_load');
  function apSetLoading(loading){
    if (!apLoadBtn) return;
    if (loading){ apLoadBtn.disabled = true; apLoadBtn.dataset._txt = apLoadBtn.textContent; apLoadBtn.textContent = 'Loading...'; }
    else { apLoadBtn.disabled = false; if (apLoadBtn.dataset._txt) apLoadBtn.textContent = apLoadBtn.dataset._txt; }
  }
  async function apLoad(){
    apSetLoading(true);
    if (!apRows) return;
    // defaults to show relevant data quickly
    setDefaultDates('ap');
    const p = new URLSearchParams();
    const from = document.getElementById('ap_from')?.value;
    const to = document.getElementById('ap_to')?.value;
    const status = document.getElementById('ap_status')?.value;
    // default approval to pending for supervisors/site managers/admins
    const approvalSel = document.getElementById('ap_approval');
    let approval = approvalSel?.value;
    if (!approval) { approval = 'pending'; if (approvalSel) approvalSel.value = 'pending'; }
    if (from) p.append('from', from);
    if (to) p.append('to', to);
    if (status) p.append('status', status);
    if (approval) p.append('approval_status', approval);
    let json;
    try {
      const res = await fetchJsonWithFallback(`/list?${p.toString()}`);
      if (!res || !res.ok) {
        const txt = res ? await res.text() : 'No response';
        apRows.innerHTML = `<tr><td colspan="9">Failed to load approvals (${res?res.status:0}): ${String(txt).replace(/</g,'&lt;')}</td></tr>`;
        apSetLoading(false);
        return;
      }
      json = await res.json().catch(()=>({success:false,message:'Invalid JSON'}));
    } catch(e) {
      apRows.innerHTML = '<tr><td colspan="9">Failed to load approvals</td></tr>';
      apSetLoading(false);
      return;
    }
    apRows.innerHTML = '';
    if (!json.success || !Array.isArray(json.data) || json.data.length===0){
      apRows.innerHTML = '<tr><td colspan="9">No records</td></tr>'; apSetLoading(false); return;
    }
    json.data.forEach(r=>{
      const tr = document.createElement('tr');
      const actionsHtml = (function(){
        if (currentRole === 'supervisor' || currentRole === 'admin') {
          return `
            <div class="d-inline-flex gap-1">
              <button class="btn btn-xs btn-success rounded-pill px-2" onclick="apApprove(${r.id}, 'approved')">Supervisor Approve</button>
              <button class="btn btn-xs btn-outline-danger rounded-pill px-2" onclick="apApprove(${r.id}, 'rejected')">Reject</button>
            </div>
          `;
        }
        if (currentRole === 'site_manager' || currentRole === 'admin') {
          return `<button class=\"btn btn-xs btn-primary rounded-pill px-2\" onclick=\"apFinal(${r.id}, 'approved')\">Final Approve</button>`;
        }
        return '';
      })();
      const statusCls = (function(s){
        switch((s||'').toLowerCase()){
          case 'present': return 'bg-success';
          case 'late': return 'bg-warning';
          case 'manual': return 'bg-info';
          case 'absent': return 'bg-danger';
          default: return 'bg-light text-dark';
        }
      })(r.status);
      const apprCls = (function(a){
        switch((a||'').toLowerCase()){
          case 'pending': return 'bg-warning';
          case 'rejected': return 'bg-danger';
          case 'supervisor_approved':
          case 'site_manager_approved':
          case 'approved': return 'bg-success';
          default: return 'bg-light text-dark';
        }
      })(r.approval_status);
      tr.innerHTML = `
        <td><input type="checkbox" class="ap_sel" value="${r.id ?? ''}"></td>
        <td>${r.id ?? ''}</td>
        <td>${r.user_name ?? r.user_id ?? ''}</td>
        <td>${r.project_name ?? r.project_id ?? ''}</td>
        <td>${r.check_in ?? ''}</td>
        <td>${r.check_out ?? ''}</td>
        <td><span class="badge ${statusCls}">${r.status ?? ''}</span></td>
        <td><span class="badge ${apprCls}">${r.approval_status ?? ''}</span></td>
        <td>${actionsHtml}</td>`;
      apRows.appendChild(tr);
    });
    apSetLoading(false);
  }
  async function apApprove(id, action){
    const body = new URLSearchParams(); body.append('csrf_token', csrf); body.append('attendance_id', id); body.append('action', action);
    const res = await fetchJsonWithFallback('/approve', {method:'POST', body, headers: {'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','Accept':'application/json'}});
    if (!res) { alert('Network error'); return; }
    let j;
    try {
      j = await res.json();
    } catch(err) {
      const t = await res.text().catch(()=> '');
      console.error('Approve JSON parse failed', {status: res.status, headers: Object.fromEntries(res.headers.entries()), body: t});
      alert(`Approve failed (${res.status})\nInvalid JSON: ${String(t).slice(0,300)}`);
      return;
    }
    alert(j.message || (j.success?'OK':'Failed'));
    apLoad();
    loadKpis();
  }
  async function apFinal(id, action){
    const body = new URLSearchParams(); body.append('csrf_token', csrf); body.append('attendance_id', id); body.append('action', action);
    const res = await fetchJsonWithFallback('/final_approve', {method:'POST', body, headers: {'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','Accept':'application/json'}});
    if (!res) { alert('Network error'); return; }
    let j;
    try {
      j = await res.json();
    } catch(err) {
      const t = await res.text().catch(()=> '');
      console.error('Final approve JSON parse failed', {status: res.status, headers: Object.fromEntries(res.headers.entries()), body: t});
      alert(`Final approve failed (${res.status})\nInvalid JSON: ${String(t).slice(0,300)}`);
      return;
    }
    alert(j.message || (j.success?'OK':'Failed'));
    apLoad();
    loadKpis();
  }
  window.apApprove = apApprove; window.apFinal = apFinal;
  document.getElementById('ap_load')?.addEventListener('click', apLoad);

  // Bulk actions
  document.getElementById('ap_sel_all')?.addEventListener('change', (e)=>{
    document.querySelectorAll('.ap_sel').forEach(cb=> cb.checked = e.target.checked);
  });
  async function runBulk(fn){
    const ids = Array.from(document.querySelectorAll('.ap_sel:checked')).map(cb=>cb.value).filter(Boolean);
    if (ids.length===0) { alert('No rows selected'); return; }
    for (const id of ids) { await fn(id); }
    apLoad();
    loadKpis();
  }
  function setBulkLoading(on){
    ['ap_bulk_approve','ap_bulk_reject','ap_bulk_final'].forEach(id=>{
      const b = document.getElementById(id); if(!b) return;
      if (on){ b.disabled = true; if(!b.dataset._txt){ b.dataset._txt = b.textContent; } b.textContent = 'Working...'; }
      else { b.disabled = false; if (b.dataset._txt){ b.textContent = b.dataset._txt; } }
    });
  }
  document.getElementById('ap_bulk_approve')?.addEventListener('click', async ()=>{ setBulkLoading(true); await runBulk((id)=> apApprove(id,'approved')); setBulkLoading(false); });
  document.getElementById('ap_bulk_reject')?.addEventListener('click', async ()=>{ setBulkLoading(true); await runBulk((id)=> apApprove(id,'rejected')); setBulkLoading(false); });
  document.getElementById('ap_bulk_final')?.addEventListener('click', async ()=>{ setBulkLoading(true); await runBulk((id)=> apFinal(id,'approved')); setBulkLoading(false); });

  // Reports widget
  const rpRows = document.getElementById('rp_rows');
  function rpParams(){
    const p = new URLSearchParams();
    const from = document.getElementById('rp_from')?.value;
    const to = document.getElementById('rp_to')?.value;
    const user = document.getElementById('rp_user')?.value;
    const proj = document.getElementById('rp_project')?.value;
    const status = document.getElementById('rp_status')?.value;
    const approval = document.getElementById('rp_approval')?.value;
    if (from) p.append('from', from); if (to) p.append('to', to);
    if (user) p.append('user_id', user); if (proj) p.append('project_id', proj);
    if (status) p.append('status', status); if (approval) p.append('approval_status', approval);
    return p;
  }
  async function rpLoad(){
    const rpApplyBtn = document.getElementById('rp_apply');
    function rpSetLoading(loading){
      if (!rpApplyBtn) return;
      if (loading){ rpApplyBtn.disabled = true; rpApplyBtn.dataset._txt = rpApplyBtn.textContent; rpApplyBtn.textContent = 'Loading...'; }
      else { rpApplyBtn.disabled = false; if (rpApplyBtn.dataset._txt) rpApplyBtn.textContent = rpApplyBtn.dataset._txt; }
    }
    rpSetLoading(true);
    if (!rpRows) return;
    setDefaultDates('rp');
    const p = rpParams();
    let json;
    try {
      const res = await fetchJsonWithFallback(`/list?${p.toString()}`);
      if (!res || !res.ok) {
        const txt = res ? await res.text() : 'No response';
        rpRows.innerHTML = `<tr><td colspan="7">Failed to load reports (${res?res.status:0}): ${String(txt).replace(/</g,'&lt;')}</td></tr>`;
        rpSetLoading(false); return;
      }
      json = await res.json().catch(()=>({success:false,message:'Invalid JSON'}));
    } catch(e) {
      rpRows.innerHTML = '<tr><td colspan="7">Failed to load reports</td></tr>';
      rpSetLoading(false); return;
    }
    rpRows.innerHTML = '';
    if (!json.success || !Array.isArray(json.data) || json.data.length===0){
      rpRows.innerHTML = '<tr><td colspan="7">No records</td></tr>'; rpSetLoading(false); return;
    }
    json.data.forEach(r=>{
      const tr = document.createElement('tr');
      const statusCls = (function(s){
        switch((s||'').toLowerCase()){
          case 'present': return 'bg-success';
          case 'late': return 'bg-warning';
          case 'manual': return 'bg-info';
          case 'absent': return 'bg-danger';
          default: return 'bg-light text-dark';
        }
      })(r.status);
      const apprCls = (function(a){
        switch((a||'').toLowerCase()){
          case 'pending': return 'bg-warning';
          case 'rejected': return 'bg-danger';
          case 'supervisor_approved':
          case 'site_manager_approved':
          case 'approved': return 'bg-success';
          default: return 'bg-light text-dark';
        }
      })(r.approval_status);
      tr.innerHTML = `
        <td>${r.id ?? ''}</td>
        <td>${r.user_name ?? r.user_id ?? ''}</td>
        <td>${r.project_name ?? r.project_id ?? ''}</td>
        <td>${r.check_in ?? ''}</td>
        <td>${r.check_out ?? ''}</td>
        <td><span class="badge ${statusCls}">${r.status ?? ''}</span></td>
        <td><span class="badge ${apprCls}">${r.approval_status ?? ''}</span></td>`;
      rpRows.appendChild(tr);
    });
    rpSetLoading(false);
  }
  function rpExport(){
    const p = rpParams();
    const primary = makePrimary(`/export?${p.toString()}`);
    const fallback = makeFallback(`/export?${p.toString()}`);
    // Try opening primary; if it fails due to CORS/404, user can use fallback
    const w = window.open(primary, '_blank');
    setTimeout(()=>{ if (!w || w.closed) window.open(fallback, '_blank'); }, 500);
  }
  function rpPrint(){
    const table = document.querySelector('#rp_rows')?.closest('table');
    if (!table) return;
    const w = window.open('', '_blank');
    w.document.write('<html><head><title>Attendance Report</title>');
    w.document.write('<link rel="stylesheet" href="<?= url('/public/assets/css/bootstrap.min.css') ?>">');
    w.document.write('</head><body>');
    w.document.write(table.outerHTML);
    w.document.write('</body></html>');
    w.document.close(); w.focus();
    w.print();
  }
  document.getElementById('rp_apply')?.addEventListener('click', rpLoad);
  document.getElementById('rp_export')?.addEventListener('click', rpExport);
  document.getElementById('rp_print')?.addEventListener('click', rpPrint);

  // Weekly calendar utilities
  function getMonday(d){ const dt = new Date(d); const day = dt.getDay(); const diff = (day===0?-6:1) - day; dt.setDate(dt.getDate()+diff); dt.setHours(0,0,0,0); return dt; }
  function parseTimeStr(str){ const [h,m] = (str||'').split(':').map(Number); if (isNaN(h)||isNaN(m)) return null; return {h, m}; }
  function diffHours(a,b){ const ms = b-a; return Math.max(0, Math.round(ms/36e4)/10); } // 0.1h precision
  function asDate(dateStr){ const d = new Date(dateStr); d.setHours(0,0,0,0); return d; }
  function ymd(d){ return d.toISOString().slice(0,10); }
  async function wkLoad(){
    const wkLoadBtn = document.getElementById('wk_load');
    function wkSetLoading(loading){
      if (!wkLoadBtn) return;
      if (loading){ wkLoadBtn.disabled = true; wkLoadBtn.dataset._txt = wkLoadBtn.textContent; wkLoadBtn.textContent = 'Loading...'; }
      else { wkLoadBtn.disabled = false; if (wkLoadBtn.dataset._txt) wkLoadBtn.textContent = wkLoadBtn.dataset._txt; }
    }
    wkSetLoading(true);
    const startInput = document.getElementById('wk_start');
    let start = startInput?.value ? new Date(startInput.value) : getMonday(new Date());
    start = getMonday(start);
    if (startInput && !startInput.value) startInput.value = ymd(start);
    const user = document.getElementById('wk_user')?.value;
    const shiftStart = parseTimeStr(document.getElementById('wk_shift_start')?.value || '09:00');
    const shiftEnd = parseTimeStr(document.getElementById('wk_shift_end')?.value || '18:00');
    const graceMin = parseInt(document.getElementById('wk_grace')?.value || '10', 10) || 0;
    const from = ymd(start);
    const toDate = new Date(start); toDate.setDate(start.getDate()+6); const to = ymd(toDate);
    const p = new URLSearchParams(); p.append('from', from); p.append('to', to); if (user) p.append('user_id', user);
    const res = await fetchJsonWithFallback(`/list?${p.toString()}`);
    const json = res && res.ok ? await res.json().catch(()=>({success:false})) : {success:false};
    const tbody = document.getElementById('wk_rows'); tbody.innerHTML = '';
    if (!json.success) { tbody.innerHTML = '<tr><td colspan="5">Failed to load</td></tr>'; wkSetLoading(false); return; }
    const byDate = {}; (json.data||[]).forEach(r=>{ byDate[r.date] = r; });
    let weekTotal = 0; let overtime = 0;
    for (let i=0;i<7;i++){
      const d = new Date(start); d.setDate(start.getDate()+i);
      const key = ymd(d);
      const r = byDate[key] || {};
      const ci = r.check_in ? new Date(r.check_in) : null;
      const co = r.check_out ? new Date(r.check_out) : null;
      let hours = 0; if (ci && co) hours = diffHours(ci, co);
      weekTotal += hours;
      // Late/Early flags
      let flags = [];
      if (shiftStart){ const s = new Date(d); s.setHours(shiftStart.h, shiftStart.m + graceMin, 0, 0); if (ci && ci > s) flags.push('Late'); }
      if (shiftEnd){ const e = new Date(d); e.setHours(shiftEnd.h, shiftEnd.m, 0, 0); if (co && co < e) flags.push('Early'); }
      // Overtime if hours > 8 per day
      if (hours > 8) overtime += (hours - 8);
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${key}</td><td>${r.check_in ?? ''}</td><td>${r.check_out ?? ''}</td><td>${hours.toFixed(1)}</td><td>${flags.join(', ')}</td>`;
      tbody.appendChild(tr);
    }
    document.getElementById('wk_total').textContent = weekTotal.toFixed(1);
    document.getElementById('wk_overtime').textContent = 'Overtime: ' + overtime.toFixed(1);
    wkSetLoading(false);
  }
  document.getElementById('wk_load')?.addEventListener('click', wkLoad);

  // KPI loader (uses existing /list endpoint)
  async function loadKpis(){
    const today = ymd(new Date());
    // Present today
    try {
      const p2 = new URLSearchParams(); p2.append('from', today); p2.append('to', today); p2.append('status', 'present');
      const r2 = await fetchJsonWithFallback(`/list?${p2.toString()}`);
      const j2 = r2 && r2.ok ? await r2.json().catch(()=>({success:false,data:[]})) : {success:false,data:[]};
      document.getElementById('kpi_present').textContent = Array.isArray(j2.data) ? j2.data.length : '0';
    } catch(e){ document.getElementById('kpi_present').textContent = '0'; }

    // Late today
    try {
      const p3 = new URLSearchParams(); p3.append('from', today); p3.append('to', today); p3.append('status', 'late');
      const r3 = await fetchJsonWithFallback(`/list?${p3.toString()}`);
      const j3 = r3 && r3.ok ? await r3.json().catch(()=>({success:false,data:[]})) : {success:false,data:[]};
      document.getElementById('kpi_late').textContent = Array.isArray(j3.data) ? j3.data.length : '0';
    } catch(e){ document.getElementById('kpi_late').textContent = '0'; }

    // Absent today
    try {
      const p5 = new URLSearchParams(); p5.append('from', today); p5.append('to', today); p5.append('status', 'absent');
      const r5 = await fetchJsonWithFallback(`/list?${p5.toString()}`);
      const j5 = r5 && r5.ok ? await r5.json().catch(()=>({success:false,data:[]})) : {success:false,data:[]};
      document.getElementById('kpi_absent').textContent = Array.isArray(j5.data) ? j5.data.length : '0';
    } catch(e){ document.getElementById('kpi_absent').textContent = '0'; }

    // Pending approvals (last 7 days)
    try {
      const p1 = new URLSearchParams(); p1.append('from', ymd(new Date(Date.now()-6*24*3600*1000))); p1.append('to', today); p1.append('approval_status', 'pending');
      const r1 = await fetchJsonWithFallback(`/list?${p1.toString()}`);
      const j1 = r1 && r1.ok ? await r1.json().catch(()=>({success:false,data:[]})) : {success:false,data:[]};
      document.getElementById('kpi_pending').textContent = Array.isArray(j1.data) ? j1.data.length : '0';
    } catch(e){ document.getElementById('kpi_pending').textContent = '0'; }
  }

  // initial loads
  apLoad();
  rpLoad();
  wkLoad();
  loadKpis();

  // In-page reminder for checkout after 9h
  async function maybeRemindCheckout(){
    if (reminderShown) return;
    const p = new URLSearchParams(); p.append('from', ymd(new Date())); p.append('to', ymd(new Date()));
    const res = await fetchJsonWithFallback(`/list?${p.toString()}`); if (!res || !res.ok) return;
    const j = await res.json().catch(()=>({success:false})); if (!j.success) return;
    const me = j.data?.find(r=> (r.user_id+'') === ('<?= (int) getCurrentUserId(); ?>'));
    if (me && me.check_in && !me.check_out){
      const ci = new Date(me.check_in); const diffH = diffHours(ci, new Date());
      if (diffH >= 9){ alert('Reminder: You have been checked in for '+diffH.toFixed(1)+' hours. Don\'t forget to check out.'); reminderShown = true; }
    }
  }
  setInterval(maybeRemindCheckout, 5*60*1000);
})();
</script>