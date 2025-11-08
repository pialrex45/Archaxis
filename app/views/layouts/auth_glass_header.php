<?php
// Fullscreen background + centered glass card layout for auth pages
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../core/helpers.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// Allow background override via variable or ENV; fallback to default path
$authBgUrl = $authBgUrl
  ?? env('AUTH_BG_URL')
  ?? url('/assets/img/login-bg.jpg');

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo htmlspecialchars($pageTitle ?? 'Sign in'); ?> - <?php echo htmlspecialchars(env('APP_NAME','Archaxis')); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
  <link href="<?php echo url('/assets/css/auth-glass.css'); ?>?v=20251017" rel="stylesheet" />
  <?php if (!empty($extraAuthStyles)): ?>
    <link href="<?php echo htmlspecialchars($extraAuthStyles, ENT_QUOTES); ?>" rel="stylesheet" />
  <?php endif; ?>
</head>
<body class="auth-glass-body<?php echo !empty($authShowBrandNav) ? ' has-brandbar' : ''; ?>">
  <!-- Background image with overlay -->
  <div class="auth-glass-bg" style="background-image:url('<?php echo htmlspecialchars($authBgUrl, ENT_QUOTES); ?>')"></div>
  <div class="auth-glass-overlay"></div>

  <?php $showBrand = !empty($authShowBrandNav); ?>
  <?php if ($showBrand): ?>
    <div class="auth-brandbar d-flex align-items-center px-3 px-md-4 py-2">
      <div class="d-flex align-items-center gap-2">
        <span class="brand-dot"></span>
        <span class="brand-name fw-semibold"><?php echo htmlspecialchars(env('APP_NAME','Archaxis')); ?></span>
      </div>
      <?php $navMode = $authBrandNav ?? 'right'; ?>
      <?php if ($navMode !== 'none'): ?>
        <div class="ms-auto d-flex gap-2">
          <a class="btn btn-outline-light btn-sm auth-pill" href="<?php echo url('/'); ?>">Home</a>
          <a class="btn btn-primary btn-sm auth-pill" href="<?php echo url('/register'); ?>">Join</a>
        </div>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <!-- Back to Home link -->
    <a class="back-to-home small" href="<?php echo url('/'); ?>">&larr; Back to Home</a>
  <?php endif; ?>

  <!-- Content slot (centered) -->
  <?php 
    $justify = (isset($authAlign) && $authAlign === 'left') ? 'justify-content-start' : 'justify-content-center';
    $offset = isset($authContentOffsetPx) ? (int)$authContentOffsetPx : 0;
  ?>
  <main class="d-flex align-items-center <?php echo $justify; ?> min-vh-100 px-3">
    <div class="content-slot w-100" style="max-width: <?php echo (int)($authContentMaxWidth ?? 520); ?>px; margin-left: <?php echo $offset; ?>px;">