<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../models/Task.php';
require_once __DIR__ . '/../../models/TaskUpdate.php';
require_once __DIR__ . '/../../models/Project.php';
// Provide lightweight flash helper fallback if global one not loaded
if (!function_exists('setFlashMessage')) {
  function setFlashMessage($msg, $type='info'){ if(session_status()!==PHP_SESSION_ACTIVE){@session_start();} $_SESSION['flash'][]=['message'=>$msg,'type'=>$type]; }
}

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

// Get task details & flexible access check (mirrors controller logic)
$taskModel = new Task();
$task = $taskModel->getById($taskId);
$currentUserId = getCurrentUserId();
$hasAccess = false;
if ($task) {
  if ((int)$task['assigned_to'] === (int)$currentUserId) {
    $hasAccess = true;
  } else {
    // Check recent assigned-away cache
    $recent = isset($_SESSION['sc_recent_assigned_away']) && is_array($_SESSION['sc_recent_assigned_away']) ? $_SESSION['sc_recent_assigned_away'] : [];
    $now = time();
    foreach ($recent as $tid => $ts) {
      if ((int)$tid === (int)$taskId && ($now - (int)$ts) <= 86400) { $hasAccess = true; break; }
    }
    // Check prior activity (task_updates)
    if (!$hasAccess) {
      try {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT 1 FROM task_updates WHERE task_id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([(int)$taskId, (int)$currentUserId]);
        if ($stmt->fetchColumn()) { $hasAccess = true; }
      } catch (Throwable $e) { /* table may not exist; ignore */ }
    }
  }
}
if (!$task || !$hasAccess) {
  setFlashMessage('Task not found or you do not have access', 'danger');
  redirect('/sub-contractor/tasks');
  exit;
}

// Get project details
$projectModel = new Project();
$project = $projectModel->getById($task['project_id']);

// Get task updates/progress
$taskUpdateModel = new TaskUpdate();
$updates = $taskUpdateModel->getByTask($taskId);

$pageTitle = 'Task Details: ' . htmlspecialchars($task['title']);
$currentPage = 'tasks';

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid">
  <div id="taskFeedback"></div>
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
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
          <h6 class="m-0 font-weight-bold text-primary">Task Information</h6>
          <button id="scTaskRefresh" type="button" class="btn btn-sm btn-outline-secondary"><i class="fas fa-rotate"></i> Refresh</button>
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
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
          <h6 class="m-0 font-weight-bold text-primary mb-0">Progress Timeline</h6>
          <button id="scTimelineRefresh" type="button" class="btn btn-sm btn-outline-secondary"><i class="fas fa-rotate"></i> Refresh</button>
        </div>
        <div class="card-body">
          <?php if (empty($updates)): ?>
            <p class="text-center text-muted fst-italic">No progress updates yet.</p>
          <?php else: ?>
            <div class="timeline enhanced" id="taskTimeline">
              <?php foreach ($updates as $update): 
                $status = strtolower($update['status'] ?? '');
                $icon = 'circle';
                if ($status === 'completed') $icon = 'check';
                elseif ($status === 'in progress') $icon = 'spinner';
                elseif ($status === 'pending') $icon = 'hourglass-half';
              ?>
              <div class="timeline-item" data-status="<?php echo htmlspecialchars($status); ?>">
                <div class="timeline-marker">
                  <span class="marker-inner status-<?php echo htmlspecialchars(str_replace(' ','-',$status)); ?>">
                    <i class="fas fa-<?php echo $icon; ?>"></i>
                  </span>
                </div>
                <div class="timeline-card shadow-sm">
                  <div class="timeline-meta small text-muted mb-1">
                    <i class="fas fa-clock me-1"></i><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($update['created_at']))); ?>
                  </div>
                  <h6 class="mb-2 fw-semibold">Status changed to <span class="badge rounded-pill bg-<?php echo $status==='completed'?'success':($status==='in progress'?'primary':($status==='pending'?'warning':'secondary')); ?> text-uppercase small px-2 py-1"><?php echo htmlspecialchars($update['status']); ?></span></h6>
                  <div class="timeline-note mb-2">
                    <?php echo nl2br(htmlspecialchars($update['note'] ?? 'No notes provided.')); ?>
                  </div>
                  <?php if (!empty($update['photo_path'])): ?>
                  <div>
                    <a href="<?php echo url($update['photo_path']); ?>" target="_blank" class="btn btn-sm btn-outline-info"><i class="fas fa-image"></i> Attachment</a>
                  </div>
                  <?php endif; ?>
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

