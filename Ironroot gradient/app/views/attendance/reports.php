<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
$csrf = generate_csrf_token();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Attendance Reports</title>
  <link rel="stylesheet" href="<?= url('/public/assets/css/style.css') ?>">
  <style>
    .kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin: 12px 0; }
    .kpi { padding: 14px; border: 1px solid #e9edf5; border-radius: 8px; background: #fff; box-shadow: 0 6px 16px rgba(16,24,40,0.05); }
    .kpi .label { color: #6c757d; font-size: .85rem; }
    .kpi .value { font-size: 1.35rem; font-weight: 700; }
    .filters { display: flex; flex-wrap: wrap; gap: 8px; margin: 10px 0; }
    .actions { margin-left: auto; display: inline-flex; gap: 8px; }
    .table-wrap { margin-top: 12px; }
    .muted { color: #6c757d; font-size: .9rem; }
  </style>
</head>
<body class="theme-soft">
  <h1>Attendance Reports</h1>

  <div class="filters">
    <input type="date" id="from" />
    <input type="date" id="to" />
    <select id="user_id" style="min-width:200px">
      <option value="">All Users</option>
    </select>
    <select id="project_id" style="min-width:200px">
      <option value="">All Projects</option>
    </select>
    <select id="approval_status">
      <option value="">Approval: Any</option>
      <option value="pending">Pending</option>
      <option value="supervisor_approved">Supervisor Approved</option>
      <option value="rejected">Rejected</option>
      <option value="final_approved">Final Approved</option>
    </select>
    <div class="actions">
      <button id="run" class="btn btn-primary btn-soft">Run</button>
      <button id="csv" class="btn btn-ghost">Export CSV</button>
    </div>
  </div>

  <div class="filters" style="margin-top:-6px">
    <span class="muted" style="align-self:center">Quick ranges:</span>
    <button class="btn btn-ghost" data-preset="today">Today</button>
    <button class="btn btn-ghost" data-preset="last7">Last 7 days</button>
    <button class="btn btn-ghost" data-preset="thisWeek">This week</button>
    <button class="btn btn-ghost" data-preset="thisMonth">This month</button>
  </div>

  <div id="chart" style="width:100%;height:180px;border:1px solid #e9edf5;border-radius:8px;background:#fff;overflow:hidden"></div>

  <div class="kpis">
    <div class="kpi">
      <div class="label">Total Records</div>
      <div class="value" id="k_total">–</div>
    </div>
    <div class="kpi">
      <div class="label">Present</div>
      <div class="value" id="k_present">–</div>
    </div>
    <div class="kpi">
      <div class="label">Late</div>
      <div class="value" id="k_late">–</div>
    </div>
    <div class="kpi">
      <div class="label">Absent</div>
      <div class="value" id="k_absent">–</div>
    </div>
  </div>

  <div class="table-wrap table-responsive">
    <table class="table-full table-compact table-hover table-striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>User</th>
          <th>Project</th>
          <th>Date</th>
          <th>In</th>
          <th>Out</th>
          <th>Status</th>
          <th>Approval</th>
        </tr>
      </thead>
      <tbody id="rows"></tbody>
    </table>
    <div id="empty" class="muted" style="display:none; padding:8px;">No records for selected filters.</div>
  </div>

  <script src="<?= url('/public/assets/js/ui.js') ?>"></script>
  <script>
    const apiAttendance = "<?= url('/api/attendance') ?>";
    const apiShared = "<?= url('/api/shared') ?>";

    function qs() {
      const p = new URLSearchParams();
      const from = document.getElementById('from').value;
      const to = document.getElementById('to').value;
      const user_id = document.getElementById('user_id').value;
      const project_id = document.getElementById('project_id').value;
      const approval_status = document.getElementById('approval_status').value;
      if (from) p.append('from', from);
      if (to) p.append('to', to);
      if (user_id) p.append('user_id', user_id);
      if (project_id) p.append('project_id', project_id);
      if (approval_status) p.append('approval_status', approval_status);
      return p;
    }

    async function loadUsers() {
      try {
        const j = await fetchJSON(`${apiShared}/users_list.php`);
        if (!j.success) throw new Error(j.message || 'Failed to load users');
        const sel = document.getElementById('user_id');
        (j.data || []).forEach(u => {
          const opt = document.createElement('option');
          opt.value = u.id; opt.textContent = u.label || `User #${u.id}`;
          sel.appendChild(opt);
        });
      } catch (e) { toast(e.message || 'Could not populate users', 'warning'); }
    }

    async function loadProjects() {
      try {
        const j = await fetchJSON(`${apiShared}/projects_list.php`);
        if (!j.success) throw new Error(j.message || 'Failed to load projects');
        const sel = document.getElementById('project_id');
        (j.data || []).forEach(p => {
          const opt = document.createElement('option');
          opt.value = p.id; opt.textContent = p.name || `Project #${p.id}`;
          sel.appendChild(opt);
        });
      } catch (e) { toast(e.message || 'Could not populate projects', 'warning'); }
    }

    function setKPIs(totals) {
      const t = totals || {};
      document.getElementById('k_total').textContent = t.total ?? 0;
      document.getElementById('k_present').textContent = t.present ?? 0;
      document.getElementById('k_late').textContent = t.late ?? 0;
      document.getElementById('k_absent').textContent = t.absent ?? 0;
    }

    function approvalChip(val) {
      const v = (val || '').toString();
      let cls = 'info', label = v || '—';
      if (v === 'pending') { cls = 'warning'; label = 'Pending'; }
      else if (v === 'supervisor_approved') { cls = 'info'; label = 'Supervisor Approved'; }
      else if (v === 'final_approved') { cls = 'success'; label = 'Final Approved'; }
      else if (v === 'rejected') { cls = 'danger'; label = 'Rejected'; }
      return `<span class="chip ${cls}">${label}</span>`;
    }

    async function loadStats() {
      const p = qs();
      const url = `${apiAttendance}/stats.php?${p.toString()}`;
      const j = await fetchJSON(url);
      if (!j.success) {
        toast(j.message || 'Failed to load stats', 'danger');
        return { totals: { total:0, present:0, late:0, absent:0 } };
      }
      return j.data || {};
    }

    async function loadTable() {
      const rows = document.getElementById('rows');
      const empty = document.getElementById('empty');
      rows.innerHTML = '';
      const p = qs();
      // Use existing list API
      const url = `${apiAttendance}/list.php?${p.toString()}`;
      const j = await fetchJSON(url);
      if (!j.success || !Array.isArray(j.data) || j.data.length === 0) {
        empty.style.display = 'block';
        renderChart([]);
        return;
      }
      empty.style.display = 'none';
      renderChart(j.data);
      j.data.forEach(r => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${r.id ?? ''}</td>
          <td>${r.user_name ?? r.user_id ?? ''}</td>
          <td>${r.project_name ?? r.project_id ?? ''}</td>
          <td>${r.date ?? ''}</td>
          <td>${r.check_in ?? r.check_in_time ?? ''}</td>
          <td>${r.check_out ?? r.check_out_time ?? ''}</td>
          <td>${r.status ?? ''}</td>
          <td>${approvalChip(r.approval_status)}</td>
        `;
        rows.appendChild(tr);
      });
    }

    function renderChart(data) {
      const el = document.getElementById('chart');
      const w = el.clientWidth || 600; const h = el.clientHeight || 180;
      const counts = {};
      (data || []).forEach(r => { const d = r.date || ''; if (!d) return; counts[d] = (counts[d]||0)+1; });
      const labels = Object.keys(counts).sort();
      const max = Math.max(1, ...labels.map(d => counts[d]));
      const pad = 24; const innerW = w - pad*2; const innerH = h - pad*2;
      const barW = labels.length ? innerW / labels.length : innerW;
      let svg = `<svg width="${w}" height="${h}" xmlns="http://www.w3.org/2000/svg">`;
      svg += `<rect x="0" y="0" width="${w}" height="${h}" fill="#ffffff"/>`;
      // axes
      svg += `<line x1="${pad}" y1="${h-pad}" x2="${w-pad}" y2="${h-pad}" stroke="#e9edf5"/>`;
      svg += `<line x1="${pad}" y1="${pad}" x2="${pad}" y2="${h-pad}" stroke="#e9edf5"/>`;
      labels.forEach((d, i) => {
        const val = counts[d];
        const bh = Math.round((val / max) * innerH);
        const x = Math.round(pad + i * barW + barW*0.15);
        const y = Math.round(h - pad - bh);
        const bw = Math.max(4, Math.round(barW*0.7));
        svg += `<rect x="${x}" y="${y}" width="${bw}" height="${bh}" fill="#667eea" opacity="0.85"/>`;
        if (labels.length <= 14) {
          svg += `<text x="${x + bw/2}" y="${h - pad + 12}" font-size="10" text-anchor="middle" fill="#6c757d">${d.slice(5)}</text>`;
        }
      });
      svg += `</svg>`;
      el.innerHTML = svg;
    }

    async function run() {
      const data = await loadStats();
      setKPIs(data.totals);
      await loadTable();
    }

    function formatDate(d) {
      const y = d.getFullYear();
      const m = String(d.getMonth()+1).padStart(2,'0');
      const day = String(d.getDate()).padStart(2,'0');
      return `${y}-${m}-${day}`;
    }

    function applyPreset(name) {
      const fromEl = document.getElementById('from');
      const toEl = document.getElementById('to');
      const now = new Date();
      let from = new Date(now), to = new Date(now);
      if (name === 'today') {
        // from/to are today
      } else if (name === 'last7') {
        from.setDate(now.getDate()-6);
      } else if (name === 'thisWeek') {
        const day = now.getDay(); // 0 Sun..6 Sat
        const diff = (day === 0 ? 6 : day-1); // Monday start
        from.setDate(now.getDate()-diff);
      } else if (name === 'thisMonth') {
        from = new Date(now.getFullYear(), now.getMonth(), 1);
      }
      fromEl.value = formatDate(from);
      toEl.value = formatDate(to);
      run();
    }

    document.getElementById('run').addEventListener('click', run);
    document.getElementById('csv').addEventListener('click', () => {
      const p = qs();
      const url = `${apiAttendance}/export_csv.php?${p.toString()}`;
      window.location.href = url;
    });

    document.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-preset]');
      if (!btn) return;
      applyPreset(btn.getAttribute('data-preset'));
    });

    // Populate dropdowns and auto-run
    Promise.all([loadUsers(), loadProjects()]).then(run);
  </script>
</body>
</html>
