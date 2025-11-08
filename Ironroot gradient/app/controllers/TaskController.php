<?php
// Task controller for handling task operations

require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/helpers.php';

class TaskController {
    private $taskModel;
    private $projectModel;
    
    public function __construct() {
        $this->taskModel = new Task();
        $this->projectModel = new Project();
    }
    
    /**
     * Create a new task
     * 
     * @param array $data
     * @return array
     */
    public function create($data) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate input data
            $projectId = isset($data['project_id']) ? (int)$data['project_id'] : 0;
            $title = isset($data['title']) ? sanitize($data['title']) : '';
            $description = isset($data['description']) ? sanitize($data['description']) : '';
            $assignedTo = isset($data['assigned_to']) ? (int)$data['assigned_to'] : null;
            $status = isset($data['status']) ? sanitize($data['status']) : 'pending';
            $dueDate = isset($data['due_date']) ? $data['due_date'] : null;
            
            // Validation
            if (empty($projectId)) {
                return ['success' => false, 'message' => 'Project ID is required'];
            }
            
            if (empty($title)) {
                return ['success' => false, 'message' => 'Task title is required'];
            }
            
            // Validate status
            $validStatuses = ['pending', 'in progress', 'completed', 'cancelled'];
            if (!in_array($status, $validStatuses)) {
                return ['success' => false, 'message' => 'Invalid task status'];
            }
            
            // Validate dates if provided
            if ($dueDate && !strtotime($dueDate)) {
                return ['success' => false, 'message' => 'Invalid due date format'];
            }
            
            // Check if project exists
            $project = $this->projectModel->getById($projectId);
            if (!$project) {
                return ['success' => false, 'message' => 'Project not found'];
            }
            
            // Check if user has permission to create task in this project
            $userRole = getCurrentUserRole();
            $userId = getCurrentUserId();
            
            if (!in_array($userRole, ['admin', 'manager']) && $project['owner_id'] != $userId) {
                return ['success' => false, 'message' => 'Insufficient permissions to create task in this project'];
            }
            
            // If assigning to a user, check if that user exists
            if ($assignedTo) {
                // We would normally check if the user exists, but we don't have a method for that yet
                // For now, we'll just pass it through
            }
            
            // Create task
            $result = $this->taskModel->create($projectId, $title, $description, $assignedTo, $status, $dueDate);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error creating task: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get task by ID
     * 
     * @param int $taskId
     * @return array
     */
    public function get($taskId) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate task ID
            if (empty($taskId)) {
                return ['success' => false, 'message' => 'Task ID is required'];
            }
            
            // Get task
            $task = $this->taskModel->getById($taskId);
            
            if (!$task) {
                return ['success' => false, 'message' => 'Task not found'];
            }
            
            // Check if user has permission to view this task
            $userRole = getCurrentUserRole();
            $userId = getCurrentUserId();
            
            // Admins and managers can view all tasks
            // Supervisors and workers can only view tasks they're assigned to or tasks in projects they own
            if (!in_array($userRole, ['admin', 'manager'])) {
                $project = $this->projectModel->getById($task['project_id']);
                if (!$project || ($project['owner_id'] != $userId && $task['assigned_to'] != $userId)) {
                    return ['success' => false, 'message' => 'Insufficient permissions to view this task'];
                }
            }
            
