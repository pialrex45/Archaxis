<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth(); if (!hasRole('project_manager')) { http_response_code(403); die('Access denied.'); }
include_once __DIR__ . '/../layouts/header.php';
?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Projects</h2>
    <a href="#" class="btn btn-primary" id="btnNewProject">
        <i class="fas fa-plus-circle"></i> New Project
    </a>
  </div>
  <div class="card">
    <div class="card-body">
      <div id="projectsTable">Loading...</div>
    </div>
  </div>
</div>

<!-- Edit Project Modal -->
<div class="modal fade" id="editProjectModal" tabindex="-1" aria-labelledby="editProjectModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editProjectModalLabel">Edit Project</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="editProjectForm">
          <input type="hidden" id="edit_project_id">
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="edit_project_name" class="form-label">Project Name</label>
              <input type="text" class="form-control" id="edit_project_name" name="name" required>
            </div>
            <div class="col-md-6">
              <label for="edit_project_status" class="form-label">Status</label>
              <select class="form-select" id="edit_project_status" name="status" required>
                <option value="planning">Planning</option>
                <option value="active">Active</option>
                <option value="on hold">On Hold</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label for="edit_project_description" class="form-label">Description</label>
            <textarea class="form-control" id="edit_project_description" name="description" rows="3"></textarea>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="edit_start_date" class="form-label">Start Date</label>
              <input type="date" class="form-control" id="edit_start_date" name="start_date">
            </div>
            <div class="col-md-6">
              <label for="edit_end_date" class="form-label">End Date</label>
              <input type="date" class="form-control" id="edit_end_date" name="end_date">
            </div>
          </div>
          <div class="mb-3">
            <label for="edit_budget" class="form-label">Budget</label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input type="number" class="form-control" id="edit_budget" name="budget" step="0.01">
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="saveEditProjectBtn">Save Changes</button>
      </div>
    </div>
  </div>
</div>

<!-- Assign Site Manager Modal -->
<div class="modal fade" id="assignSiteManagerModal" tabindex="-1" aria-labelledby="assignSiteManagerModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="assignSiteManagerModalLabel">Assign Site Manager</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="assignSiteManagerForm">
          <input type="hidden" id="assign_project_id">
          <div class="mb-3">
            <label for="project_name_display" class="form-label">Project</label>
            <input type="text" class="form-control" id="project_name_display" readonly>
          </div>
          <div class="mb-3">
            <label for="site_manager_id" class="form-label">Select Site Manager</label>
            <select class="form-select" id="site_manager_id" name="site_manager_id" required>
              <option value="">-- Select Site Manager --</option>
              <!-- Will be populated dynamically -->
            </select>
          </div>
        </form>
        <div id="assignmentMessage" class="mt-3"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="saveSiteManagerAssignmentBtn">Assign</button>
      </div>
    </div>
  </div>
</div>

<script>
// Global variables to store projects data
let projectsData = [];

