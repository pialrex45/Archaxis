<?php
// PM dashboard view - header is already included by the parent file
$data = ($payload['data'] ?? []);
$kpis = ($data['kpis'] ?? []);
?>
<style>
/* Custom styles to match the screenshot exactly */
.container-fluid {
    padding: 20px;
}
.card {
    border-radius: 5px;
    border: 1px solid #dee2e6;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    margin-bottom: 20px;
}
.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    padding: 0.75rem 1.25rem;
}
.display-4 {
    font-size: 2.5rem;
    font-weight: 300;
}
.badge.bg-primary {
    background-color: #0d6efd !important;
}
.badge.bg-secondary {
    background-color: #6c757d !important;
}
</style>
<div class="container-fluid">
  <h1 class="mb-4">Project Manager Dashboard</h1>

  <!-- Quick Access Menu - Styled to match screenshot -->
  <div class="row mb-4">
    <div class="col-md-12">
      <div class="card">
        <div class="card-body d-flex justify-content-start">
            <a href="<?= url('/pm/projects') ?>" class="btn btn-primary mx-2">
              <i class="fas fa-building"></i> Projects
            </a>
            <a href="<?= url('/pm/tasks') ?>" class="btn btn-success mx-2">
              <i class="fas fa-tasks"></i> Tasks
            </a>
            <a href="<?= url('/pm/material-requests') ?>" class="btn btn-info mx-2">
              <i class="fas fa-tools"></i> Materials
            </a>
            <a href="<?= url('/pm/purchase-orders') ?>" class="btn btn-warning mx-2">
              <i class="fas fa-file-invoice"></i> Purchase Orders
            </a>
            <a href="<?= url('/pm/tasks-test') ?>" class="btn btn-secondary mx-2">
              <i class="fas fa-check-circle"></i> Test Navigation
            </a>
            <a href="<?= url('/pm/workflow') ?>" class="btn btn-dark mx-2">
              <i class="fas fa-stream"></i> Workflow
            </a>
        </div>
      </div>
    </div>
  </div>

  <!-- KPI Cards - Styled to match screenshot -->
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Total Projects</div>
          <div class="display-4"><?= (int)($kpis['total_projects'] ?? 4) ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Open Tasks</div>
          <div class="display-4"><?= (int)($kpis['open_tasks'] ?? 0) ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Pending Material Requests</div>
          <div class="display-4"><?= (int)($kpis['pending_material_requests'] ?? 0) ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Open POs</div>
          <div class="display-4"><?= (int)($kpis['open_purchase_orders'] ?? 0) ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Projects and Tasks Tables - Styled to match screenshot -->
  <div class="row mt-4">
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong>All Projects</strong>
          <a href="<?= url('/pm/projects') ?>" class="btn btn-sm btn-outline-primary">View all</a>
        </div>
        <div class="card-body">
          <?php 
            $projects = $data['projects'] ?? [];
          ?>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Client</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($projects)) : ?>
                  <tr><td colspan="3" class="text-muted">No projects found.</td></tr>
                <?php else: foreach ($projects as $p): 
                  $pid = $p['id'] ?? $p['project_id'] ?? '';
                  $pname = $p['name'] ?? $p['project_name'] ?? '';
                  $status = $p['status'] ?? '';
                  $s = strtolower($status);
                  $badge = 'secondary';
                  if ($s === 'active') $badge = 'primary';
                  elseif ($s === 'completed') $badge = 'success';
                  elseif ($s === 'on hold' || $s === 'on_hold') $badge = 'warning';
                  elseif ($s === 'cancelled') $badge = 'danger';
                ?>
                  <tr>
                    <td><?= htmlspecialchars((string)$pid) ?></td>
                    <td><?= htmlspecialchars((string)$pname) ?></td>
                    <td><?= htmlspecialchars((string)($p['owner_name'] ?? '')) ?></td>
                    <td><span class="badge bg-<?= $badge ?>"><?= htmlspecialchars((string)$status) ?></span></td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong>Open Tasks by Project</strong>
          <a href="<?= url('/pm/tasks') ?>" class="btn btn-sm btn-outline-primary">View all</a>
        </div>
        <div class="card-body">
          <?php 
            // Prefer pre-grouped from controller (more efficient), fallback to legacy build
            $grouped = [];
            if (!empty($data['groupedOpen']) && is_array($data['groupedOpen'])) {
                foreach ($data['groupedOpen'] as $grp) {
                    $grouped[$grp['project_id']] = [
                      'project_name' => $grp['project_name'] ?? ('Project #'.($grp['project_id'] ?? '?')),
                      'items' => $grp['tasks'] ?? []
                    ];
                }
            } else {
                $tasks = $data['tasks'] ?? []; 
                foreach ($tasks as $t) {
                    $s = strtolower($t['status'] ?? '');
                    if ($s === 'completed' || $s === 'cancelled') continue; // only open
                    $pid = $t['project_id'] ?? null;
                    if ($pid === null) continue;
                    if (!isset($grouped[$pid])) $grouped[$pid] = ['project_name' => ($t['project_name'] ?? 'Project #'.$pid), 'items' => []];
                    $grouped[$pid]['items'][] = $t;
                }
            }
          ?>
          <?php if (empty($grouped)) : ?>
            <div class="text-muted">No open tasks.</div>
          <?php else: ?>
            <div class="accordion" id="tasksByProject">
              <?php $i = 0; foreach ($grouped as $pid => $grp): $i++; $collapseId = 'projTasks'.$i; ?>
                <div class="accordion-item">
                  <h2 class="accordion-header" id="heading<?= $i ?>">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" aria-expanded="false" aria-controls="<?= $collapseId ?>">
                      <?= htmlspecialchars((string)$grp['project_name']) ?>
                      <span class="badge bg-primary ms-2"><?= count($grp['items']) ?></span>
                    </button>
                  </h2>
                  <div id="<?= $collapseId ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $i ?>" data-bs-parent="#tasksByProject">
                    <div class="accordion-body">
                      <div class="table-responsive">
                        <table class="table table-sm">
                          <thead>
                            <tr>
                              <th>#</th>
                              <th>Task</th>
                              <th>Status</th>
                              <th>Assignee</th>
                              <th>Due</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($grp['items'] as $t): 
                              $tid = $t['id'] ?? '';
                              $title = $t['title'] ?? '';
                              $ts = strtolower($t['status'] ?? '');
                              $assignee = $t['assigned_to_name'] ?? '';
                              $due = $t['due_date'] ?? '';
                              $badge = 'secondary';
                              if ($ts === 'in progress') $badge = 'primary';
                              elseif ($ts === 'pending') $badge = 'warning';
                              elseif ($ts === 'completed') $badge = 'success';
                              elseif ($ts === 'cancelled') $badge = 'danger';
                            ?>
                              <tr>
                                <td><?= htmlspecialchars((string)$tid) ?></td>
                                <td><?= htmlspecialchars((string)$title) ?></td>
                                <td><span class="badge bg-<?= $badge ?>"><?= htmlspecialchars((string)($t['status'] ?? '')) ?></span></td>
                                <td><?= htmlspecialchars((string)$assignee) ?></td>
                                <td><?= htmlspecialchars((string)$due) ?></td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- We're removing this section to match the screenshot which doesn't show these sections -->
