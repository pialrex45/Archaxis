<?php
// Split layout header for auth pages (light, image left, form right)
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../core/helpers.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo htmlspecialchars($pageTitle ?? 'Sign in'); ?> - <?php echo htmlspecialchars(env('APP_NAME','Archaxis')); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
  <link href="<?php echo url('/assets/css/auth-split.css'); ?>?v=20251011" rel="stylesheet" />
  <?php if (!empty($extraAuthStyles)): ?>
    <link href="<?php echo htmlspecialchars($extraAuthStyles, ENT_QUOTES); ?>" rel="stylesheet" />
  <?php endif; ?>
</head>
<body class="auth-split-body">
  <main class="container-fluid px-0">
    <div class="row g-0 min-vh-100">
      <!-- Left: Hero image and caption -->
  <div class="col-12 col-lg-7 order-lg-2 split-left position-relative">
        <div class="split-bg" <?php if (!empty($leftBgUrl)) { echo 'style="background:url('.htmlspecialchars($leftBgUrl, ENT_QUOTES).') center/cover no-repeat"'; } ?>></div>
        <div class="split-overlay"></div>
        <div class="split-caption">
          <h1 class="display-5 fw-bold mb-2"><?php echo htmlspecialchars($leftCaptionTitle ?? 'Welcome back'); ?></h1>
          <?php if (!empty($leftCaptionSubtitle)): ?>
            <p class="lead mb-0"><?php echo htmlspecialchars($leftCaptionSubtitle); ?></p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Right: Content (form card is injected by view) -->
  <div class="col-12 col-lg-5 order-lg-1 split-right d-flex flex-column">
        <div class="split-right-top px-3 px-lg-5 pt-3 pt-lg-4">
          <a href="<?php echo url('/'); ?>" class="back-link">&larr; Back to Home</a>
        </div>
        <div class="flex-grow-1 d-flex align-items-center justify-content-center px-3 px-lg-5">
          <div class="content-slot w-100" style="max-width: 520px;">
