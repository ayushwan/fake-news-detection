<?php
/**
 * Main Configuration File
 * AI-Powered Fake News Detection System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Application settings
define('APP_NAME', 'Fake News Detective');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/fake-news-detection');
define('APP_DEBUG', true);

// Directory paths
define('ROOT_PATH', dirname(__FILE__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('LOGS_PATH', ROOT_PATH . '/logs');

// ML API Configuration
define('ML_API_URL', 'http://localhost:5000');
define('ML_API_TIMEOUT', 30);
define('ML_API_KEY', 'your-api-key-here');

// Security settings
define('JWT_SECRET', 'your-jwt-secret-key-change-in-production');
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_DOCUMENT_TYPES', ['txt', 'pdf', 'doc', 'docx']);

// Pagination settings
define('ITEMS_PER_PAGE', 20);
define('MAX_ITEMS_PER_PAGE', 100);

// Rate limiting
define('API_RATE_LIMIT', 100); // requests per hour
define('SUBMISSION_RATE_LIMIT', 10); // submissions per hour

// External APIs
define('GOOGLE_TRANSLATE_API_KEY', 'your-google-translate-api-key');
define('NEWS_API_KEY', 'your-news-api-key');

// Email configuration (for notifications)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('FROM_EMAIL', 'noreply@fakenews.com');
define('FROM_NAME', 'Fake News Detective');

// Cache settings
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 3600); // 1 hour
define('CACHE_PATH', ROOT_PATH . '/cache');

// Response codes
define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_INTERNAL_SERVER_ERROR', 500);

// Create required directories if they don't exist
$requiredDirs = [UPLOAD_PATH, LOGS_PATH, CACHE_PATH];
foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Helper functions
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function logMessage($level, $message, $context = []) {
    $logFile = LOGS_PATH . '/' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = $context ? ' ' . json_encode($context) : '';
    $logEntry = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

function sendJsonResponse($data, $statusCode = HTTP_OK) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken();
    }
    return $_SESSION['csrf_token'];
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        sendJsonResponse(['error' => 'Admin access required'], HTTP_FORBIDDEN);
    }
}

// Set timezone
date_default_timezone_set('UTC');

// Include database config
require_once __DIR__ . '/database-config.php';
?>