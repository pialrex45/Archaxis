<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/helpers.php';

$appName = function_exists('env') ? (env('APP_NAME', 'Archaxis')) : 'Archaxis';
$pageTitle = $appName . ' — Construction Management Platform';
$currentPage = 'home';
// Inject landing CSS into head via layout hook
$extraStyles = '<link rel="stylesheet" href="'.htmlspecialchars(url('/assets/css/landing.css'), ENT_QUOTES).'">';
// Give body a landing class for full-bleed hero
$bodyClass = 'landing-body';
// Force guest-like navbar on marketing landing regardless of auth state
$forceGuestNav = true;
?>
<?php include_once __DIR__ . '/layouts/header.php'; ?>

<!-- HERO (Second-page style) -->
<section class="position-relative overflow-hidden hero-ax py-5" style="background: radial-gradient(1000px 500px at -10% -20%, rgba(13,110,253,.25), transparent 60%), radial-gradient(900px 450px at 110% 0%, rgba(123,97,255,.18), transparent 55%), linear-gradient(180deg, #eef4ff, #ffffff);">
    <div class="container position-relative">
        <div class="row align-items-center g-4 g-lg-5">
            <div class="col-lg-6 order-2 order-lg-1">
                <span class="badge rounded-pill mb-3">Modern Construction Platform</span>
                <h1 class="display-4 fw-bold lh-tight mb-2" style="letter-spacing:-.02em;">
                    Build faster with <?php echo htmlspecialchars($appName); ?>
                </h1>
                <p class="lead text-secondary mt-2 section-subtitle">
                    A clean, professional landing for Clients, Suppliers, and Project Teams. Keep projects, procurement, messaging, and finance in one connected hub.
                </p>

                <?php if (isAuthenticated()): ?>
                    <div class="mt-4 d-flex flex-wrap gap-2">
                        <a class="btn btn-primary btn-lg" href="<?php echo url('/dashboard'); ?>">
                            Go to Dashboard
                        </a>
                        <a class="btn btn-outline-secondary btn-lg" href="<?php echo url('/messages'); ?>">
                            Open Messages
                        </a>
                    </div>
                <?php else: ?>
                    <div class="mt-4 d-flex flex-wrap gap-2">
                        <a class="btn btn-primary btn-lg" href="<?php echo url('/login'); ?>">Login</a>
                        <a class="btn btn-outline-primary btn-lg" href="<?php echo url('/register'); ?>">Sign up</a>
                    </div>
                    <small class="text-muted d-block mt-2">Already a member? Login. New here? Create your account in minutes.</small>
                <?php endif; ?>

                <div class="row mt-5 g-3">
                    <div class="col-12 col-md-4">
                        <div class="p-3 border rounded-3 h-100 card-feature">
                            <div class="text-primary mb-2"><i class="fa-solid fa-user-tie fa-lg"></i></div>
                            <h6 class="mb-1">Clients</h6>
                            <p class="mb-2 small text-secondary">Request builds, track progress, and approve milestones in real time.</p>
                            <a class="small" href="<?php echo url('/register?role=client'); ?>">Get started →</a>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="p-3 border rounded-3 h-100 card-feature">
                            <div class="text-success mb-2"><i class="fa-solid fa-truck fa-lg"></i></div>
                            <h6 class="mb-1">Suppliers</h6>
                            <p class="mb-2 small text-secondary">List products, manage orders, and deliver to active projects.</p>
                            <a class="small" href="<?php echo url('/register'); ?>">Join marketplace →</a>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="p-3 border rounded-3 h-100 card-feature">
                            <div class="text-warning mb-2"><i class="fa-solid fa-helmet-safety fa-lg"></i></div>
                            <h6 class="mb-1">Project Teams</h6>
                            <p class="mb-2 small text-secondary">Coordinate tasks, materials, finances, and reporting in one place.</p>
                            <a class="small" href="<?php echo url('/register?role=project_manager'); ?>">Start managing →</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 order-1 order-lg-2">
                <div class="ratio ratio-16x9 rounded-4 shadow" style="background: linear-gradient(135deg, #eff3ff, #ffffff);">
                  <div class="d-flex align-items-center justify-content-center">
                    <div class="text-center p-4">
                      <img src="<?php echo url('/assets/img/logo.svg'); ?>" alt="<?php echo htmlspecialchars($appName); ?> logo" class="mb-3 hero-logo">
                      <h5 class="fw-semibold mb-1">Business landing page for construction teams</h5>
                      <p class="text-secondary small mb-0">Projects • Materials • Finance • Messaging • Reports</p>
                    </div>
                  </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Curved separator -->
    <svg viewBox="0 0 1440 120" preserveAspectRatio="none" style="display:block; width:100%; height:80px; margin-top: -10px;">
      <path fill="#ffffff" d="M0,64L80,58.7C160,53,320,43,480,64C640,85,800,139,960,149.3C1120,160,1280,128,1360,112L1440,96L1440,0L1360,0C1280,0,1120,0,960,0C800,0,640,0,480,0C320,0,160,0,80,0L0,0Z"></path>
    </svg>
