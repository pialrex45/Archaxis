<?php
// Login view

// Include necessary files
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../app/core/auth.php';
require_once __DIR__ . '/../../../app/core/helpers.php';

// Redirect if already authenticated
redirectIfAuthenticated();

// Generate CSRF token
$csrfToken = generateCSRFToken();
?>

<?php 
  $pageTitle = 'Login';
  include __DIR__ . '/../layouts/auth_header.php'; 
?>

        <div class="auth-intro small-text">START FOR FREE</div>
        <h1 class="mb-2">Log in<span class="dot">.</span></h1>
        <p class="auth-sub mb-4">Don't have an account? <a href="<?php echo url('/register'); ?>">Create one</a></p>

        <?php if (isset($_SESSION['flash_message'])): ?>
          <div class="alert alert-<?php echo htmlspecialchars($_SESSION['flash_message_type'] ?? 'info'); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php unset($_SESSION['flash_message'], $_SESSION['flash_message_type']); ?>
        <?php endif; ?>
        
        <?php if (isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> Logged out successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <form id="loginForm" action="<?php echo url('/api/auth/login'); ?>" method="POST" class="auth-card mt-2">
          <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

          <div class="mb-3">
            <label for="email" class="form-label small-text">Email</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-regular fa-envelope"></i></span>
              <input type="email" class="form-control" id="email" name="email" placeholder="you@example.com" required />
            </div>
          </div>

          <div class="mb-3">
            <label for="password" class="form-label small-text">Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
              <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required />
            </div>
          </div>

          <div class="d-flex gap-2 mt-3">
            <a href="<?php echo url('/register'); ?>" class="btn btn-muted w-50">Create account</a>
            <button type="submit" class="btn btn-primary w-50">Log in</button>
          </div>
        </form>

<script>
// Handle form submission with AJAX
document.getElementById('loginForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  fetch(form.action, {
    method: 'POST',
    body: formData,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      window.location.href = '<?php echo url('/dashboard'); ?>';
    } else {
      const msg = document.createElement('div');
      msg.className = 'alert alert-danger alert-dismissible fade show mt-3';
      msg.innerHTML = `${data.message || 'Login failed'}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
      document.querySelector('.auth-content').prepend(msg);
    }
  })
  .catch(() => {
    const msg = document.createElement('div');
    msg.className = 'alert alert-danger alert-dismissible fade show mt-3';
    msg.innerHTML = `An error occurred during login. Please try again.<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    document.querySelector('.auth-content').prepend(msg);
  });
});
</script>

<?php include __DIR__ . '/../layouts/auth_footer.php'; ?>