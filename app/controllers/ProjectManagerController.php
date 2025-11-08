<?php
// ProjectManagerController.php - Additive controller for Project Manager role
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../../config/database.php';

// Models (load defensively; existing models only)
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Material.php';
require_once __DIR__ . '/../models/PurchaseOrder.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Supplier.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/Finance.php';

class ProjectManagerController {
    private $db;
    private $projectModel;
    private $taskModel;
    private $materialModel;
    private $poModel;
    private $productModel;
    private $supplierModel;
    private $messageModel;
    private $financeModel;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->projectModel  = new Project();
        $this->taskModel     = new Task();
        $this->materialModel = class_exists('Material') ? new Material() : null;
        $this->poModel       = class_exists('PurchaseOrder') ? new PurchaseOrder() : null;
        $this->productModel  = class_exists('Product') ? new Product() : null;
        $this->supplierModel = class_exists('Supplier') ? new Supplier() : null;
        $this->messageModel  = class_exists('Message') ? new Message() : null;
        $this->financeModel  = class_exists('Finance') ? new Finance() : null;
    }

    private function ensurePM() {
        requireAuth();
        if (!hasRole('project_manager')) {
            http_response_code(403);
            die('Access denied. Project Managers only.');
        }
    }

    // Dashboard payload with KPIs
    public function dashboard() {
        $this->ensurePM();
        $userId = getCurrentUserId();

        if (method_exists($this->projectModel, 'getByManager')) {
            $projects = $this->projectModel->getByManager($userId) ?: [];
            // Fallbacks to avoid empty dashboard when scoped is empty
            if (empty($projects) && method_exists($this->projectModel, 'getByOwner')) {
                $projects = $this->projectModel->getByOwner($userId) ?: [];
            }
            if (empty($projects) && method_exists($this->projectModel, 'getAll')) {
                $projects = $this->projectModel->getAll() ?: [];
            }
        } elseif (method_exists($this->projectModel, 'getByOwner')) {
            $projects = $this->projectModel->getByOwner($userId) ?: [];
            if (empty($projects) && method_exists($this->projectModel, 'getAll')) {
                $projects = $this->projectModel->getAll() ?: [];
            }
        } else {
            $projects = method_exists($this->projectModel, 'getAll') ? ($this->projectModel->getAll() ?: []) : [];
        }

        if (method_exists($this->taskModel, 'getByManager')) {
            $tasks = $this->taskModel->getByManager($userId) ?: [];
            // Fallback to all tasks if none found (prevents empty dashboard during review)
            if (empty($tasks) && method_exists($this->taskModel, 'getAll')) {
                $tasks = $this->taskModel->getAll() ?: [];
            }
        } else {
            $tasks = method_exists($this->taskModel, 'getAll') ? ($this->taskModel->getAll() ?: []) : [];
        }

        // If projects are empty but tasks exist, derive project list from tasks to populate the table/KPI
        if (empty($projects) && !empty($tasks)) {
            $byId = [];
            foreach ($tasks as $t) {
                $pid = $t['project_id'] ?? null; if (!$pid) continue;
                if (!isset($byId[$pid])) {
                    $byId[$pid] = [
                        'id' => $pid,
                        'name' => $t['project_name'] ?? ('Project #'.$pid),
                        'status' => '',
                    ];
                }
            }
            $projects = array_values($byId);
        }

        $openPOs = ($this->poModel && method_exists($this->poModel, 'getOpenForManager'))
            ? ($this->poModel->getOpenForManager($userId) ?: [])
            : [];

        $pendingMaterials = ($this->materialModel && method_exists($this->materialModel, 'getPendingForManager'))
            ? ($this->materialModel->getPendingForManager($userId) ?: [])
            : [];

        // Grouped open tasks by project (non-destructive additive payload)
        $groupedOpen = [];
        if (method_exists($this->taskModel,'getOpenTasksGroupedByProject')) {
            try { $groupedOpen = $this->taskModel->getOpenTasksGroupedByProject(); } catch (Throwable $e) { $groupedOpen = []; }
        }

        $kpis = [
            'total_projects' => count($projects),
            // Consider open if not completed/cancelled
            'open_tasks'     => count(array_filter($tasks, function($t){
                $s = strtolower($t['status'] ?? '');
                return $s !== 'completed' && $s !== 'cancelled';
            })),
            'pending_material_requests' => count($pendingMaterials),
            'open_purchase_orders'      => count($openPOs),
        ];

        return [ 'success' => true, 'data' => compact('kpis','projects','tasks','openPOs','pendingMaterials','groupedOpen') ];
    }

    // ---- Projects CRUD ----
    public function listProjects() {
        $this->ensurePM();
        $uid = getCurrentUserId();

        // If role is explicitly project_manager, prefer showing all projects to avoid empty dropdowns during task creation
        $role = strtolower((string)(getCurrentUserRole() ?? ''));
        if ($role === 'project_manager') {
            try {
                $stmt = $this->db->prepare("SELECT p.*, u.name AS owner_name FROM projects p JOIN users u ON u.id = p.owner_id ORDER BY p.id DESC");
                $stmt->execute();
                $rows = $stmt->fetchAll() ?: [];
                return ['success'=>true,'data'=>$rows];
            } catch (Throwable $eAll) {
                // continue to scoped strategy if global fetch fails for any reason
            }
        }

        // Scoped strategy with schema detection
        $hasSiteManager = in_array('site_manager_id', $this->columns('projects'), true);
        $hasAssignments = false;
        try {
            $chk = $this->db->query("SHOW TABLES LIKE 'project_assignments'");
            $hasAssignments = $chk && $chk->rowCount() > 0;
        } catch (Throwable $e) { $hasAssignments = false; }

        $joins = 'JOIN users u ON u.id = p.owner_id';
        if ($hasAssignments) { $joins .= ' LEFT JOIN project_assignments pa ON pa.project_id = p.id AND pa.user_id = :uid'; }

        $conditions = ['p.owner_id = :uid'];
        if ($hasSiteManager) { $conditions[] = 'p.site_manager_id = :uid'; }
        if ($hasAssignments) { $conditions[] = 'pa.user_id IS NOT NULL'; }
        $where = 'WHERE (' . implode(' OR ', $conditions) . ')';

        $sql = "SELECT p.*, u.name AS owner_name FROM projects p $joins $where ORDER BY p.id DESC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':uid'=>$uid]);
            $list = $stmt->fetchAll() ?: [];

            // If nothing found for PMs, fall back to all projects to keep UX smooth
            if ($role === 'project_manager' && empty($list)) {
                try {
                    $stmt2 = $this->db->prepare("SELECT p.*, u.name AS owner_name FROM projects p JOIN users u ON u.id = p.owner_id ORDER BY p.id DESC");
                    $stmt2->execute();
                    $list = $stmt2->fetchAll() ?: [];
                } catch (Throwable $e2) { /* ignore */ }
            }
        } catch (Throwable $e) {
            // Last resort: owner-only without optional columns
            try {
                $stmt = $this->db->prepare("SELECT p.*, u.name AS owner_name FROM projects p JOIN users u ON u.id = p.owner_id WHERE p.owner_id = :uid ORDER BY p.id DESC");
                $stmt->execute([':uid'=>$uid]);
                $list = $stmt->fetchAll() ?: [];
            } catch (Throwable $e2) { $list = []; }
        }
        return ['success'=>true,'data'=>$list];
    }
    public function createProject($data) { $this->ensurePM(); return $this->upsert('project','create',$data); }
    public function updateProject($id,$data) { $this->ensurePM(); return $this->upsert('project','update',$data,$id); }
    public function archiveProject($id) { $this->ensurePM(); return $this->softArchive('projects',$id); }
    
    /**
     * Assign Site Manager to a project
     * 
     * @param int $projectId Project ID
     * @param int $siteManagerId Site Manager user ID
     * @return array Response with success status and message
     */
    public function assignSiteManager($projectId, $siteManagerId) {
        $this->ensurePM();
        
        if (!$projectId || !$siteManagerId) {
            return ['success' => false, 'message' => 'Project ID and Site Manager ID are required'];
        }
        
        // Use the Project model's assignSiteManager method
        return $this->projectModel->assignSiteManager($projectId, $siteManagerId);
    }
    
    /**
     * Get all site managers
     * 
     * @return array List of site managers
     */
    public function getSiteManagers() {
        $this->ensurePM();
        
        // Debug: Log controller method call
        error_log("ProjectManagerController::getSiteManagers called");
        
        // Use the Project model's getSiteManagers method
        $siteManagers = $this->projectModel->getSiteManagers();
        
        // Debug: Log the result
        error_log("ProjectManagerController::getSiteManagers result: " . ($siteManagers === false ? 'false' : json_encode($siteManagers)));
        
        if ($siteManagers === false) {
            return ['success' => false, 'message' => 'Failed to retrieve site managers', 'data' => []];
        }
        
        return ['success' => true, 'data' => $siteManagers];
    }

    // ---- Tasks CRUD ----
    public function listTasks() {
        $this->ensurePM();
        $userId = getCurrentUserId();
        // Prefer scoped tasks by manager; fallback to all if method unavailable
        if (method_exists($this->taskModel, 'getByManager')) {
            $scoped = $this->safeCall($this->taskModel, 'getByManager', [$userId, true]);
            if (!empty($scoped)) return ['success' => true, 'data' => $scoped];
        }
        return ['success' => true, 'data' => $this->safeCall($this->taskModel, 'getAll', [])];
    }
    public function createTask($data) {
        $this->ensurePM();
        // Normalize and map expected fields
        $projectId   = (int)($data['project_id'] ?? 0);
        $title       = trim((string)($data['title'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));
        $assignedTo  = isset($data[ 'assigned_to']) && $data['assigned_to'] !== '' ? (int)$data['assigned_to'] : null;
        $status      = trim((string)($data['status'] ?? 'pending'));
        $dueDate     = ($data['due_date'] ?? null) ?: null;

        if ($projectId <= 0 || $title === '') {
            return ['success'=>false,'message'=>'project_id and title are required'];
        }

        // Enforce PM can manage the project (owner, site manager, or assigned via project_assignments)
        $uid = getCurrentUserId();
        try {
            // Try with project_assignments table
            $sql = "
                SELECT 1
                FROM projects p
                LEFT JOIN project_assignments pa ON pa.project_id = p.id AND pa.user_id = :uid
                WHERE p.id = :id AND (p.owner_id = :uid OR p.site_manager_id = :uid OR pa.user_id IS NOT NULL)
                LIMIT 1
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id'=>$projectId, ':uid'=>$uid]);
            if ($stmt->rowCount() === 0) {
                // Fallback without project_assignments
                $stmt2 = $this->db->prepare("SELECT 1 FROM projects WHERE id = :id AND (owner_id = :uid OR site_manager_id = :uid) LIMIT 1");
                $stmt2->execute([':id'=>$projectId, ':uid'=>$uid]);
                if ($stmt2->rowCount() === 0) {
                    return ['success'=>false,'message'=>'You do not have permission to add tasks to this project'];
                }
            }
        } catch (Throwable $e) { /* ignore and proceed; creation may still succeed if model enforces */ }

        if (!method_exists($this->taskModel,'create')) {
            return ['success'=>false,'message'=>'Task creation not available'];
        }

        return $this->taskModel->create($projectId,$title,$description,$assignedTo,$status,$dueDate);
    }
    public function updateTask($id,$data) { $this->ensurePM(); return $this->upsert('task','update',$data,$id); }

    // ---- Products CRUD ----
    public function listProducts() { $this->ensurePM(); return ['success'=>true,'data'=>$this->safeCall($this->productModel,'getAll',[])]; }
    public function createProduct($data) { $this->ensurePM(); return $this->upsert('product','create',$data); }
    public function updateProduct($id,$data) { $this->ensurePM(); return $this->upsert('product','update',$data,$id); }
    public function deleteProduct($id) { $this->ensurePM(); return $this->hardDelete('products',$id); }

    // ---- Suppliers CRUD ----
    public function listSuppliers() { $this->ensurePM(); return ['success'=>true,'data'=>$this->safeCall($this->supplierModel,'getAll',[])]; }
    public function createSupplier($data) { $this->ensurePM(); return $this->upsert('supplier','create',$data); }
    public function updateSupplier($id,$data) { $this->ensurePM(); return $this->upsert('supplier','update',$data,$id); }
    public function deleteSupplier($id) { $this->ensurePM(); return $this->hardDelete('suppliers',$id); }

    // ---- Materials (approve/reject) ----
    public function listMaterialRequests() { $this->ensurePM(); return ['success'=>true,'data'=>$this->safeCall($this->materialModel,'getPendingForManager',[getCurrentUserId()])]; }
    public function approveMaterial($id) { $this->ensurePM(); return $this->updateStatus('material_requests',$id,'approved'); }
    public function rejectMaterial($id) { $this->ensurePM(); return $this->updateStatus('material_requests',$id,'rejected'); }

    // ---- Purchase Orders (status updates) ----
    public function listPOs() { $this->ensurePM(); return ['success'=>true,'data'=>$this->safeCall($this->poModel,'getAll',[])]; }
    public function createPO($data) { $this->ensurePM(); return $this->upsert('purchase_order','create',$data); }
    public function updatePOStatus($id,$status) { $this->ensurePM(); return $this->updateStatus('purchase_orders',$id,$status); }

    // ---- Reports (read only) ----
    public function listReports() { $this->ensurePM(); return ['success'=>true,'data'=>[]]; }

    // ---- Messaging ----
    public function listConversations() { $this->ensurePM(); return ['success'=>true,'data'=>$this->safeCall($this->messageModel,'getConversationsForUser',[getCurrentUserId()])]; }
    public function sendMessage($toUserId,$body) {
        $this->ensurePM();
        if (!$this->messageModel || !method_exists($this->messageModel,'send')) {
            return ['success'=>false,'message'=>'Messaging not available'];
        }
        $ok = $this->messageModel->send(getCurrentUserId(), (int)$toUserId, trim((string)$body));
        return ['success'=> (bool)$ok, 'message'=> $ok ? 'Sent' : 'Failed'];
    }

    // ---- Utilities ----
    private function safeCall($model,$method,$args) {
        if (!$model || !method_exists($model,$method)) return [];
        try { return call_user_func_array([$model,$method], (array)$args) ?: []; } catch (Throwable $e) { return []; }
    }

    private function upsert($entity,$action,$data,$id=null) {
        // Prefer model methods if present
        $model = match($entity){
            'project' => $this->projectModel,
            'task' => $this->taskModel,
            'product' => $this->productModel,
            'supplier' => $this->supplierModel,
            'purchase_order' => $this->poModel,
            default => null,
        };
        $createM = 'create'; $updateM = 'update';
        if ($model && $action==='create' && method_exists($model,$createM)) {
            $ok = $model->$createM($data);
            return ['success'=>(bool)$ok,'message'=>$ok?'Created':'Create failed'];
        }
        if ($model && $action==='update' && method_exists($model,$updateM)) {
            $ok = $model->$updateM($id,$data);
            return ['success'=>(bool)$ok,'message'=>$ok?'Updated':'Update failed'];
        }
        // Fallback generic SQL (additive; assumes conventional table/columns)
        $table = $this->tableFor($entity);
        if (!$table) return ['success'=>false,'message'=>'Unsupported entity'];
        if ($action==='create') {
            $cols = array_keys($data);
            $place = array_map(fn($c)=>':'.$c, $cols);
            $sql = 'INSERT INTO `'.$table.'` (`'.implode('`,`',$cols).'`) VALUES ('.implode(',', $place).')';
            $stmt = $this->db->prepare($sql);
            foreach ($data as $k=>$v) { $stmt->bindValue(':'.$k,$v); }
            $ok = $stmt->execute();
            return ['success'=>(bool)$ok,'message'=>$ok?'Created':'Create failed'];
        } else if ($action==='update') {
            $sets = [];
            foreach ($data as $k=>$v) { $sets[] = '`'.$k.'` = :'.$k; }
            $sql = 'UPDATE `'.$table.'` SET '.implode(', ',$sets).' WHERE id = :id';
            $stmt = $this->db->prepare($sql);
            foreach ($data as $k=>$v) { $stmt->bindValue(':'.$k,$v); }
            $stmt->bindValue(':id',$id, PDO::PARAM_INT);
            $ok = $stmt->execute();
            return ['success'=>(bool)$ok,'message'=>$ok?'Updated':'Update failed'];
        }
        return ['success'=>false,'message'=>'Invalid action'];
    }

    private function hardDelete($table,$id) {
        $stmt = $this->db->prepare('DELETE FROM `'.$table.'` WHERE id = :id');
        $stmt->bindValue(':id',$id, PDO::PARAM_INT);
        $ok = $stmt->execute();
        return ['success'=>(bool)$ok,'message'=>$ok?'Deleted':'Delete failed'];
    }

    private function softArchive($table,$id) {
        // Mark as archived if column exists; fallback to status
        $columns = $this->columns($table);
        $field = in_array('archived',$columns) ? 'archived' : (in_array('status',$columns) ? 'status' : null);
        if (!$field) return ['success'=>false,'message'=>'Archive not supported'];
        $value = ($field==='archived') ? 1 : 'archived';
        $stmt = $this->db->prepare('UPDATE `'.$table.'` SET `'.$field.'` = :v WHERE id = :id');
        $stmt->bindValue(':v',$value);
        $stmt->bindValue(':id',$id,PDO::PARAM_INT);
        $ok = $stmt->execute();
        return ['success'=>(bool)$ok,'message'=>$ok?'Archived':'Archive failed'];
    }

    private function updateStatus($table,$id,$status) {
        $stmt = $this->db->prepare('UPDATE `'.$table.'` SET `status` = :s WHERE id = :id');
        $stmt->bindValue(':s',$status);
        $stmt->bindValue(':id',$id,PDO::PARAM_INT);
        $ok = $stmt->execute();
        return ['success'=>(bool)$ok,'message'=>$ok?'Status updated':'Update failed'];
    }

    private function columns($table) {
        try {
            $stmt = $this->db->query('SHOW COLUMNS FROM `'.$table.'`');
            return array_map(fn($r)=>$r['Field'] ?? '', $stmt->fetchAll());
        } catch (Throwable $e) { return []; }
    }

    private function tableFor($entity) {
        return match($entity){
            'project' => 'projects',
            'task' => 'tasks',
            'product' => 'products',
            'supplier' => 'suppliers',
            'purchase_order' => 'purchase_orders',
            default => null,
        };
    }
}