</section>

<!-- CALCULATOR HIGHLIGHT -->
<section id="calc-highlight" class="py-5" style="background: radial-gradient(800px 400px at 110% -10%, rgba(16,185,129,.12), transparent 55%), linear-gradient(180deg, #ffffff, #f7fffb);">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-6 order-2 order-lg-1">
                <span class="badge rounded-pill text-bg-success mb-3">New · Client-friendly</span>
                <h2 class="section-title mb-2">Estimate & Tax Calculator</h2>
                <p class="section-subtitle">Break down costs (labor, materials, equipment), auto‑fill unit prices by supplier and product, apply tax, and print a clean PDF.</p>
                <ul class="text-muted mb-3">
                    <li>Category totals and tax with live updates</li>
                    <li>Inline editing and PDF‑ready output</li>
                    <li>Project import/export when logged in</li>
                </ul>
                <?php if (!isAuthenticated()): ?>
                    <a class="btn btn-success btn-lg" href="<?= url('/estimate-calculator/public') ?>" target="_blank" rel="noopener">
                        Try the Calculator
                    </a>
                    <a class="btn btn-outline-secondary btn-lg ms-2" href="<?= url('/register') ?>">Create account</a>
                <?php else: ?>
                    <a class="btn btn-success btn-lg" href="<?= url('/estimate-calculator') ?>" target="_blank" rel="noopener">
                        Open Calculator
                    </a>
                    <a class="btn btn-outline-secondary btn-lg ms-2" href="<?= url('/dashboard') ?>">Go to Dashboard</a>
                <?php endif; ?>
            </div>
            <div class="col-lg-6 order-1 order-lg-2">
                <div class="calc-art rounded-4 border shadow-sm bg-white position-relative overflow-hidden">
                    <img src="<?= url('/assets/img/port-hero.jpg') ?>" alt="Estimate & Tax Calculator — demo preview" class="w-100 d-block" style="object-fit:cover; aspect-ratio:16/9;">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES/SERVICES -->
<section id="features" class="py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center text-center mb-4">
            <div class="col-lg-8">
                <h2 class="section-title">Everything your construction team needs</h2>
                <p class="section-subtitle">From bid to handover—manage projects, procurement, teams, and reporting in an integrated workspace.</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm card-feature">
                    <div class="card-body">
                        <div class="text-primary mb-3"><i class="fa-solid fa-diagram-project fa-2x"></i></div>
                        <h5 class="card-title">Project orchestration</h5>
                        <p class="card-text text-secondary">Plan phases, assign owners, and track progress with clarity.</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm card-feature">
                    <div class="card-body">
                        <div class="text-success mb-3"><i class="fa-solid fa-box-open fa-2x"></i></div>
                        <h5 class="card-title">Materials & suppliers</h5>
                        <p class="card-text text-secondary">Request, approve, and fulfill materials with supplier matching.</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm card-feature">
                    <div class="card-body">
                        <div class="text-warning mb-3"><i class="fa-solid fa-sack-dollar fa-2x"></i></div>
                        <h5 class="card-title">Finance & reports</h5>
                        <p class="card-text text-secondary">Keep budgets in check and export clear, role-based reports.</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm card-feature">
                    <div class="card-body">
                        <div class="text-info mb-3"><i class="fa-solid fa-comments fa-2x"></i></div>
                        <h5 class="card-title">Built-in messaging</h5>
                        <p class="card-text text-secondary">Stay aligned with secure, contextual conversations.</p>
                    </div>
                </div>
            </div>
        </div>

                <?php if (!isAuthenticated()): ?>
                <div class="text-center mt-5">
                        <a href="<?php echo url('/register'); ?>" class="btn btn-primary btn-lg me-2">Create free account</a>
                        <a href="<?php echo url('/login'); ?>" class="btn btn-outline-secondary btn-lg">I already have an account</a>
                </div>
                <?php endif; ?>
    </div>
