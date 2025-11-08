<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/helpers.php';

$pageTitle = 'Home';
$currentPage = 'home';
?>
<?php include_once __DIR__ . '/layouts/header.php'; ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12 text-center">
            <h1 class="display-4">Welcome to Smart Construction Site Management System</h1>
            <p class="lead">Efficiently manage your construction projects, tasks, materials, and finances in one place.</p>
            
            <?php if (isAuthenticated()): ?>
                <div class="mt-4">
                    <a href="<?php echo url('/dashboard'); ?>" class="btn btn-primary btn-lg me-2">Go to Dashboard</a>
                </div>
            <?php else: ?>
                <div class="mt-4">
                    <a href="<?php echo url('/login'); ?>" class="btn btn-primary btn-lg me-2">Login</a>
                    <a href="<?php echo url('/register'); ?>" class="btn btn-secondary btn-lg">Register</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row mt-5">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-project-diagram fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Project Management</h5>
                    <p class="card-text">Create and manage construction projects with detailed tracking and status updates.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-tasks fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Task Tracking</h5>
                    <p class="card-text">Assign and monitor tasks with due dates, priorities, and progress tracking.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-boxes fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Materials Management</h5>
                    <p class="card-text">Request, approve, and track materials for your construction projects.</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-money-bill-wave fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Finance Tracking</h5>
                    <p class="card-text">Monitor project expenses and income with detailed financial reporting.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-envelope fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Messaging</h5>
                    <p class="card-text">Communicate with team members through secure internal messaging.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-chart-bar fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Reporting</h5>
                    <p class="card-text">Generate detailed reports on project progress, expenses, and team performance.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/layouts/footer.php'; ?>