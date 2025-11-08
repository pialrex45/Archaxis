<?php
require_once __DIR__ . '/../../core/helpers.php';
$pageTitle = 'Create new account';
$currentPage = 'register';

// Allow preselecting role via ?role=client|project_manager|...
$allowedRoles = [
  'admin','client','project_manager','site_manager','supervisor','site_engineer','logistic_officer','sub_contractor'
];
$prefRole = isset($_GET['role']) && in_array($_GET['role'], $allowedRoles, true) ? $_GET['role'] : '';

// Use glass layout with backdrop and replaceable background
// Make the signup section smaller
$authContentMaxWidth = 600; // reduced from 920 for a tighter form
$extraAuthStyles = url('/assets/css/auth-register.css');
// Use a different background for Sign Up (replace the file or override via ENV/variable)
$authBgUrl = url('/assets/img/register-bg.jpg');
// Layout options to match screenshot: show brand bar, left align content, with a small left offset
$authShowBrandNav = true;
$authAlign = 'left';
$authContentOffsetPx = 30;
include_once __DIR__ . '/../layouts/auth_glass_header.php';
?>

  <div class="auth-hero mb-3">
    <div class="auth-intro small-text">START FOR FREE</div>
    <h1 class="mb-1">Create new account<span class="dot">.</span></h1>
    <p class="auth-sub mb-3">Already a member? <a href="<?php echo url('/login'); ?>">Log in</a></p>
  </div>

  <form id="registerForm" method="POST" action="<?php echo url('/api/auth/register'); ?>" class="auth-card" novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label for="first_name" class="form-label small-text">First name *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-regular fa-id-badge"></i></span>
                <input type="text" class="form-control" id="first_name" name="first_name" required minlength="2" maxlength="50" placeholder="John">
              </div>
              <div class="invalid-feedback">Please enter your first name (2-50 characters).</div>
            </div>
            <div class="col-12 col-md-6">
              <label for="last_name" class="form-label small-text">Last name *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-regular fa-id-badge"></i></span>
                <input type="text" class="form-control" id="last_name" name="last_name" required minlength="2" maxlength="50" placeholder="Doe">
              </div>
              <div class="invalid-feedback">Please enter your last name (2-50 characters).</div>
            </div>

            <div class="col-12">
              <label for="username" class="form-label small-text">Username *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                <input type="text" class="form-control" id="username" name="username" required minlength="3" maxlength="50" placeholder="johndoe">
              </div>
              <div class="invalid-feedback">Please enter a username (3-50 characters).</div>
            </div>

            <div class="col-12">
              <label for="email" class="form-label small-text">Email *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-regular fa-envelope"></i></span>
                <input type="email" class="form-control" id="email" name="email" required placeholder="you@example.com">
              </div>
              <div class="invalid-feedback">Please enter a valid email address.</div>
            </div>

            <div class="col-12 col-md-6">
              <label for="password" class="form-label small-text">Password *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password" required minlength="6" placeholder="••••••••">
              </div>
              <div class="invalid-feedback">Password must be at least 6 characters long.</div>
            </div>
            <div class="col-12 col-md-6">
              <label for="confirm_password" class="form-label small-text">Confirm password *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="••••••••">
              </div>
              <div class="invalid-feedback">Passwords do not match.</div>
            </div>

            <div class="col-12 col-md-6">
              <label for="phone" class="form-label small-text">Phone</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                <input type="tel" class="form-control" id="phone" name="phone" maxlength="20" placeholder="+1 555 123 4567">
              </div>
              <div class="invalid-feedback">Please enter a valid phone number.</div>
            </div>
            <div class="col-12 col-md-6">
              <label for="role" class="form-label small-text">Role *</label>
              <select class="form-select" id="role" name="role" required>
                <option value="" disabled <?php echo $prefRole ? '' : 'selected'; ?> hidden>Select a role</option>
                <option value="admin" <?php echo $prefRole==='admin'?'selected':''; ?>>Admin</option>
                <option value="client" <?php echo $prefRole==='client'?'selected':''; ?>>Client</option>
                <option value="project_manager" <?php echo $prefRole==='project_manager'?'selected':''; ?>>Project Manager</option>
                <option value="site_manager" <?php echo $prefRole==='site_manager'?'selected':''; ?>>Site Manager</option>
                <option value="supervisor" <?php echo $prefRole==='supervisor'?'selected':''; ?>>Supervisor</option>
                <option value="site_engineer" <?php echo $prefRole==='site_engineer'?'selected':''; ?>>Site Engineer</option>
                <option value="logistic_officer" <?php echo $prefRole==='logistic_officer'?'selected':''; ?>>Logistic Officer</option>
                <option value="sub_contractor" <?php echo $prefRole==='sub_contractor'?'selected':''; ?>>Sub-Contractor</option>
              </select>
              <div class="invalid-feedback">Please select a role.</div>
            </div>
          </div>

          <div class="d-flex gap-2 mt-3">
            <a href="<?php echo url('/login'); ?>" class="btn btn-muted w-50">Log in</a>
            <button type="submit" class="btn btn-primary w-50">Create account</button>
          </div>
        </form>

<script>
document.getElementById('registerForm').addEventListener('submit', function(e) {
  e.preventDefault();
  this.classList.remove('was-validated');
  const formData = new FormData(this);

  let isValid = true;
  const password = formData.get('password');
  const confirmPassword = formData.get('confirm_password');
  const confirmEl = document.getElementById('confirm_password');
  if (password !== confirmPassword) {
    confirmEl.classList.add('is-invalid');
    confirmEl.nextElementSibling.textContent = 'Passwords do not match.';
    isValid = false;
  } else {
    confirmEl.classList.remove('is-invalid');
    confirmEl.classList.add('is-valid');
  }

  if (isValid) {
    fetch(this.action, {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
      const container = document.querySelector('.content-slot') || document.body;
      if (data.success) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-success alert-dismissible fade show mt-3';
        alert.innerHTML = `${data.message || 'Registration successful'}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        container.prepend(alert);
        this.reset();
        document.querySelectorAll('.is-valid').forEach(el => el.classList.remove('is-valid'));
        setTimeout(() => { window.location.href = '<?php echo url('/login'); ?>'; }, 1500);
      } else {
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show mt-3';
        alert.innerHTML = `${data.message || 'Registration failed'}<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>`;
        container.prepend(alert);
        if (data.errors) {
          Object.keys(data.errors).forEach(field => {
            const el = document.getElementById(field);
            if (el) {
              el.classList.add('is-invalid');
              const fb = el.closest('.col-12, .col-md-6')?.querySelector('.invalid-feedback') || el.nextElementSibling;
              if (fb) fb.textContent = data.errors[field];
            }
          });
        }
      }
    })
    .catch(() => {
      const container = document.querySelector('.content-slot') || document.body;
      const alert = document.createElement('div');
      alert.className = 'alert alert-danger alert-dismissible fade show mt-3';
      alert.innerHTML = `An error occurred while registering.<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
      container.prepend(alert);
    });
  }

  this.classList.add('was-validated');
});
</script>

<?php include_once __DIR__ . '/../layouts/auth_glass_footer.php'; ?>