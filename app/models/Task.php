<?php
// Task model for managing project tasks

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/ActivityLogger.php';

class Task {
    private $pdo;
    
    public function __construct() {
        $database = new Database();
        $this->pdo = $database->connect();
    }
    
    /**
     * Create a new task
     * 
     * @param int $projectId
     * @param string $title
     * @param string $description
     * @param int $assignedTo
     * @param string $status
     * @param string $dueDate
     * @return array
     */
    public function create($projectId, $title, $description, $assignedTo = null, $status = 'pending', $dueDate = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO tasks (project_id, assigned_to, title, description, status, due_date, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $result = $stmt->execute([$projectId, $assignedTo, $title, $description, $status, $dueDate]);
            
            if ($result) {
                $taskId = $this->pdo->lastInsertId();
                try { ActivityLogger::log($projectId,'task',$taskId,'create',"Task #$taskId '$title' created",null,[ 'status'=>$status,'assigned_to'=>$assignedTo,'due_date'=>$dueDate ]); } catch (Throwable $e) {}
                return [ 'success' => true, 'message' => 'Task created successfully', 'task_id' => $taskId ];
            }
            
            return ['success' => false, 'message' => 'Failed to create task'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Task creation error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get task by ID
     * 
     * @param int $taskId
     * @return array|bool
     */
    public function getById($taskId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT t.*, p.name as project_name, u.name as assigned_to_name 
                FROM tasks t 
                LEFT JOIN projects p ON t.project_id = p.id 
                LEFT JOIN users u ON t.assigned_to = u.id 
                WHERE t.id = ?
            ");
            $stmt->execute([$taskId]);
            
            if ($stmt->rowCount() > 0) {
                return $stmt->fetch();
            }
            
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get all tasks
     * 
     * @return array|bool
     */
    public function getAll() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT t.*, p.name as project_name, u.name as assigned_to_name 
                FROM tasks t 
                LEFT JOIN projects p ON t.project_id = p.id 
                LEFT JOIN users u ON t.assigned_to = u.id 
                ORDER BY t.created_at DESC
            ");
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get tasks for projects owned by a specific manager (PM)
     * Only returns non-closed tasks unless $includeClosed is true
     *
     * @param int $managerId
     * @param bool $includeClosed
     * @return array|bool
     */
    public function getByManager($managerId, $includeClosed = false) {
        // Unified resilient implementation that adapts to schema differences
        try {
            if (session_status() === PHP_SESSION_NONE) { @session_start(); }
        } catch (Throwable $e) { /* ignore */ }

        $role = isset($_SESSION['role']) ? strtolower((string)$_SESSION['role']) : '';

        // If project_manager: global visibility (respect closed toggle)
        if ($role === 'project_manager') {
            try {
                $sql = "SELECT t.*, p.name AS project_name, u.name AS assigned_to_name
                        FROM tasks t
                        INNER JOIN projects p ON t.project_id = p.id
                        LEFT JOIN users u ON t.assigned_to = u.id";
                if (!$includeClosed) {
                    $sql .= " WHERE t.status NOT IN ('completed','cancelled')";
                }
                $sql .= " ORDER BY t.created_at DESC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                return $stmt->fetchAll();
            } catch (PDOException $e) { /* continue with manager logic */ }
        }

        // Detect optional schema elements
        $hasSiteManager = false; $hasAssignments = false;
        try { $c = $this->pdo->query("SHOW COLUMNS FROM projects LIKE 'site_manager_id'"); $hasSiteManager = $c && $c->rowCount() > 0; } catch (PDOException $ignore) {}
        try { $c2 = $this->pdo->query("SHOW TABLES LIKE 'project_assignments'"); $hasAssignments = $c2 && $c2->rowCount() > 0; } catch (PDOException $ignore) {}

        $select = "SELECT t.*, p.name AS project_name, u.name AS assigned_to_name
                   FROM tasks t
                   INNER JOIN projects p ON t.project_id = p.id
                   LEFT JOIN users u ON t.assigned_to = u.id";

        $joins = '';
        if ($hasAssignments) {
            $joins .= " LEFT JOIN project_assignments pa ON pa.project_id = p.id AND pa.user_id = :uid";
        }

        $conditions = ["p.owner_id = :uid"];
        if ($hasSiteManager) { $conditions[] = "p.site_manager_id = :uid"; }
        if ($hasAssignments) { $conditions[] = "pa.user_id IS NOT NULL"; }

        $where = ' WHERE (' . implode(' OR ', $conditions) . ')';
        if (!$includeClosed) {
            $where .= " AND t.status NOT IN ('completed','cancelled')";
        }

        $order = ' ORDER BY t.created_at DESC';
        $sql = $select . $joins . $where . $order;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':uid' => $managerId]);
            $rows = $stmt->fetchAll();
            // Fallback: if nothing returned AND user may have directly assigned tasks, include those
            if (empty($rows)) {
                try {
                    $assignedSql = "SELECT t.*, p.name AS project_name, u.name AS assigned_to_name
                                     FROM tasks t
                                     INNER JOIN projects p ON t.project_id = p.id
                                     LEFT JOIN users u ON t.assigned_to = u.id
                                     WHERE t.assigned_to = :uid" . (!$includeClosed ? " AND t.status NOT IN ('completed','cancelled')" : '') . " ORDER BY t.created_at DESC";
                    $assignedStmt = $this->pdo->prepare($assignedSql);
                    $assignedStmt->execute([':uid'=>$managerId]);
                    $assigned = $assignedStmt->fetchAll();
                    if (!empty($assigned)) {
                        $rows = $assigned; // show something meaningful
                    }
                } catch (PDOException $ignore) { /* ignore */ }
            }
            return $rows;
        } catch (PDOException $e) {
            // Absolute last resort: return owner-only tasks without optional pieces
            try {
                $fallback = "SELECT t.*, p.name AS project_name, u.name AS assigned_to_name
                             FROM tasks t
                             INNER JOIN projects p ON t.project_id = p.id
                             LEFT JOIN users u ON t.assigned_to = u.id
                             WHERE p.owner_id = :uid" . (!$includeClosed ? " AND t.status NOT IN ('completed','cancelled')" : '') . " ORDER BY t.created_at DESC";
                $stmt = $this->pdo->prepare($fallback);
                $stmt->execute([':uid'=>$managerId]);
                return $stmt->fetchAll();
            } catch (PDOException $e2) {
                return false;
            }
        }
    }
    
    /**
     * Get tasks by project
     * 
     * @param int $projectId
     * @return array|bool
     */
    public function getByProject($projectId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT t.*, p.name as project_name, u.name as assigned_to_name 
                FROM tasks t 
                LEFT JOIN projects p ON t.project_id = p.id 
                LEFT JOIN users u ON t.assigned_to = u.id 
                WHERE t.project_id = ?
                ORDER BY t.created_at DESC
            ");
            $stmt->execute([$projectId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get tasks assigned to user
     * 
     * @param int $userId
     * @return array|bool
     */
    public function getAssignedTo($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT t.*, p.name as project_name, u.name as assigned_to_name 
                FROM tasks t 
                LEFT JOIN projects p ON t.project_id = p.id 
                LEFT JOIN users u ON t.assigned_to = u.id 
                WHERE t.assigned_to = ?
                ORDER BY t.created_at DESC
            ");
            $stmt->execute([$userId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get recent tasks with project name regardless of assignment
     * Used as a fallback for supervisor dashboard in multi-assignment model
     * @param int $limit
     * @return array|bool
     */
    public function getRecentWithProject($limit = 12) {
        try {
            $limit = (int)$limit;
            if ($limit < 1) { $limit = 12; }
            $stmt = $this->pdo->prepare("SELECT t.*, p.name AS project_name, u.name as assigned_to_name FROM tasks t LEFT JOIN projects p ON t.project_id = p.id LEFT JOIN users u ON t.assigned_to = u.id ORDER BY t.created_at DESC LIMIT $limit");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Update task
     * 
     * @param int $taskId
     * @param array $data
     * @return array
     */
    public function update($taskId, $data) {
        try {
            $fields = [];
            $values = [];
            
            foreach ($data as $key => $value) {
                if ($key !== 'id') { // Don't update the ID
                    $fields[] = "$key = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($fields)) {
                return ['success' => false, 'message' => 'No data to update'];
            }
            
            $values[] = $taskId; // Add task ID for WHERE clause
            
            $sql = "UPDATE tasks SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($values);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Task updated successfully'];
            } elseif ($result) {
                return ['success' => false, 'message' => 'No changes made to task'];
            }
            
            return ['success' => false, 'message' => 'Failed to update task'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Task update error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete task
     * 
     * @param int $taskId
     * @return array
     */
    public function delete($taskId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $result = $stmt->execute([$taskId]);
            
            if ($result && $stmt->rowCount() > 0) {
                try { $pid = $this->getProjectId($taskId); ActivityLogger::log($pid,'task',$taskId,'delete',"Task #$taskId deleted"); } catch (Throwable $e) {}
                return ['success' => true, 'message' => 'Task deleted successfully'];
            } elseif ($result) {
                return ['success' => false, 'message' => 'Task not found'];
            }
            
            return ['success' => false, 'message' => 'Failed to delete task'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Task deletion error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Assign task to user
     * 
     * @param int $taskId
     * @param int $userId
     * @return array
     */
    public function assign($taskId, $userId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE tasks SET assigned_to = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$userId, $taskId]);
            
            if ($result && $stmt->rowCount() > 0) {
                try { $pid = $this->getProjectId($taskId); ActivityLogger::log($pid,'task',$taskId,'assign',"Task #$taskId assigned to user $userId",null,['assigned_to'=>$userId]); } catch (Throwable $e) {}
                return ['success' => true, 'message' => 'Task assigned successfully'];
            } elseif ($result) {
                return ['success' => false, 'message' => 'Task not found'];
            }
            
            return ['success' => false, 'message' => 'Failed to assign task'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Task assignment error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update task status
     * 
     * @param int $taskId
     * @param string $status
     * @return array
     */
    public function updateStatus($taskId, $status) {
        try {
            $stmt = $this->pdo->prepare("UPDATE tasks SET status = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$status, $taskId]);
            
            if ($result && $stmt->rowCount() > 0) {
                try { $pid = $this->getProjectId($taskId); ActivityLogger::log($pid,'task',$taskId,'status_change',"Task #$taskId status changed to $status",null,['status'=>$status]); } catch (Throwable $e) {}
                return ['success' => true, 'message' => 'Task status updated successfully'];
            } elseif ($result) {
                return ['success' => false, 'message' => 'Task not found'];
            }
            
            return ['success' => false, 'message' => 'Failed to update task status'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Task status update error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update task progress
     * 
     * @param array $data
     * @return array
     */
    public function updateProgress($data) {
        try {
            // Begin transaction
            $this->pdo->beginTransaction();
            
            // First, check if the table has our custom columns
            $hasCustomColumns = $this->hasCustomColumns();
            
            // Update task data
            $sql = "UPDATE tasks SET updated_at = NOW()";
            $params = [];
            
            // Only add these fields if the columns exist
            if ($hasCustomColumns) {
                if (isset($data['progress_notes']) && !empty($data['progress_notes'])) {
                    $sql .= ", progress_notes = CONCAT(IFNULL(progress_notes, ''), :separator, :progress_notes)";
                    $params[':progress_notes'] = date('Y-m-d H:i:s') . ': ' . $data['progress_notes'];
                    $params[':separator'] = empty($params[':progress_notes']) ? '' : "\n\n";
                }
                
                if (isset($data['completion_percentage'])) {
                    $sql .= ", completion_percentage = :completion_percentage";
                    $params[':completion_percentage'] = $data['completion_percentage'];
                }
            }
            
            // Always update status if provided
            if (isset($data['status'])) {
                $sql .= ", status = :status";
                $params[':status'] = $data['status'];
            }
            
            $sql .= " WHERE id = :task_id";
            $params[':task_id'] = $data['id'];
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            // Store photo references if any and if the task_photos table exists
            if (!empty($data['photos'])) {
                // Check if the task_photos table exists
                try {
                    $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'task_photos'");
                    if ($tableCheck->rowCount() > 0) {
                        $insertPhotoSql = "INSERT INTO task_photos (task_id, photo_path, uploaded_at) VALUES (:task_id, :photo_path, NOW())";
                        $photoStmt = $this->pdo->prepare($insertPhotoSql);
                        
                        foreach ($data['photos'] as $photoPath) {
                            $photoStmt->execute([
                                ':task_id' => $data['id'],
                                ':photo_path' => $photoPath
                            ]);
                        }
                    }
                } catch (PDOException $e) {
                    // Table doesn't exist, just ignore photo storage
                }
            }
            
            // Commit transaction
            $this->pdo->commit();
            
            try { $pid = $this->getProjectId($data['id']); ActivityLogger::log($pid,'task',$data['id'],'progress',"Task #{$data['id']} progress updated",null,[ 'status'=>$data['status'] ?? null,'completion_percentage'=>$data['completion_percentage'] ?? null ]); } catch (Throwable $e) {}
            return ['success' => true, 'message' => 'Task progress updated successfully'];
        } catch (PDOException $e) {
            // Roll back transaction on error
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Task progress update error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Check if tasks table has our custom columns
     * 
     * @return bool
     */
    private function hasCustomColumns() {
        try {
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM tasks LIKE 'completion_percentage'");
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Helper to fetch project_id for a task (logging support)
    private function getProjectId($taskId) {
        try {
            $stmt = $this->pdo->prepare("SELECT project_id FROM tasks WHERE id = ? LIMIT 1");
            $stmt->execute([$taskId]);
            $pid = $stmt->fetchColumn();
            return $pid ? (int)$pid : null;
        } catch (PDOException $e) { return null; }
    }

    /**
     * Get open (non-completed / non-cancelled) tasks grouped by project.
     * Returns array of groups: [ ['project_id'=>..,'project_name'=>..,'tasks'=>[task, ...]], ... ]
     * Safe, read-only helper that does NOT alter existing APIs.
     */
    public function getOpenTasksGroupedByProject() {
        try {
            $sql = "SELECT t.*, p.name AS project_name, u.name AS assigned_to_name
                    FROM tasks t
                    INNER JOIN projects p ON t.project_id = p.id
                    LEFT JOIN users u ON t.assigned_to = u.id
                    WHERE t.status NOT IN ('completed','cancelled')
                    ORDER BY p.name ASC, t.created_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchAll();
            $groups = [];
            foreach ($rows as $r) {
                $pid = $r['project_id'];
                if (!isset($groups[$pid])) {
                    $groups[$pid] = [
                        'project_id' => $pid,
                        'project_name' => $r['project_name'] ?? ('Project #'.$pid),
                        'tasks' => []
                    ];
                }
                $groups[$pid]['tasks'][] = $r;
            }
            return array_values($groups);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get active assigned workers for a task (from task_assignments)
     * Returns array of workers: id, name, email, role_in_task
     */
    public function getAssignedWorkers($taskId) {
        try {
            $stmt = $this->pdo->prepare("SELECT ta.worker_id AS id, ta.role AS role_in_task, u.name, u.email
                                         FROM task_assignments ta
                                         JOIN users u ON u.id = ta.worker_id
                                         WHERE ta.task_id = :tid AND ta.active = 1
                                         ORDER BY u.name");
            $stmt->execute([':tid'=>(int)$taskId]);
            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) { return []; }
    }
}