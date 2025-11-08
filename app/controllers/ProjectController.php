<?php
// Project controller for handling project operations

require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/helpers.php';

class ProjectController {
    private $projectModel;
    
    public function __construct() {
        $this->projectModel = new Project();
    }
    
    /**
     * Create a new project
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
            
            // Permission check: originally only admin/manager. Expanded minimally to include
            // project_manager and client so they can create projects they own.
            $userRole = getCurrentUserRole();
            if (!in_array($userRole, ['admin', 'manager', 'project_manager', 'client'])) {
                return ['success' => false, 'message' => 'Insufficient permissions to create project'];
            }
            
            // Validate input data
            $name = isset($data['name']) ? sanitize($data['name']) : '';
            $description = isset($data['description']) ? sanitize($data['description']) : '';
            $status = isset($data['status']) ? sanitize($data['status']) : 'planning';
            $startDate = isset($data['start_date']) ? $data['start_date'] : null;
            $endDate = isset($data['end_date']) ? $data['end_date'] : null;
            
            // Validation
            if (empty($name)) {
                return ['success' => false, 'message' => 'Project name is required'];
            }
            
            // Validate status
            $validStatuses = ['planning', 'active', 'on hold', 'completed', 'cancelled'];
            if (!in_array($status, $validStatuses)) {
                return ['success' => false, 'message' => 'Invalid project status'];
            }
            
            // Validate dates if provided
            if ($startDate && !strtotime($startDate)) {
                return ['success' => false, 'message' => 'Invalid start date format'];
            }
            
            if ($endDate && !strtotime($endDate)) {
                return ['success' => false, 'message' => 'Invalid end date format'];
            }
            
            // If both dates are provided, end date should be after start date
            if ($startDate && $endDate && strtotime($endDate) < strtotime($startDate)) {
                return ['success' => false, 'message' => 'End date must be after start date'];
            }
            
            // Get current user ID as owner
            $ownerId = getCurrentUserId();
            
            // Create project
            $result = $this->projectModel->create($name, $description, $ownerId, $status, $startDate, $endDate);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error creating project: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get project by ID
     * 
     * @param int $projectId
     * @return array
     */
    public function get($projectId) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate project ID
            if (empty($projectId)) {
                return ['success' => false, 'message' => 'Project ID is required'];
            }
            
            // Get project
            $project = $this->projectModel->getById($projectId);
            
            if (!$project) {
                return ['success' => false, 'message' => 'Project not found'];
            }
            
            // Check if user has permission to view this project
            $userRole = getCurrentUserRole();
            $userId = getCurrentUserId();
            
            // Admins and managers can view all projects
            // Supervisors and workers can only view projects they're assigned to
            if (!in_array($userRole, ['admin', 'manager']) && $project['owner_id'] != $userId) {
                // Check if user has tasks in this project
                // This would require a Task model, but for now we'll restrict access
                return ['success' => false, 'message' => 'Insufficient permissions to view this project'];
            }
            
