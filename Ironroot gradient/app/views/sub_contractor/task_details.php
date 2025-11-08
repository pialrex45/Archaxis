<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../models/Task.php';
require_once __DIR__ . '/../../models/TaskUpdate.php';

requireAuth();
if (!hasRole('sub_contractor')) { http_response_code(403); die('Access denied. Sub-Contractors only.'); }

// Get the task ID from the URL
$parts = explode('/', $_SERVER['REQUEST_URI']);
$taskId = (int)end($parts);

if (!$taskId) {
    setFlashMessage('Invalid task ID', 'danger');
    redirect('/sub-contractor/tasks');
    exit;
}

// Get task details
$taskModel = new Task();
$task = $taskModel->getById($taskId);

if (!$task || $task['assigned_to'] != getCurrentUserId()) {
    setFlashMessage('Task not found or you do not have access', 'danger');
    redirect('/sub-contractor/tasks');
    exit;
}

// Get project details
$projectModel = new Project();
$project = $projectModel->getById($task['project_id']);

// Get task updates/progress
$taskUpdateModel = new TaskUpdate();
$updates = $taskUpdateModel->getByTaskId($taskId);

$pageTitle = 'Task Details: ' . htmlspecialchars($task['title']);
$currentPage = 'tasks';

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Task: <?php echo htmlspecialchars($task['title']); ?></h1>
    <a href="<?php echo url('/sub-contractor/tasks'); ?>" class="btn btn-outline-primary">
      <i class="fas fa-arrow-left"></i> Back to Tasks
    </a>
  </div>

  <!-- Task Information -->
  <div class="row mb-4">
    <div class="col-lg-12">
      <div class="card shadow">
        <div class="card-header py-3">
          <h6 class="m-0 font-weight-bold text-primary">Task Information</h6>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <p><strong>ID:</strong> <?php echo htmlspecialchars($task['id']); ?></p>
              <p><strong>Title:</strong> <?php echo htmlspecialchars($task['title']); ?></p>
              <p><strong>Project:</strong> <?php echo htmlspecialchars($project['name'] ?? 'Unknown'); ?></p>
              <p><strong>Status:</strong> 
                <span class="badge bg-<?php 
                  echo $task['status'] === 'completed' ? 'success' : 
                      ($task['status'] === 'in progress' ? 'primary' : 
                      ($task['status'] === 'pending' ? 'warning' : 'secondary')); 
                ?>">
                  <?php echo htmlspecialchars(ucfirst($task['status'])); ?>
                </span>
              </p>
            </div>
            <div class="col-md-6">
              <p><strong>Created At:</strong> <?php echo htmlspecialchars(date('Y-m-d', strtotime($task['created_at']))); ?></p>
              <p><strong>Due Date:</strong> <?php echo htmlspecialchars($task['due_date'] ?? 'Not set'); ?></p>
              <p><strong>Last Updated:</strong> <?php echo htmlspecialchars(date('Y-m-d', strtotime($task['updated_at']))); ?></p>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-12">
              <h6 class="font-weight-bold">Description</h6>
              <p><?php echo nl2br(htmlspecialchars($task['description'] ?? 'No description available.')); ?></p>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-12">
              <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateTaskStatusModal">
                <i class="fas fa-sync-alt"></i> Update Status
              </button>
              <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addProgressModal">
                <i class="fas fa-plus"></i> Add Progress Note
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Progress Timeline -->
  <div class="row mb-4">
    <div class="col-lg-12">
      <div class="card shadow">
        <div class="card-header py-3">
          <h6 class="m-0 font-weight-bold text-primary">Progress Timeline</h6>
        </div>
        <div class="card-body">
          <?php if (empty($updates)): ?>
            <p class="text-center">No progress updates yet.</p>
          <?php else: ?>
            <div class="timeline">
              <?php foreach ($updates as $update): ?>
                <div class="timeline-item">
                  <div class="timeline-badge bg-<?php 
                    echo $update['status'] === 'completed' ? 'success' : 
                        ($update['status'] === 'in progress' ? 'primary' : 
                        ($update['status'] === 'pending' ? 'warning' : 'secondary')); 
                  ?>">
                    <i class="fas fa-<?php 
                      echo $update['status'] === 'completed' ? 'check' : 
                          ($update['status'] === 'in progress' ? 'spinner' : 'pause'); 
                    ?>"></i>
                  </div>
                  <div class="timeline-panel">
                    <div class="timeline-heading">
                      <h6 class="timeline-title">Status changed to <strong><?php echo ucfirst(htmlspecialchars($update['status'])); ?></strong></h6>
                      <p><small class="text-muted"><i class="fas fa-clock"></i> <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($update['created_at']))); ?></small></p>
                    </div>
                    <div class="timeline-body">
                      <p><?php echo nl2br(htmlspecialchars($update['note'] ?? 'No notes provided.')); ?></p>
                      <?php if (!empty($update['photo_path'])): ?>
                        <div class="mt-2">
                          <a href="<?php echo url($update['photo_path']); ?>" target="_blank" class="btn btn-sm btn-outline-info">
                            <i class="fas fa-image"></i> View Attachment
                          </a>
                        </div>
                      <?php endif; ?>
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
</div>