</section>

<!-- SOLUTIONS -->
<section id="solutions" class="py-5">
    <div class="container">
        <div class="row justify-content-center text-center mb-4">
            <div class="col-lg-8">
                <h2 class="section-title">Solutions for every stakeholder</h2>
                <p class="section-subtitle">Whether you’re commissioning, supplying, or executing, Archaxis keeps everyone on the same page.</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 shadow-sm card-feature">
                    <div class="card-body">
                        <div class="text-primary mb-2"><i class="fa-solid fa-building-user"></i></div>
                        <h5>For Clients</h5>
                        <p class="text-muted">Approve milestones, track budgets, and get status updates without chasing.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm card-feature">
                    <div class="card-body">
                        <div class="text-success mb-2"><i class="fa-solid fa-warehouse"></i></div>
                        <h5>For Suppliers</h5>
                        <p class="text-muted">Respond to requests, manage POs, and deliver on time with clear requirements.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm card-feature">
                    <div class="card-body">
                        <div class="text-warning mb-2"><i class="fa-solid fa-people-group"></i></div>
                        <h5>For Project Teams</h5>
                        <p class="text-muted">Coordinate tasks, materials, and finances. Keep work and communication together.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </section>


<!-- PREVIEW: 1-minute Designer trial for guests -->
<section id="preview" class="py-5">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <h2 class="section-title">Try the Designer — 3-minute preview</h2>
                <p class="section-subtitle">Open the in-browser drawing tool used by project teams. No signup needed — you get 3 minutes to explore.</p>
                <ul class="text-muted">
                    <li>Pan, zoom, draw shapes and connectors</li>
                    <li>Runs instantly in your browser</li>
                    <li>Preview only — changes aren’t saved</li>
                </ul>
                <div class="mt-3 d-flex flex-wrap gap-2">
                    <button id="startPreviewBtn" class="btn btn-primary btn-lg">Start 3‑minute preview</button>
                    <div id="previewCountdown" class="align-self-center small text-secondary d-none">Time remaining: <span data-remaining>180</span>s</div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="calc-art rounded-4 border shadow-sm bg-white position-relative overflow-hidden">
                    <img src="<?= url('/assets/img/login-hero.jpg') ?>" alt="Designer preview illustration" class="w-100 d-block" style="object-fit:cover; aspect-ratio:16/9;">
                </div>
            </div>
        </div>
    </div>

    <!-- Modal prompting sign up after preview ends -->
    <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Preview ended</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Your 3-minute preview has ended. Create a free account to keep working and save your designs.
                </div>
                <div class="modal-footer">
                    <a href="<?= url('/register') ?>" class="btn btn-primary">Create account</a>
                    <a href="<?= url('/login') ?>" class="btn btn-outline-secondary">Log in</a>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- STATS -->
<section class="py-5">
    <div class="container">
        <div class="row g-3 g-lg-4">
            <div class="col-6 col-md-3">
                <div class="p-4 stat-tile text-center">
                    <div class="h3 mb-0">50+</div>
                    <div class="text-muted small">Active Projects</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-4 stat-tile text-center">
                    <div class="h3 mb-0">200+</div>
                    <div class="text-muted small">Suppliers</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-4 stat-tile text-center">
                    <div class="h3 mb-0">1,000+</div>
                    <div class="text-muted small">Team Members</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-4 stat-tile text-center">
                    <div class="h3 mb-0">99.9%</div>
                    <div class="text-muted small">Uptime</div>
                </div>
            </div>
        </div>
    </div>
    </section>

