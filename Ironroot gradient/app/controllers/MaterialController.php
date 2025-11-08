<?php
// Material controller for handling material operations

require_once __DIR__ . '/../models/Material.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/helpers.php';

class MaterialController {
    private $materialModel;
    private $projectModel;
    
    public function __construct() {
        $this->materialModel = new Material();
        $this->projectModel = new Project();
    }
    
    /**
     * Request a material
     * 
     * @param array $data
     * @return array
     */
    public function request($data) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate input data
            $projectId = isset($data['project_id']) ? (int)$data['project_id'] : 0;
            $materialName = isset($data['material_name']) ? sanitize($data['material_name']) : '';
            $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;
            
            // Validation
            if (empty($projectId)) {
                return ['success' => false, 'message' => 'Project ID is required'];
            }
            
            if (empty($materialName)) {
                return ['success' => false, 'message' => 'Material name is required'];
            }
            
            if ($quantity < 1) {
                return ['success' => false, 'message' => 'Quantity must be at least 1'];
            }
            
            // Check if project exists
            $project = $this->projectModel->getById($projectId);
            if (!$project) {
                return ['success' => false, 'message' => 'Project not found'];
            }
            
            // Check if user has permission to request materials for this project
            $userRole = getCurrentUserRole();
            $userId = getCurrentUserId();
            
            if (!in_array($userRole, ['admin', 'manager', 'supervisor']) && $project['owner_id'] != $userId) {
                return ['success' => false, 'message' => 'Insufficient permissions to request materials for this project'];
            }
            
            // Request material
            $result = $this->materialModel->request($projectId, $userId, $materialName, $quantity);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error requesting material: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get material by ID
     * 
     * @param int $materialId
     * @return array
     */
    public function get($materialId) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate material ID
            if (empty($materialId)) {
                return ['success' => false, 'message' => 'Material ID is required'];
            }
            
            // Get material
            $material = $this->materialModel->getById($materialId);
            
            if (!$material) {
                return ['success' => false, 'message' => 'Material not found'];
            }
            
            // Check if user has permission to view this material
            $userRole = getCurrentUserRole();
            $userId = getCurrentUserId();
            
            // Admins and managers can view all materials
            // Supervisors and workers can only view materials they requested or materials for projects they own
            if (!in_array($userRole, ['admin', 'manager'])) {
                $project = $this->projectModel->getById($material['project_id']);
                if (!$project || ($project['owner_id'] != $userId && $material['requested_by'] != $userId)) {
                    return ['success' => false, 'message' => 'Insufficient permissions to view this material'];
                }
            }
            
