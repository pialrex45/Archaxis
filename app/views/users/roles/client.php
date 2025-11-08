<?php
require_once __DIR__ . '/../../../core/auth.php';
require_once __DIR__ . '/../../../core/helpers.php';

requireAuth();
if (!hasAnyRole(['admin'])) { http_response_code(403); die('Access denied.'); }

$pageTitle = 'Client Profile';
$currentPage = 'profile';

include_once __DIR__ . '/../../../views/layouts/header.php';
?>
<div class="row">
  <div class="col-md-12">
    <h1 class="mb-4">Client Profile</h1>
    <div class="alert alert-info">Placeholder for Client role profile.</div>
  </div>
</div>
<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>
