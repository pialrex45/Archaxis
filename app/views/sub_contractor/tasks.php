<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/SubContractorController.php';

requireAuth();
if (!hasRole('sub_contractor')) { http_response_code(403); die('Access denied. Sub-Contractors only.'); }

// Get all tasks assigned to the sub-contractor
$controller = new SubContractorController();
$tasksResponse = $controller->tasksAssigned(100);
$tasks = ($tasksResponse['success'] && isset($tasksResponse['data'])) ? $tasksResponse['data'] : [];

$pageTitle = 'My Tasks';
$currentPage = 'sub_contractor_tasks';

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1>My Tasks</h1>
    <div class="d-flex gap-2 align-items-center">
      <select id="scStatusFilter" class="form-select form-select-sm" style="width:160px">
        <option value="" selected>All Statuses</option>
        <option value="pending">Pending</option>
        <option value="in progress">In Progress</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
      </select>
      <button class="btn btn-sm btn-outline-secondary" id="scStatusReset">Reset</button>
    </div>
  </div>

  <?php if (empty($tasks)): ?>
    <div class="alert alert-info">
      <i class="fas fa-info-circle"></i> You don't have any assigned tasks yet.
    </div>
  <?php else: ?>
    <!-- Tasks Overview Cards -->
    <div class="row mb-4">
      <?php
        $pendingCount = 0;
        $inProgressCount = 0;
        $completedCount = 0;
        
        foreach ($tasks as $task) {
          if ($task['status'] === 'pending') {
            $pendingCount++;
          } elseif ($task['status'] === 'in progress') {
            $inProgressCount++;
          } elseif ($task['status'] === 'completed') {
            $completedCount++;
          }
        }
      ?>
      
      <div class="col-md-4 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Tasks</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pendingCount; ?></div>
              </div>
              <div class="col-auto">
                <i class="fas fa-pause fa-2x text-gray-300"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-4 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">In Progress</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $inProgressCount; ?></div>
              </div>
              <div class="col-auto">
                <i class="fas fa-spinner fa-2x text-gray-300"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-4 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Completed</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $completedCount; ?></div>
              </div>
              <div class="col-auto">
                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Tasks Table -->
    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Tasks</h6>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="tasksTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Project</th>
                <th>Status</th>
                <th>Due Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($tasks as $task): ?>
                <tr>
                  <td><?php echo htmlspecialchars($task['id']); ?></td>
                  <td><?php echo htmlspecialchars($task['title']); ?></td>
                  <td><?php echo htmlspecialchars($task['project_name'] ?? 'Unknown'); ?></td>
                  <td>
                    <span class="badge bg-<?php 
                      echo $task['status'] === 'completed' ? 'success' : 
                          ($task['status'] === 'in progress' ? 'primary' : 
                          ($task['status'] === 'pending' ? 'warning' : 'secondary')); 
                    ?>">
                      <?php echo htmlspecialchars(ucfirst($task['status'])); ?>
                    </span>
                  </td>
                  <td><?php echo htmlspecialchars($task['due_date'] ?? 'Not set'); ?></td>
                  <td>
                    <!-- View button removed as requested -->
                    <button class="btn btn-sm btn-primary update-status-btn" 
                            data-task-id="<?php echo htmlspecialchars($task['id']); ?>"
                            data-task-title="<?php echo htmlspecialchars($task['title']); ?>"
                            data-task-status="<?php echo htmlspecialchars($task['status']); ?>">
                      <i class="fas fa-sync-alt"></i> Update
                    </button>
                    <button class="btn btn-sm btn-outline-primary add-progress-btn" 
                            data-task-id="<?php echo htmlspecialchars($task['id']); ?>"
                            data-task-title="<?php echo htmlspecialchars($task['title']); ?>">
                      <i class="fas fa-plus"></i> Progress
                    </button>
                    <button class="btn btn-sm btn-secondary assign-supervisor-btn"
                            data-task-id="<?php echo htmlspecialchars($task['id']); ?>"
                            data-task-title="<?php echo htmlspecialchars($task['title']); ?>">
                      <i class="fas fa-user-check"></i> Assign
                    </button>
                    <?php if (!empty($task['assigned_to_name'])): ?>
                      <span class="badge bg-info ms-2 assigned-supervisor-tag">Assigned to: <?php echo htmlspecialchars($task['assigned_to_name']); ?></span>
                    <?php endif; ?>
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

