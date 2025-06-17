<?php
/**
 * User Model
 * AI-Powered Fake News Detection System
 */

require_once 'Database.php';

class User extends Database {
    protected $table = 'users';
    
    /**
     * Create a new user
     */
    public function createUser($name, $email, $password, $role = 'user') {
        // Check if user already exists
        if ($this->findByEmail($email)) {
            throw new Exception('User with this email already exists');
        }
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $userData = [
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'role' => $role,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->create($userData);
    }
    
    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $query = "SELECT * FROM {$this->table} WHERE email = ?";
        $stmt = $this->execute($query, [$email]);
        return $stmt->fetch();
    }
    
    /**
     * Authenticate user
     */
    public function authenticate($email, $password) {
        $user = $this->findByEmail($email);
        
        if (!$user) {
            return false;
        }
        
        if ($user['status'] !== 'active') {
            throw new Exception('Account is suspended or banned');
        }
        
        if (!password_verify($password, $user['password'])) {
            return false;
        }
        
        // Update last login
        $this->update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);
        
        return $user;
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        $allowedFields = ['name', 'email', 'profile_image'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            return $this->update($userId, $updateData);
        }
        
        return false;
    }
    
    /**
     * Change user password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        $user = $this->find($userId);
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            throw new Exception('Current password is incorrect');
        }
        
        if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            throw new Exception('Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long');
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->update($userId, ['password' => $hashedPassword, 'updated_at' => date('Y-m-d H:i:s')]);
    }
    
    /**
     * Get user statistics
     */
    public function getUserStats($userId) {
        $query = "
            SELECT 
                u.id,
                u.name,
                u.email,
                u.created_at,
                COUNT(s.id) as total_submissions,
                SUM(CASE WHEN s.prediction = 'FAKE' THEN 1 ELSE 0 END) as fake_submissions,
                SUM(CASE WHEN s.prediction = 'REAL' THEN 1 ELSE 0 END) as real_submissions,
                AVG(s.confidence) as avg_confidence,
                MAX(s.submitted_at) as last_submission
            FROM users u
            LEFT JOIN submissions s ON u.id = s.user_id
            WHERE u.id = ?
            GROUP BY u.id, u.name, u.email, u.created_at
        ";
        
        $stmt = $this->execute($query, [$userId]);
        return $stmt->fetch();
    }
    
    /**
     * Get all users with pagination
     */
    public function getAllUsers($page = 1, $perPage = ITEMS_PER_PAGE) {
        return $this->paginate($page, $perPage, [], 'created_at', 'DESC');
    }
    
    /**
     * Search users
     */
    public function searchUsers($searchTerm, $limit = 20) {
        return $this->search(['name', 'email'], $searchTerm, $limit);
    }
    
    /**
     * Ban/suspend user
     */
    public function changeUserStatus($userId, $status) {
        $allowedStatuses = ['active', 'suspended', 'banned'];
        
        if (!in_array($status, $allowedStatuses)) {
            throw new Exception('Invalid status');
        }
        
        return $this->update($userId, ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
    }
    
    /**
     * Verify user email
     */
    public function verifyEmail($userId) {
        return $this->update($userId, ['email_verified' => true, 'updated_at' => date('Y-m-d H:i:s')]);
    }
    
    /**
     * Get user activity (recent submissions)
     */
    public function getUserActivity($userId, $limit = 10) {
        $query = "
            SELECT 
                s.*,
                'submission' as activity_type
            FROM submissions s
            WHERE s.user_id = ?
            ORDER BY s.submitted_at DESC
            LIMIT ?
        ";
        
        $stmt = $this->execute($query, [$userId, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get dashboard statistics for admin
     */
    public function getDashboardStats() {
        $stats = [];
        
        // Total users
        $stats['total_users'] = $this->count();
        
        // Users by status
        $query = "SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status";
        $stmt = $this->execute($query);
        $statusCounts = $stmt->fetchAll();
        
        foreach ($statusCounts as $row) {
            $stats['users_by_status'][$row['status']] = (int)$row['count'];
        }
        
        // New users this month
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $stmt = $this->execute($query);
        $result = $stmt->fetch();
        $stats['new_users_month'] = (int)$result['count'];
        
        // Active users (logged in last 7 days)
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $stmt = $this->execute($query);
        $result = $stmt->fetch();
        $stats['active_users_week'] = (int)$result['count'];
        
        return $stats;
    }
}
?>