            return [
                'success' => true,
                'data' => $project
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching project: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all projects
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
            
            // Get projects based on user role
            if (in_array($userRole, ['admin', 'manager'])) {
                $projects = $this->projectModel->getAll();
            } elseif ($userRole === 'project_manager') {
                // Try manager scope (owner / site manager / assignments) with fallback to ownership
                if (method_exists($this->projectModel, 'getByManager')) {
                    $projects = $this->projectModel->getByManager($userId);
                    if (!$projects) {
                        $projects = $this->projectModel->getByOwner($userId);
                    }
                } else {
                    $projects = $this->projectModel->getByOwner($userId);
                }
            } elseif ($userRole === 'client') {
                // Clients: only projects they own
                $projects = $this->projectModel->getByOwner($userId);
            } else {
                // Other roles: restrict to ownership for now (future: tasks/assignments)
                $projects = $this->projectModel->getByOwner($userId);
            }
            
            return [
                'success' => true,
                'data' => $projects ?: []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching projects: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update project
     * 
     * @param int $projectId
     * @param array $data
     * @return array
     */
    public function update($projectId, $data) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate project ID
            if (empty($projectId)) {
                return ['success' => false, 'message' => 'Project ID is required'];
            }
            
            // Get existing project
            $project = $this->projectModel->getById($projectId);
            
            if (!$project) {
                return ['success' => false, 'message' => 'Project not found'];
            }
            
            // Check if user has permission to update this project
            $userRole = getCurrentUserRole();
            $userId = getCurrentUserId();
            
            if (!in_array($userRole, ['admin', 'manager']) && $project['owner_id'] != $userId) {
                return ['success' => false, 'message' => 'Insufficient permissions to update this project'];
            }
            
            // Validate and sanitize input data
            $updateData = [];
            
            if (isset($data['name'])) {
                $name = sanitize($data['name']);
                if (!empty($name)) {
                    $updateData['name'] = $name;
                }
            }
            
            if (isset($data['description'])) {
                $updateData['description'] = sanitize($data['description']);
            }
            
            if (isset($data['status'])) {
                $status = sanitize($data['status']);
                $validStatuses = ['planning', 'active', 'on hold', 'completed', 'cancelled'];
                if (in_array($status, $validStatuses)) {
                    $updateData['status'] = $status;
                }
            }
            
            if (isset($data['start_date'])) {
                $startDate = $data['start_date'];
                if (empty($startDate) || strtotime($startDate)) {
                    $updateData['start_date'] = $startDate ?: null;
                }
            }
            
            if (isset($data['end_date'])) {
                $endDate = $data['end_date'];
                if (empty($endDate) || strtotime($endDate)) {
                    $updateData['end_date'] = $endDate ?: null;
                }
            }
            
            // Validate dates if both are provided
            if (isset($updateData['start_date']) && isset($updateData['end_date'])) {
                $startDate = $updateData['start_date'];
                $endDate = $updateData['end_date'];
                
                if ($startDate && $endDate && strtotime($endDate) < strtotime($startDate)) {
                    return ['success' => false, 'message' => 'End date must be after start date'];
                }
            }
            
            // Update project
            if (!empty($updateData)) {
                $result = $this->projectModel->update($projectId, $updateData);
                return $result;
            } else {
                return ['success' => false, 'message' => 'No valid data to update'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating project: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete project
     * 
     * @param int $projectId
     * @return array
     */
    public function delete($projectId) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Check if user has permission (admin or manager)
            $userRole = getCurrentUserRole();
            if (!in_array($userRole, ['admin', 'manager'])) {
                return ['success' => false, 'message' => 'Insufficient permissions to delete project'];
            }
            
            // Validate project ID
            if (empty($projectId)) {
                return ['success' => false, 'message' => 'Project ID is required'];
            }
            
            // Get existing project
            $project = $this->projectModel->getById($projectId);
            
            if (!$project) {
                return ['success' => false, 'message' => 'Project not found'];
            }
            
            // Delete project
            $result = $this->projectModel->delete($projectId);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error deleting project: ' . $e->getMessage()];
        }
    }
    
    /**
     * Assign project to user
     * 
     * @param int $projectId
     * @param int $userId
     * @return array
     */
    public function assign($projectId, $userId) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Check if user has permission (admin or manager)
            $userRole = getCurrentUserRole();
            if (!in_array($userRole, ['admin', 'manager'])) {
                return ['success' => false, 'message' => 'Insufficient permissions to assign project'];
            }
            
            // Validate input
            if (empty($projectId) || empty($userId)) {
                return ['success' => false, 'message' => 'Project ID and User ID are required'];
            }
            
            // Get existing project
            $project = $this->projectModel->getById($projectId);
            
            if (!$project) {
                return ['success' => false, 'message' => 'Project not found'];
            }
            
            // Assign project
            $result = $this->projectModel->assign($projectId, $userId);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error assigning project: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update project status
     * 
     * @param int $projectId
     * @param string $status
     * @return array
     */
    public function updateStatus($projectId, $status) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate project ID
            if (empty($projectId)) {
                return ['success' => false, 'message' => 'Project ID is required'];
            }
            
            // Validate status
            $validStatuses = ['planning', 'active', 'on hold', 'completed', 'cancelled'];
            if (!in_array($status, $validStatuses)) {
                return ['success' => false, 'message' => 'Invalid project status'];
            }
            
            // Get existing project
            $project = $this->projectModel->getById($projectId);
            
            if (!$project) {
                return ['success' => false, 'message' => 'Project not found'];
            }
            
            // Check if user has permission to update this project
            $userRole = getCurrentUserRole();
            $userId = getCurrentUserId();
            
            if (!in_array($userRole, ['admin', 'manager']) && $project['owner_id'] != $userId) {
                return ['success' => false, 'message' => 'Insufficient permissions to update this project'];
            }
            
            // Update status
            $result = $this->projectModel->updateStatus($projectId, $status);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating project status: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get projects for a specific worker
     *
     * @param int $workerId
     * @return array
     */
    public function getProjectsForWorker($workerId) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            if (empty($workerId)) {
                return ['success' => false, 'message' => 'Worker ID is required'];
            }
            
            // Get tasks assigned to this worker
            require_once __DIR__ . '/TaskController.php';
            $taskController = new TaskController();
            $tasksResult = $taskController->getAssignedTo($workerId);
            
            if (!$tasksResult['success']) {
                return ['success' => false, 'message' => 'Failed to retrieve worker tasks'];
            }
            
            $tasks = $tasksResult['data'] ?? [];
            
            // Extract unique project IDs
            $projectIds = [];
            foreach ($tasks as $task) {
                if (isset($task['project_id']) && !in_array($task['project_id'], $projectIds)) {
                    $projectIds[] = $task['project_id'];
                }
            }
            
            // Get project details for these IDs
            $projects = [];
            foreach ($projectIds as $projectId) {
                $project = $this->projectModel->getById($projectId);
                if ($project) {
                    $projects[] = $project;
                }
            }
            
            return [
                'success' => true,
                'message' => 'Projects retrieved successfully',
                'data' => $projects
            ];
        } catch (Exception $e) {
            return [
                'success' => false, 
                'message' => 'Error retrieving projects: ' . $e->getMessage()
            ];
        }
    }
}