<!-- Update Task Status Modal -->
<div class="modal fade" id="updateTaskStatusModal" tabindex="-1" aria-labelledby="updateTaskStatusModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="updateTaskStatusModalLabel">Update Task Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
  <form id="updateTaskStatusForm" action="<?php echo url('api/sub_contractor/tasks.php?action=update_status'); ?>" method="POST">
        <div class="modal-body">
          <input type="hidden" id="task_id" name="task_id">
          <div class="mb-3">
            <label class="form-label">Task</label>
            <div id="task_title" class="form-control-plaintext"></div>
          </div>
          <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status" required>
              <option value="pending">Pending</option>
              <option value="in progress">In Progress</option>
              <option value="completed">Completed</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="note" class="form-label">Progress Note</label>
            <textarea class="form-control" id="note" name="note" rows="3" placeholder="Add progress note or comments..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Status</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Assign Supervisor Modal -->
<div class="modal fade" id="assignSupervisorModal" tabindex="-1" aria-labelledby="assignSupervisorModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="assignSupervisorModalLabel">Assign Supervisor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
  <form id="assignSupervisorForm" action="<?php echo url('api/sub_contractor/tasks.php?action=assign_to_supervisor'); ?>" method="POST">
        <div class="modal-body">
          <input type="hidden" id="assign_task_id" name="task_id">
          <div class="mb-3">
            <label class="form-label">Task</label>
            <div id="assign_task_title" class="form-control-plaintext"></div>
          </div>
          <div class="mb-3">
            <label for="supervisor_id" class="form-label">Supervisor</label>
            <select class="form-select" id="supervisor_id" name="supervisor_id" required>
              <option value="">Loading...</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Assign</button>
        </div>
      </form>
    </div>
  </div>
</div>

  <!-- Quick Progress Modal -->
  <div class="modal fade" id="scQuickProgressModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Progress</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="scQuickProgressForm" action="<?php echo url('api/sub_contractor/tasks.php?action=add_progress'); ?>" method="POST">
          <div class="modal-body">
            <input type="hidden" id="qp_task_id" name="task_id">
            <div class="mb-2 small text-muted" id="qp_task_title"></div>
            <div class="mb-3">
              <label class="form-label">Progress Note</label>
              <textarea class="form-control" name="note" id="qp_note" rows="3" placeholder="What changed?" required></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">Optional Status Change</label>
              <select class="form-select" name="status" id="qp_status">
                <option value="">(No Change)</option>
                <option value="pending">Pending</option>
                <option value="in progress">In Progress</option>
                <option value="completed">Completed</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>

