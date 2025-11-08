<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth();
$pageTitle = 'Designer';
$currentPage = 'designer';
include_once __DIR__ . '/../layouts/header.php';
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h1 class="h4 mb-0">Designing & Drawing</h1>
    <div class="d-flex gap-2">
      <a href="<?php echo url('/designer/app'); ?>" target="_blank" class="btn btn-sm btn-primary">Open Fullscreen</a>
    </div>
  </div>
  <div class="ratio ratio-16x9 border rounded shadow-sm">
    <iframe src="<?php echo url('/designer/app'); ?>" title="Designer" style="border:0;"></iframe>
  </div>
</div>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
