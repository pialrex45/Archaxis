<?php
// Authentication controller

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/helpers.php';

class AuthController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    /**
     * Handle user signup
     * 
     * @param array $data
     * @return array
     */
    public function signup($data) {
        // Accept multiple input shapes and normalize to expected fields
        $rawName = isset($data['name']) ? $data['name'] : (isset($data['username']) ? $data['username'] : '');
        $first = isset($data['first_name']) ? trim($data['first_name']) : '';
        $last  = isset($data['last_name']) ? trim($data['last_name']) : '';
        // If first/last provided, build a display name; else use rawName
        $name = sanitize(trim(($first || $last) ? trim($first . ' ' . $last) : (string)$rawName));
        $email = isset($data['email']) ? sanitize($data['email']) : '';
        $password = isset($data['password']) ? $data['password'] : '';
        $confirmPassword = isset($data['confirm_password']) ? $data['confirm_password'] : '';
        $role = isset($data['role']) ? sanitize($data['role']) : 'worker';
        $rank = isset($data['rank']) ? sanitize($data['rank']) : null;
        
        // Validation
        if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }
        
        if (!validateEmail($email)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters'];
        }
        
        if ($password !== $confirmPassword) {
            return ['success' => false, 'message' => 'Passwords do not match'];
        }
        
        // Register user
        $result = $this->userModel->register($name, $email, $password, $role, $rank);
        
        return $result;
    }
    
    /**
     * Handle user login
     * 
     * @param array $data
     * @return array
     */
    public function login($data) {
        // Validate input data
        $email = isset($data['email']) ? sanitize($data['email']) : '';
        $password = isset($data['password']) ? $data['password'] : '';
        
        // Validation
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email and password are required'];
        }
        
        if (!validateEmail($email)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        // Check rate limiting
        if ($this->isRateLimited($email)) {
            return ['success' => false, 'message' => 'Too many login attempts. Please try again later.'];
        }
        
        // Login user
        $result = $this->userModel->login($email, $password);
        
        // Update rate limiting on failed login
        if (!$result['success']) {
            $this->recordFailedLogin($email);
            return $result;
        }
        
        // Clear failed login attempts on successful login
        $this->clearFailedLoginAttempts($email);
        
        // Set session variables
        if ($result['success']) {
            loginUser($result['user']['id'], $result['user']['name'], $result['user']['role']);
        }
        
        return $result;
    }
    
    /**
     * Handle user logout
     * 
     * @return array
     */
    public function logout() {
        logoutUser();
        return ['success' => true, 'message' => 'Successfully logged out'];
    }
    
    /**
     * Approve a user
     * 
     * @param int $userId
     * @param int $approvingUserId
     * @return array
     */
    public function approveUser($userId, $approvingUserId) {
        // Validate input
        if (empty($userId) || empty($approvingUserId)) {
            return ['success' => false, 'message' => 'User ID and approving user ID are required'];
        }
        
        // Approve user
        $result = $this->userModel->approve($userId, $approvingUserId);
        
        return $result;
    }
    
    /**
     * Check if login attempts are rate limited
     * 
     * @param string $email
     * @return bool
     */
    private function isRateLimited($email) {
        $attempts = isset($_SESSION['login_attempts'][$email]) ? $_SESSION['login_attempts'][$email] : 0;
        $lastAttempt = isset($_SESSION['login_last_attempt'][$email]) ? $_SESSION['login_last_attempt'][$email] : 0;
        
        // Reset attempts after 15 minutes
        if (time() - $lastAttempt > 900) {
            $this->clearFailedLoginAttempts($email);
            return false;
        }
        
        // Limit to 5 attempts
        return $attempts >= 5;
    }
    
    /**
     * Record a failed login attempt
     * 
     * @param string $email
     * @return void
     */
    private function recordFailedLogin($email) {
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
            $_SESSION['login_last_attempt'] = [];
        }
        
        if (!isset($_SESSION['login_attempts'][$email])) {
            $_SESSION['login_attempts'][$email] = 0;
        }
        
        $_SESSION['login_attempts'][$email]++;
        $_SESSION['login_last_attempt'][$email] = time();
    }
    
    /**
     * Clear failed login attempts
     * 
     * @param string $email
     * @return void
     */
    private function clearFailedLoginAttempts($email) {
        if (isset($_SESSION['login_attempts'][$email])) {
            unset($_SESSION['login_attempts'][$email]);
            unset($_SESSION['login_last_attempt'][$email]);
        }
    }
    
    /**
     * Get all unapproved users
     * 
     * @return array
     */
    public function getUnapprovedUsers() {
        return $this->userModel->getUnapprovedUsers();
    }
}