<!-- End of Main Content -->
        </div> <!-- .row -->
    </div> <!-- .container -->
    
    <!-- Footer -->
    <footer class="bg-light text-center text-lg-start mt-5">
        <div class="container p-4">
            <div class="row">
                <div class="col-lg-6 col-md-12 mb-4 mb-md-0">
                    <h5 class="text-uppercase">Smart Construction Site Management System</h5>
                    <p>
                        A comprehensive solution for managing all aspects of construction site operations.
                    </p>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <h5 class="text-uppercase">Links</h5>
                    <ul class="list-unstyled mb-0">
                        <li>
                            <a href="<?php echo url('/'); ?>" class="text-dark">Home</a>
                        </li>
                        <li>
                            <a href="<?php echo url('/about'); ?>" class="text-dark">About</a>
                        </li>
                        <li>
                            <a href="<?php echo url('/contact'); ?>" class="text-dark">Contact</a>
                        </li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <h5 class="text-uppercase">Contact</h5>
                    <ul class="list-unstyled mb-0">
                        <li>
                            <i class="fas fa-envelope"></i> info@construction-system.com
                        </li>
                        <li>
                            <i class="fas fa-phone"></i> +1 (555) 123-4567
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="text-center p-3 bg-primary text-white">
            &copy; <?php echo date('Y'); ?> Smart Construction Site Management System. All rights reserved.
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

            // Also add click handler for profile dropdown specifically
            document.getElementById('userDropdown').addEventListener('click', function(e) {
                e.preventDefault();
                var dropdown = bootstrap.Dropdown.getInstance(this) || new bootstrap.Dropdown(this);
                dropdown.toggle();
            });
            
            // Handle logout clicks
            document.querySelectorAll('.logout-link').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Call the logout API
                    $.ajax({
                        url: '<?php echo url('/api/logout.php'); ?>',
                        type: 'POST',
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                // Redirect to login with a success parameter
                                window.location.href = '<?php echo url('/login?logout=success'); ?>';
                            }
                        },
                        error: function() {
                            // If the API fails, fallback to the regular logout
                            window.location.href = '<?php echo url('/logout'); ?>';
                        }
                    });
                });
            });
        });
    </script>
    
    <!-- Project main.js (not present by default) -->
    <!-- <script src="<?php echo url('/assets/js/main.js'); ?>"></script> -->
    
    <!-- CSRF Token for AJAX requests -->
    <?php if (isAuthenticated()): ?>
        <script>
            const CSRF_TOKEN = '<?php echo generateCSRFToken(); ?>';
        </script>
    <?php endif; ?>
</body>
</html>