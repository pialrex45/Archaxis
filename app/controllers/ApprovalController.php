<?php
// Approval controller for handling approval operations

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Material.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/helpers.php';

class ApprovalController {
    private $userModel;
    private $materialModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->materialModel = new Material();
    }
    
    /**
     * Approve a user registration
     * 
     * @param int $userId
     * @return array
     */
    public function approveUser($userId) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Check if user has permission (admin or manager)
            $approvingUserId = getCurrentUserId();
            $approvingUserRole = getCurrentUserRole();
            
            if (!in_array($approvingUserRole, ['admin', 'manager'])) {
                return ['success' => false, 'message' => 'Insufficient permissions to approve users'];
            }
            
            // Validate user ID
            if (empty($userId)) {
                return ['success' => false, 'message' => 'User ID is required'];
            }
            
            // Approve user
            $result = $this->userModel->approve($userId, $approvingUserId);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error approving user: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all unapproved users
     * 
     * @return array
     */
    public function getUnapprovedUsers() {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Check if user has permission (admin or manager)
            $userRole = getCurrentUserRole();
            
            if (!in_array($userRole, ['admin', 'manager'])) {
                return ['success' => false, 'message' => 'Insufficient permissions to view unapproved users'];
            }
            
            // Get unapproved users
            $users = $this->userModel->getUnapprovedUsers();
            
            return [
                'success' => true,
                'data' => $users ?: []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching unapproved users: ' . $e->getMessage()];
        }
    }
    
    /**
     * Approve a material request
     * 
     * @param int $materialId
     * @return array
     */
    public function approveMaterial($materialId) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Check if user has permission (admin or manager)
            $userRole = getCurrentUserRole();
            
            if (!in_array($userRole, ['admin', 'manager'])) {
                return ['success' => false, 'message' => 'Insufficient permissions to approve materials'];
            }
            
            // Validate material ID
            if (empty($materialId)) {
                return ['success' => false, 'message' => 'Material ID is required'];
            }
            
            // Approve material
            $result = $this->materialModel->approve($materialId);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error approving material: ' . $e->getMessage()];
        }
    }
    
    /**
     * Reject a material request
     * 
     * @param int $materialId
     * @return array
     */
    public function rejectMaterial($materialId) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Check if user has permission (admin or manager)
            $userRole = getCurrentUserRole();
            
            if (!in_array($userRole, ['admin', 'manager'])) {
                return ['success' => false, 'message' => 'Insufficient permissions to reject materials'];
            }
            
            // Validate material ID
            if (empty($materialId)) {
                return ['success' => false, 'message' => 'Material ID is required'];
            }
            
            // Reject material
            $result = $this->materialModel->reject($materialId);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error rejecting material: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all pending material requests
     * 
     * @return array
     */
    public function getPendingMaterials() {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Check if user has permission (admin or manager)
            $userRole = getCurrentUserRole();
            
            if (!in_array($userRole, ['admin', 'manager'])) {
                return ['success' => false, 'message' => 'Insufficient permissions to view pending materials'];
            }
            
            // Get pending materials
            $materials = $this->materialModel->getByStatus('requested');
            
            return [
                'success' => true,
                'data' => $materials ?: []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching pending materials: ' . $e->getMessage()];
        }
    }
    
    /**
     * Mark material as ordered
     * 
     * @param int $materialId
     * @return array
     */
    public function markMaterialOrdered($materialId) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Check if user has permission (admin or manager)
            $userRole = getCurrentUserRole();
            
            if (!in_array($userRole, ['admin', 'manager'])) {
                return ['success' => false, 'message' => 'Insufficient permissions to update material status'];
            }
            
            // Validate material ID
            if (empty($materialId)) {
                return ['success' => false, 'message' => 'Material ID is required'];
            }
            
            // Mark material as ordered
            $result = $this->materialModel->markOrdered($materialId);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating material status: ' . $e->getMessage()];
        }
    }
    
    /**
     * Mark material as delivered
     * 
     * @param int $materialId
     * @return array
     */
    public function markMaterialDelivered($materialId) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Check if user has permission (admin or manager)
            $userRole = getCurrentUserRole();
            
            if (!in_array($userRole, ['admin', 'manager'])) {
                return ['success' => false, 'message' => 'Insufficient permissions to update material status'];
            }
            
            // Validate material ID
            if (empty($materialId)) {
                return ['success' => false, 'message' => 'Material ID is required'];
            }
            
            // Mark material as delivered
            $result = $this->materialModel->markDelivered($materialId);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating material status: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all materials by status
     * 
     * @param string $status
     * @return array
     */
    public function getMaterialsByStatus($status) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Check if user has permission (admin or manager)
            $userRole = getCurrentUserRole();
            
            if (!in_array($userRole, ['admin', 'manager'])) {
                return ['success' => false, 'message' => 'Insufficient permissions to view materials'];
            }
            
            // Validate status
            $validStatuses = ['requested', 'approved', 'rejected', 'ordered', 'delivered'];
            if (!in_array($status, $validStatuses)) {
                return ['success' => false, 'message' => 'Invalid material status'];
            }
            
            // Get materials by status
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
     * Get approval statistics
     * 
     * @return array
     */
    public function getApprovalStats() {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Check if user has permission (admin or manager)
            $userRole = getCurrentUserRole();
            
            if (!in_array($userRole, ['admin', 'manager'])) {
                return ['success' => false, 'message' => 'Insufficient permissions to view approval statistics'];
            }
            
            // Get statistics
            $unapprovedUsers = count($this->userModel->getUnapprovedUsers() ?: []);
            $pendingMaterials = count($this->materialModel->getByStatus('requested') ?: []);
            $approvedMaterials = count($this->materialModel->getByStatus('approved') ?: []);
            $rejectedMaterials = count($this->materialModel->getByStatus('rejected') ?: []);
            
            return [
                'success' => true,
                'data' => [
                    'unapproved_users' => $unapprovedUsers,
                    'pending_materials' => $pendingMaterials,
                    'approved_materials' => $approvedMaterials,
                    'rejected_materials' => $rejectedMaterials
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching approval statistics: ' . $e->getMessage()];
        }
    }
}