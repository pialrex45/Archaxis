<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';

// Check authentication and role
requireAuth();
if (!hasAnyRole(['admin', 'manager'])) {
    http_response_code(403);
    die('Access denied. Admins and managers only.');
}

// Set page title and current page
$pageTitle = 'Create Project';
$currentPage = 'projects';
?>

<?php include_once __DIR__ . '/../../views/layouts/header.php'; ?>
<?php include_once __DIR__ . '/../../views/layouts/sidebar.php'; ?>

<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Create New Project</h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <form id="projectForm" method="POST" action="<?php echo url('/api/projects/create'); ?>" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="mb-3">
                    <label for="name" class="form-label">Project Name *</label>
                    <input type="text" class="form-control" id="name" name="name" required minlength="3" maxlength="100">
                    <div class="invalid-feedback">Please enter a project name (3-100 characters).</div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4" maxlength="1000"></textarea>
                    <div class="invalid-feedback">Description must be less than 1000 characters.</div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date">
                            <div class="invalid-feedback">Please enter a valid start date.</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date">
                            <div class="invalid-feedback">Please enter a valid end date.</div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="planning">Planning</option>
                        <option value="active">Active</option>
                        <option value="on hold">On Hold</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Create Project</button>
                    <a href="<?php echo url('/projects'); ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('projectForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Reset validation styles
    this.classList.remove('was-validated');
    
    // Get form data
    const formData = new FormData(this);
    
    // Client-side validation
    let isValid = true;
    
    // Validate project name
    const name = formData.get('name');
    if (!name || name.length < 3 || name.length > 100) {
        document.getElementById('name').classList.add('is-invalid');
        isValid = false;
    } else {
        document.getElementById('name').classList.remove('is-invalid');
        document.getElementById('name').classList.add('is-valid');
    }
    
    // Validate dates
    const startDate = formData.get('start_date');
    const endDate = formData.get('end_date');
    
    if (startDate && endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        if (start > end) {
            document.getElementById('end_date').classList.add('is-invalid');
            document.getElementById('end_date').nextElementSibling.textContent = 'End date must be after start date.';
            isValid = false;
        } else {
            document.getElementById('end_date').classList.remove('is-invalid');
            document.getElementById('end_date').classList.add('is-valid');
        }
    }
    
    // If client-side validation passes, submit to server
    if (isValid) {
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show';
                alert.innerHTML = `
                    ${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.col-md-9').insertBefore(alert, document.querySelector('.row'));
                
                // Reset form
                this.reset();
                document.querySelectorAll('.is-valid').forEach(el => el.classList.remove('is-valid'));
                
                // Redirect to projects list after 2 seconds
                setTimeout(() => {
                    window.location.href = '<?php echo url('/projects'); ?>';
                }, 2000);
            } else {
                // Show error message
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger alert-dismissible fade show';
                alert.innerHTML = `
                    ${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.col-md-9').insertBefore(alert, document.querySelector('.row'));
                
                // Handle field-specific errors
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        const fieldElement = document.getElementById(field);
                        if (fieldElement) {
                            fieldElement.classList.add('is-invalid');
                            fieldElement.nextElementSibling.textContent = data.errors[field];
                        }
                    });
                }
            }
        })
        .catch(error => {
            // Show error message
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger alert-dismissible fade show';
            alert.innerHTML = `
                An error occurred while creating the project.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.col-md-9').insertBefore(alert, document.querySelector('.row'));
        });
    }
    
    // Add validation class to show feedback
    this.classList.add('was-validated');
});
</script>

<?php include_once __DIR__ . '/../../views/layouts/footer.php'; ?>