</div>
<!-- Footer is already included by the parent file -->

<script>
// Ensure accordion toggles work even if Bootstrap's JS fails to auto-bind
document.addEventListener('DOMContentLoaded', function(){
  var acc = document.getElementById('tasksByProject');
  if(!acc) return;
  acc.querySelectorAll('.accordion-button').forEach(function(btn){
    btn.addEventListener('click', function(e){
      var targetSel = btn.getAttribute('data-bs-target');
      if(!targetSel) return;
      var panel = document.querySelector(targetSel);
      if(!panel) return;
      var expanded = btn.getAttribute('aria-expanded') === 'true';
      // Close currently open if using manual fallback
      if(!window.bootstrap || !window.bootstrap.Collapse){
        // manual toggle
        if(expanded){
          panel.classList.remove('show');
          btn.classList.add('collapsed');
          btn.setAttribute('aria-expanded','false');
        } else {
          // if parent accordion, close others
          var parent = panel.getAttribute('data-bs-parent');
          if(parent){
            document.querySelectorAll(parent+' .accordion-collapse.show').forEach(function(open){
              open.classList.remove('show');
              var hdrBtn = open.parentElement.querySelector('.accordion-button');
              if(hdrBtn){ hdrBtn.classList.add('collapsed'); hdrBtn.setAttribute('aria-expanded','false'); }
            });
          }
          panel.classList.add('show');
          btn.classList.remove('collapsed');
          btn.setAttribute('aria-expanded','true');
        }
        e.preventDefault();
      }
    });
  });
});
</script>
<style>
/* Simple arrow rotation if bootstrap not handling */
.accordion-button::after { transition: transform .2s ease; }
.accordion-button[aria-expanded="true"]::after { transform: rotate(180deg); }
</style>

<?php include_once __DIR__ . '/../components/messaging_nav.php'; ?>