            return [
                'success' => true,
                'data' => $task
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching task: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all tasks
     * 
     * @return array
     */
    public function getAll() {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            $userRole = getCurrentUserRole();
            $userId = getCurrentUserId();
            
            // Get tasks based on user role
            if (in_array($userRole, ['admin', 'manager'])) {
                // Admins and managers can see all tasks
                $tasks = $this->taskModel->getAll();
            } elseif ($userRole === 'site_manager') {
                // Site Managers: tasks for projects they manage or are assigned to
                $tasks = $this->taskModel->getByManager($userId) ?: [];
            } else {
                // Other roles: tasks assigned to user or in projects they own
                $tasks = array_merge(
                    $this->taskModel->getAssignedTo($userId) ?: [],
                    $this->getTasksForOwnedProjects($userId) ?: []
                );
                
                // Remove duplicates
                $taskIds = [];
                $uniqueTasks = [];
                foreach ($tasks as $task) {
                    if (!in_array($task['id'], $taskIds)) {
                        $taskIds[] = $task['id'];
                        $uniqueTasks[] = $task;
                    }
                }
                $tasks = $uniqueTasks;
            }
            
            return [
                'success' => true,
                'data' => $tasks ?: []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching tasks: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get tasks by project
     * 
     * @param int $projectId
     * @return array
     */
    public function getByProject($projectId) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate project ID
            if (empty($projectId)) {
                return ['success' => false, 'message' => 'Project ID is required'];
            }
            
            // Check if project exists
            $project = $this->projectModel->getById($projectId);
            if (!$project) {
                return ['success' => false, 'message' => 'Project not found'];
            }
            
            // Check if user has permission to view tasks in this project
            $userRole = getCurrentUserRole();
            $userId = getCurrentUserId();
            
            if (!in_array($userRole, ['admin', 'manager'])) {
                $allowed = false;
                // Owner can view
                if ((int)$project['owner_id'] === (int)$userId) {
                    $allowed = true;
                }
                // Site manager of the project can view
                if (!$allowed && $userRole === 'site_manager' && (int)($project['site_manager_id'] ?? 0) === (int)$userId) {
                    $allowed = true;
                }
                // If not yet allowed, try project_assignments
                if (!$allowed) {
                    try {
                        $db = Database::getConnection();
                        $stmt = $db->prepare('SELECT 1 FROM project_assignments WHERE project_id = :pid AND user_id = :uid LIMIT 1');
                        $stmt->execute([':pid'=>$projectId, ':uid'=>$userId]);
                        $allowed = $stmt->fetchColumn() ? true : false;
                    } catch (Throwable $e) { /* ignore if table missing */ }
                }
                if (!$allowed) {
                    return ['success' => false, 'message' => 'Insufficient permissions to view tasks in this project'];
                }
            }
            
            // Get tasks
            $tasks = $this->taskModel->getByProject($projectId);
            
            return [
                'success' => true,
                'data' => $tasks ?: []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching tasks: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get tasks assigned to user
     * 
     * @param int $userId
     * @return array
     */
    public function getAssignedTo($userId = null) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // If no user ID provided, use current user
            if (empty($userId)) {
                $userId = getCurrentUserId();
            }
            
            // Check if user has permission to view these tasks
            $userRole = getCurrentUserRole();
            $currentUserId = getCurrentUserId();
            
            if (!in_array($userRole, ['admin', 'manager']) && $userId != $currentUserId) {
                return ['success' => false, 'message' => 'Insufficient permissions to view tasks assigned to this user'];
            }
            
            // Get tasks
            $tasks = $this->taskModel->getAssignedTo($userId);
            
            return [
                'success' => true,
                'data' => $tasks ?: []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching tasks: ' . $e->getMessage()];
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
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate task ID
            if (empty($taskId)) {
                return ['success' => false, 'message' => 'Task ID is required'];
            }
            
            // Get existing task
            $task = $this->taskModel->getById($taskId);
            
            if (!$task) {
                return ['success' => false, 'message' => 'Task not found'];
            }
            
            // Check if user has permission to update this task
            $userRole = getCurrentUserRole();
            $userId = getCurrentUserId();
            
            if (!in_array($userRole, ['admin', 'manager'])) {
                $project = $this->projectModel->getById($task['project_id']);
                if (!$project || ($project['owner_id'] != $userId && $task['assigned_to'] != $userId)) {
                    return ['success' => false, 'message' => 'Insufficient permissions to update this task'];
                }
            }
            
            // Validate and sanitize input data
            $updateData = [];
            
            if (isset($data['title'])) {
                $title = sanitize($data['title']);
                if (!empty($title)) {
                    $updateData['title'] = $title;
                }
            }
            
            if (isset($data['description'])) {
                $updateData['description'] = sanitize($data['description']);
            }
            
            if (isset($data['status'])) {
                $status = sanitize($data['status']);
                $validStatuses = ['pending', 'in progress', 'completed', 'cancelled'];
                if (in_array($status, $validStatuses)) {
                    $updateData['status'] = $status;
                }
            }
            
            if (isset($data['due_date'])) {
                $dueDate = $data['due_date'];
                if (empty($dueDate) || strtotime($dueDate)) {
                    $updateData['due_date'] = $dueDate ?: null;
                }
            }
            
            if (isset($data['assigned_to'])) {
                $assignedTo = $data['assigned_to'];
                if (empty($assignedTo)) {
                    $updateData['assigned_to'] = null;
                } else {
                    // We would normally check if the user exists, but we don't have a method for that yet
                    $updateData['assigned_to'] = (int)$assignedTo;
                }
            }
            
            // Update task
            if (!empty($updateData)) {
                $result = $this->taskModel->update($taskId, $updateData);
                return $result;
            } else {
                return ['success' => false, 'message' => 'No valid data to update'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating task: ' . $e->getMessage()];
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
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate task ID
            if (empty($taskId)) {
                return ['success' => false, 'message' => 'Task ID is required'];
            }
            
            // Get existing task
            $task = $this->taskModel->getById($taskId);
            
            if (!$task) {
                return ['success' => false, 'message' => 'Task not found'];
            }
            
            // Check if user has permission to delete this task
            $userRole = getCurrentUserRole();
            $userId = getCurrentUserId();
            
            if (!in_array($userRole, ['admin', 'manager'])) {
                $project = $this->projectModel->getById($task['project_id']);
                if (!$project || $project['owner_id'] != $userId) {
                    return ['success' => false, 'message' => 'Insufficient permissions to delete this task'];
                }
            }
            
            // Delete task
            $result = $this->taskModel->delete($taskId);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error deleting task: ' . $e->getMessage()];
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
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate input
            if (empty($taskId) || empty($userId)) {
                return ['success' => false, 'message' => 'Task ID and User ID are required'];
            }
            
            // Get existing task
            $task = $this->taskModel->getById($taskId);
            
            if (!$task) {
                return ['success' => false, 'message' => 'Task not found'];
            }
            
            // Check if user has permission to assign this task
            $userRole = getCurrentUserRole();
            $currentUserId = getCurrentUserId();
                
            if (!in_array($userRole, ['admin', 'manager'])) {
                $project = $this->projectModel->getById($task['project_id']);
                if (!$project) {
                    return ['success' => false, 'message' => 'Project not found for this task'];
                }
                $allowed = false;
                // Owner of project can assign
                if ((int)$project['owner_id'] === (int)$currentUserId) {
                    $allowed = true;
                }
                // Site manager assigned to the project can assign
                if (!$allowed && $userRole === 'site_manager' && (int)($project['site_manager_id'] ?? 0) === (int)$currentUserId) {
                    $allowed = true;
                }
                // Project assignments also allow
                if (!$allowed) {
                    try {
                        $db = Database::getConnection();
                        $stmt = $db->prepare('SELECT 1 FROM project_assignments WHERE project_id = :pid AND user_id = :uid LIMIT 1');
                        $stmt->execute([':pid' => (int)$project['id'], ':uid' => (int)$currentUserId]);
                        $allowed = $stmt->fetchColumn() ? true : false;
                    } catch (Throwable $e) { /* ignore missing table */ }
                }
                if (!$allowed) {
                    return ['success' => false, 'message' => 'Insufficient permissions to assign this task'];
                }
            }
            
            // Assign task
            $result = $this->taskModel->assign($taskId, $userId);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error assigning task: ' . $e->getMessage()];
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
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate task ID
            if (empty($taskId)) {
                return ['success' => false, 'message' => 'Task ID is required'];
            }
            
            // Validate status
            $validStatuses = ['pending', 'in progress', 'completed', 'cancelled'];
            if (!in_array($status, $validStatuses)) {
                return ['success' => false, 'message' => 'Invalid task status'];
            }
            
            // Get existing task
            $task = $this->taskModel->getById($taskId);
            
            if (!$task) {
                return ['success' => false, 'message' => 'Task not found'];
            }
            
            // Check if user has permission to update this task
            $userRole = getCurrentUserRole();
            $userId = getCurrentUserId();
            
            if (!in_array($userRole, ['admin', 'manager']) && $task['assigned_to'] != $userId) {
                // Users can only update status of tasks assigned to them
                // Admins and managers can update any task
                return ['success' => false, 'message' => 'Insufficient permissions to update this task'];
            }
            
            // Update status
            $result = $this->taskModel->updateStatus($taskId, $status);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating task status: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update task progress
     * 
     * @param int $taskId
     * @param string $notes
     * @param int $completionPercentage
     * @param array $photos
     * @return array
     */
    public function updateProgress($taskId, $notes, $completionPercentage, $photos = []) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate task ID
            if (empty($taskId)) {
                return ['success' => false, 'message' => 'Task ID is required'];
            }
            
            // Get existing task
            $task = $this->taskModel->getById($taskId);
            
            if (!$task) {
                return ['success' => false, 'message' => 'Task not found'];
            }
            
            // Check if user has permission to update this task
            $userRole = getCurrentUserRole();
            $userId = getCurrentUserId();
            
            if (!in_array($userRole, ['admin', 'manager']) && $task['assigned_to'] != $userId) {
                // Users can only update progress of tasks assigned to them
                // Admins and managers can update any task
                return ['success' => false, 'message' => 'Insufficient permissions to update this task'];
            }
            
            // Update task progress
            $data = [
                'id' => $taskId,
                'progress_notes' => $notes,
                'completion_percentage' => $completionPercentage
            ];
            
            // Handle photo storage separately if the column exists
            if (!empty($photos)) {
                $data['photos'] = $photos;
            }
            
            // If completion percentage is 100, also update status to completed
            if ($completionPercentage == 100 && $task['status'] != 'completed') {
                $data['status'] = 'completed';
            } else if ($completionPercentage > 0 && $task['status'] == 'pending') {
                $data['status'] = 'in progress';
            }
            
            // Just try calling updateProgress - if the method can handle it properly
            $result = $this->taskModel->updateProgress($data);
            
            $result = $this->taskModel->updateProgress($data);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating task progress: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get tasks for projects owned by user
     * 
     * @param int $userId
     * @return array
     */
    private function getTasksForOwnedProjects($userId) {
        try {
            $projects = $this->projectModel->getByOwner($userId) ?: [];
            $allTasks = [];
            
            foreach ($projects as $project) {
                $tasks = $this->taskModel->getByProject($project['id']) ?: [];
                $allTasks = array_merge($allTasks, $tasks);
            }
            
            return $allTasks;
        } catch (Exception $e) {
            return [];
        }
    }
}