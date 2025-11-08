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
      <form id="updateTaskStatusForm" action="<?php echo url('api/sub_contractor/tasks/update_status'); ?>" method="POST">
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
      <form id="assignSupervisorForm" action="<?php echo url('api/sub_contractor/tasks/assign_to_supervisor'); ?>" method="POST">
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

<script>
  (function waitForLibs(){
    if (!(window.jQuery && window.bootstrap)) { return setTimeout(waitForLibs, 50); }
    const $ = window.jQuery;
    $(function(){
      if ($.fn.DataTable) {
        $('#tasksTable').DataTable({ order: [[0,'desc']] });
      }

      // Handle update status button clicks (delegated for DataTables redraws)
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
          if (response && response.success) { alert('Task status updated successfully'); location.reload(); }
          else { alert('Error: ' + ((response && response.message) || 'Failed to update task status')); }
        }).fail(function(){
          alert('An error occurred while processing your request');
        });
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
            // Non-destructive UX: keep row visible, just mark as assigned
            var tid = $('#assign_task_id').val();
            var $btn = $(document).find('.assign-supervisor-btn[data-task-id="'+tid+'"]');
            if ($btn.length) {
              $btn.prop('disabled', true)
                  .removeClass('btn-secondary').addClass('btn-success')
                  .html('<i class="fas fa-check"></i> Assigned');
              // Show supervisor name inline next to actions
              var supName = $('#supervisor_id option:selected').text();
              var $cell = $btn.closest('td');
              var $tag = $cell.find('.assigned-supervisor-tag');
              var label = supName && supName.trim() ? supName.trim() : 'Supervisor';
              if ($tag.length) {
                $tag.text('Assigned to: ' + label);
              } else {
                $cell.append(' <span class="badge bg-info ms-2 assigned-supervisor-tag">Assigned to: ' + label + '</span>');
              }
            }
            // Close modal
            var modalEl = document.getElementById('assignSupervisorModal');
            var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.hide();
            alert('Task assigned successfully');
          } else {
            alert('Error: ' + ((response && response.message) || 'Failed to assign'));
          }
        }).fail(function(){ alert('An error occurred while processing your request'); });
      });
    });
  })();
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
