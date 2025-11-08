<?php
require_once __DIR__ . '/../../../core/auth.php';
require_once __DIR__ . '/../../../core/helpers.php';

requireAuth();
if (!hasAnyRole(['admin'])) { http_response_code(403); die('Access denied.'); }

$pageTitle = 'Site Manager Profile';
$currentPage = 'profile';

include_once __DIR__ . '/../../../views/layouts/header.php';
?>
<div class="row">
  <div class="col-md-12">
    <h1 class="mb-4">Site Manager Profile</h1>
    <div class="alert alert-info">Placeholder for Site Manager role profile.</div>
  </div>
</div>
<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>
