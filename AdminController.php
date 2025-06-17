<?php
/**
 * Admin Controller
 * AI-Powered Fake News Detection System
 */

require_once 'app-config.php';
require_once 'User.php';
require_once 'Submission.php';

class AdminController {
    private $userModel;
    private $submissionModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->submissionModel = new Submission();
    }
    
    /**
     * Admin dashboard
     */
    public function dashboard() {
        requireAdmin();
        
        // Get dashboard statistics
        $userStats = $this->userModel->getDashboardStats();
        $submissionStats = $this->submissionModel->getSubmissionStats();
        
        $dashboardData = [
            'users' => $userStats,
            'submissions' => $submissionStats,
            'system' => $this->getSystemStats()
        ];
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['json'])) {
            sendJsonResponse($dashboardData);
        }
        
        include 'admin-dashboard.php';
    }
    
    /**
     * User management
     */
    public function manageUsers() {
        requireAdmin();
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = min((int)($_GET['per_page'] ?? ITEMS_PER_PAGE), MAX_ITEMS_PER_PAGE);
        $search = $_GET['search'] ?? '';
        
        if (!empty($search)) {
            $users = $this->userModel->searchUsers($search, $perPage);
            $result = ['data' => $users, 'pagination' => null];
        } else {
            $result = $this->userModel->getAllUsers($page, $perPage);
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['json'])) {
            sendJsonResponse($result);
        }
        
        include 'admin-users.php';
    }
    
    /**
     * Submission management
     */
    public function manageSubmissions() {
        requireAdmin();
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = min((int)($_GET['per_page'] ?? ITEMS_PER_PAGE), MAX_ITEMS_PER_PAGE);
        $search = $_GET['search'] ?? '';
        
        if (!empty($search)) {
            $submissions = $this->submissionModel->searchSubmissions($search, $perPage);
            $result = ['data' => $submissions, 'pagination' => null];
        } else {
            $result = $this->submissionModel->getAllSubmissionsWithUsers($page, $perPage);
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['json'])) {
            sendJsonResponse($result);
        }
        
        include 'admin-submissions.php';
    }
    
    /**
     * Change user status
     */
    public function changeUserStatus() {
        requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $userId = (int)($_POST['user_id'] ?? 0);
                $status = $_POST['status'] ?? '';
                $csrfToken = $_POST['csrf_token'] ?? '';
                
                if (!validateCSRFToken($csrfToken)) {
                    throw new Exception('Invalid security token');
                }
                
                if (!$userId || empty($status)) {
                    throw new Exception('User ID and status are required');
                }
                
                // Prevent admin from changing their own status
                if ($userId == $_SESSION['user_id']) {
                    throw new Exception('Cannot change your own status');
                }
                
                $success = $this->userModel->changeUserStatus($userId, $status);
                
                if ($success) {
                    logMessage('INFO', 'User status changed by admin', [
                        'admin_id' => $_SESSION['user_id'],
                        'user_id' => $userId,
                        'new_status' => $status
                    ]);
                    
                    sendJsonResponse(['success' => true, 'message' => 'User status updated successfully']);
                } else {
                    throw new Exception('Failed to update user status');
                }
                
            } catch (Exception $e) {
                sendJsonResponse(['error' => $e->getMessage()], HTTP_BAD_REQUEST);
            }
        }
    }
    
    /**
     * Delete user
     */
    public function deleteUser() {
        requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $userId = (int)($_POST['user_id'] ?? 0);
                $csrfToken = $_POST['csrf_token'] ?? '';
                
                if (!validateCSRFToken($csrfToken)) {
                    throw new Exception('Invalid security token');
                }
                
                if (!$userId) {
                    throw new Exception('User ID is required');
                }
                
                // Prevent admin from deleting themselves
                if ($userId == $_SESSION['user_id']) {
                    throw new Exception('Cannot delete your own account');
                }
                
                // Check if user is another admin
                $user = $this->userModel->find($userId);
                if ($user && $user['role'] === 'admin') {
                    throw new Exception('Cannot delete another admin account');
                }
                
                $success = $this->userModel->delete($userId);
                
                if ($success) {
                    logMessage('WARNING', 'User deleted by admin', [
                        'admin_id' => $_SESSION['user_id'],
                        'deleted_user_id' => $userId,
                        'deleted_user_email' => $user['email'] ?? 'unknown'
                    ]);
                    
                    sendJsonResponse(['success' => true, 'message' => 'User deleted successfully']);
                } else {
                    throw new Exception('Failed to delete user');
                }
                
            } catch (Exception $e) {
                sendJsonResponse(['error' => $e->getMessage()], HTTP_BAD_REQUEST);
            }
        }
    }
    
    /**
     * Get system statistics
     */
    public function getSystemStats() {
        requireAdmin();
        
        $stats = [
            'server' => [
                'php_version' => phpversion(),
                'memory_usage' => memory_get_usage(true),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'disk_free_space' => disk_free_space('.'),
                'server_time' => date('Y-m-d H:i:s'),
                'uptime' => $this->getServerUptime()
            ],
            'database' => [
                'total_tables' => $this->getDatabaseTableCount(),
                'database_size' => $this->getDatabaseSize()
            ],
            'files' => [
                'upload_dir_size' => $this->getDirectorySize(UPLOAD_PATH),
                'log_dir_size' => $this->getDirectorySize(LOGS_PATH),
                'total_uploads' => $this->countFiles(UPLOAD_PATH)
            ]
        ];
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['json'])) {
            sendJsonResponse($stats);
        }
        
        return $stats;
    }
    
    /**
     * Manage flags
     */
    public function manageFlags() {
        requireAdmin();
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = min((int)($_GET['per_page'] ?? ITEMS_PER_PAGE), MAX_ITEMS_PER_PAGE);
        $status = $_GET['status'] ?? 'pending';
        
        $offset = ($page - 1) * $perPage;
        
        $query = "
            SELECT 
                f.*,
                s.content as submission_content,
                s.prediction as submission_prediction,
                u.name as flagger_name,
                u.email as flagger_email,
                su.name as submission_user_name
            FROM flags f
            JOIN submissions s ON f.submission_id = s.id
            JOIN users u ON f.user_id = u.id
            JOIN users su ON s.user_id = su.id
            WHERE f.status = ?
            ORDER BY f.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $flags = $this->submissionModel->query($query, [$status, $perPage, $offset])->fetchAll();
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as count FROM flags WHERE status = ?";
        $totalCount = $this->submissionModel->query($countQuery, [$status])->fetch()['count'];
        
        $result = [
            'data' => $flags,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalCount,
                'total_pages' => ceil($totalCount / $perPage)
            ]
        ];
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['json'])) {
            sendJsonResponse($result);
        }
        
        include 'admin-flags.php';
    }
    
    /**
     * Update flag status
     */
    public function updateFlagStatus() {
        requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $flagId = (int)($_POST['flag_id'] ?? 0);
                $status = $_POST['status'] ?? '';
                $csrfToken = $_POST['csrf_token'] ?? '';
                
                if (!validateCSRFToken($csrfToken)) {
                    throw new Exception('Invalid security token');
                }
                
                $allowedStatuses = ['pending', 'reviewed', 'resolved', 'dismissed'];
                if (!in_array($status, $allowedStatuses)) {
                    throw new Exception('Invalid status');
                }
                
                $updateData = [
                    'status' => $status,
                    'reviewed_by' => $_SESSION['user_id'],
                    'reviewed_at' => date('Y-m-d H:i:s')
                ];
                
                // Update flag
                $flagsTable = new Database();
                $flagsTable->table = 'flags';
                $success = $flagsTable->update($flagId, $updateData);
                
                if ($success) {
                    logMessage('INFO', 'Flag status updated by admin', [
                        'admin_id' => $_SESSION['user_id'],
                        'flag_id' => $flagId,
                        'new_status' => $status
                    ]);
                    
                    sendJsonResponse(['success' => true, 'message' => 'Flag status updated successfully']);
                } else {
                    throw new Exception('Failed to update flag status');
                }
                
            } catch (Exception $e) {
                sendJsonResponse(['error' => $e->getMessage()], HTTP_BAD_REQUEST);
            }
        }
    }
    
    /**
     * Export data
     */
    public function exportData() {
        requireAdmin();
        
        $type = $_GET['type'] ?? '';
        $format = $_GET['format'] ?? 'csv';
        
        switch ($type) {
            case 'users':
                $this->exportUsers($format);
                break;
            case 'submissions':
                $this->exportSubmissions($format);
                break;
            case 'stats':
                $this->exportStats($format);
                break;
            default:
                sendJsonResponse(['error' => 'Invalid export type'], HTTP_BAD_REQUEST);
        }
    }
    
    /**
     * Export users data
     */
    private function exportUsers($format) {
        $users = $this->userModel->findAll();
        
        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="users_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Name', 'Email', 'Role', 'Status', 'Created At', 'Last Login']);
            
            foreach ($users as $user) {
                fputcsv($output, [
                    $user['id'],
                    $user['name'],
                    $user['email'],
                    $user['role'],
                    $user['status'],
                    $user['created_at'],
                    $user['last_login']
                ]);
            }
            
            fclose($output);
        } else {
            sendJsonResponse($users);
        }
    }
    
    /**
     * Export submissions data
     */
    private function exportSubmissions($format) {
        $submissions = $this->submissionModel->getAllSubmissionsWithUsers(1, 1000)['data'];
        
        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="submissions_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'User', 'Type', 'Prediction', 'Confidence', 'Submitted At']);
            
            foreach ($submissions as $submission) {
                fputcsv($output, [
                    $submission['id'],
                    $submission['user_name'],
                    $submission['submission_type'],
                    $submission['prediction'],
                    $submission['confidence'],
                    $submission['submitted_at']
                ]);
            }
            
            fclose($output);
        } else {
            sendJsonResponse($submissions);
        }
    }
    
    /**
     * Get server uptime
     */
    private function getServerUptime() {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return $load[0] ?? 'Unknown';
        }
        return 'Unknown';
    }
    
    /**
     * Get database table count
     */
    private function getDatabaseTableCount() {
        try {
            $result = $this->userModel->query("SHOW TABLES")->fetchAll();
            return count($result);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get database size
     */
    private function getDatabaseSize() {
        try {
            $query = "
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS db_size 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            ";
            $result = $this->userModel->query($query)->fetch();
            return ($result['db_size'] ?? 0) . ' MB';
        } catch (Exception $e) {
            return 'Unknown';
        }
    }
    
    /**
     * Get directory size
     */
    private function getDirectorySize($directory) {
        if (!is_dir($directory)) {
            return 0;
        }
        
        $size = 0;
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        
        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        
        return round($size / 1024 / 1024, 2) . ' MB';
    }
    
    /**
     * Count files in directory
     */
    private function countFiles($directory) {
        if (!is_dir($directory)) {
            return 0;
        }
        
        $count = 0;
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        
        foreach ($files as $file) {
            if ($file->isFile()) {
                $count++;
            }
        }
        
        return $count;
    }
}
?>