            return [
                'success' => true,
                'data' => $material
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching material: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all materials
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
            
            // Get materials based on user role
            if (in_array($userRole, ['admin', 'manager'])) {
                // Admins and managers can see all materials
                $materials = $this->materialModel->getAll();
            } else {
                // Supervisors and workers can only see materials they requested or materials for projects they own
                $materials = array_merge(
                    $this->materialModel->getRequestedBy($userId) ?: [],
                    $this->getMaterialsForOwnedProjects($userId) ?: []
                );
                
                // Remove duplicates
                $materialIds = [];
                $uniqueMaterials = [];
                foreach ($materials as $material) {
                    if (!in_array($material['id'], $materialIds)) {
                        $materialIds[] = $material['id'];
                        $uniqueMaterials[] = $material;
                    }
                }
                $materials = $uniqueMaterials;
            }
            
            return [
                'success' => true,
                'data' => $materials ?: []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching materials: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get materials by project
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
            
            // Check if user has permission to view materials for this project
            $userRole = getCurrentUserRole();
            $userId = getCurrentUserId();
            
            if (!in_array($userRole, ['admin', 'manager']) && $project['owner_id'] != $userId) {
                return ['success' => false, 'message' => 'Insufficient permissions to view materials for this project'];
            }
            
            // Get materials
            $materials = $this->materialModel->getByProject($projectId);
            
            return [
                'success' => true,
                'data' => $materials ?: []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching materials: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get materials requested by user
     * 
     * @param int $userId
     * @return array
     */
    public function getRequestedBy($userId = null) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // If no user ID provided, use current user
            if (empty($userId)) {
                $userId = getCurrentUserId();
            }
            
            // Check if user has permission to view these materials
            $userRole = getCurrentUserRole();
            $currentUserId = getCurrentUserId();
            
            if (!in_array($userRole, ['admin', 'manager']) && $userId != $currentUserId) {
                return ['success' => false, 'message' => 'Insufficient permissions to view materials requested by this user'];
            }
            
            // Get materials
            $materials = $this->materialModel->getRequestedBy($userId);
            
            return [
                'success' => true,
                'data' => $materials ?: []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching materials: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get materials by status
     * 
     * @param string $status
     * @return array
     */
    public function getByStatus($status) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate status
            $validStatuses = ['requested', 'approved', 'rejected', 'ordered', 'delivered'];
            if (!in_array($status, $validStatuses)) {
                return ['success' => false, 'message' => 'Invalid material status'];
            }
            
            // Check if user has permission to view materials by status
            $userRole = getCurrentUserRole();
            
            if (!in_array($userRole, ['admin', 'manager'])) {
                return ['success' => false, 'message' => 'Insufficient permissions to view materials by status'];
            }
            
            // Get materials
            $materials = $this->materialModel->getByStatus($status);
            
            return [
                'success' => true,
                'data' => $materials ?: []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching materials: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update material request
     * 
     * @param int $materialId
     * @param array $data
     * @return array
     */
    public function update($materialId, $data) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate material ID
            if (empty($materialId)) {
                return ['success' => false, 'message' => 'Material ID is required'];
            }
            
            // Get existing material
            $material = $this->materialModel->getById($materialId);
            
            if (!$material) {
                return ['success' => false, 'message' => 'Material not found'];
            }
            
            // Check if user has permission to update this material
            $userRole = getCurrentUserRole();
            $userId = getCurrentUserId();
            
            if (!in_array($userRole, ['admin', 'manager']) && $material['requested_by'] != $userId) {
                return ['success' => false, 'message' => 'Insufficient permissions to update this material'];
            }
            
            // Validate and sanitize input data
            $updateData = [];
            
            if (isset($data['material_name'])) {
                $materialName = sanitize($data['material_name']);
                if (!empty($materialName)) {
                    $updateData['material_name'] = $materialName;
                }
            }
            
            if (isset($data['quantity'])) {
                $quantity = (int)$data['quantity'];
                if ($quantity > 0) {
                    $updateData['quantity'] = $quantity;
                }
            }
            
            if (isset($data['status'])) {
                $status = sanitize($data['status']);
                $validStatuses = ['requested', 'approved', 'rejected', 'ordered', 'delivered'];
                if (in_array($status, $validStatuses)) {
                    // Only admins and managers can change status
                    if (in_array($userRole, ['admin', 'manager'])) {
                        $updateData['status'] = $status;
                    }
                }
            }
            
            // Update material
            if (!empty($updateData)) {
                $result = $this->materialModel->update($materialId, $updateData);
                return $result;
            } else {
                return ['success' => false, 'message' => 'No valid data to update'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating material: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete material request
     * 
     * @param int $materialId
     * @return array
     */
    public function delete($materialId) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate material ID
            if (empty($materialId)) {
                return ['success' => false, 'message' => 'Material ID is required'];
            }
            
            // Get existing material
            $material = $this->materialModel->getById($materialId);
            
            if (!$material) {
                return ['success' => false, 'message' => 'Material not found'];
            }
            
            // Check if user has permission to delete this material
            $userRole = getCurrentUserRole();
            $userId = getCurrentUserId();
            
            if (!in_array($userRole, ['admin', 'manager']) && $material['requested_by'] != $userId) {
                return ['success' => false, 'message' => 'Insufficient permissions to delete this material'];
            }
            
            // Delete material
            $result = $this->materialModel->delete($materialId);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error deleting material: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get materials for projects owned by user
     * 
     * @param int $userId
     * @return array
     */
    private function getMaterialsForOwnedProjects($userId) {
        try {
            $projects = $this->projectModel->getByOwner($userId) ?: [];
            $allMaterials = [];
            
            foreach ($projects as $project) {
                $materials = $this->materialModel->getByProject($project['id']) ?: [];
                $allMaterials = array_merge($allMaterials, $materials);
            }
            
            return $allMaterials;
        } catch (Exception $e) {
            return [];
        }
    }
}