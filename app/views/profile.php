<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/helpers.php';

// Check authentication
requireAuth();

$pageTitle = 'Profile';
$currentPage = 'profile';

// Get current user data
$currentUserId = getCurrentUserId();
$userRole = getCurrentUserRole();
$username = $_SESSION['username'] ?? '';
?>

<?php include_once __DIR__ . '/layouts/header.php'; ?>

<?php 
// Check if sidebar exists and include it if it does
$sidebarPath = __DIR__ . '/layouts/sidebar.php';
$hasSidebar = file_exists($sidebarPath);

if ($hasSidebar) {
    include_once $sidebarPath; 
?>
<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
<?php } else { ?>
<div class="container mt-4">
<?php } ?>

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Profile</h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">User Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($username); ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($userRole); ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">User ID</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUserId); ?>" readonly>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <form id="changePasswordForm">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const newPassword = formData.get('new_password');
    const confirmPassword = formData.get('confirm_password');
    
    if (newPassword !== confirmPassword) {
        alert('New password and confirm password do not match.');
        return;
    }
    
    // In a real implementation, this would make an AJAX call to change the password
    alert('In a real implementation, this would change your password.');
});
</script>

</div> <!-- Close the main content div -->

<?php include_once __DIR__ . '/layouts/footer.php'; ?>