<?php
// User model for authentication system

require_once __DIR__ . '/../../config/database.php';

class User {
    private $pdo;
    
    public function __construct() {
        $database = new Database();
        $this->pdo = $database->connect();
    }
    
    /**
     * Register a new user
     * 
     * @param string $name
     * @param string $email
     * @param string $password
     * @param string $role
     * @param string $rank
     * @return array|bool
     */
    public function register($name, $email, $password, $role = 'worker', $rank = null) {
        try {
            // Check if email already exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            // Hash password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user with approved = 0 (pending approval)
            $stmt = $this->pdo->prepare("
                INSERT INTO users (name, email, password_hash, role, rank, approved, created_at) 
                VALUES (?, ?, ?, ?, ?, 0, NOW())
            ");
            
            $result = $stmt->execute([$name, $email, $passwordHash, $role, $rank]);
            
            if ($result) {
                $userId = $this->pdo->lastInsertId();
                return [
                    'success' => true,
                    'message' => 'User registered successfully. Awaiting approval.',
                    'user_id' => $userId,
                    'name' => $name,
                    'email' => $email,
                    'role' => $role
                ];
            }
            
            return ['success' => false, 'message' => 'Registration failed'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Registration error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Login user
     * 
     * @param string $email
     * @param string $password
     * @return array|bool
     */
    public function login($email, $password) {
        try {
            // Get user by email
            $stmt = $this->pdo->prepare("
                SELECT id, name, email, password_hash, role, approved 
                FROM users 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            $user = $stmt->fetch();
            
            // Check if user is approved
            if ($user['approved'] == 0) {
                return ['success' => false, 'message' => 'Account not approved yet'];
            }
            
            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                return [
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ]
                ];
            }
            
            return ['success' => false, 'message' => 'Invalid email or password'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Login error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Logout user
     * 
     * @return bool
     */
    public function logout() {
        // This is handled in the session management, but we can add any cleanup here if needed
        return true;
    }
    
    /**
     * Approve user
     * 
     * @param int $userId
     * @param int $approvingUserId
     * @return array|bool
     */
    public function approve($userId, $approvingUserId) {
        try {
            // Check if the approving user has permission (admin or manager)
            $stmt = $this->pdo->prepare("
                SELECT role FROM users WHERE id = ?
            ");
            $stmt->execute([$approvingUserId]);
            
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Approving user not found'];
            }
            
            $approvingUser = $stmt->fetch();
            
            // Only admin and manager roles can approve users
            if (!in_array($approvingUser['role'], ['admin', 'manager'])) {
                return ['success' => false, 'message' => 'Insufficient permissions to approve users'];
            }
            
            // Update user approved status
            $stmt = $this->pdo->prepare("
                UPDATE users SET approved = 1 WHERE id = ?
            ");
            $result = $stmt->execute([$userId]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'User approved successfully'];
            } elseif ($result) {
                return ['success' => false, 'message' => 'User not found or already approved'];
            }
            
            return ['success' => false, 'message' => 'Approval failed'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Approval error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get user by ID
     * 
     * @param int $userId
     * @return array|bool
     */
    public function getUserById($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, name, email, role, rank, approved, created_at 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            
            if ($stmt->rowCount() > 0) {
                return $stmt->fetch();
            }
            
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get all unapproved users
     * 
     * @return array|bool
     */
    public function getUnapprovedUsers() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, name, email, role, rank, created_at 
                FROM users 
                WHERE approved = 0
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }
}