<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/ProjectController.php';

// Check authentication and role
requireAuth();
if (!hasAnyRole(['admin', 'manager', 'supervisor'])) {
    http_response_code(403);
    die('Access denied. Admins, managers, and supervisors only.');
}

// Set page title and current page
$pageTitle = 'Log Finance';
$currentPage = 'finance';

// Get project ID from URL parameter (optional)
$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

// Create ProjectController instance to get projects for dropdown
$projectController = new ProjectController();
$projectsResult = $projectController->getAll();
$projects = $projectsResult['success'] ? $projectsResult['data'] : [];
?>

<?php include_once __DIR__ . '/../../views/layouts/header.php'; ?>
<?php include_once __DIR__ . '/../../views/layouts/sidebar.php'; ?>

<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Log Finance Transaction</h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <form id="financeForm" method="POST" action="<?php echo url('/api/finance/log'); ?>" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="mb-3">
                    <label for="project_id" class="form-label">Project *</label>
                    <select class="form-select" id="project_id" name="project_id" required>
                        <option value="">Select a project</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>" <?php echo $projectId == $project['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($project['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Please select a project.</div>
                </div>
                
                <div class="mb-3">
                    <label for="type" class="form-label">Transaction Type *</label>
                    <select class="form-select" id="type" name="type" required>
                        <option value="income">Income</option>
                        <option value="expense">Expense</option>
                    </select>
                    <div class="invalid-feedback">Please select a transaction type.</div>
                </div>
                
                <div class="mb-3">
                    <label for="amount" class="form-label">Amount *</label>
                    <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0.01" max="999999999.99" required>
                    <div class="invalid-feedback">Please enter a valid amount (0.01-999999999.99).</div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description *</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required minlength="3" maxlength="1000"></textarea>
                    <div class="invalid-feedback">Please enter a description (3-1000 characters).</div>
                </div>
                
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Log Transaction</button>
                    <a href="<?php echo url('/finance'); ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('financeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Reset validation styles
    this.classList.remove('was-validated');
    
    // Get form data
    const formData = new FormData(this);
    
    // Client-side validation
    let isValid = true;
    
    // Validate project
    const projectId = formData.get('project_id');
    if (!projectId) {
        document.getElementById('project_id').classList.add('is-invalid');
        isValid = false;
    } else {
        document.getElementById('project_id').classList.remove('is-invalid');
        document.getElementById('project_id').classList.add('is-valid');
    }
    
    // Validate type
    const type = formData.get('type');
    if (!type) {
        document.getElementById('type').classList.add('is-invalid');
        isValid = false;
    } else {
        document.getElementById('type').classList.remove('is-invalid');
        document.getElementById('type').classList.add('is-valid');
    }
    
    // Validate amount
    const amount = parseFloat(formData.get('amount'));
    if (isNaN(amount) || amount < 0.01 || amount > 999999999.99) {
        document.getElementById('amount').classList.add('is-invalid');
        isValid = false;
    } else {
        document.getElementById('amount').classList.remove('is-invalid');
        document.getElementById('amount').classList.add('is-valid');
    }
    
    // Validate description
    const description = formData.get('description');
    if (!description || description.length < 3 || description.length > 1000) {
        document.getElementById('description').classList.add('is-invalid');
        isValid = false;
    } else {
        document.getElementById('description').classList.remove('is-invalid');
        document.getElementById('description').classList.add('is-valid');
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
                
                // Redirect to finance summary after 2 seconds
                setTimeout(() => {
                    window.location.href = '<?php echo url('/finance'); ?>';
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
                An error occurred while logging the transaction.
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