<script>
  (function waitForLibs(){
    if (!(window.jQuery && window.bootstrap)) { return setTimeout(waitForLibs, 50); }
    const $ = window.jQuery;
    $(function(){
      if ($.fn.DataTable) {
        $('#tasksTable').DataTable({ order: [[0,'desc']] });
      }

      // Insert global feedback container
      if (!document.getElementById('scFeedback')) {
        $('body').append('<div id="scFeedback" style="position:fixed;top:1rem;right:1rem;z-index:9999;max-width:320px;"></div>');
      }

      function pushFeedback(msg, type){
        const id = 'fb_'+Date.now()+Math.random().toString(16).slice(2);
        const color = type==='error'?'danger':(type==='success'?'success':'secondary');
        const $el = $('<div class="alert alert-'+color+' alert-dismissible fade show shadow-sm py-2 px-3 mb-2" id="'+id+'" role="alert" style="font-size:0.875rem;">'
          + $('<div/>').text(msg).html()
          +'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="font-size:0.6rem;"></button></div>');
        $('#scFeedback').append($el);
        setTimeout(()=>{ $el.alert('close'); }, 5000);
      }

      // Status filter logic
      const statusFilter = $('#scStatusFilter');
      const statusReset = $('#scStatusReset');
      function applyStatusFilter(){
        const val = (statusFilter.val()||'').toLowerCase();
        $('#tasksTable tbody tr').each(function(){
          if(!val){ $(this).show(); return; }
          const badgeText = ($(this).find('td:nth-child(4) .badge').text()||'').toLowerCase();
          if (badgeText === val) $(this).show(); else $(this).hide();
        });
      }
      statusFilter.on('change', applyStatusFilter);
      statusReset.on('click', function(){ statusFilter.val(''); applyStatusFilter(); });

      // Handle update status button clicks (delegated for DataTables redraws)
      // Quick progress button
      $(document).on('click','.add-progress-btn', function(){
        const tid = $(this).data('task-id');
        const title = $(this).data('task-title');
        $('#qp_task_id').val(tid);
        $('#qp_task_title').text(title);
        $('#qp_note').val('');
        $('#qp_status').val('');
        new bootstrap.Modal(document.getElementById('scQuickProgressModal')).show();
      });

      // Quick progress form submit
      function recalcSummary(){
        let p=0,i=0,c=0;
        $('#tasksTable tbody tr').each(function(){
          const st = ($(this).find('td:nth-child(4) .badge').text()||'').trim().toLowerCase();
          if(st==='pending') p++; else if(st==='in progress') i++; else if(st==='completed') c++;
        });
        $('.card .text-uppercase.mb-1:contains("Pending Tasks")').closest('.card').find('.h5').text(p);
        $('.card .text-uppercase.mb-1:contains("In Progress")').closest('.card').find('.h5').text(i);
        $('.card .text-uppercase.mb-1:contains("Completed")').closest('.card').find('.h5').text(c);
      }
      $('#scQuickProgressForm').on('submit', function(e){
        e.preventDefault();
        $.ajax({
          url: $(this).attr('action'),
          type: 'POST',
          data: $(this).serialize(),
          dataType: 'json'
        }).done(function(resp){
          if (resp && resp.success){
            pushFeedback('Progress added', 'success');
            const modalEl = document.getElementById('scQuickProgressModal');
            bootstrap.Modal.getInstance(modalEl).hide();
            // If user selected a status change, update row badge immediately
            const newStatus = ($('#qp_status').val()||'').toLowerCase();
            if(newStatus){
              const tid = $('#qp_task_id').val();
              const $row = $('#tasksTable tbody tr').filter(function(){ return $(this).find('td:first').text().trim()===tid; });
              if($row.length){
                const $badgeCell = $row.find('td:nth-child(4) .badge');
                const cls = newStatus==='completed'?'success':(newStatus==='in progress'?'primary':(newStatus==='pending'?'warning':'secondary'));
                $badgeCell.removeClass('bg-success bg-primary bg-warning bg-secondary').addClass('bg-'+cls).text(newStatus.replace(/\b\w/g,c=>c.toUpperCase()));
                // Update button data-task-status so main update modal shows current
                $row.find('.update-status-btn').data('task-status', newStatus);
                recalcSummary();
              }
            } else {
              // Provide reassurance by reloading after short delay so user sees persisted progress elsewhere if needed
              setTimeout(()=>{ location.reload(); }, 1200);
            }
          } else { pushFeedback('Add progress failed: '+((resp&&resp.message)||'Unknown'), 'error'); }
        }).fail(function(){ pushFeedback('Network error adding progress', 'error'); });
      });
      $(document).on('click', '.update-status-btn', function() {
        const taskId = $(this).data('task-id');
        const taskTitle = $(this).data('task-title');
        const taskStatus = $(this).data('task-status');
        $('#task_id').val(taskId);
        $('#task_title').text(taskTitle);
        $('#status').val(taskStatus);
        const modalEl = document.getElementById('updateTaskStatusModal');
        new bootstrap.Modal(modalEl).show();
      });

      // Handle task status update form submission
      $('#updateTaskStatusForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
          url: $(this).attr('action'),
          type: 'POST',
          data: $(this).serialize(),
          dataType: 'json'
        }).done(function(response){
          if (response && response.success) { pushFeedback('Task status updated', 'success'); setTimeout(()=>location.reload(), 700); }
          else { pushFeedback('Update failed: '+ ((response && response.message) || 'Unknown error'), 'error'); }
        }).fail(function(){ pushFeedback('Network error updating task', 'error'); });
      });

      // Handle assign supervisor button clicks
      $(document).on('click', '.assign-supervisor-btn', function() {
        const taskId = $(this).data('task-id');
        const taskTitle = $(this).data('task-title');
        $('#assign_task_id').val(taskId);
        $('#assign_task_title').text(taskTitle);
        $('#supervisor_id').html('<option value="">Loading...</option>');
        var primaryUrl = '<?php echo url('api/supervisor/list'); ?>';
        var fallbackUrl = '/api/supervisor/list';
        $.getJSON(primaryUrl).done(function(resp){
          if (resp && resp.success) {
            const opts = ['<option value="">Select supervisor...</option>'];
            (resp.data||[]).forEach(function(u){ opts.push('<option value="'+u.id+'">'+(u.name||('Supervisor #'+u.id))+'</option>'); });
            $('#supervisor_id').html(opts.join(''));
          } else { $('#supervisor_id').html('<option value="">Failed to load</option>'); }
        }).fail(function(jq){
          // Retry with a simple absolute path fallback
          $.getJSON(fallbackUrl).done(function(resp2){
            if (resp2 && resp2.success) {
              const opts = ['<option value="">Select supervisor...</option>'];
              (resp2.data||[]).forEach(function(u){ opts.push('<option value="'+u.id+'">'+(u.name||('Supervisor #'+u.id))+'</option>'); });
              $('#supervisor_id').html(opts.join(''));
            } else {
              $('#supervisor_id').html('<option value="">Failed to load</option>');
            }
          }).fail(function(jq2){
            $('#supervisor_id').html('<option value="">Failed to load</option>');
            console.warn('Load supervisors failed (both URLs)', {primary: {status: jq && jq.status, body: jq && jq.responseText}, fallback: {status: jq2 && jq2.status, body: jq2 && jq2.responseText}});
          });
        });
        new bootstrap.Modal(document.getElementById('assignSupervisorModal')).show();
      });

      // Handle assign supervisor form submission
      $('#assignSupervisorForm').on('submit', function(e){
        e.preventDefault();
        $.ajax({
          url: $(this).attr('action'),
          type: 'POST',
          data: $(this).serialize(),
          dataType: 'json'
        }).done(function(response){
          if (response && response.success) {
            var tid = $('#assign_task_id').val();
            var $btn = $(document).find('.assign-supervisor-btn[data-task-id="'+tid+'"]');
            if ($btn.length) {
              $btn.prop('disabled', true)
                  .removeClass('btn-secondary').addClass('btn-success')
                  .html('<i class="fas fa-check"></i> Assigned');
              var supName = $('#supervisor_id option:selected').text();
              var $cell = $btn.closest('td');
              var $tag = $cell.find('.assigned-supervisor-tag');
              var label = supName && supName.trim() ? supName.trim() : 'Supervisor';
              if ($tag.length) { $tag.text('Assigned to: ' + label); }
              else { $cell.append(' <span class="badge bg-info ms-2 assigned-supervisor-tag">Assigned to: ' + label + '</span>'); }
            }
            var modalEl = document.getElementById('assignSupervisorModal');
            var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.hide();
            pushFeedback('Task assigned to supervisor', 'success');
          } else { pushFeedback('Assignment failed: '+ ((response && response.message) || 'Unknown error'), 'error'); }
        }).fail(function(){ pushFeedback('Network error assigning task', 'error'); });
      });
    });
  })();
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
