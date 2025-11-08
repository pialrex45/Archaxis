<?php
require_once __DIR__ . '/../core/helpers.php';
$pageTitle = 'Contact Us';
include __DIR__ . '/layouts/header.php';
?>

<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <h1 class="mb-3">Get in touch</h1>
        <p class="text-muted">Tell us about your projects, procurement needs, or partnerships.</p>

        <div id="contactAlert" class="alert d-none"></div>

        <form id="contactForm" class="card shadow-sm p-4" action="<?php echo url('/api/contact'); ?>" method="post">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Name</label>
              <input name="name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input name="email" type="email" class="form-control" required>
            </div>
            <div class="col-12">
              <label class="form-label">Message</label>
              <textarea name="message" class="form-control" rows="5" required></textarea>
            </div>
          </div>
          <div class="mt-3">
            <button class="btn btn-primary" type="submit">Send message</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
<script>
document.getElementById('contactForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const form = e.target;
  const alert = document.getElementById('contactAlert');
  alert.className = 'alert d-none';
  try {
    const res = await fetch(form.action, { method: 'POST', body: new FormData(form) });
    const data = await res.json();
    alert.className = 'alert ' + (data.success ? 'alert-success' : 'alert-danger');
    alert.textContent = data.message || (data.success ? 'Sent!' : 'Failed to send');
    alert.classList.remove('d-none');
    if (data.success) form.reset();
  } catch (err) {
    alert.className = 'alert alert-danger';
    alert.textContent = 'Something went wrong. Please try again.';
    alert.classList.remove('d-none');
  }
});
</script>

<?php include __DIR__ . '/layouts/footer.php'; ?>
