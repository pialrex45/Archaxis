<?php
// Dashboard controller for handling role-specific dashboard data

require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/Material.php';
require_once __DIR__ . '/../models/Finance.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/helpers.php';
// Additive: Database is needed by getTotalUsers(); require it defensively (correct path)
require_once __DIR__ . '/../../config/database.php';
// Additive: Optionally load models only if present to avoid autoload dependency
$optionalModels = [
    __DIR__ . '/../models/PurchaseOrder.php',
    __DIR__ . '/../models/Report.php',
];
foreach ($optionalModels as $opt) {
    if (file_exists($opt)) {
        require_once $opt;
    }
}

class DashboardController {
    private $projectModel;
    private $taskModel;
    private $messageModel;
    private $attendanceModel;
    private $materialModel;
    private $financeModel;
    
    public function __construct() {
        $this->projectModel = new Project();
        $this->taskModel = new Task();
        $this->messageModel = new Message();
        $this->attendanceModel = new Attendance();
        $this->materialModel = new Material();
        $this->financeModel = new Finance();
    }
    
    /**
     * Get dashboard data for admin users
     * 
     * @return array
     */
    public function getAdminDashboard() {
        try {
            $userId = getCurrentUserId();
            
            if (!$userId) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Get statistics
            $totalProjects = 0;
            if (method_exists($this->projectModel, 'getAll')) {
                $totalProjects = count($this->projectModel->getAll() ?: []);
            }
            $totalUsers = $this->getTotalUsers();
            $pendingApprovals = 0;
            if (method_exists($this->materialModel, 'getByStatus')) {
                $pendingApprovals = count($this->materialModel->getByStatus('requested') ?: []);
            }
            $recentProjects = [];
            if (method_exists($this->projectModel, 'getAll')) {
                $recentProjects = array_slice($this->projectModel->getAll() ?: [], 0, 5);
            }
            
            // Get recent activities
            $recentActivities = $this->getRecentActivities();
            
            return [
                'success' => true,
                'data' => [
                    'statistics' => [
                        'total_projects' => $totalProjects,
                        'total_users' => $totalUsers,
                        'pending_approvals' => $pendingApprovals
                    ],
                    'recent_projects' => $recentProjects,
                    'recent_activities' => $recentActivities
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching dashboard data: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get dashboard data for client users (read-only views)
     *
     * Returns safe defaults and only uses model methods if they exist.
     * This is fully additive and non-breaking.
     *
     * @return array
     */
    public function getClientDashboard() {
        try {
            $userId = getCurrentUserId();
            if (!$userId) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }

            // Projects visible to client
            $projects = [];
            if (method_exists($this->projectModel, 'getForClient')) {
                $projects = $this->projectModel->getForClient($userId) ?: [];
            } elseif (method_exists($this->projectModel, 'getAll')) {
                $projects = array_slice($this->projectModel->getAll() ?: [], 0, 5);
            }

            // Tasks related to client's projects
            $tasks = [];
            if (!empty($projects)) {
                if (method_exists($this->taskModel, 'getByProject')) {
                    foreach (array_slice($projects, 0, 5) as $proj) {
                        $projId = $proj['id'] ?? null;
                        if ($projId) {
                            $projTasks = $this->taskModel->getByProject($projId) ?: [];
                            $tasks = array_merge($tasks, array_slice($projTasks, 0, 3));
                        }
                    }
                    $tasks = array_slice($tasks, 0, 5);
                } elseif (method_exists($this->taskModel, 'getAll')) {
                    $tasks = array_slice($this->taskModel->getAll() ?: [], 0, 5);
                }
            } elseif (method_exists($this->taskModel, 'getAll')) {
                $tasks = array_slice($this->taskModel->getAll() ?: [], 0, 5);
            }

            // Materials related to client's projects
            $materials = [];
            if (class_exists('Material')) {
                if (method_exists($this->materialModel, 'getForClient')) {
                    $materials = $this->materialModel->getForClient($userId) ?: [];
                } elseif (method_exists($this->materialModel, 'getAll')) {
                    $materials = array_slice($this->materialModel->getAll() ?: [], 0, 5);
                }
            }

            // Purchase Orders (optional model)
            $purchaseOrders = [];
            if (class_exists('PurchaseOrder')) {
                $poModel = new PurchaseOrder();
                if (method_exists($poModel, 'getForClient')) {
                    $purchaseOrders = $poModel->getForClient($userId) ?: [];
                } elseif (method_exists($poModel, 'getAll')) {
                    $purchaseOrders = array_slice($poModel->getAll() ?: [], 0, 5);
                }
            }

            // Reports (optional model)
            $reports = [];
            if (class_exists('Report')) {
                $reportModel = new Report();
                if (method_exists($reportModel, 'getForClient')) {
                    $reports = $reportModel->getForClient($userId) ?: [];
                } elseif (method_exists($reportModel, 'getAll')) {
                    $reports = array_slice($reportModel->getAll() ?: [], 0, 5);
                }
            }

            // Finance summary (read-only)
            $finance = [
                'total_invoiced' => 0,
                'total_paid' => 0,
                'outstanding' => 0,
            ];
            if (class_exists('Finance')) {
                if (method_exists($this->financeModel, 'getSummaryForClient')) {
                    $sum = $this->financeModel->getSummaryForClient($userId) ?: [];
                    $finance['total_invoiced'] = (float)($sum['total_invoiced'] ?? 0);
                    $finance['total_paid'] = (float)($sum['total_paid'] ?? 0);
                    $finance['outstanding'] = (float)($sum['outstanding'] ?? 0);
                }
            }

            // Messages with PM
            $messages = [];
            if (method_exists($this->messageModel, 'getForUser')) {
                $messages = array_slice($this->messageModel->getForUser($userId) ?: [], 0, 5);
            }

            // Admin-like computed stats for Client (additive)
            $openTasks = 0;
            foreach ($tasks as $t) {
                $status = strtolower((string)($t['status'] ?? ''));
                if (in_array($status, ['pending', 'in progress'])) { $openTasks++; }
            }

            $statistics = [
                'total_projects' => count($projects),
                'open_tasks' => $openTasks,
                'outstanding_invoices' => (float)($finance['outstanding'] ?? 0),
            ];

            // Recent projects list for client (additive)
            $recentProjectsClient = array_map(function($p) {
                return [
                    'id' => $p['id'] ?? null,
                    'name' => $p['name'] ?? ($p['title'] ?? ''),
                    'status' => $p['status'] ?? 'n/a',
                    'deadline' => $p['deadline'] ?? ($p['due_date'] ?? null),
                    'created_at' => $p['created_at'] ?? null,
                ];
            }, array_slice($projects, 0, 5));

            // Recent activities derived from messages and tasks (additive)
            $recentActivities = [];
            foreach (array_slice($messages, 0, 3) as $m) {
                $recentActivities[] = [
                    'type' => 'message',
                    'action' => 'received',
                    'description' => 'Message: ' . (string)($m['subject'] ?? substr((string)($m['message_text'] ?? ''), 0, 40)),
                    'timestamp' => $m['created_at'] ?? date('Y-m-d H:i:s'),
                ];
            }
            foreach (array_slice($tasks, 0, 2) as $t) {
                $recentActivities[] = [
                    'type' => 'task',
                    'action' => strtolower((string)($t['status'] ?? 'updated')),
                    'description' => "Task '" . (string)($t['title'] ?? $t['name'] ?? '') . "' status",
                    'timestamp' => $t['updated_at'] ?? $t['created_at'] ?? date('Y-m-d H:i:s'),
                ];
            }
            usort($recentActivities, function($a, $b) {
                return strtotime($b['timestamp']) <=> strtotime($a['timestamp']);
            });
            $recentActivities = array_slice($recentActivities, 0, 5);

            return [
                'success' => true,
                'data' => [
                    'statistics' => $statistics,
                    'recent_projects_client' => $recentProjectsClient,
                    'recent_activities' => $recentActivities,
                    'projects' => array_map(function($p) {
                        return [
                            'id' => $p['id'] ?? null,
                            'name' => $p['name'] ?? ($p['title'] ?? ''),
                            'status' => $p['status'] ?? 'n/a',
                            'deadline' => $p['deadline'] ?? ($p['due_date'] ?? ''),
                        ];
                    }, array_slice($projects, 0, 5)),
                    'tasks' => array_slice($tasks, 0, 5),
                    'materials' => array_slice($materials, 0, 5),
                    'purchase_orders' => array_slice($purchaseOrders, 0, 5),
                    'reports' => array_slice($reports, 0, 5),
                    'finance' => $finance,
                    'messages' => $messages,
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching client dashboard: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get dashboard data for manager users
     * 
     * @return array
     */
    public function getManagerDashboard() {
        try {
            $userId = getCurrentUserId();
            
            if (!$userId) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Get projects owned by this manager
            $projects = [];
            if (method_exists($this->projectModel, 'getByOwner')) {
                $projects = $this->projectModel->getByOwner($userId) ?: [];
            } elseif (method_exists($this->projectModel, 'getAll')) {
                // Fallback to some projects for display only
                $projects = array_slice($this->projectModel->getAll() ?: [], 0, 5);
            }
            $totalProjects = count($projects);
            
            // Get tasks for these projects
            $totalTasks = 0;
            $completedTasks = 0;
            $pendingTasks = 0;
            
            foreach ($projects as $project) {
                $tasks = [];
                if (method_exists($this->taskModel, 'getByProject')) {
                    $tasks = $this->taskModel->getByProject($project['id']) ?: [];
                } elseif (method_exists($this->taskModel, 'getAll')) {
                    $tasks = array_slice($this->taskModel->getAll() ?: [], 0, 5);
                }
                $totalTasks += count($tasks);
                
                foreach ($tasks as $task) {
                    if ($task['status'] === 'completed') {
                        $completedTasks++;
                    } elseif ($task['status'] === 'pending' || $task['status'] === 'in progress') {
                        $pendingTasks++;
                    }
                }
            }
            
            // Get recent messages
            $recentMessages = [];
            if (method_exists($this->messageModel, 'getForUser')) {
                $recentMessages = array_slice($this->messageModel->getForUser($userId) ?: [], 0, 5);
            }
            
            // Get pending material requests
            $pendingMaterials = [];
            if (method_exists($this->materialModel, 'getByStatus')) {
                $pendingMaterials = $this->materialModel->getByStatus('requested') ?: [];
            }
            
            return [
                'success' => true,
                'data' => [
                    'projects' => [
                        'total' => $totalProjects,
                        'list' => array_slice($projects, 0, 5)
                    ],
                    'tasks' => [
                        'total' => $totalTasks,
                        'completed' => $completedTasks,
                        'pending' => $pendingTasks
                    ],
                    'recent_messages' => $recentMessages,
                    'pending_materials' => array_slice($pendingMaterials, 0, 5)
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching dashboard data: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get dashboard data for supervisor users
     * 
     * @return array
     */
    public function getSupervisorDashboard() {
        try {
            $userId = getCurrentUserId();
            
            if (!$userId) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Get tasks assigned to this supervisor
            $assignedTasks = $this->taskModel->getAssignedTo($userId) ?: [];
            $totalTasks = count($assignedTasks);
            
            // Count task statuses
            $completedTasks = 0;
            $inProgressTasks = 0;
            $pendingTasks = 0;
            
            foreach ($assignedTasks as $task) {
                if ($task['status'] === 'completed') {
                    $completedTasks++;
                } elseif ($task['status'] === 'in progress') {
                    $inProgressTasks++;
                } elseif ($task['status'] === 'pending') {
                    $pendingTasks++;
                }
            }
            
            // Get recent messages
            $recentMessages = array_slice($this->messageModel->getForUser($userId) ?: [], 0, 5);
            
            // Get today's attendance status
            $today = date('Y-m-d');
            $attendance = $this->attendanceModel->getByUserAndDate($userId, $today);
            
            return [
                'success' => true,
                'data' => [
                    'tasks' => [
                        'total' => $totalTasks,
                        'completed' => $completedTasks,
                        'in_progress' => $inProgressTasks,
                        'pending' => $pendingTasks,
                        'recent' => array_slice($assignedTasks, 0, 5)
                    ],
                    'recent_messages' => $recentMessages,
                    'attendance' => $attendance ?: null
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching dashboard data: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get dashboard data for worker users
     * 
     * @return array
     */
    public function getWorkerDashboard() {
        try {
            $userId = getCurrentUserId();
            
            if (!$userId) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Get tasks assigned to this worker
            $assignedTasks = $this->taskModel->getAssignedTo($userId) ?: [];
            $totalTasks = count($assignedTasks);
            
            // Count task statuses
            $completedTasks = 0;
            $inProgressTasks = 0;
            $pendingTasks = 0;
            
            foreach ($assignedTasks as $task) {
                if ($task['status'] === 'completed') {
                    $completedTasks++;
                } elseif ($task['status'] === 'in progress') {
                    $inProgressTasks++;
                } elseif ($task['status'] === 'pending') {
                    $pendingTasks++;
                }
            }
            
            // Get recent messages
            $recentMessages = array_slice($this->messageModel->getForUser($userId) ?: [], 0, 5);
            
            // Get today's attendance status
            $today = date('Y-m-d');
            $attendance = $this->attendanceModel->getByUserAndDate($userId, $today);
            
            // Get recent materials
            $recentMaterials = array_slice($this->materialModel->getRequestedBy($userId) ?: [], 0, 5);
            
            // Get assigned projects
            $projectIds = [];
            foreach ($assignedTasks as $task) {
                if (!in_array($task['project_id'], $projectIds)) {
                    $projectIds[] = $task['project_id'];
                }
            }
            
            return [
                'success' => true,
                'data' => [
                    'tasks' => [
                        'total' => $totalTasks,
                        'completed' => $completedTasks,
                        'in_progress' => $inProgressTasks,
                        'pending' => $pendingTasks,
                        'recent' => array_slice($assignedTasks, 0, 5),
                        'project_count' => count($projectIds)
                    ],
                    'recent_messages' => $recentMessages,
                    'attendance' => $attendance ?: null,
                    'recent_materials' => $recentMaterials
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching dashboard data: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get dashboard data based on user role
     * 
     * @return array
     */
    public function getDashboard() {
        $userRole = getCurrentUserRole();
        
        switch ($userRole) {
            case 'admin':
                return $this->getAdminDashboard();
            case 'manager':
                return $this->getManagerDashboard();
            case 'site_manager':
                return $this->getSiteManagerDashboard();
            case 'supervisor':
                return $this->getSupervisorDashboard();
            case 'worker':
                return $this->getWorkerDashboard();
            case 'client':
                // Not used by routing today, but provided additively for API reuse
                return $this->getClientDashboard();
            default:
                return ['success' => false, 'message' => 'Invalid user role'];
        }
    }
    
    /**
     * Get total number of users
     * 
     * @return int
     */
    private function getTotalUsers() {
        try {
            // We would normally get this from a User model, but since we don't have that method yet,
            // we'll use a direct query
            $database = new Database();
            $pdo = $database->connect();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users");
            $stmt->execute();
            
            $result = $stmt->fetch();
            return $result ? (int)$result['total'] : 0;
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Get recent activities across the system
     * 
     * @return array
     */
    private function getRecentActivities() {
        // Simplified, resilient implementation
        $activities = [];

        // Recent projects (guarded)
        $recentProjects = [];
        if (isset($this->projectModel) && method_exists($this->projectModel, 'getAll')) {
            $list = $this->projectModel->getAll() ?: [];
            $recentProjects = array_slice($list, 0, 3);
        }
        foreach ($recentProjects as $project) {
            $name = isset($project['name']) ? (string)$project['name'] : ((string)($project['title'] ?? 'Project'));
            $ts = isset($project['created_at']) ? (string)$project['created_at'] : ((string)($project['timestamp'] ?? date('Y-m-d H:i:s')));
            $activities[] = [
                'type' => 'project',
                'action' => 'created',
                'description' => "Project '{$name}' was created",
                'timestamp' => $ts
            ];
        }

        // Recent tasks (guarded)
        $recentTasks = [];
        if (isset($this->taskModel) && method_exists($this->taskModel, 'getAll')) {
            $list = $this->taskModel->getAll() ?: [];
            $recentTasks = array_slice($list, 0, 3);
        }
        foreach ($recentTasks as $task) {
            $title = isset($task['title']) ? (string)$task['title'] : ((string)($task['name'] ?? 'Task'));
            $ts = isset($task['created_at']) ? (string)$task['created_at'] : ((string)($task['timestamp'] ?? date('Y-m-d H:i:s')));
            $activities[] = [
                'type' => 'task',
                'action' => 'created',
                'description' => "Task '{$title}' was created",
                'timestamp' => $ts
            ];
        }

        // Sort by timestamp safely
        usort($activities, function($a, $b) {
            $ta = isset($a['timestamp']) ? strtotime((string)$a['timestamp']) : 0;
            $tb = isset($b['timestamp']) ? strtotime((string)$b['timestamp']) : 0;
            return $tb <=> $ta;
        });

        return array_slice($activities, 0, 5);
    }
}