<?php
// Finance controller for handling finance operations

require_once __DIR__ . '/../models/Finance.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/helpers.php';

class FinanceController {
    private $financeModel;
    private $projectModel;
    
    public function __construct() {
        $this->financeModel = new Finance();
        $this->projectModel = new Project();
    }
    
    /**
     * Log income or expense
     * 
     * @param array $data
     * @return array
     */
    public function logTransaction($data) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate input data
            $projectId = isset($data['project_id']) ? (int)$data['project_id'] : 0;
            $type = isset($data['type']) ? sanitize($data['type']) : '';
            $amount = isset($data['amount']) ? (float)$data['amount'] : 0;
            $description = isset($data['description']) ? sanitize($data['description']) : '';
            
            // Validation
            if (empty($projectId)) {
                return ['success' => false, 'message' => 'Project ID is required'];
            }
            
            if (empty($type)) {
                return ['success' => false, 'message' => 'Transaction type is required'];
            }
            
            if (!in_array($type, ['income', 'expense'])) {
                return ['success' => false, 'message' => 'Invalid transaction type. Must be "income" or "expense".'];
            }
            
            if ($amount <= 0) {
                return ['success' => false, 'message' => 'Amount must be greater than zero'];
            }
            
            // Check if project exists
            $project = $this->projectModel->getById($projectId);
            if (!$project) {
                return ['success' => false, 'message' => 'Project not found'];
            }
            
            // Check if user has permission to log transactions for this project
            $userRole = getCurrentUserRole();
            $userId = getCurrentUserId();
            
            if (!in_array($userRole, ['admin', 'manager']) && $project['owner_id'] != $userId) {
                return ['success' => false, 'message' => 'Insufficient permissions to log transactions for this project'];
            }
            
            // Log transaction
            $result = $this->financeModel->logTransaction($projectId, $type, $amount, $description);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error logging transaction: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get transaction by ID
     * 
     * @param int $financeId
     * @return array
     */
    public function get($financeId) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate finance ID
            if (empty($financeId)) {
                return ['success' => false, 'message' => 'Transaction ID is required'];
            }
            
            // Get transaction
            $transaction = $this->financeModel->getById($financeId);
            
            if (!$transaction) {
                return ['success' => false, 'message' => 'Transaction not found'];
            }
            
            // Check if user has permission to view this transaction
            $userRole = getCurrentUserRole();
            $userId = getCurrentUserId();
            
            // Admins and managers can view all transactions
            // Others can only view transactions for projects they own
            if (!in_array($userRole, ['admin', 'manager'])) {
                $project = $this->projectModel->getById($transaction['project_id']);
                if (!$project || $project['owner_id'] != $userId) {
                    return ['success' => false, 'message' => 'Insufficient permissions to view this transaction'];
                }
            }
            
