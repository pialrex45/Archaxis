<?php
// User controller for managing user-related operations

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/helpers.php';

class UserController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    /**
     * Get user by ID
     * 
     * @param int $userId
     * @return array|bool
     */
    public function getUserById($userId) {
        if (empty($userId)) {
            return ['success' => false, 'message' => 'User ID is required'];
        }
        
        $user = $this->userModel->getUserById($userId);
        
        if ($user) {
            return [
                'success' => true,
                'user' => $user
            ];
        }
        
        return ['success' => false, 'message' => 'User not found'];
    }
    
    /**
     * Get all users
     * 
     * @return array
     */
    public function getAllUsers() {
        try {
            $database = new Database();
            $pdo = $database->connect();
            
            $stmt = $pdo->prepare("
                SELECT id, name, email, role, rank, approved, created_at 
                FROM users 
                ORDER BY name ASC
            ");
            $stmt->execute();
            
            $users = $stmt->fetchAll();
            
            return [
                'success' => true,
                'users' => $users
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error fetching users: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get users by role
     * 
     * @param string $role
     * @return array
     */
    public function getUsersByRole($role) {
        if (empty($role)) {
            return ['success' => false, 'message' => 'Role is required'];
        }
        
        try {
            $database = new Database();
            $pdo = $database->connect();
            
            $stmt = $pdo->prepare("
                SELECT id, name, email, role, rank, approved, created_at 
                FROM users 
                WHERE role = ? AND approved = 1
                ORDER BY name ASC
            ");
            $stmt->execute([$role]);
            
            $users = $stmt->fetchAll();
            
            return [
                'success' => true,
                'users' => $users
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error fetching users: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update user profile
     * 
     * @param int $userId
     * @param array $data
     * @return array
     */
    public function updateUser($userId, $data) {
        if (empty($userId)) {
            return ['success' => false, 'message' => 'User ID is required'];
        }
        
        $name = isset($data['name']) ? sanitize($data['name']) : null;
        $email = isset($data['email']) ? sanitize($data['email']) : null;
        $role = isset($data['role']) ? sanitize($data['role']) : null;
        $rank = isset($data['rank']) ? sanitize($data['rank']) : null;
        $password = isset($data['password']) ? $data['password'] : null;
        
        // At least one field should be updated
        if (empty($name) && empty($email) && empty($role) && empty($rank) && empty($password)) {
            return ['success' => false, 'message' => 'No data provided for update'];
        }
        
        try {
            $database = new Database();
            $pdo = $database->connect();
            
            // Start building the query
            $query = "UPDATE users SET ";
            $params = [];
            
            if ($name) {
                $query .= "name = ?, ";
                $params[] = $name;
            }
            
            if ($email) {
                // Check if email already exists for another user
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $userId]);
                
                if ($stmt->rowCount() > 0) {
                    return ['success' => false, 'message' => 'Email already in use by another account'];
                }
                
                $query .= "email = ?, ";
                $params[] = $email;
            }
            
            if ($role) {
                $query .= "role = ?, ";
                $params[] = $role;
            }
            
            if ($rank) {
                $query .= "rank = ?, ";
                $params[] = $rank;
            }
            
            if ($password) {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $query .= "password_hash = ?, ";
                $params[] = $passwordHash;
            }
            
            // Remove trailing comma and space
            $query = rtrim($query, ", ");
            
            // Add WHERE clause
            $query .= " WHERE id = ?";
            $params[] = $userId;
            
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute($params);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'User updated successfully'];
            } elseif ($result) {
                return ['success' => true, 'message' => 'No changes made to user'];
            }
            
            return ['success' => false, 'message' => 'Failed to update user'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error updating user: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete user
     * 
     * @param int $userId
     * @return array
     */
    public function deleteUser($userId) {
        if (empty($userId)) {
            return ['success' => false, 'message' => 'User ID is required'];
        }
        
        try {
            $database = new Database();
            $pdo = $database->connect();
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $result = $stmt->execute([$userId]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'User deleted successfully'];
            }
            
            return ['success' => false, 'message' => 'User not found or already deleted'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error deleting user: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all user contacts (for messaging)
     * 
     * @param int $userId
     * @return array
     */
    public function getUserContacts($userId) {
        if (empty($userId)) {
            return ['success' => false, 'message' => 'User ID is required'];
        }
        
        try {
            $database = new Database();
            $pdo = $database->connect();
            
            // Get user's role first
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            $user = $stmt->fetch();
            $userRole = $user['role'];
            
            // Different roles have different contacts
            // Workers can contact supervisors, site engineers, and site managers
            // Managers can contact all users
            // Customize this based on your specific requirements
            
            $query = "
                SELECT id, name, email, role, rank
                FROM users 
                WHERE approved = 1 AND id != ?
            ";
            
            // Filter contacts based on role if needed
            if ($userRole == 'worker') {
                $query .= " AND role IN ('supervisor', 'site_engineer', 'site_manager', 'project_manager')";
            } elseif ($userRole == 'supervisor') {
                $query .= " AND role IN ('worker', 'site_engineer', 'site_manager', 'project_manager')";
            }
            
            $query .= " ORDER BY name ASC";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$userId]);
            
            $contacts = $stmt->fetchAll();
            
            return [
                'success' => true,
                'contacts' => $contacts
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error fetching contacts: ' . $e->getMessage()];
        }
    }
}
