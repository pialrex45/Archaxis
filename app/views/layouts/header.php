<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $appName = function_exists('env') ? (env('APP_NAME', 'Archaxis')) : 'Archaxis'; ?>
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : htmlspecialchars($appName); ?></title>
    <!-- DEBUG: <?php echo 'url(\'/test\')=' . url('/test') . ' | SCRIPT_NAME=' . ($_SERVER['SCRIPT_NAME'] ?? 'none'); ?> -->
    
    <!-- CSS Files -->
    <!-- Bootstrap CSS via CDN (local file not present) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Project custom styles -->
    <link rel="stylesheet" href="<?php echo url('/assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('/assets/css/modal-fix.css'); ?>">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script defer src="<?php echo url('/assets/js/modal-fix.js'); ?>"></script>
    
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
    <?php if (!empty($extraStyles)) { echo $extraStyles; } ?>
    <?php if (!empty($extraHeadScripts)) { echo $extraHeadScripts; } ?>
        <script>
            // Expose app base path for JS URL builders when hosted in a subfolder
            window.__APP_URL = '<?php echo rtrim(url('/'), '/'); ?>';
            // Back-compat alias expected by some standalone pages/scripts (e.g., diagram exporter)
            // This ensures API calls resolve correctly from /designer route as well.
            window.__APP_BASE_URL = window.__APP_URL;
            // Expose current user context for client-side helpers (e.g., chat bubble coloring)
            <?php if (function_exists('isAuthenticated') && isAuthenticated()): ?>
            window.__CURRENT_USER_ID = <?php echo (int) getCurrentUserId(); ?>;
            window.__CURRENT_USER_ROLE = '<?php echo htmlspecialchars(strtolower((string) getCurrentUserRole()), ENT_QUOTES); ?>';
            window.__CURRENT_USER_NAME = '<?php echo htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES); ?>';
            <?php endif; ?>
        </script>
</head>
<body class="theme-soft <?php echo isset($bodyClass) ? htmlspecialchars($bodyClass) : ''; ?>" style="--sc-font: 'Poppins', system-ui, sans-serif; font-family: var(--sc-font);">
    <!-- Navigation Bar matching screenshot -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary navbar-gradient sticky-top">
        <div class="container-fluid">
                        <?php
                            // Determine safe internal landing link
                            // Always send authenticated users to unified dashboard route unless overridden
                            $brandLink = '/';
                            $auth = function_exists('isAuthenticated') && isAuthenticated();
                            $forceGuest = !empty($forceGuestNav);
                            if ($auth && !$forceGuest) {
                                $brandLink = '/dashboard';
                            }
                        ?>
                        <a class="navbar-brand" href="<?php echo url($brandLink); ?>">
                <img src="<?php echo url('/assets/img/logo.svg'); ?>" alt="<?php echo htmlspecialchars($appName); ?>" height="44" class="me-2" style="vertical-align:middle;"> 
                <span class="fw-semibold"><?php echo htmlspecialchars($appName); ?></span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php 
                    $auth = function_exists('isAuthenticated') && isAuthenticated();
                    $forceGuest = !empty($forceGuestNav);
                ?>
                <?php if ($auth && !$forceGuest): ?>
                    <!-- Small quick links -->
                    <ul class="navbar-nav">
                        <li class="nav-item"><a class="nav-link" href="<?php echo url('/designs'); ?>"><i class="fa-solid fa-drafting-compass me-1"></i>Designs</a></li>
                    </ul>
                    
                                        <!-- User Menu (right aligned) with Profile link showing as in the screenshot -->
                    <ul class="navbar-nav ms-auto align-items-center">
                                                <!-- Messages quick access for all roles -->
                                                                        <li class="nav-item">
                                                                            <a class="nav-link" href="<?php echo url('/messages'); ?>" title="Open Messages">
                                                                                <i class="fas fa-comments me-1"></i> Messages
                                                                            </a>
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
                        <li class="nav-item d-none d-lg-block"><a class="nav-link" href="<?php echo url('/#features'); ?>">Features</a></li>
                        <li class="nav-item d-none d-lg-block"><a class="nav-link" href="<?php echo url('/#solutions'); ?>">Solutions</a></li>
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
    <form id="logoutForm" action="<?php echo url('/api/logout.php'); ?>" method="POST" class="d-none">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
    </form>
    <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Unified, idempotent logout handler to avoid duplicate bindings across header/footer
                if (!window.__logout) {
                    window.__logout = function() {
                        try {
                            var endpoint = '<?php echo url('/api/logout.php'); ?>';
                            return fetch(endpoint, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: '' })
                                .then(function(res){ return res.json().catch(function(){ return { success: false }; }); })
                                .then(function(json){
                                    if (json && json.success) {
                                        window.location.href = '<?php echo url('/login?logout=success'); ?>';
                                    } else {
                                        window.location.href = '<?php echo url('/logout'); ?>';
                                    }
                                })
                                .catch(function(){ window.location.href = '<?php echo url('/logout'); ?>'; });
                        } catch (e) {
                            window.location.href = '<?php echo url('/logout'); ?>';
                        }
                    }
                }
                if (!window.__logoutBound) {
                    document.querySelectorAll('.logout-link').forEach(function(link){
                        link.addEventListener('click', function(e){ e.preventDefault(); window.__logout(); });
                    });
                    window.__logoutBound = true;
                }
      });
    </script>
    
    <!-- Main Content Container -->
    <div class="container mt-4 shadow-soft border-gradient-primary surface-glass rounded-3 p-3 fade-in">
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