<!-- Update Task Status Modal -->
<div class="modal fade" id="updateTaskStatusModal" tabindex="-1" aria-labelledby="updateTaskStatusModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="updateTaskStatusModalLabel">Update Task Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="updateTaskStatusForm" action="<?php echo url('/api/sub_contractor/tasks/update_status'); ?>" method="POST">
        <div class="modal-body">
          <input type="hidden" name="task_id" value="<?php echo htmlspecialchars($task['id']); ?>">
          <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status" required>
              <option value="pending" <?php echo $task['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
              <option value="in progress" <?php echo $task['status'] === 'in progress' ? 'selected' : ''; ?>>In Progress</option>
              <option value="completed" <?php echo $task['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
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

<!-- Add Progress Modal -->
<div class="modal fade" id="addProgressModal" tabindex="-1" aria-labelledby="addProgressModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addProgressModalLabel">Add Progress Note</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="addProgressForm" action="<?php echo url('/api/sub_contractor/tasks/add_progress'); ?>" method="POST" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" name="task_id" value="<?php echo htmlspecialchars($task['id']); ?>">
          <div class="mb-3">
            <label for="progress_note" class="form-label">Progress Note</label>
            <textarea class="form-control" id="progress_note" name="note" rows="5" required placeholder="Describe the progress, challenges, or any important information..."></textarea>
          </div>
          <div class="mb-3">
            <label for="photo" class="form-label">Attachment (Optional)</label>
            <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
            <small class="form-text text-muted">Upload a photo of the work if applicable.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Add Progress Note</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Custom CSS for Timeline -->
<style>
  .timeline {
    position: relative;
    padding: 20px 0;
  }
  
  .timeline:before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #e9ecef;
    left: 20px;
    margin-left: -2px;
  }
  
  .timeline-item {
    position: relative;
    margin-bottom: 30px;
  }
  
  .timeline-badge {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    text-align: center;
    position: absolute;
    left: 20px;
    margin-left: -20px;
    color: white;
    padding-top: 10px;
  }
  
  .timeline-panel {
    position: relative;
    width: calc(100% - 65px);
    float: right;
    border: 1px solid #d4d4d4;
    border-radius: 6px;
    padding: 20px;
    margin-right: 15px;
    box-shadow: 0 1px 6px rgba(0, 0, 0, 0.05);
  }
  
  .timeline-title {
    margin-top: 0;
    color: inherit;
  }
  
  .timeline-body > p,
  .timeline-body > ul {
    margin-bottom: 0;
  }
</style>

<script>
  $(document).ready(function() {
    // Handle task status update form submission
    $('#updateTaskStatusForm').on('submit', function(e) {
      e.preventDefault();
      
      $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            alert('Task status updated successfully');
            location.reload();
          } else {
            alert('Error: ' + (response.message || 'Failed to update task status'));
          }
        },
        error: function() {
          alert('An error occurred while processing your request');
        }
      });
    });
    
    // Handle progress note form submission
    $('#addProgressForm').on('submit', function(e) {
      e.preventDefault();
      
      var formData = new FormData(this);
      
      $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            alert('Progress note added successfully');
            location.reload();
          } else {
            alert('Error: ' + (response.message || 'Failed to add progress note'));
          }
        },
        error: function() {
          alert('An error occurred while processing your request');
        }
      });
    });
  });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
