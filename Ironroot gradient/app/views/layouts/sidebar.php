<?php
// Sidebar navigation based on user role
$userRole = getCurrentUserRole();
?>

<div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'dashboard') ? 'active' : ''; ?>" href="<?php echo url('/dashboard'); ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            
            <?php if (hasAnyRole(['admin', 'owner', 'project_manager'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'projects') ? 'active' : ''; ?>" href="<?php echo url('/projects'); ?>">
                    <i class="fas fa-project-diagram"></i> Projects
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasAnyRole(['admin', 'owner', 'project_manager', 'site_manager', 'supervisor'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'tasks') ? 'active' : ''; ?>" href="<?php echo url('/tasks'); ?>">
                    <i class="fas fa-tasks"></i> Tasks
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasAnyRole(['admin', 'owner', 'project_manager', 'site_manager', 'supervisor', 'general_contractor'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'materials') ? 'active' : ''; ?>" href="<?php echo url('/materials'); ?>">
                    <i class="fas fa-boxes"></i> Materials
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasAnyRole(['admin', 'owner', 'project_manager', 'site_manager', 'supervisor', 'general_contractor'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'marketplace') ? 'active' : ''; ?>" href="<?php echo url('/marketplace'); ?>">
                    <i class="fas fa-store"></i> Marketplace
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasAnyRole(['admin', 'owner', 'project_manager', 'site_manager', 'supervisor', 'general_contractor'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'purchase_orders') ? 'active' : ''; ?>" href="<?php echo url('/purchase-orders'); ?>">
                    <i class="fas fa-file-invoice-dollar"></i> Purchase Orders
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasAnyRole(['admin', 'owner', 'project_manager', 'site_manager', 'supervisor', 'general_contractor'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'finance') ? 'active' : ''; ?>" href="<?php echo url('/finance'); ?>">
                    <i class="fas fa-money-bill-wave"></i> Finance
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'messages') ? 'active' : ''; ?>" href="<?php echo url('/messages'); ?>">
                    <i class="fas fa-envelope"></i> Messages
                    <?php if (isset($unreadMessagesCount) && $unreadMessagesCount > 0): ?>
                        <span class="badge bg-danger rounded-pill float-end"><?php echo $unreadMessagesCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <?php if (hasAnyRole(['admin', 'owner', 'project_manager', 'site_manager', 'supervisor'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'attendance') ? 'active' : ''; ?>" href="<?php echo url('/attendance'); ?>">
                    <i class="fas fa-clock"></i> Attendance
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasAnyRole(['admin', 'owner', 'project_manager'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'reports') ? 'active' : ''; ?>" href="<?php echo url('/reports'); ?>">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'pm_tax_report') ? 'active' : ''; ?>" href="<?php echo url('/pm/tax-report'); ?>">
                    <i class="fas fa-file-invoice-dollar"></i> Tax Report
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasRole('admin')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'users') ? 'active' : ''; ?>" href="<?php echo url('/users'); ?>">
                    <i class="fas fa-users"></i> Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'products') ? 'active' : ''; ?>" href="<?php echo url('/products'); ?>">
                    <i class="fas fa-box"></i> Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'suppliers') ? 'active' : ''; ?>" href="<?php echo url('/suppliers'); ?>">
                    <i class="fas fa-industry"></i> Suppliers
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'settings') ? 'active' : ''; ?>" href="<?php echo url('/settings'); ?>">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
            <?php endif; ?>
        </ul>
        
        <?php if (hasRole('client')): ?>
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Client</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'client_projects') ? 'active' : ''; ?>" href="<?php echo url('/client/projects'); ?>">
                    <i class="fas fa-project-diagram"></i> Projects
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'client_tasks') ? 'active' : ''; ?>" href="<?php echo url('/client/tasks'); ?>">
                    <i class="fas fa-tasks"></i> Tasks
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'client_materials') ? 'active' : ''; ?>" href="<?php echo url('/client/materials'); ?>">
                    <i class="fas fa-boxes"></i> Materials
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'client_purchase_orders') ? 'active' : ''; ?>" href="<?php echo url('/client/purchase-orders'); ?>">
                    <i class="fas fa-file-invoice-dollar"></i> Purchase Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'client_reports') ? 'active' : ''; ?>" href="<?php echo url('/client/reports'); ?>">
                    <i class="fas fa-chart-line"></i> Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'client_messages') ? 'active' : ''; ?>" href="<?php echo url('/client/messages'); ?>">
                    <i class="fas fa-envelope"></i> Messages
                </a>
            </li>
        </ul>
        <?php endif; ?>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Account</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'profile') ? 'active' : ''; ?>" href="<?php echo url('/profile'); ?>">
                    <i class="fas fa-user-circle"></i> Profile
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo url('/logout'); ?>">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</div>