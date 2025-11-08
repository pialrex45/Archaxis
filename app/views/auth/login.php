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
  $pageTitle = 'Sign in';
  // Background photo can be changed by:
  // 1) Setting AUTH_BG_URL in .env (absolute/relative URL)
  // 2) Setting $authBgUrl here
  // 3) Replacing file at public/assets/img/login-bg.jpg
  // Example: $authBgUrl = url('/uploads/your-photo.jpg');
  // $authBgUrl = url('/assets/img/your-photo.jpg');
  // Show the brand bar like the screenshot
  $authShowBrandNav = true;
  // Show Home button (and Join) on the brand bar for easy navigation back to landing
  $authBrandNav = 'right';
  include __DIR__ . '/../layouts/auth_glass_header.php'; 
?>

          <div class="auth-card">
            <h2 class="mb-1">Sign in</h2>
            <p class="auth-sub mb-4 small">Use your email account</p>

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

            <form id="loginForm" action="<?php echo url('/api/auth/login'); ?>" method="POST">
              <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

              <div class="mb-3">
                <label for="email" class="form-label small text-uppercase">Email</label>
                <div class="icon-input">
                  <i class="fa-regular fa-user"></i>
                  <input type="email" class="form-control" id="email" name="email" placeholder="you@example.com" required />
                </div>
              </div>

              <div class="mb-3">
                <label for="password" class="form-label small text-uppercase">Password</label>
                <div class="icon-input">
                  <i class="fa-regular fa-eye-slash" id="pwdEyeIcon" role="button" title="Show/Hide password"></i>
                  <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required />
                </div>
              </div>

              <button type="submit" class="btn btn-primary w-100 mb-3 text-uppercase">Enter</button>
              <div class="d-flex justify-content-start auth-link-row small">
                <a href="<?php echo url('/register'); ?>">Create account</a>
              </div>
            </form>
          </div>

<script>
// Show/Hide password (icon on input)
document.getElementById('pwdEyeIcon').addEventListener('click', function(){
  const pwd = document.getElementById('password');
  const is = pwd.getAttribute('type') === 'password';
  pwd.setAttribute('type', is ? 'text' : 'password');
  this.classList.toggle('fa-eye');
  this.classList.toggle('fa-eye-slash');
});

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
  (document.querySelector('.content-slot') || document.body).prepend(msg);
    }
  })
  .catch(() => {
    const msg = document.createElement('div');
    msg.className = 'alert alert-danger alert-dismissible fade show mt-3';
    msg.innerHTML = `An error occurred during login. Please try again.<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
  (document.querySelector('.content-slot') || document.body).prepend(msg);
  });
});
</script>

<?php include __DIR__ . '/../layouts/auth_glass_footer.php'; ?>