<?php
// Signup view

// Include necessary files
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../app/core/auth.php';
require_once __DIR__ . '/../../../app/core/helpers.php';

// Redirect if already authenticated
redirectIfAuthenticated();

// Generate CSRF token
$csrfToken = generateCSRFToken();
?>

<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">Register</h3>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($_SESSION['flash_message_type'] ?? 'info'); ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php 
                    unset($_SESSION['flash_message']);
                    unset($_SESSION['flash_message_type']);
                    ?>
                <?php endif; ?>
                
                <form id="signupForm" action="<?php echo url('/api/auth/signup'); ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">Password must be at least 6 characters long.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="" disabled selected hidden>Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="client">Client</option>
                            <option value="project_manager">Project Manager</option>
                            <option value="site_manager">Site Manager</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="site_engineer">Site Engineer</option>
                            <option value="logistic_officer">Logistic Officer</option>
                            <option value="sub_contractor">Sub-Contractor</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="rank" class="form-label">Rank/Position</label>
                        <input type="text" class="form-control" id="rank" name="rank">
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Register</button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <p>Already have an account? <a href="<?php echo url('/login'); ?>">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Handle form submission with AJAX
document.getElementById('signupForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message and redirect to login
            alert(data.message);
            window.location.href = '<?php echo url('/login'); ?>';
        } else {
            // Show error message
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred during registration. Please try again.');
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>