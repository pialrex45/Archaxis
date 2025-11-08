<!-- End of Main Content -->
        </div> <!-- .row -->
    </div> <!-- .container -->
    
    <!-- Footer -->
    <?php $appName = function_exists('env') ? (env('APP_NAME', 'Archaxis')) : 'Archaxis'; ?>
    <footer class="bg-light text-center text-lg-start mt-5">
        <div class="container p-4">
            <div class="row">
                <div class="col-lg-6 col-md-12 mb-4 mb-md-0">
                    <h5 class="text-uppercase"><?php echo htmlspecialchars($appName); ?> — Construction Management Platform</h5>
                    <p>
                        Manage clients, suppliers, teams, projects, and operations in one streamlined platform.
                    </p>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <h5 class="text-uppercase">Links</h5>
                    <ul class="list-unstyled mb-0">
                        <li>
                            <a href="<?php echo url('/'); ?>" class="text-dark">Home</a>
                        </li>
                        
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <h5 class="text-uppercase">Contact</h5>
                    <ul class="list-unstyled mb-0">
                        <li>
                            <i class="fas fa-envelope"></i> randomuiu@gmail.com
                        </li>
                        <li>
                            <i class="fas fa-phone"></i> +8801816188048
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="text-center p-3 bg-primary text-white">
            &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($appName); ?>. All rights reserved.
        </div>
    </footer>
    
    <!-- JavaScript Files -->
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Initialize all dropdowns -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap dropdowns
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            dropdownElementList.map(function(dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });

            // Also add click handler for profile dropdown specifically (guard if missing)
            var userDropdownEl = document.getElementById('userDropdown');
            if (userDropdownEl) {
                userDropdownEl.addEventListener('click', function(e) {
                    e.preventDefault();
                    var dropdown = bootstrap.Dropdown.getInstance(this) || new bootstrap.Dropdown(this);
                    dropdown.toggle();
                });
            }
            
            // Delegate to the unified logout handler set in header; avoid re-binding multiple times
            if (!window.__footerLogoutBound) {
                document.querySelectorAll('.logout-link').forEach(function(link) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        if (typeof window.__logout === 'function') { window.__logout(); }
                        else { window.location.href = '<?php echo url('/logout'); ?>'; }
                    });
                });
                window.__footerLogoutBound = true;
            }
        });
    </script>
    
    <!-- Project main.js (not present by default) -->
    <!-- <script src="<?php echo url('/assets/js/main.js'); ?>"></script> -->
    
    <?php if (!empty($extraScripts)) { echo $extraScripts; } ?>

    <!-- CSRF Token for AJAX requests -->
    <?php if (isAuthenticated()): ?>
        <script>
            const CSRF_TOKEN = '<?php echo generateCSRFToken(); ?>';
        </script>
    <?php endif; ?>
</body>
</html>