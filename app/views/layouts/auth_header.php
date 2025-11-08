<?php
// Minimal header for auth pages (dark theme)
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../core/helpers.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
$csrf = generateCSRFToken();
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo htmlspecialchars($pageTitle ?? 'Auth'); ?> - <?php echo htmlspecialchars(env('APP_NAME','Archaxis')); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
  <link href="<?php echo url('/assets/css/auth.css'); ?>?v=20251008-3" rel="stylesheet" />
</head>
<body class="<?php echo htmlspecialchars($bodyClass ?? 'auth-body'); ?>">
  <main class="auth-wrapper container-fluid px-0">
    <div class="row g-0 min-vh-100">
      <div class="col-12 col-lg-6 auth-left d-flex flex-column justify-content-center">
        <div class="auth-brand d-flex align-items-center mb-4 px-4 px-lg-5">
          <div class="brand-dot me-2"></div>
          <div class="brand-name fw-semibold"><?php echo htmlspecialchars(env('APP_NAME','Archaxis')); ?></div>
          <!-- Desktop nav -->
          <div class="ms-auto d-none d-lg-flex gap-2">
            <a class="btn btn-outline-light btn-sm auth-pill" href="<?php echo url('/'); ?>">Home</a>
            <a class="btn btn-primary btn-sm auth-pill" href="<?php echo url('/register'); ?>">Join</a>
          </div>
          <!-- Mobile nav -->
          <div class="ms-auto d-flex d-lg-none gap-2">
            <a class="btn btn-outline-light btn-sm auth-pill" href="<?php echo url('/'); ?>">Home</a>
            <a class="btn btn-primary btn-sm auth-pill" href="<?php echo url('/register'); ?>">Join</a>
          </div>
        </div>
        <div class="auth-content px-4 px-lg-5">
