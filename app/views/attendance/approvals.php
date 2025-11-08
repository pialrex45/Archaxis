<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
$csrf = generate_csrf_token();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Attendance Approvals</title>
  <link rel="stylesheet" href="<?= url('/public/assets/css/style.css') ?>">
  <style>
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ddd; padding: 8px; }
    th { background: #f6f6f6; }
    .filters { display: flex; gap: 8px; margin: 10px 0; }
    .actions button { margin-right: 6px; }
    .text-gradient {
      background: linear-gradient(to right, #3498db, #f1c40f);
      -webkit-background-clip: text;
      background-clip: text;
      -webkit-text-fill-color: transparent;
      color: transparent;
    }
    .btn-gradient {
      background-image: linear-gradient(to right, #3498db, #f1c40f);
      color: #fff;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
    }
    .btn-gradient-primary {
      background-image: linear-gradient(to right, #3498db, #4e73df);
    }
    .btn-gradient-success {
      background-image: linear-gradient(to right, #2ecc71, #1abc9c);
    }
    .btn-gradient-danger {
      background-image: linear-gradient(to right, #e74c3c, #c0392b);
    }
    .btn-gradient-info {
      background-image: linear-gradient(to right, #2f4f7f, #1a3d6e);
    }
    .border-gradient-primary {
      border: 1px solid;
      border-image-source: linear-gradient(to right, #3498db, #4e73df);
      border-image-slice: 1;
    }
    .surface-glass {
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(10px);
    }
    .rounded-3 {
      border-radius: 8px;
    }
    .shadow-soft {
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>
<body class="theme-soft">
  <h1 class="text-gradient">Attendance Approvals</h1>
  <div class="filters">
    <input type="date" id="from" />
    <input type="date" id="to" />
    <select id="status">
      <option value="">Status</option>
      <option value="present">Present</option>
      <option value="late">Late</option>
      <option value="absent">Absent</option>
    </select>
    <select id="approval_status">
      <option value="pending">Pending</option>
      <option value="supervisor_approved">Supervisor Approved</option>
      <option value="rejected">Rejected</option>
      <option value="final_approved">Final Approved</option>
    </select>
    <button id="load" class="btn btn-gradient btn-gradient-primary">Load</button>
    <button id="openReports" class="btn btn-gradient btn-gradient-info">Open Reports</button>
  </div>

  <div class="table-responsive shadow-soft border-gradient-primary surface-glass rounded-3">
    <table class="table-full table-compact table-hover table-striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>User</th>
          <th>Project</th>
          <th>Zone</th>
          <th>Check-in</th>
          <th>Check-out</th>
          <th>Status</th>
          <th>Approval</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="rows"></tbody>
    </table>
  </div>

  <!-- Attendance Approval Modals -->
  <div class="modal fade" id="supervisorApproveModal" tabindex="-1" aria-labelledby="supervisorApproveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="supervisorApproveModalLabel">Supervisor Approve Attendance</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to approve this attendance record?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-success" id="supervisorApproveBtn">Approve</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="supervisorRejectModal" tabindex="-1" aria-labelledby="supervisorRejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="supervisorRejectModalLabel">Reject Attendance</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to reject this attendance record?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger" id="supervisorRejectBtn">Reject</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="finalApproveModal" tabindex="-1" aria-labelledby="finalApproveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="finalApproveModalLabel">Final Approve Attendance</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to final approve this attendance record?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="finalApproveBtn">Final Approve</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    const csrf = "<?= $csrf ?>";
    const apiBase = "<?= url('/api/attendance') ?>";
    
    // User permissions from PHP
    const canSupervisorApprove = <?= hasAnyRole(['admin', 'manager', 'supervisor']) ? 'true' : 'false' ?>;
    const canFinalApprove = <?= hasAnyRole(['admin', 'manager']) ? 'true' : 'false' ?>;

    function approvalChip(val) {
      const v = (val || '').toString();
      let cls = 'info', label = v;
      if (v === 'pending') { cls = 'warning'; label = 'Pending'; }
      else if (v === 'supervisor_approved') { cls = 'info'; label = 'Supervisor Approved'; }
      else if (v === 'final_approved') { cls = 'success'; label = 'Final Approved'; }
      else if (v === 'rejected') { cls = 'danger'; label = 'Rejected'; }
      return `<span class="chip ${cls}">${label}</span>`;
    }

    async function load() {
      const p = new URLSearchParams();
      const from = document.getElementById('from').value;
      const to = document.getElementById('to').value;
      const status = document.getElementById('status').value;
      const approval = document.getElementById('approval_status').value;
      if (from) p.append('from', from);
      if (to) p.append('to', to);
      if (status) p.append('status', status);
      if (approval) p.append('approval_status', approval);
      const res = await fetch(`${apiBase}/list.php?${p.toString()}`);
      let json = { success: false };
      try { json = await res.json(); } catch (e) {}
      const rows = document.getElementById('rows');
      rows.innerHTML = '';
      if (!json.success || !Array.isArray(json.data)) { rows.innerHTML = '<tr><td colspan="9">No data</td></tr>'; return; }
      json.data.forEach(r => {
        const tr = document.createElement('tr');
        tr.className = 'fade-in';
        
        // Build action buttons based on user role and attendance status
        let actionButtons = '';
        
        if (canSupervisorApprove && r.approval_status === 'pending') {
          actionButtons += `
            <button type="button" class="btn btn-sm btn-outline-success me-1" data-bs-toggle="modal" data-bs-target="#supervisorApproveModal" data-attendance-id="${r.id}" data-action="approved">
              <i class="fas fa-check"></i> Supervisor Approve
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger me-1" data-bs-toggle="modal" data-bs-target="#supervisorRejectModal" data-attendance-id="${r.id}" data-action="rejected">
              <i class="fas fa-times"></i> Reject
            </button>
          `;
        }
        
        if (canFinalApprove && r.approval_status === 'supervisor_approved') {
          actionButtons += `
            <button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#finalApproveModal" data-attendance-id="${r.id}" data-action="approved">
              <i class="fas fa-check-double"></i> Final Approve
            </button>
          `;
        }
        
        if (!actionButtons) {
          actionButtons = '<span class="text-muted">No actions available</span>';
        }
        
        tr.innerHTML = `
          <td>${r.id ?? ''}</td>
          <td>${r.user_name ?? r.user_id ?? ''}</td>
          <td>${r.project_name ?? r.project_id ?? ''}</td>
          <td>${r.zone_name ?? r.zone_id ?? ''}</td>
          <td>${r.check_in ?? ''}</td>
          <td>${r.check_out ?? ''}</td>
          <td>${r.status ?? ''}</td>
          <td>${approvalChip(r.approval_status)}</td>
          <td class="actions">${actionButtons}</td>
        `;
        rows.appendChild(tr);
      });
    }

    async function supApprove(id, action) {
      const body = new URLSearchParams();
      body.append('csrf_token', csrf);
      body.append('attendance_id', id);
      body.append('action', action);
      const res = await fetch(`${apiBase}/approve.php`, { method: 'POST', body });
      let j = { success: false };
      try { j = await res.json(); } catch (e) {}
      showAttendanceAlert(j.success ? 'success' : 'danger', j.message || (j.success ? 'Approval processed successfully' : 'Failed to process approval'));
      load();
    }

    async function finalApprove(id, action) {
      const body = new URLSearchParams();
      body.append('csrf_token', csrf);
      body.append('attendance_id', id);
      body.append('action', action);
      const res = await fetch(`${apiBase}/final_approve.php`, { method: 'POST', body });
      let j = { success: false };
      try { j = await res.json(); } catch (e) {}
      showAttendanceAlert(j.success ? 'success' : 'danger', j.message || (j.success ? 'Final approval processed successfully' : 'Failed to process final approval'));
      load();
    }

    function showAttendanceAlert(type, message) {
      const alert = document.createElement('div');
      alert.className = `alert alert-${type} alert-dismissible fade show`;
      alert.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
      const container = document.querySelector('.col-md-9') || document.querySelector('.col-lg-10') || document.body;
      const anchor = document.querySelector('.border-bottom') || container.firstChild;
      container.insertBefore(alert, anchor);
      setTimeout(() => { try { new bootstrap.Alert(alert).close(); } catch(e){} }, 3000);
    }

    let currentAttendanceId = null;
    let currentAction = null;

    document.addEventListener('click', function(e) {
      const btn = e.target.closest('[data-bs-toggle="modal"]');
      if (!btn) return;
      currentAttendanceId = parseInt(btn.getAttribute('data-attendance-id'), 10);
      currentAction = btn.getAttribute('data-action');
    });

    document.getElementById('supervisorApproveBtn').addEventListener('click', () => {
      if (currentAttendanceId) {
        supApprove(currentAttendanceId, currentAction);
        const modal = bootstrap.Modal.getInstance(document.getElementById('supervisorApproveModal'));
        modal?.hide();
      }
    });

    document.getElementById('supervisorRejectBtn').addEventListener('click', () => {
      if (currentAttendanceId) {
        supApprove(currentAttendanceId, 'rejected');
        const modal = bootstrap.Modal.getInstance(document.getElementById('supervisorRejectModal'));
        modal?.hide();
      }
    });

    document.getElementById('finalApproveBtn').addEventListener('click', () => {
      if (currentAttendanceId) {
        finalApprove(currentAttendanceId, currentAction);
        const modal = bootstrap.Modal.getInstance(document.getElementById('finalApproveModal'));
        modal?.hide();
      }
    });

    // Link to Reports with current filters
    (function(){
      const btn = document.getElementById('openReports');
      if (!btn) return;
      btn.addEventListener('click', () => {
        const p = new URLSearchParams();
        const from = document.getElementById('from')?.value || '';
        const to = document.getElementById('to')?.value || '';
        const status = document.getElementById('status')?.value || '';
        const approval = document.getElementById('approval_status')?.value || '';
        if (from) p.append('from', from);
        if (to) p.append('to', to);
        if (status) p.append('status', status);
        if (approval) p.append('approval_status', approval);
        const base = "<?= url('/attendance/reports') ?>";
        window.location.href = p.toString() ? `${base}?${p.toString()}` : base;
      });
    })();

    document.getElementById('load').addEventListener('click', load);
    load();
  </script>
</body>
</html>