<style>
/* Themed variables */
:root {--tl-line: linear-gradient(to bottom,#e052a0,#f15c41,#ef9d43);--tl-card-bar:linear-gradient(to bottom,#ff8a05,#ff4d4d);--tl-font:'Poppins',system-ui,sans-serif;--tl-bg-fade:rgba(255,255,255,.55);} 
.theme-dark:root {--tl-bg-fade:rgba(0,0,0,.4);} 

/* Enhanced timeline styling with new palette */
.timeline.enhanced {position:relative;margin:0;padding:0 0 0 70px;font-family:var(--tl-font);} 
.timeline.enhanced:before {content:'';position:absolute;left:32px;top:0;bottom:0;width:4px;background:var(--tl-line);border-radius:4px;opacity:.4;} 
.timeline-item {position:relative;margin-bottom:30px;opacity:0;transform:translateY(14px) scale(.98);transition:all .55s cubic-bezier(.4,.1,.15,1);} 
.timeline-item.visible {opacity:1;transform:translateY(0) scale(1);} 
.timeline-marker {position:absolute;left:0;top:4px;width:64px;display:flex;justify-content:center;}
.timeline-marker .marker-inner {width:46px;height:46px;display:flex;align-items:center;justify-content:center;border-radius:18px;font-size:15px;color:#fff;box-shadow:0 6px 14px -4px rgba(0,0,0,.35),0 0 0 5px rgba(255,255,255,.35);background:linear-gradient(135deg,#6a11cb,#ff4d4d);position:relative;overflow:hidden;font-weight:600;letter-spacing:.5px;} 
.timeline-marker .marker-inner:after {content:'';position:absolute;inset:0;border-radius:inherit;background:radial-gradient(circle at 30% 30%,rgba(255,255,255,.45),rgba(255,255,255,0));mix-blend-mode:overlay;} 
.marker-inner.status-completed {background:linear-gradient(135deg,#0bab64,#3bb78f);} 
.marker-inner.status-in-progress {background:linear-gradient(135deg,#0575e6,#021b79);} 
.marker-inner.status-pending {background:linear-gradient(135deg,#f7971e,#ffd200);} 
.marker-inner.status- {background:linear-gradient(135deg,#868e96,#495057);} 
.timeline-card {background:var(--bs-body-bg,#fff);backdrop-filter:saturate(1.4) blur(2px);border:1px solid rgba(0,0,0,.04);border-radius:18px;padding:20px 22px;position:relative;overflow:hidden;box-shadow:0 4px 14px -4px rgba(0,0,0,.18);} 
.timeline-card:before {content:'';position:absolute;left:0;top:0;bottom:0;width:5px;background:var(--tl-card-bar);opacity:.85;} 
.timeline-card h6 {font-size:1rem;letter-spacing:.3px;} 
.timeline-meta {font-size:.7rem;letter-spacing:.5px;text-transform:uppercase;} 
.timeline-note {white-space:pre-line;font-size:.92rem;line-height:1.45;font-weight:400;} 
.timeline-item:last-child {margin-bottom:6px;} 
.timeline-card .badge {font-weight:600;letter-spacing:.5px;} 
@media (max-width:576px){ .timeline.enhanced {padding-left:58px;} .timeline-marker{width:54px;left:-2px;} .timeline.enhanced:before{left:26px;} .timeline-marker .marker-inner{width:42px;height:42px;} }
</style>

<script>
  (function(){
    const fb = document.getElementById('taskFeedback');
    function escapeHtml(str){ return String(str).replace(/[&<>"]|'/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c])); }
    function push(msg,type){
      if(!fb){ alert(msg); return; }
      fb.innerHTML = '<div class="alert alert-'+(type||'info')+' py-2 mb-2">'+escapeHtml(msg)+'</div>';
      setTimeout(()=>{ if(fb) fb.innerHTML=''; },3500);
    }
    function postForm(form, useFormData){
      const action = form.getAttribute('action');
      const opts = { method:'POST'};
      if(useFormData){ opts.body = new FormData(form); }
      else { opts.headers = {'Content-Type':'application/x-www-form-urlencoded'}; opts.body = new URLSearchParams(new FormData(form)); }
      return fetch(action, opts).then(r=>r.json().catch(()=>({success:false,message:'Invalid JSON'})));
    }
    function applyTaskData(t){
      if(!t) return; const badge = document.querySelector('span.badge.bg-success, span.badge.bg-primary, span.badge.bg-warning, span.badge.bg-secondary');
      if(badge){
        const st=(t.status||'').toLowerCase();
        let cls='secondary'; if(st==='completed') cls='success'; else if(st==='in progress') cls='primary'; else if(st==='pending') cls='warning';
        badge.className='badge bg-'+cls; badge.textContent=st.replace(/\b\w/g,c=>c.toUpperCase());
      }
      const sel=document.getElementById('status'); if(sel){ sel.value=t.status; }
    }
    function renderTimeline(updates){
      const wrap=document.getElementById('taskTimeline'); if(!wrap) return; wrap.innerHTML='';
      (updates||[]).forEach(u=>{
        const s=(u.status||'').toLowerCase(); let icon='circle'; if(s==='completed') icon='check'; else if(s==='in progress') icon='spinner'; else if(s==='pending') icon='hourglass-half';
        let cls='secondary'; if(s==='completed') cls='success'; else if(s==='in progress') cls='primary'; else if(s==='pending') cls='warning';
        const div=document.createElement('div'); div.className='timeline-item visible';
        div.innerHTML=`<div class="timeline-marker"><span class="marker-inner status-${s.replace(/ /g,'-')}"><i class="fas fa-${icon}"></i></span></div><div class="timeline-card shadow-sm"><div class="timeline-meta small text-muted mb-1"><i class="fas fa-clock me-1"></i>${escapeHtml((u.created_at||'').replace('T',' ').substring(0,16))}</div><h6 class="mb-2 fw-semibold">Status changed to <span class="badge rounded-pill bg-${cls} text-uppercase small px-2 py-1">${escapeHtml(u.status||'—')}</span></h6><div class="timeline-note mb-2">${escapeHtml(u.note||'No notes provided.')}</div>${u.photo_path?('<div><a target="_blank" href="<?php echo url('/'); ?>'+u.photo_path.replace(/^\//,'')+'" class="btn btn-sm btn-outline-info"><i class="fas fa-image"></i> Attachment</a></div>'):''}</div>`;
        wrap.appendChild(div);
      });
    }
    function fetchTask(){
      const id=<?php echo (int)$task['id']; ?>;
      fetch('<?php echo rtrim(url('/api/sub_contractor/tasks'),'/'); ?>/'+id).then(r=>r.json()).then(j=>{ if(j&&j.success){ applyTaskData(j.data); renderTimeline(j.data.updates); push('Refreshed','success'); } else { push('Refresh failed','danger'); } }).catch(()=>push('Network error','danger'));
    }
    const statusForm=document.getElementById('updateTaskStatusForm');
    if(statusForm){ statusForm.addEventListener('submit', function(ev){ ev.preventDefault(); postForm(statusForm,false).then(r=>{ if(r.success){ push('Status updated','success'); fetchTask(); } else { push(r.message||'Failed to update','danger'); } }).catch(()=>push('Network error','danger')); }, {passive:false}); }
    const progressForm=document.getElementById('addProgressForm');
    if(progressForm){ progressForm.addEventListener('submit', function(ev){ ev.preventDefault(); postForm(progressForm,true).then(r=>{ if(r.success){ push('Progress added','success'); fetchTask(); progressForm.reset(); } else { push(r.message||'Failed to add progress','danger'); } }).catch(()=>push('Network error','danger')); }, {passive:false}); }
    document.getElementById('scTaskRefresh')?.addEventListener('click', fetchTask);
    document.getElementById('scTimelineRefresh')?.addEventListener('click', fetchTask);
    // Reveal animation using IntersectionObserver
    try {
      const items = document.querySelectorAll('#taskTimeline .timeline-item');
      const io = new IntersectionObserver((entries)=>{
        entries.forEach(en=>{ if(en.isIntersecting){ en.target.classList.add('visible'); io.unobserve(en.target);} });
      },{root:null,threshold:.12});
      items.forEach(it=>io.observe(it));
    } catch(e){
      // Fallback: show all
      document.querySelectorAll('#taskTimeline .timeline-item').forEach(it=>it.classList.add('visible'));
    }
  })();
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
