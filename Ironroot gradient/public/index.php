<?php
// Front controller and router

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Include autoloader or config files
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/core/auth.php';
require_once BASE_PATH . '/app/core/helpers.php';

// Start session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
// Enforce session timeout on each request (1 hour default)
if (function_exists('checkSessionTimeout')) {
    checkSessionTimeout(3600);
}

// Get the requested URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Normalize when app is served from a subdirectory (e.g., /Ironroot)
$baseUrl = rtrim(env('APP_URL', ''), '/');
$appBasePath = $baseUrl ? (parse_url($baseUrl, PHP_URL_PATH) ?: '') : '';
if ($appBasePath && strpos($uri, $appBasePath) === 0) {
    $uri = substr($uri, strlen($appBasePath));
    if ($uri === '') { $uri = '/'; }
}

// Remove query string and fragment
$uri = explode('?', $uri)[0];
$uri = explode('#', $uri)[0];

// Remove trailing slash if not root
if ($uri !== '/' && substr($uri, -1) === '/') {
    $uri = rtrim($uri, '/');
}

// Basic routing
switch ($uri) {
    case '/':
    case '/home':
        // Home page
        $pageTitle = 'Home';
        include BASE_PATH . '/app/views/home.php';
        break;
        
    case '/login':
        // Login page
        $pageTitle = 'Login';
        include BASE_PATH . '/app/views/auth/login.php';
        break;
        
    case '/register':
        // Registration page
        $pageTitle = 'Register';
        include BASE_PATH . '/app/views/auth/register.php';
        break;
        
    case '/dashboard':
        // Dashboard page - routes to role-specific dashboard
        requireAuth();
        include BASE_PATH . '/app/views/dashboard.php';
        break;

    // ---- Worker module routes ----
    case '/worker':
    case '/worker/dashboard':
        requireAuth();
        if (!hasRole('worker')) { http_response_code(403); echo 'Access denied. Workers only.'; break; }
        $pageTitle = 'Worker Dashboard';
        include BASE_PATH . '/app/views/dashboards/worker.php';
        break;
    
    case '/worker/tasks':
        requireAuth();
        if (!hasRole('worker')) { http_response_code(403); echo 'Access denied. Workers only.'; break; }
        $pageTitle = 'My Tasks';
        include BASE_PATH . '/app/views/worker/tasks.php';
        break;
        
    case '/worker/attendance':
        requireAuth();
        if (!hasRole('worker')) { http_response_code(403); echo 'Access denied. Workers only.'; break; }
        $pageTitle = 'My Attendance';
        include BASE_PATH . '/app/views/worker/attendance.php';
        break;
        
    case '/worker/material_requests':
        requireAuth();
        if (!hasRole('worker')) { http_response_code(403); echo 'Access denied. Workers only.'; break; }
        $pageTitle = 'Material Requests';
        include BASE_PATH . '/app/views/worker/material_requests.php';
        break;
        
    case '/worker/messages':
        requireAuth();
        if (!hasRole('worker')) { http_response_code(403); echo 'Access denied. Workers only.'; break; }
        $pageTitle = 'My Messages';
        include BASE_PATH . '/app/views/worker/messages.php';
        break;
        
    case '/worker/task_details':
        requireAuth();
        if (!hasRole('worker')) { http_response_code(403); echo 'Access denied. Workers only.'; break; }
        $pageTitle = 'Task Details';
        include BASE_PATH . '/app/views/worker/task_details.php';
        break;
        
    // ---- Project Manager module (additive) ----
    case '/project-manager':
        requireAuth();
        if (!hasRole('project_manager')) { http_response_code(403); echo 'Access denied. Project Managers only.'; break; }
        require_once BASE_PATH . '/app/controllers/ProjectManagerController.php';
        $pmCtl = new ProjectManagerController();
        $payload = $pmCtl->dashboard();
        $pageTitle = 'Project Manager Dashboard';
        include BASE_PATH . '/app/views/pm/dashboard.php';
        break;

    case '/pm/projects':
        requireAuth(); if (!hasRole('project_manager')) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'PM • Projects';
        include BASE_PATH . '/app/views/pm/projects.php';
        break;

    case '/pm/tasks':
        requireAuth(); if (!hasRole('project_manager')) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'PM • Tasks';
        include BASE_PATH . '/app/views/pm/tasks.php';
        break;
        
    case '/pm/tasks-test':
        requireAuth(); if (!hasRole('project_manager')) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'PM • Tasks Test';
        include BASE_PATH . '/app/views/pm/tasks_test.php';
        break;

    case '/pm/products':
        requireAuth(); if (!hasRole('project_manager')) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'PM • Products';
        include BASE_PATH . '/app/views/pm/products.php';
        break;

    case '/pm/suppliers':
        requireAuth(); if (!hasRole('project_manager')) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'PM • Suppliers';
        include BASE_PATH . '/app/views/pm/suppliers.php';
        break;

    case '/pm/material-requests':
        requireAuth(); if (!hasRole('project_manager')) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'PM • Material Requests';
        include BASE_PATH . '/app/views/pm/material_requests.php';
        break;

    case '/pm/purchase-orders':
        requireAuth(); if (!hasRole('project_manager')) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'PM • Purchase Orders';
        include BASE_PATH . '/app/views/pm/purchase_orders.php';
        break;

    case '/pm/reports':
        requireAuth(); if (!hasRole('project_manager')) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'PM • Reports & Analytics';
        include BASE_PATH . '/app/views/pm/reports.php';
        break;

    case '/pm/messages':
        requireAuth(); if (!hasRole('project_manager')) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'PM • Messages';
        include BASE_PATH . '/app/views/pm/messages.php';
        break;

    case '/pm/tax-report':
        requireAuth();
        if (!hasAnyRole(['admin','project_manager'])) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Tax Report';
        $currentPage = 'pm_tax_report';
        include BASE_PATH . '/app/views/pm/tax_report.php';
        break;

    case '/profile':
        // Profile page
        requireAuth();
        $pageTitle = 'Profile';
        include BASE_PATH . '/app/views/profile.php';
        break;
        
    case '/projects':
        // Projects page
        requireAuth();
        $pageTitle = 'Projects';
        include BASE_PATH . '/app/views/projects/index.php';
        break;
        
    case '/projects/create':
        // Create Project page
        requireAuth();
        $pageTitle = 'Create Project';
        include BASE_PATH . '/app/views/projects/create.php';
        break;
        
    case '/projects/edit':
        // Edit Project page
        requireAuth();
        $pageTitle = 'Edit Project';
        include BASE_PATH . '/app/views/projects/edit.php';
        break;
        
    case '/projects/show':
        // Show Project page
        requireAuth();
        $pageTitle = 'Project Details';
        include BASE_PATH . '/app/views/projects/show.php';
        break;
        
    case '/tasks':
        // Tasks page
        requireAuth();
        $pageTitle = 'Tasks';
        include BASE_PATH . '/app/views/tasks/index.php';
        break;
        
    case '/tasks/create':
        // Create Task page
        requireAuth();
        $pageTitle = 'Create Task';
        include BASE_PATH . '/app/views/tasks/create.php';
        break;
        
    case '/tasks/edit':
        // Edit Task page
        requireAuth();
        $pageTitle = 'Edit Task';
        include BASE_PATH . '/app/views/tasks/edit.php';
        break;
        
    case '/tasks/show':
        // Show Task page
        requireAuth();
        $pageTitle = 'Task Details';
        include BASE_PATH . '/app/views/tasks/show.php';
        break;
        
    case '/materials':
        // Materials page
        requireAuth();
        $pageTitle = 'Materials';
        include BASE_PATH . '/app/views/materials/index.php';
        break;
        
    case '/finance':
        // Finance page
        requireAuth();
        $pageTitle = 'Finance';
        include BASE_PATH . '/app/views/finance/index.php';
        break;
        
    case '/messages':
        // Messages page
        requireAuth();
        $pageTitle = 'Messages';
        include BASE_PATH . '/app/views/messages/index.php';
        break;
        
    case '/attendance':
        // Attendance page
        requireAuth();
        $pageTitle = 'Attendance';
        include BASE_PATH . '/app/views/attendance/index.php';
        break;
        
    // Additive: Attendance approvals queue (supervisor/site_manager/admin)
    case '/attendance/approvals':
        requireAuth();
        if (!hasAnyRole(['admin','supervisor','site_manager'])) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Attendance Approvals';
        include BASE_PATH . '/app/views/attendance/approvals.php';
        break;

    case '/attendance/reports':
        requireAuth();
        if (!hasAnyRole(['admin','project_manager','site_manager'])) {
            http_response_code(403);
            $pageTitle = 'Forbidden';
            include BASE_PATH . '/app/views/403.php';
            break;
        }
        $pageTitle = 'Attendance Reports';
        include BASE_PATH . '/app/views/attendance/reports.php';
        break;
        
    case '/marketplace':
        // Marketplace page
        requireAuth();
        $pageTitle = 'Marketplace';
        include BASE_PATH . '/app/views/marketplace/index.php';
        break;
        
    case '/reports':
        // Reports page
        requireAuth();
        $pageTitle = 'Reports';
        include BASE_PATH . '/app/views/reports/index.php';
        break;

    case '/users':
        // Admin - Users management placeholder view
        requireAuth();
        if (!hasRole('admin')) {
            http_response_code(403);
            echo 'Access denied. Admins only.';
            break;
        }
        $pageTitle = 'Users';
        include BASE_PATH . '/app/views/users/index.php';
        break;

    case '/logout':
        // Logout and redirect to login
        require_once BASE_PATH . '/app/controllers/AuthController.php';
        require_once BASE_PATH . '/app/core/auth.php'; // Make sure auth.php is included
        
        session_start(); // Make sure session is started
        
        $authCtl = new AuthController();
        $authCtl->logout();
        
        // Additional direct logout for redundancy
        logoutUser();
        
        // Redirect with header instead of using redirect function
        header("Location: " . url('/login'));
        exit;

    case '/purchase-orders':
        // Purchase Orders list
        requireAuth();
        $pageTitle = 'Purchase Orders';
        include BASE_PATH . '/app/views/purchase_orders/index.php';
        break;

    case '/purchase-orders/create':
        // Create Purchase Order
        requireAuth();
        $pageTitle = 'Create Purchase Order';
        include BASE_PATH . '/app/views/purchase_orders/create.php';
        break;

    case '/purchase-orders/show':
        // Show Purchase Order details
        requireAuth();
        $pageTitle = 'Purchase Order Details';
        include BASE_PATH . '/app/views/purchase_orders/show.php';
        break;

    case '/suppliers':
        // Admin - Suppliers list
        requireAuth();
        if (!hasRole('admin')) { http_response_code(403); echo 'Access denied. Admins only.'; break; }
        $pageTitle = 'Suppliers';
        include BASE_PATH . '/app/views/suppliers/index.php';
        break;

    case '/suppliers/create':
        // Admin - Create supplier
        requireAuth();
        if (!hasRole('admin')) { http_response_code(403); echo 'Access denied. Admins only.'; break; }
        $pageTitle = 'Create Supplier';
        include BASE_PATH . '/app/views/suppliers/create.php';
        break;

    case '/suppliers/edit':
        // Admin - Edit supplier
        requireAuth();
        if (!hasRole('admin')) { http_response_code(403); echo 'Access denied. Admins only.'; break; }
        $pageTitle = 'Edit Supplier';
        include BASE_PATH . '/app/views/suppliers/edit.php';
        break;

    case '/suppliers/show':
        // Admin - Show supplier
        requireAuth();
        if (!hasRole('admin')) { http_response_code(403); echo 'Access denied. Admins only.'; break; }
        $pageTitle = 'Supplier Details';
        include BASE_PATH . '/app/views/suppliers/show.php';
        break;

    case '/products':
        // Admin - Products list
        requireAuth();
        if (!hasRole('admin')) { http_response_code(403); echo 'Access denied. Admins only.'; break; }
        $pageTitle = 'Products';
        include BASE_PATH . '/app/views/products/index.php';
        break;

    case '/products/create':
        // Admin - Create product
        requireAuth();
        if (!hasRole('admin')) { http_response_code(403); echo 'Access denied. Admins only.'; break; }
        $pageTitle = 'Create Product';
        include BASE_PATH . '/app/views/products/create.php';
        break;

    case '/products/edit':
        // Admin - Edit product
        requireAuth();
        if (!hasRole('admin')) { http_response_code(403); echo 'Access denied. Admins only.'; break; }
        $pageTitle = 'Edit Product';
        include BASE_PATH . '/app/views/products/edit.php';
        break;

    case '/products/show':
        // Admin - Show product
        requireAuth();
        if (!hasRole('admin')) { http_response_code(403); echo 'Access denied. Admins only.'; break; }
        $pageTitle = 'Product Details';
        include BASE_PATH . '/app/views/products/show.php';
        break;
        
    // --- Additive role dashboards (accessible to Admin for now) ---
    case '/dashboard/client':
        requireAuth();
        if (!hasAnyRole(['admin','client'])) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Client Dashboard';
        include BASE_PATH . '/app/views/dashboards/client.php';
        break;

    case '/dashboard/project-manager':
        requireAuth();
        if (!hasAnyRole(['admin','project_manager'])) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Project Manager Dashboard';
        include BASE_PATH . '/app/views/dashboards/project_manager.php';
        break;

    case '/dashboard/site-manager':
        requireAuth();
        if (!hasAnyRole(['admin','site_manager'])) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Site Manager Dashboard';
        include BASE_PATH . '/app/views/dashboards/site_manager.php';
        break;

    case '/dashboard/site-engineer':
        requireAuth();
        if (!hasAnyRole(['admin','site_engineer'])) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Site Engineer Dashboard';
        include BASE_PATH . '/app/views/dashboards/site_engineer.php';
        break;

    case '/dashboard/logistic-officer':
        requireAuth();
        if (!hasAnyRole(['admin','logistic_officer'])) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Logistic Officer Dashboard';
        include BASE_PATH . '/app/views/dashboards/logistic_officer.php';
        break;

    case '/dashboard/sub-contractor':
        requireAuth();
        if (!hasAnyRole(['admin','sub_contractor'])) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Sub-Contractor Dashboard';
        include BASE_PATH . '/app/views/dashboards/sub_contractor.php';
        break;

    case '/dashboard/worker':
        requireAuth();
        if (!hasAnyRole(['admin','worker'])) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Worker Dashboard';
        include BASE_PATH . '/app/views/dashboards/worker.php';
        break;

    case '/dashboard/supervisor':
        requireAuth();
        if (!hasAnyRole(['admin','supervisor'])) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Supervisor Dashboard';
        include BASE_PATH . '/app/views/dashboards/supervisor.php';
        break;

    case '/dashboard/manager':
        requireAuth();
        if (!hasAnyRole(['admin','manager'])) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Manager Dashboard';
        include BASE_PATH . '/app/views/dashboards/manager.php';
        break;

    case '/dashboard/admin':
        requireAuth();
        if (!hasAnyRole(['admin'])) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Admin Dashboard';
        include BASE_PATH . '/app/views/dashboards/admin.php';
        break;

    // --- Additive role profile pages ---
    case '/profile/client':
        requireAuth();
        if (!hasAnyRole(['admin'])) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Client Profile';
        include BASE_PATH . '/app/views/users/roles/client.php';
        break;

    case '/profile/project-manager':
        requireAuth();
        if (!hasAnyRole(['admin'])) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Project Manager Profile';
        include BASE_PATH . '/app/views/users/roles/project_manager.php';
        break;

    case '/profile/site-manager':
        requireAuth();
        if (!hasAnyRole(['admin'])) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Site Manager Profile';
        include BASE_PATH . '/app/views/users/roles/site_manager.php';
        break;

    case '/profile/site-engineer':
        requireAuth();
        if (!hasAnyRole(['admin'])) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Site Engineer Profile';
        include BASE_PATH . '/app/views/users/roles/site_engineer.php';
        break;

    case '/profile/logistic-officer':
        requireAuth();
        if (!hasAnyRole(['admin'])) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Logistic Officer Profile';
        include BASE_PATH . '/app/views/users/roles/logistic_officer.php';
        break;

    case '/profile/sub-contractor':
        requireAuth();
        if (!hasAnyRole(['admin'])) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Sub-Contractor Profile';
        include BASE_PATH . '/app/views/users/roles/sub_contractor.php';
        break;
        
    // --- Sub-Contractor Module Routes ---
    case '/sub-contractor/projects':
        requireAuth();
        if (!hasRole('sub_contractor')) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'My Projects';
        include BASE_PATH . '/app/views/sub_contractor/projects.php';
        break;
        
    case '/sub-contractor/project/:id':
        requireAuth();
        if (!hasRole('sub_contractor')) { http_response_code(403); echo 'Access denied.'; break; }
        // Extract the project ID from the URL
        $parts = explode('/', $uri);
        $projectId = end($parts);
        $pageTitle = 'Project Details';
        include BASE_PATH . '/app/views/sub_contractor/project_details.php';
        break;
        
    case '/sub-contractor/tasks':
        requireAuth();
        if (!hasRole('sub_contractor')) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'My Tasks';
        include BASE_PATH . '/app/views/sub_contractor/tasks.php';
        break;
        
    case '/sub-contractor/task/:id':
        requireAuth();
        if (!hasRole('sub_contractor')) { http_response_code(403); echo 'Access denied.'; break; }
        // Extract the task ID from the URL
        $parts = explode('/', $uri);
        $taskId = end($parts);
        $pageTitle = 'Task Details';
        include BASE_PATH . '/app/views/sub_contractor/task_details.php';
        break;
        
    case '/sub-contractor/materials':
        requireAuth();
        if (!hasRole('sub_contractor')) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Materials';
        include BASE_PATH . '/app/views/sub_contractor/materials.php';
        break;
        
    case '/sub-contractor/purchase-orders':
        requireAuth();
        if (!hasRole('sub_contractor')) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Purchase Orders';
        include BASE_PATH . '/app/views/sub_contractor/purchase_orders.php';
        break;

    // --- Additive: Client read-only pages ---
    case '/client/projects':
        requireAuth();
        if (!hasRole('client')) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Client Projects';
        include BASE_PATH . '/app/views/client/projects.php';
        break;

    case '/client/projects/create':
        requireAuth();
        if (!hasRole('client')) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Create Project';
        include BASE_PATH . '/app/views/client/project_create.php';
        break;

    case '/client/tasks':
        requireAuth();
        if (!hasRole('client')) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Client Tasks';
        include BASE_PATH . '/app/views/client/tasks.php';
        break;

    case '/client/materials':
        requireAuth();
        if (!hasRole('client')) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Client Materials';
        include BASE_PATH . '/app/views/client/materials.php';
        break;

    case '/client/purchase-orders':
        requireAuth();
        if (!hasRole('client')) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Client Purchase Orders';
        include BASE_PATH . '/app/views/client/purchase_orders.php';
        break;

    case '/client/reports':
        requireAuth();
        if (!hasRole('client')) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Client Reports';
        include BASE_PATH . '/app/views/client/reports.php';
        break;

    case '/client/messages':
        requireAuth();
        if (!hasRole('client')) { http_response_code(403); echo 'Access denied.'; break; }
        $pageTitle = 'Client Messages';
        include BASE_PATH . '/app/views/client/messages.php';
        break;

    case '/tax/api/breakdown':
        // Additive JSON endpoint: Role-based monthly tax breakdown
        requireAuth();
        if (!hasAnyRole(['admin','project_manager'])) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Access denied.']); break; }
        try {
            $pdo = Database::getConnection();
            if (!headers_sent()) { header('Content-Type: application/json'); }
            // Read inputs
            $role = isset($_GET['role']) ? trim((string)$_GET['role']) : (isset($_POST['role']) ? trim((string)$_POST['role']) : '');
            $from = isset($_GET['from']) ? $_GET['from'] : (isset($_POST['from']) ? $_POST['from'] : null);
            $to = isset($_GET['to']) ? $_GET['to'] : (isset($_POST['to']) ? $_POST['to'] : null);
            // Normalize role label to internal key (e.g., "Logistic Officer" -> "logistic_officer")
            if ($role !== '') {
                $r = strtolower(trim($role));
                $r = str_replace([' ', '-'], '_', $r);
                // Simple map for common variants
                $map = [
                    'project manager' => 'project_manager',
                    'site manager' => 'site_manager',
                    'site engineer' => 'site_engineer',
                    'logistic officer' => 'logistic_officer',
                    'sub contractor' => 'sub_contractor'
                ];
                if (isset($map[$r])) { $role = $map[$r]; } else { $role = $r; }
            }
            // Default to current month if not provided
            if (!$from || !$to) {
                $first = new DateTime('first day of this month');
                $last = new DateTime('last day of this month');
                $from = $from ?: $first->format('Y-m-d');
                $to = $to ?: $last->format('Y-m-d');
            }
            // Simple date validation
            $fromOk = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from);
            $toOk = preg_match('/^\d{4}-\d{2}-\d{2}$/', $to);
            if (!$fromOk || !$toOk) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid date format. Use YYYY-MM-DD.']); break; }

            // Try stored procedure first
            $data = [];
            $usedProc = false;
            try {
                $stmt = $pdo->prepare('CALL sp_get_tax_breakdown_by_role(:role, :from, :to)');
                $stmt->bindValue(':role', $role);
                $stmt->bindValue(':from', $from);
                $stmt->bindValue(':to', $to);
                if ($stmt->execute()) {
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $data = $rows ?: [];
                    $usedProc = true;
                }
            } catch (Throwable $e) {
                // Fallback inline SQL
                $sql = "SELECT p.id AS payment_id,
                               u.name AS user_name,
                               u.role AS user_role,
                               pr.name AS project_name,
                               pr.project_type,
                               p.amount AS base_amount,
                               CASE WHEN pr.project_type = 'Government' THEN 0.10 ELSE 0.15 END AS vat_rate,
                               ROUND(p.amount * CASE WHEN pr.project_type = 'Government' THEN 0.10 ELSE 0.15 END, 2) AS vat_amount,
                               CASE WHEN u.role = 'logistic_officer' THEN 0.00 ELSE 0.05 END AS ait_rate,
                               ROUND(p.amount * CASE WHEN u.role = 'logistic_officer' THEN 0.00 ELSE 0.05 END, 2) AS ait_amount,
                               ROUND(p.amount - (p.amount * CASE WHEN pr.project_type = 'Government' THEN 0.10 ELSE 0.15 END)
                                     - (p.amount * CASE WHEN u.role = 'logistic_officer' THEN 0.00 ELSE 0.05 END), 2) AS net_payable
                        FROM payments p
                        JOIN users u ON p.user_id = u.id
                        JOIN projects pr ON p.project_id = pr.id
                        WHERE p.status = 'approved' AND p.date BETWEEN :from AND :to";
                if ($role !== '') { $sql .= " AND u.role = :role"; }
                $sql .= " ORDER BY p.date DESC, p.id DESC";
                $q = $pdo->prepare($sql);
                $q->bindValue(':from', $from);
                $q->bindValue(':to', $to);
                if ($role !== '') { $q->bindValue(':role', $role); }
                $q->execute();
                $data = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }

            // Log to tax_audit (best-effort)
            try {
                $ins = $pdo->prepare('INSERT INTO tax_audit (requested_by, role_filter, from_date, to_date, rows_returned, created_at) VALUES (:uid, :role, :from, :to, :rows, NOW())');
                $uid = function_exists('getCurrentUserId') ? (int)(getCurrentUserId() ?: 0) : 0;
                $ins->bindValue(':uid', $uid ?: null, $uid ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $ins->bindValue(':role', $role !== '' ? $role : null, $role !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $ins->bindValue(':from', $from);
                $ins->bindValue(':to', $to);
                $ins->bindValue(':rows', count($data), PDO::PARAM_INT);
                $ins->execute();
            } catch (Throwable $e) { /* ignore logging errors */ }

            echo json_encode(['success'=>true,'used_procedure'=>$usedProc,'data'=>$data]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
        }
        break;

    default:
        // Check if it's an API request
        if (strpos($uri, '/api/') === 0) {
            // API routes
            $apiPath = substr($uri, 5); // Remove '/api/' prefix
            
            // Normalize: don't double-append .php if already present
            $apiFilePath = BASE_PATH . '/api/' . $apiPath;
            if (substr($apiPath, -4) !== '.php') {
                $apiFilePath .= '.php';
            }
            
            if (file_exists($apiFilePath)) {
                if (!headers_sent()) { header('Content-Type: application/json'); }
                include $apiFilePath;
            } else {
                // Backward-compatible fallback: trim trailing segments until a PHP file exists
                $segments = array_values(array_filter(explode('/', $apiPath)));
                $found = false;
                for ($i = count($segments); $i > 0; $i--) {
                    $candidate = implode('/', array_slice($segments, 0, $i));
                    $candidateFile = BASE_PATH . '/api/' . $candidate;
                    if (substr($candidate, -4) !== '.php') {
                        $candidateFile .= '.php';
                    }
                    if (file_exists($candidateFile)) {
                        // Set PATH_INFO to the remaining segments for the included script to parse
                        $remaining = array_slice($segments, $i);
                        $_SERVER['PATH_INFO'] = '/' . implode('/', $remaining);
                        if (!headers_sent()) { header('Content-Type: application/json'); }
                        include $candidateFile;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    http_response_code(404);
                    echo json_encode(['error' => 'API endpoint not found']);
                }
            }
        } else {
            // For all other routes, show 404 page
            http_response_code(404);
            $pageTitle = 'Page Not Found';
            include BASE_PATH . '/app/views/404.php';
        }
        break;
}

// Function to redirect to a specific URL
function redirect($url) {
    header("Location: $url");
    exit();
}

// Function to generate URL paths
function url($path = '') {
    $baseUrl = rtrim(env('APP_URL', ''), '/');
    return $baseUrl . ($path ? '/' . ltrim($path, '/') : '');
}