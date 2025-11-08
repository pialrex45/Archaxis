<?php
// ClientController.php - Additive controller for Client role (read-only plus optional create project)
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/Task.php';

class ClientController {
    private $dashboard;

    public function __construct() {
        $this->dashboard = new DashboardController();
    }

    // Ensure current user is a client
    private function ensureClient() {
        requireAuth();
        if (!hasRole('client')) {
            http_response_code(403);
            die('Access denied. Clients only.');
        }
    }

    // Retrieve the aggregated client dashboard payload (reuses existing logic)
    public function getPayload() {
        $this->ensureClient();
        $result = $this->dashboard->getClientDashboard();
        if (!($result['success'] ?? false)) {
            return [ 'success' => false, 'data' => [], 'message' => $result['message'] ?? 'Unavailable' ];
        }
        return $result;
    }

    public function listProjects() {
        $this->ensureClient();
        $userId = getCurrentUserId();
        $model = new Project();
        // Always restrict to projects owned/assigned to this client. Do NOT fall back to global list.
        $projects = [];
        if (method_exists($model,'getForClient')) { $projects = $model->getForClient($userId) ?: []; }
        if (empty($projects) && method_exists($model,'getByOwner')) { $projects = $model->getByOwner($userId) ?: []; }
        // No global fallback: security boundary
        return [ 'success' => true, 'data' => $projects ];
    }

    public function listTasks() {
        $this->ensureClient();
        $userId = getCurrentUserId();
        $projectModel = new Project();
        $taskModel = new Task();
        $projects = [];
        if (method_exists($projectModel,'getForClient')) { $projects = $projectModel->getForClient($userId) ?: []; }
        if (empty($projects) && method_exists($projectModel,'getByOwner')) { $projects = $projectModel->getByOwner($userId) ?: []; }
        $tasks = [];
        if (!empty($projects) && method_exists($taskModel,'getByProject')) {
            foreach ($projects as $p){ $pid=$p['id']??null; if($pid){ $list=$taskModel->getByProject($pid) ?: []; foreach($list as $t){ $tasks[]=$t; } } }
        }
        return ['success'=>true,'data'=>$tasks];
    }

    public function listMaterials() {
        $res = $this->getPayload();
        return [ 'success' => $res['success'] ?? false, 'data' => ($res['data']['materials'] ?? []) ];
    }

    public function listPurchaseOrders() {
        $res = $this->getPayload();
        return [ 'success' => $res['success'] ?? false, 'data' => ($res['data']['purchase_orders'] ?? []) ];
    }

    public function listReports() {
        $res = $this->getPayload();
        return [ 'success' => $res['success'] ?? false, 'data' => ($res['data']['reports'] ?? []) ];
    }

    public function listMessages() {
        $res = $this->getPayload();
        return [ 'success' => $res['success'] ?? false, 'data' => ($res['data']['messages'] ?? []) ];
    }

    /**
     * Create a new project as a Client (additive enhancement)
     * - Requires POST
     * - Validates minimal fields
     * - Uses Project model if create/insert/save exists; falls back to direct DB insert
     */
    public function createProject() {
        $this->ensureClient();
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            return [ 'success' => false, 'message' => 'Method not allowed' ];
        }

        // CSRF check if helper exists
        if (function_exists('verify_csrf_token')) {
            $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
            if (!verify_csrf_token($token)) {
                http_response_code(419);
                return [ 'success' => false, 'message' => 'Invalid CSRF token' ];
            }
        }

        $userId = getCurrentUserId();
        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $deadline = trim((string)($_POST['deadline'] ?? ''));

        if ($name === '') {
            http_response_code(422);
            return [ 'success' => false, 'message' => 'Project name is required' ];
        }

        // Prepare values
        $payload = [
            'name' => $name,
            'description' => $description,
            'deadline' => $deadline ?: null,
            'owner_id' => $userId,
            'status' => 'planning'
        ];

        try {
            $projectModel = new Project();
            // Prefer model create signature: (name, description, owner_id, status='planning', startDate=null, endDate=null)
            if (method_exists($projectModel, 'create')) {
                $res = $projectModel->create($payload['name'], $payload['description'], $payload['owner_id'], $payload['status'], null, $payload['deadline']);
                if (is_array($res) && ($res['success'] ?? false)) {
                    return [ 'success' => true, 'id' => (int)($res['project_id'] ?? 0), 'message' => 'Project created' ];
                }
                // If model returns primitive ID
                if (is_numeric($res)) {
                    return [ 'success' => true, 'id' => (int)$res, 'message' => 'Project created' ];
                }
                // Fall through to direct insert if unexpected
            }

            // Fallback: direct insert with owner_id (safe, parameterized)
            $db = new Database();
            $pdo = $db->connect();
            $stmt = $pdo->prepare("INSERT INTO projects (name, description, owner_id, status, end_date, created_at, updated_at) VALUES (:name, :description, :owner_id, :status, :end_date, NOW(), NOW())");
            $stmt->execute([
                ':name' => $payload['name'],
                ':description' => $payload['description'],
                ':owner_id' => $payload['owner_id'],
                ':status' => $payload['status'],
                ':end_date' => $payload['deadline'] ?: null,
            ]);
            $id = (int)$pdo->lastInsertId();
            return [ 'success' => true, 'id' => $id, 'message' => 'Project created' ];
        } catch (Exception $e) {
            http_response_code(500);
            return [ 'success' => false, 'message' => 'Failed to create project: ' . $e->getMessage() ];
        }
    }
}