// Load projects data
function loadProjects() {
  fetch('<?= url('/api/pm/projects/list') ?>', {headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r => r.json())
    .then(d => {
      const wrap = document.getElementById('projectsTable');
      if (!d.success || !Array.isArray(d.data) || d.data.length === 0) { 
        wrap.innerHTML = '<div class="text-muted">No projects found.</div>'; 
        return; 
      }
      
      projectsData = d.data; // Store projects data
      
      let html = '<div class="table-responsive"><table class="table table-hover"><thead><tr><th>#</th><th>Name</th><th>Status</th><th>Site Manager</th><th>Actions</th></tr></thead><tbody>';
      
      d.data.forEach(p => {
        const projectId = p.id || p.project_id || '';
        const projectName = p.name || p.project_name || '';
        const status = p.status || '';
        const siteManager = p.site_manager_name || 'Not Assigned';
        
        // Create status badge
        let statusBadgeClass = 'secondary';
        if (status === 'active') statusBadgeClass = 'primary';
        else if (status === 'completed') statusBadgeClass = 'success';
        else if (status === 'on hold' || status === 'on_hold') statusBadgeClass = 'warning';
        else if (status === 'cancelled') statusBadgeClass = 'danger';
        
        html += `
          <tr>
            <td>${projectId}</td>
            <td>${projectName}</td>
            <td><span class="badge bg-${statusBadgeClass}">${status}</span></td>
            <td>${siteManager}</td>
            <td>
              <button class="btn btn-sm btn-outline-primary edit-project" data-id="${projectId}" data-name="${projectName}">
                <i class="fas fa-edit"></i> Edit
              </button>
              <button class="btn btn-sm btn-outline-info assign-site-manager" data-id="${projectId}" data-name="${projectName}">
                <i class="fas fa-user-plus"></i> Assign SM
              </button>
            </td>
          </tr>`;
      });
      
      html += '</tbody></table></div>';
      wrap.innerHTML = html;
      
      // Add event listeners to buttons
      document.querySelectorAll('.edit-project').forEach(btn => {
        btn.addEventListener('click', function() {
          const projectId = this.getAttribute('data-id');
          openEditProjectModal(projectId);
        });
      });
      
      document.querySelectorAll('.assign-site-manager').forEach(btn => {
        btn.addEventListener('click', function() {
          const projectId = this.getAttribute('data-id');
          const projectName = this.getAttribute('data-name');
          openAssignSiteManagerModal(projectId, projectName);
        });
      });
    })
    .catch(() => { 
      document.getElementById('projectsTable').innerHTML = '<div class="text-danger">Failed to load projects.</div>'; 
    });
}

// Open edit project modal
function openEditProjectModal(projectId) {
  const project = projectsData.find(p => (p.id == projectId || p.project_id == projectId));
  
  if (!project) {
    alert('Project not found!');
    return;
  }
  
  // Populate form fields
  document.getElementById('edit_project_id').value = projectId;
  document.getElementById('edit_project_name').value = project.name || project.project_name || '';
  // Normalize status to match select options
  const normalizedStatus = (project.status === 'on_hold') ? 'on hold' : (project.status || 'planning');
  document.getElementById('edit_project_status').value = normalizedStatus;
  document.getElementById('edit_project_description').value = project.description || '';
  document.getElementById('edit_start_date').value = project.start_date || '';
  document.getElementById('edit_end_date').value = project.end_date || '';
  document.getElementById('edit_budget').value = project.budget || '';
  
  // Show modal
  const editModal = new bootstrap.Modal(document.getElementById('editProjectModal'));
  editModal.show();
}

// Load site managers for assignment
function loadSiteManagers() {
  console.log('Loading site managers...');
  return fetch('<?= url('/api/pm/site-managers/list') ?>', {headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r => r.json())
    .then(d => {
      console.log('Site managers response:', d);
      
      if (!d.success && !Array.isArray(d)) {
        return false;
      }
      
      const select = document.getElementById('site_manager_id');
      select.innerHTML = '<option value="">-- Select Site Manager --</option>';
      
      // Handle both formats: array directly or inside d.data
      const managers = Array.isArray(d) ? d : (d.data || []);
      
      managers.forEach(sm => {
        // Make sure we're using the correct field names
        const id = sm.id || sm.user_id;
        const name = sm.name || sm.user_name || `${sm.first_name || ''} ${sm.last_name || ''}`.trim();
        
        if (id && name) {
          select.innerHTML += `<option value="${id}">${name}</option>`;
        }
      });
      
      return true;
    })
    .catch(error => {
      console.error('Error loading site managers:', error);
      return false;
    });
}

// Open assign site manager modal
function openAssignSiteManagerModal(projectId, projectName) {
  // Set project details
  document.getElementById('assign_project_id').value = projectId;
  document.getElementById('project_name_display').value = projectName;
  
  // Load site managers
  loadSiteManagers().then(success => {
    if (!success) {
      document.getElementById('assignmentMessage').innerHTML = 
        '<div class="alert alert-warning">Unable to load site managers.</div>';
    }
  });
  
  // Show modal
  const assignModal = new bootstrap.Modal(document.getElementById('assignSiteManagerModal'));
  assignModal.show();
}

// Save project changes
document.getElementById('saveEditProjectBtn').addEventListener('click', function() {
  const projectId = document.getElementById('edit_project_id').value;
  
  const projectData = {
    name: document.getElementById('edit_project_name').value,
    status: document.getElementById('edit_project_status').value,
    description: document.getElementById('edit_project_description').value,
    start_date: document.getElementById('edit_start_date').value,
    end_date: document.getElementById('edit_end_date').value,
    budget: document.getElementById('edit_budget').value
  };
  
  // Send update request
  fetch('<?= url('/api/pm/projects/update.php') ?>', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': '<?= generateCSRFToken() ?>'
    },
    body: JSON.stringify({
      project_id: projectId,
      data: projectData
    })
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      // Close modal and reload projects
      bootstrap.Modal.getInstance(document.getElementById('editProjectModal')).hide();
      loadProjects();
    } else {
      alert('Failed to update project: ' + (d.message || 'Unknown error'));
    }
  })
  .catch(err => {
    alert('Error: ' + err.message);
  });
});

// Assign site manager
document.getElementById('saveSiteManagerAssignmentBtn').addEventListener('click', function() {
  // Get form values
  const projectId = document.getElementById('assign_project_id').value;
  const siteManagerId = document.getElementById('site_manager_id').value;
  const siteManagerName = document.getElementById('site_manager_id').options[document.getElementById('site_manager_id').selectedIndex].text;
  
  // Basic validation
  if (!siteManagerId) {
    document.getElementById('assignmentMessage').innerHTML = 
      '<div class="alert alert-danger">Please select a site manager.</div>';
    return;
  }
  
  console.log('Assigning site manager:', { projectId, siteManagerId, siteManagerName });
  
  // Send assignment request
  fetch('<?= url('/api/pm/projects/assign-site-manager.php') ?>', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': '<?= generateCSRFToken() ?>'
    },
    body: JSON.stringify({
      project_id: projectId,
      site_manager_id: siteManagerId
    })
  })
  .then(r => r.json())
  .then(d => {
    console.log('Assignment response:', d);
    
    if (d.success) {
      document.getElementById('assignmentMessage').innerHTML = 
        '<div class="alert alert-success">Site Manager assigned successfully!</div>';
      
      // Update the projects data in memory with the new site manager
      const projectIndex = projectsData.findIndex(p => p.id == projectId || p.project_id == projectId);
      if (projectIndex !== -1) {
        projectsData[projectIndex].site_manager_id = siteManagerId;
        projectsData[projectIndex].site_manager_name = siteManagerName;
      }
      
      // Reload projects after a delay
      setTimeout(() => {
        bootstrap.Modal.getInstance(document.getElementById('assignSiteManagerModal')).hide();
        loadProjects();
      }, 1500);
    } else {
      document.getElementById('assignmentMessage').innerHTML = 
        `<div class="alert alert-danger">Failed: ${d.message || 'Unknown error'}</div>`;
    }
  })
  .catch(err => {
    console.error('Assignment error:', err);
    document.getElementById('assignmentMessage').innerHTML = 
      `<div class="alert alert-danger">Error: ${err.message}</div>`;
  });
});

// Load projects on page load
document.addEventListener('DOMContentLoaded', function() {
  loadProjects();
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