            return [
                'success' => true,
                'data' => $transaction
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching transaction: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all transactions
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
            
            // Get transactions based on user role
            if (in_array($userRole, ['admin', 'manager'])) {
                // Admins and managers can see all transactions
                $transactions = $this->financeModel->getAll();
            } else {
                // Others can only see transactions for projects they own
                $projects = $this->projectModel->getByOwner($userId) ?: [];
                $transactions = [];
                
                foreach ($projects as $project) {
                    $projectTransactions = $this->financeModel->getByProject($project['id']) ?: [];
                    $transactions = array_merge($transactions, $projectTransactions);
                }
            }
            
            return [
                'success' => true,
                'data' => $transactions ?: []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching transactions: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get transactions by project
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
            
            // Check if user has permission to view transactions for this project
            $userRole = getCurrentUserRole();
            $userId = getCurrentUserId();
            
            if (!in_array($userRole, ['admin', 'manager']) && $project['owner_id'] != $userId) {
                return ['success' => false, 'message' => 'Insufficient permissions to view transactions for this project'];
            }
            
            // Get transactions
            $transactions = $this->financeModel->getByProject($projectId);
            
            return [
                'success' => true,
                'data' => $transactions ?: []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching transactions: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get transactions by type
     * 
     * @param string $type
     * @return array
     */
    public function getByType($type) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate type
            if (!in_array($type, ['income', 'expense'])) {
                return ['success' => false, 'message' => 'Invalid transaction type. Must be "income" or "expense".'];
            }
            
            // Check if user has permission to view transactions by type
            $userRole = getCurrentUserRole();
            
            if (!in_array($userRole, ['admin', 'manager'])) {
                return ['success' => false, 'message' => 'Insufficient permissions to view transactions by type'];
            }
            
            // Get transactions
            $transactions = $this->financeModel->getByType($type);
            
            return [
                'success' => true,
                'data' => $transactions ?: []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching transactions: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get transactions within date range
     * 
     * @param string $startDate
     * @param string $endDate
     * @param int $projectId
     * @return array
     */
    public function getByDateRange($startDate, $endDate, $projectId = null) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate dates
            if (empty($startDate) || empty($endDate)) {
                return ['success' => false, 'message' => 'Start date and end date are required'];
            }
            
            if (!strtotime($startDate) || !strtotime($endDate)) {
                return ['success' => false, 'message' => 'Invalid date format'];
            }
            
            if (strtotime($endDate) < strtotime($startDate)) {
                return ['success' => false, 'message' => 'End date must be after start date'];
            }
            
            // Check if user has permission to view transactions
            $userRole = getCurrentUserRole();
            
            if (!in_array($userRole, ['admin', 'manager']) && !empty($projectId)) {
                // If project ID is specified, check if user owns that project
                $project = $this->projectModel->getById($projectId);
                if (!$project || $project['owner_id'] != getCurrentUserId()) {
                    return ['success' => false, 'message' => 'Insufficient permissions to view transactions for this project'];
                }
            }
            
            // Get transactions
            $transactions = $this->financeModel->getByDateRange($startDate, $endDate, $projectId);
            
            return [
                'success' => true,
                'data' => $transactions ?: []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching transactions: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update transaction
     * 
     * @param int $financeId
     * @param array $data
     * @return array
     */
    public function update($financeId, $data) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate finance ID
            if (empty($financeId)) {
                return ['success' => false, 'message' => 'Transaction ID is required'];
            }
            
            // Get existing transaction
            $transaction = $this->financeModel->getById($financeId);
            
            if (!$transaction) {
                return ['success' => false, 'message' => 'Transaction not found'];
            }
            
            // Check if user has permission to update this transaction
            $userRole = getCurrentUserRole();
            $userId = getCurrentUserId();
            
            if (!in_array($userRole, ['admin', 'manager'])) {
                $project = $this->projectModel->getById($transaction['project_id']);
                if (!$project || $project['owner_id'] != $userId) {
                    return ['success' => false, 'message' => 'Insufficient permissions to update this transaction'];
                }
            }
            
            // Validate and sanitize input data
            $updateData = [];
            
            if (isset($data['type'])) {
                $type = sanitize($data['type']);
                if (in_array($type, ['income', 'expense'])) {
                    $updateData['type'] = $type;
                }
            }
            
            if (isset($data['amount'])) {
                $amount = (float)$data['amount'];
                if ($amount > 0) {
                    $updateData['amount'] = $amount;
                }
            }
            
            if (isset($data['description'])) {
                $updateData['description'] = sanitize($data['description']);
            }
            
            // Update transaction
            if (!empty($updateData)) {
                $result = $this->financeModel->update($financeId, $updateData);
                return $result;
            } else {
                return ['success' => false, 'message' => 'No valid data to update'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating transaction: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete transaction
     * 
     * @param int $financeId
     * @return array
     */
    public function delete($financeId) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate finance ID
            if (empty($financeId)) {
                return ['success' => false, 'message' => 'Transaction ID is required'];
            }
            
            // Get existing transaction
            $transaction = $this->financeModel->getById($financeId);
            
            if (!$transaction) {
                return ['success' => false, 'message' => 'Transaction not found'];
            }
            
            // Check if user has permission to delete this transaction
            $userRole = getCurrentUserRole();
            $userId = getCurrentUserId();
            
            if (!in_array($userRole, ['admin', 'manager'])) {
                $project = $this->projectModel->getById($transaction['project_id']);
                if (!$project || $project['owner_id'] != $userId) {
                    return ['success' => false, 'message' => 'Insufficient permissions to delete this transaction'];
                }
            }
            
            // Delete transaction
            $result = $this->financeModel->delete($financeId);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error deleting transaction: ' . $e->getMessage()];
        }
    }
    
    /**
     * Generate financial summary for a project
     * 
     * @param int $projectId
     * @return array
     */
    public function getSummary($projectId = null) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Check if user has permission to view financial summary
            $userRole = getCurrentUserRole();
            $userId = getCurrentUserId();
            
            if (!empty($projectId)) {
                // If project ID is specified, check if user owns that project
                $project = $this->projectModel->getById($projectId);
                if (!$project || (!in_array($userRole, ['admin', 'manager']) && $project['owner_id'] != $userId)) {
                    return ['success' => false, 'message' => 'Insufficient permissions to view financial summary for this project'];
                }
            } else {
                // If no project ID, user must be admin or manager
                if (!in_array($userRole, ['admin', 'manager'])) {
                    return ['success' => false, 'message' => 'Insufficient permissions to view overall financial summary'];
                }
            }
            
            // Get summary
            $summary = $this->financeModel->getSummary($projectId);
            
            return [
                'success' => true,
                'data' => $summary ?: []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching financial summary: ' . $e->getMessage()];
        }
    }
}