<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Smart Construction Site Management System'; ?></title>
    
    <!-- CSS Files -->
    <!-- Bootstrap CSS via CDN (local file not present) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Project custom styles -->
    <link rel="stylesheet" href="<?php echo url('/assets/css/style.css'); ?>">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo url('/assets/img/logo.svg'); ?>">
    <link rel="alternate icon" href="<?php echo url('/assets/img/logo.svg'); ?>">
    
    <style>
        /* Additional styles to match the screenshot */
        .navbar-dark.bg-primary {
            background-color: #0d6efd !important;
        }
        .navbar .nav-link {
            color: rgba(255,255,255,0.85) !important;
            padding: 0.5rem 1rem;
        }
        .navbar .nav-link:hover {
            color: #fff !important;
        }
        /* Make profile and logout links more prominent */
        .navbar-nav.ms-auto .nav-link {
            padding: 0.5rem 1rem;
            margin-left: 0.25rem;
            border-radius: 4px;
        }
        .navbar-nav.ms-auto .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
        }
    </style>
</head>
<body class="theme-soft">
    <!-- Navigation Bar matching screenshot -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary navbar-gradient sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo url('/'); ?>">
                <img src="<?php echo url('/assets/img/logo.svg'); ?>" alt="Smart Construction" height="44" class="me-2" style="vertical-align:middle;"> 
                <span class="fw-semibold">Smart Construction</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if (isAuthenticated()): ?>
                    <!-- Main Navigation moved to sidebar; header links hidden by request -->
                    <ul class="navbar-nav d-none">
                        <!-- intentionally empty -->
                    </ul>
                    
                    <!-- User Menu (right aligned) with Profile link showing as in the screenshot -->
                    <ul class="navbar-nav ms-auto align-items-center">
                        <!-- Dark mode toggle -->
                        <li class="nav-item me-1">
                          <button id="themeToggle" class="btn btn-sm btn-outline-light" type="button" title="Toggle theme">
                            <i class="fas fa-moon"></i>
                          </button>
                        </li>
                        <!-- Direct access to profile -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('/profile'); ?>">
                                <i class="fas fa-user-circle me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                            </a>
                        </li>
                        
                        <!-- Separate visible logout button -->
                        <li class="nav-item">
                            <a class="nav-link logout-link" href="#" title="Logout">
                                <i class="fas fa-sign-out-alt me-1"></i> Logout
                            </a>
                        </li>
                    </ul>
                <?php else: ?>
                    <!-- Guest Menu -->
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('/login'); ?>">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('/register'); ?>">
                                <i class="fas fa-user-plus"></i> Register
                            </a>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <!-- Hidden Logout Form -->
    <form id="logoutForm" action="<?php echo url('/api/auth/logout.php'); ?>" method="POST" class="d-none">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
    </form>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Theme: respect saved preference or system default
        try {
          var pref = localStorage.getItem('theme');
          if (!pref) {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) pref = 'dark';
          }
          if (pref === 'dark') {
            document.body.classList.add('theme-dark');
            document.documentElement.setAttribute('data-bs-theme', 'dark');
          }
        } catch(e) {}

        var toggle = document.getElementById('themeToggle');
        if (toggle) {
          toggle.addEventListener('click', function(){
            var isDark = document.body.classList.toggle('theme-dark');
            document.documentElement.setAttribute('data-bs-theme', isDark ? 'dark' : 'light');
            try { localStorage.setItem('theme', isDark ? 'dark' : 'light'); } catch(e) {}
            // Switch icon
            var i = toggle.querySelector('i');
            if (i) { i.className = isDark ? 'fas fa-sun' : 'fas fa-moon'; }
          });
        }
        var logoutLinks = document.querySelectorAll('.logout-link');
        logoutLinks.forEach(function(link){
          link.addEventListener('click', function(e){
            e.preventDefault();
            var form = document.getElementById('logoutForm');
            if (!form) return;
            // Submit via fetch to handle JSON and redirect cleanly
            var formData = new FormData(form);
            fetch(form.action, { method: 'POST', body: formData, credentials: 'same-origin' })
              .then(function(res){ return res.json().catch(function(){ return {success:false}; }); })
              .then(function(json){
                if (json && json.success) {
                  window.location.href = '<?php echo url('/login'); ?>';
                } else {
                  // Fallback: submit the form traditionally
                  form.submit();
                }
              })
              .catch(function(){ form.submit(); });
          });
        });
      });
    </script>
    
    <!-- Main Content Container -->
    <div class="container mt-4 shadow-soft border-gradient-primary surface-glass rounded-3 p-3">
        <div class="row">
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo htmlspecialchars($_SESSION['flash_message_type'] ?? 'info'); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php 
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_message_type']);
            ?>
        <?php endif; ?>