<!-- TESTIMONIALS (placeholder) -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center text-center mb-4">
            <div class="col-lg-8">
                <h2 class="section-title">Trusted by modern construction teams</h2>
                <p class="section-subtitle">Real teams streamline procurement, coordination, and delivery using Archaxis.</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="p-4 testimonial rounded-3 shadow-sm">
                    <p class="mb-2">“Archaxis centralized our entire operation. Materials, suppliers, and tasks in one place made delivery faster.”</p>
                    <div class="small text-muted">— Project Manager, BuildPro Ltd.</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-4 testimonial rounded-3 shadow-sm">
                    <p class="mb-2">“We finally see progress and cost in the same view. Approvals are quicker and issues get resolved faster.”</p>
                    <div class="small text-muted">— Site Manager, MetroConstruct</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-5 cta-ax border-top">
    <div class="container">
        <div class="row align-items-center g-3">
            <div class="col-lg-8">
                <h3 class="mb-1">Ready to streamline your next build?</h3>
                <p class="text-muted mb-0">Create your account and connect your clients, suppliers, and teams in minutes.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <?php if (!isAuthenticated()): ?>
                    <a class="btn btn-primary btn-lg" href="<?php echo url('/register'); ?>">Get started</a>
                <?php else: ?>
                    <a class="btn btn-primary btn-lg" href="<?php echo url('/dashboard'); ?>">Open dashboard</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php ob_start(); ?>
<script>
    (function(){
        var startBtn = document.getElementById('startPreviewBtn');
        var countdown = document.getElementById('previewCountdown');
        var span = countdown ? countdown.querySelector('[data-remaining]') : null;
        var timer = null;

            function openPreviewWindow() {
                // Open a tab synchronously on click to avoid popup blockers
                // We'll navigate it after the preview token is created
                var w = window.open('about:blank','_blank');
                if (!w) {
                    alert('Please allow popups to open the preview.');
                }
                return w;
            }

        function startCountdown(seconds){
            if (!countdown || !span) return;
            countdown.classList.remove('d-none');
            var remaining = seconds;
            span.textContent = remaining;
            clearInterval(timer);
            timer = setInterval(function(){
                remaining -= 1;
                if (remaining <= 0) {
                    clearInterval(timer);
                    span.textContent = 0;
                    var modalEl = document.getElementById('previewModal');
                    if (modalEl && window.bootstrap) {
                        var modal = new bootstrap.Modal(modalEl);
                        modal.show();
                    }
                    return;
                }
                span.textContent = remaining;
            }, 1000);
        }

            function startPreview(){
                var win = openPreviewWindow();
                // Start the token request in parallel; begin countdown immediately
                startCountdown(180);
                fetch('<?php echo url('/designer/preview-start'); ?>', { credentials: 'same-origin' })
                    .then(function(r){ return r.json().catch(function(){ return {}; }); })
                    .then(function(data){
                        if (data && data.success && data.until) {
                            var now = Math.floor(Date.now()/1000);
                            var remaining = Math.max(0, data.until - now);
                            startCountdown(remaining);
                        }
                    })
                    .catch(function(){ /* ignore; we still try to open */ })
                            .finally(function(){
                                try {
                                    var target = '<?php echo url('/designer/preview'); ?>';
                                    if (win) {
                                        win.location.href = target;
                                    } else {
                                        // Popup blocked: navigate current tab as a fallback
                                        window.location.href = target;
                                    }
                                } catch(e) {
                                    window.location.href = '<?php echo url('/designer/preview'); ?>';
                                }
                            });
            }

        if (startBtn) startBtn.addEventListener('click', startPreview);
    })();
</script>
<?php $extraScripts = (isset($extraScripts) ? $extraScripts : '') . ob_get_clean(); ?>

    <?php include_once __DIR__ . '/layouts/footer.php'; ?>