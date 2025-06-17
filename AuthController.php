<?php
/**
 * Authentication Controller
 * AI-Powered Fake News Detection System
 */

require_once 'app-config.php';
require_once 'User.php';

class AuthController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    /**
     * Handle user login
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $email = sanitizeInput($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $csrfToken = $_POST['csrf_token'] ?? '';
                
                // Validate CSRF token
                if (!validateCSRFToken($csrfToken)) {
                    throw new Exception('Invalid security token');
                }
                
                // Validate input
                if (empty($email) || empty($password)) {
                    throw new Exception('Email and password are required');
                }
                
                // Check rate limiting
                $this->checkLoginRateLimit($email);
                
                // Authenticate user
                $user = $this->userModel->authenticate($email, $password);
                
                if (!$user) {
                    $this->recordFailedLogin($email);
                    throw new Exception('Invalid email or password');
                }
                
                // Set session data
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['login_time'] = time();
                
                // Clear failed login attempts
                $this->clearFailedLogins($email);
                
                // Log successful login
                logMessage('INFO', 'User logged in successfully', ['user_id' => $user['id'], 'email' => $email]);
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    sendJsonResponse(['success' => true, 'redirect' => APP_URL . '/admin.php']);
                } else {
                    sendJsonResponse(['success' => true, 'redirect' => APP_URL . '/dashboard.php']);
                }
                
            } catch (Exception $e) {
                logMessage('WARNING', 'Login failed: ' . $e->getMessage(), ['email' => $email ?? 'unknown']);
                sendJsonResponse(['error' => $e->getMessage()], HTTP_BAD_REQUEST);
            }
        }
        
        // Display login form
        $csrfToken = generateCSRFToken();
        include 'login.php';
    }
    
    /**
     * Handle user registration
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $name = sanitizeInput($_POST['name'] ?? '');
                $email = sanitizeInput($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                $csrfToken = $_POST['csrf_token'] ?? '';
                
                // Validate CSRF token
                if (!validateCSRFToken($csrfToken)) {
                    throw new Exception('Invalid security token');
                }
                
                // Validate input
                $this->validateRegistrationInput($name, $email, $password, $confirmPassword);
                
                // Create user
                $userId = $this->userModel->createUser($name, $email, $password);
                
                // Auto-login after registration
                $user = $this->userModel->find($userId);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['login_time'] = time();
                
                logMessage('INFO', 'New user registered', ['user_id' => $userId, 'email' => $email]);
                
                sendJsonResponse([
                    'success' => true,
                    'message' => 'Account created successfully',
                    'redirect' => APP_URL . '/dashboard.php'
                ]);
                
            } catch (Exception $e) {
                logMessage('WARNING', 'Registration failed: ' . $e->getMessage(), ['email' => $email ?? 'unknown']);
                sendJsonResponse(['error' => $e->getMessage()], HTTP_BAD_REQUEST);
            }
        }
        
        // Display registration form
        $csrfToken = generateCSRFToken();
        include 'register.php';
    }
    
    /**
     * Handle user logout
     */
    public function logout() {
        $userId = $_SESSION['user_id'] ?? null;
        
        // Clear session
        session_unset();
        session_destroy();
        
        if ($userId) {
            logMessage('INFO', 'User logged out', ['user_id' => $userId]);
        }
        
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }
    
    /**
     * Validate registration input
     */
    private function validateRegistrationInput($name, $email, $password, $confirmPassword) {
        if (empty($name) || strlen($name) < 2) {
            throw new Exception('Name must be at least 2 characters long');
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address');
        }
        
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            throw new Exception('Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long');
        }
        
        if ($password !== $confirmPassword) {
            throw new Exception('Passwords do not match');
        }
        
        // Check password strength
        if (!$this->isStrongPassword($password)) {
            throw new Exception('Password must contain at least one uppercase letter, one lowercase letter, and one number');
        }
    }
    
    /**
     * Check password strength
     */
    private function isStrongPassword($password) {
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $password);
    }
    
    /**
     * Check login rate limiting
     */
    private function checkLoginRateLimit($email) {
        $key = 'login_attempts_' . md5($email);
        $attempts = $_SESSION[$key] ?? 0;
        $lockoutKey = 'login_lockout_' . md5($email);
        $lockoutTime = $_SESSION[$lockoutKey] ?? 0;
        
        // Check if account is locked
        if ($lockoutTime > time()) {
            $remainingTime = $lockoutTime - time();
            throw new Exception("Account locked. Try again in " . ceil($remainingTime / 60) . " minutes");
        }
        
        // Check if too many attempts
        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            $_SESSION[$lockoutKey] = time() + LOCKOUT_DURATION;
            throw new Exception("Too many failed attempts. Account locked for " . (LOCKOUT_DURATION / 60) . " minutes");
        }
    }
    
    /**
     * Record failed login attempt
     */
    private function recordFailedLogin($email) {
        $key = 'login_attempts_' . md5($email);
        $_SESSION[$key] = ($_SESSION[$key] ?? 0) + 1;
    }
    
    /**
     * Clear failed login attempts
     */
    private function clearFailedLogins($email) {
        $key = 'login_attempts_' . md5($email);
        $lockoutKey = 'login_lockout_' . md5($email);
        unset($_SESSION[$key], $_SESSION[$lockoutKey]);
    }
    
    /**
     * Check if user session is valid
     */
    public function checkSession() {
        if (!isLoggedIn()) {
            return false;
        }
        
        // Check session timeout
        $loginTime = $_SESSION['login_time'] ?? 0;
        if (time() - $loginTime > SESSION_TIMEOUT) {
            $this->logout();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Get current user data
     */
    public function getCurrentUser() {
        if (!isLoggedIn()) {
            return null;
        }
        
        return $this->userModel->find($_SESSION['user_id']);
    }
    
    /**
     * Update user profile
     */
    public function updateProfile() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $userId = $_SESSION['user_id'];
                $name = sanitizeInput($_POST['name'] ?? '');
                $email = sanitizeInput($_POST['email'] ?? '');
                $csrfToken = $_POST['csrf_token'] ?? '';
                
                if (!validateCSRFToken($csrfToken)) {
                    throw new Exception('Invalid security token');
                }
                
                $updateData = [];
                
                if (!empty($name) && strlen($name) >= 2) {
                    $updateData['name'] = $name;
                }
                
                if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    // Check if email is already taken by another user
                    $existingUser = $this->userModel->findByEmail($email);
                    if ($existingUser && $existingUser['id'] != $userId) {
                        throw new Exception('Email address is already taken');
                    }
                    $updateData['email'] = $email;
                }
                
                if (!empty($updateData)) {
                    $this->userModel->updateProfile($userId, $updateData);
                    
                    // Update session data
                    if (isset($updateData['name'])) {
                        $_SESSION['user_name'] = $updateData['name'];
                    }
                    if (isset($updateData['email'])) {
                        $_SESSION['user_email'] = $updateData['email'];
                    }
                    
                    logMessage('INFO', 'User profile updated', ['user_id' => $userId]);
                    sendJsonResponse(['success' => true, 'message' => 'Profile updated successfully']);
                } else {
                    throw new Exception('No valid data to update');
                }
                
            } catch (Exception $e) {
                sendJsonResponse(['error' => $e->getMessage()], HTTP_BAD_REQUEST);
            }
        }
    }
    
    /**
     * Change user password
     */
    public function changePassword() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $userId = $_SESSION['user_id'];
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                $csrfToken = $_POST['csrf_token'] ?? '';
                
                if (!validateCSRFToken($csrfToken)) {
                    throw new Exception('Invalid security token');
                }
                
                if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                    throw new Exception('All password fields are required');
                }
                
                if ($newPassword !== $confirmPassword) {
                    throw new Exception('New passwords do not match');
                }
                
                if (!$this->isStrongPassword($newPassword)) {
                    throw new Exception('New password must contain at least one uppercase letter, one lowercase letter, and one number');
                }
                
                $this->userModel->changePassword($userId, $currentPassword, $newPassword);
                
                logMessage('INFO', 'User password changed', ['user_id' => $userId]);
                sendJsonResponse(['success' => true, 'message' => 'Password changed successfully']);
                
            } catch (Exception $e) {
                sendJsonResponse(['error' => $e->getMessage()], HTTP_BAD_REQUEST);
            }
        }
    }
}
?>