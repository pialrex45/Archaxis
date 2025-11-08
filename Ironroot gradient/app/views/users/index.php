<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';

// Check authentication and role
requireAuth();
if (!hasRole('admin')) {
    http_response_code(403);
    die('Access denied. Admins only.');
}

$pageTitle = 'Users';
$currentPage = 'users';
?>

<?php include_once __DIR__ . '/../layouts/header.php'; ?>
<?php include_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">User Management</h1>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex flex-wrap gap-2 align-items-center">
                    <h5 class="mb-0">User Management</h5>
                    <div class="ms-auto d-flex gap-2 align-items-center">
                        <label class="form-label m-0 small">Role</label>
                        <select id="adminUsersRole" class="form-select form-select-sm" style="width:160px">
                            <option value="">All</option>
                            <option value="admin">Admin</option>
                            <option value="client">Client</option>
                            <option value="project_manager">Project Manager</option>
                            <option value="site_manager">Site Manager</option>
                            <option value="site_engineer">Site Engineer</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="logistic_officer">Logistic Officer</option>
                            <option value="sub_contractor">Sub-Contractor</option>
                        </select>
                        <label class="form-label m-0 small">Approved</label>
                        <select id="adminUsersApproved" class="form-select form-select-sm" style="width:140px">
                            <option value="all">All</option>
                            <option value="1">Approved</option>
                            <option value="0">Pending/Banned</option>
                        </select>
                        <button class="btn btn-sm btn-outline-primary" onclick="loadAdminUsers();">Load</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Approved</th>
                                    <th>Created</th>
                                    <th style="width:220px">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="adminUsersBody">
                                <tr><td colspan="7" class="text-muted">Use the filters above and click Load.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function escapeHtml(s){
        if(s===null||s===undefined) return '';
        return String(s).replace(/[&<>"'`=\/]/g,function(c){
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;','/':'&#x2F;','`':'&#x60;','=':'&#x3D;'}[c]||c;
        });
    }

    function formatRoleLabel(r){
        if(!r) return '';
        const map = { 'sub_contractor':'Sub-Contractor' };
        if(map[r]) return map[r];
        return r.replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    async function loadAdminUsers(){
        const role = document.getElementById('adminUsersRole').value;
        const approved = document.getElementById('adminUsersApproved').value || 'all';
        const body = document.getElementById('adminUsersBody');
        body.innerHTML = '<tr><td colspan="7" class="text-muted">Loading…</td></tr>';
        const url = '<?= url('api/admin/users.php') ?>?action=list' + (role?('&role='+encodeURIComponent(role)):'') + (approved?('&approved='+encodeURIComponent(approved)):'');
        try{
            const res = await fetch(url);
            const j = await res.json();
            if(!j || !j.success){ body.innerHTML = '<tr><td colspan="7" class="text-danger">Failed to load</td></tr>'; return; }
            const users = Array.isArray(j.users)? j.users : [];
            if(users.length===0){ body.innerHTML = '<tr><td colspan="7" class="text-muted">No users found.</td></tr>'; return; }
            body.innerHTML = users.map(u => renderAdminUserRow(u)).join('');
        }catch(e){ body.innerHTML = '<tr><td colspan="7" class="text-danger">Failed to load</td></tr>'; }
    }

    function renderAdminUserRow(u){
        const id = u.id; const approved = Number(u.approved)===1;
        const roleSel = `<select class=\"form-select form-select-sm\" style=\"width:150px\" id=\"role-${id}\">`
            + ['admin','client','project_manager','site_manager','supervisor','worker','site_engineer','logistic_officer','general_manager','sub_contractor']
                .map(r=>`<option value=\"${r}\" ${String(u.role).toLowerCase()===r?'selected':''}>${formatRoleLabel(r)}</option>`).join('')
            + `</select>`;
        const approveBtn = approved? '' : `<button class=\"btn btn-success btn-sm\" onclick=\"approveUser(${id})\">Approve</button>`;
        const banBtn = approved? `<button class=\"btn btn-warning btn-sm\" onclick=\"banUser(${id})\">Ban</button>` : `<button class=\"btn btn-secondary btn-sm\" onclick=\"unbanUser(${id})\">Unban</button>`;
        const delBtn = `<button class=\"btn btn-danger btn-sm\" onclick=\"deleteUser(${id})\">Delete</button>`;
        const setRoleBtn = `<button class=\"btn btn-outline-primary btn-sm\" onclick=\"saveRole(${id})\">Save Role</button>`;
        return `<tr id=\"user-row-${id}\">`
            + `<td>${escapeHtml(id)}</td>`
            + `<td>${escapeHtml(u.name||'')}</td>`
            + `<td>${escapeHtml(u.email||'')}</td>`
            + `<td>${roleSel}</td>`
            + `<td>${approved?'<span class=\"badge bg-success\">Yes</span>':'<span class=\"badge bg-secondary\">No</span>'}</td>`
            + `<td>${escapeHtml(u.created_at||'')}</td>`
            + `<td class=\"d-flex gap-2 flex-wrap\">${approveBtn} ${banBtn} ${setRoleBtn} ${delBtn}</td>`
            + `</tr>`;
    }

    async function approveUser(id){ await postUserAction('approve', {id}); await refreshRow(id); }
    async function banUser(id){ await postUserAction('ban', {id, enabled:false}); await refreshRow(id); }
    async function unbanUser(id){ await postUserAction('unban', {id, enabled:true}); await refreshRow(id); }
    async function deleteUser(id){ if(!confirm('Delete this user?')) return; await postUserAction('delete', {id}); const row=document.getElementById('user-row-'+id); if(row) row.remove(); }
    async function saveRole(id){ const role = document.getElementById('role-'+id).value; await postUserAction('set_role', {id, role}); await refreshRow(id); }

    async function postUserAction(action, body){
        try{
            const res = await fetch('<?= url('api/admin/users.php') ?>?action='+encodeURIComponent(action), { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body||{}) });
            const j = await res.json(); if(!j || !j.success){ alert((j&&j.message)||'Operation failed'); }
        }catch(e){ alert('Request failed'); }
    }

    async function refreshRow(id){
        const role = document.getElementById('adminUsersRole').value;
        const approved = document.getElementById('adminUsersApproved').value || 'all';
        try{
            const url = '<?= url('api/admin/users.php') ?>?action=list' + (role?('&role='+encodeURIComponent(role)):'') + (approved?('&approved='+encodeURIComponent(approved)):'');
            const res = await fetch(url); const j = await res.json();
            if(!j || !j.success) return; const users = j.users||[]; const u = users.find(x=> String(x.id)===String(id));
            if(!u){ const row=document.getElementById('user-row-'+id); if(row) row.remove(); return; }
            const row=document.getElementById('user-row-'+id); if(row) row.outerHTML = renderAdminUserRow(u);
        }catch(e){ /* ignore */ }
    }
    </script>
</div>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>