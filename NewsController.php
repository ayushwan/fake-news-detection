<?php
/**
 * News Analysis Controller
 * AI-Powered Fake News Detection System
 */

require_once 'app-config.php';
require_once 'Submission.php';
require_once 'APIClient.php';
require_once 'NewsExtractor.php';

class NewsController {
    private $submissionModel;
    private $apiClient;
    private $newsExtractor;
    
    public function __construct() {
        $this->submissionModel = new Submission();
        $this->apiClient = new APIClient();
        $this->newsExtractor = new NewsExtractor();
    }
    
    /**
     * Submit news for analysis
     */
    public function submitNews() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $userId = $_SESSION['user_id'];
                $submissionType = $_POST['submission_type'] ?? '';
                $csrfToken = $_POST['csrf_token'] ?? '';
                
                // Validate CSRF token
                if (!validateCSRFToken($csrfToken)) {
                    throw new Exception('Invalid security token');
                }
                
                // Check rate limiting
                $this->checkSubmissionRateLimit($userId);
                
                // Process submission based on type
                switch ($submissionType) {
                    case 'text':
                        $result = $this->handleTextSubmission($userId);
                        break;
                    case 'url':
                        $result = $this->handleUrlSubmission($userId);
                        break;
                    case 'image':
                        $result = $this->handleImageSubmission($userId);
                        break;
                    default:
                        throw new Exception('Invalid submission type');
                }
                
                logMessage('INFO', 'News submission processed', [
                    'user_id' => $userId,
                    'submission_id' => $result['submission_id'],
                    'prediction' => $result['prediction']
                ]);
                
                sendJsonResponse([
                    'success' => true,
                    'submission_id' => $result['submission_id'],
                    'prediction' => $result['prediction'],
                    'confidence' => $result['confidence'],
                    'processing_time' => $result['processing_time']
                ]);
                
            } catch (Exception $e) {
                logMessage('ERROR', 'News submission failed: ' . $e->getMessage(), ['user_id' => $userId ?? null]);
                sendJsonResponse(['error' => $e->getMessage()], HTTP_BAD_REQUEST);
            }
        }
    }
    
    /**
     * Handle text submission
     */
    private function handleTextSubmission($userId) {
        $content = trim($_POST['content'] ?? '');
        
        if (empty($content)) {
            throw new Exception('Content is required');
        }
        
        if (strlen($content) < 10) {
            throw new Exception('Content is too short for analysis');
        }
        
        if (strlen($content) > 10000) {
            throw new Exception('Content is too long (max 10,000 characters)');
        }
        
        // Create submission record
        $submissionId = $this->submissionModel->createSubmission($userId, 'text', $content);
        
        // Analyze with ML API
        $analysis = $this->apiClient->analyzeNews($content, 'text');
        
        // Update submission with results
        $this->submissionModel->updatePrediction(
            $submissionId,
            $analysis['prediction'],
            $analysis['confidence'],
            $analysis['processing_time']
        );
        
        return [
            'submission_id' => $submissionId,
            'prediction' => $analysis['prediction'],
            'confidence' => $analysis['confidence'],
            'processing_time' => $analysis['processing_time']
        ];
    }
    
    /**
     * Handle URL submission
     */
    private function handleUrlSubmission($userId) {
        $url = trim($_POST['url'] ?? '');
        
        if (empty($url)) {
            throw new Exception('URL is required');
        }
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Please enter a valid URL');
        }
        
        // Check if URL is accessible
        if (!$this->newsExtractor->isUrlAccessible($url)) {
            throw new Exception('URL is not accessible or does not exist');
        }
        
        // Extract content from URL
        $extractedData = $this->newsExtractor->extractFromUrl($url);
        
        if (strlen($extractedData['text']) < 50) {
            throw new Exception('Could not extract sufficient content from the URL');
        }
        
        // Create submission record
        $submissionId = $this->submissionModel->createSubmission(
            $userId,
            'url',
            $extractedData['text'],
            $url
        );
        
        // Analyze with ML API
        $analysis = $this->apiClient->analyzeNews($extractedData['text'], 'url');
        
        // Update submission with results
        $this->submissionModel->updatePrediction(
            $submissionId,
            $analysis['prediction'],
            $analysis['confidence'],
            $analysis['processing_time']
        );
        
        return [
            'submission_id' => $submissionId,
            'prediction' => $analysis['prediction'],
            'confidence' => $analysis['confidence'],
            'processing_time' => $analysis['processing_time'],
            'extracted_title' => $extractedData['title'],
            'word_count' => $extractedData['word_count']
        ];
    }
    
    /**
     * Handle image submission (OCR)
     */
    private function handleImageSubmission($userId) {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Please select an image file');
        }
        
        $uploadedFile = $_FILES['image'];
        
        // Validate file
        $this->validateImageFile($uploadedFile);
        
        // Save uploaded file
        $imagePath = $this->saveUploadedFile($uploadedFile, $userId);
        
        try {
            // Extract text from image using OCR
            $extractedText = $this->extractTextFromImage($imagePath);
            
            if (strlen($extractedText) < 10) {
                throw new Exception('Could not extract sufficient text from the image');
            }
            
            // Create submission record
            $submissionId = $this->submissionModel->createSubmission(
                $userId,
                'image',
                $extractedText,
                null,
                $imagePath
            );
            
            // Analyze with ML API
            $analysis = $this->apiClient->analyzeNews($extractedText, 'image');
            
            // Update submission with results
            $this->submissionModel->updatePrediction(
                $submissionId,
                $analysis['prediction'],
                $analysis['confidence'],
                $analysis['processing_time']
            );
            
            return [
                'submission_id' => $submissionId,
                'prediction' => $analysis['prediction'],
                'confidence' => $analysis['confidence'],
                'processing_time' => $analysis['processing_time'],
                'extracted_text_length' => strlen($extractedText)
            ];
            
        } catch (Exception $e) {
            // Clean up uploaded file on error
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
            throw $e;
        }
    }
    
    /**
     * Validate uploaded image file
     */
    private function validateImageFile($file) {
        // Check file size
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception('File size exceeds maximum limit of ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB');
        }
        
        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mimeType, $allowedMimes)) {
            throw new Exception('Only JPEG, PNG, and GIF images are allowed');
        }
        
        // Check if it's actually an image
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new Exception('Invalid image file');
        }
    }
    
    /**
     * Save uploaded file
     */
    private function saveUploadedFile($file, $userId) {
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'news_' . $userId . '_' . time() . '_' . uniqid() . '.' . $extension;
        $uploadPath = UPLOAD_PATH . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to save uploaded file');
        }
        
        return $uploadPath;
    }
    
    /**
     * Extract text from image using OCR (requires pytesseract)
     */
    private function extractTextFromImage($imagePath) {
        // This would typically call a Python script or external OCR service
        // For demonstration, using a simple approach
        
        $pythonScript = __DIR__ . '/ocr_extract.py';
        $command = "python3 $pythonScript " . escapeshellarg($imagePath);
        
        $output = shell_exec($command);
        
        if (empty($output)) {
            throw new Exception('Could not extract text from image');
        }
        
        return trim($output);
    }
    
    /**
     * Get user submission history
     */
    public function getSubmissionHistory() {
        requireLogin();
        
        $userId = $_SESSION['user_id'];
        $page = (int)($_GET['page'] ?? 1);
        $perPage = min((int)($_GET['per_page'] ?? ITEMS_PER_PAGE), MAX_ITEMS_PER_PAGE);
        
        $result = $this->submissionModel->getUserSubmissions($userId, $page, $perPage);
        
        sendJsonResponse($result);
    }
    
    /**
     * Get submission details
     */
    public function getSubmissionDetails() {
        requireLogin();
        
        $submissionId = (int)($_GET['id'] ?? 0);
        $userId = $_SESSION['user_id'];
        
        if (!$submissionId) {
            sendJsonResponse(['error' => 'Submission ID is required'], HTTP_BAD_REQUEST);
        }
        
        $submission = $this->submissionModel->getSubmissionDetails($submissionId);
        
        if (!$submission) {
            sendJsonResponse(['error' => 'Submission not found'], HTTP_NOT_FOUND);
        }
        
        // Check if user owns this submission or is admin
        if ($submission['user_id'] != $userId && !isAdmin()) {
            sendJsonResponse(['error' => 'Access denied'], HTTP_FORBIDDEN);
        }
        
        sendJsonResponse($submission);
    }
    
    /**
     * Flag a submission
     */
    public function flagSubmission() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $userId = $_SESSION['user_id'];
                $submissionId = (int)($_POST['submission_id'] ?? 0);
                $reason = $_POST['reason'] ?? '';
                $description = trim($_POST['description'] ?? '');
                $csrfToken = $_POST['csrf_token'] ?? '';
                
                if (!validateCSRFToken($csrfToken)) {
                    throw new Exception('Invalid security token');
                }
                
                if (!$submissionId || empty($reason)) {
                    throw new Exception('Submission ID and reason are required');
                }
                
                $allowedReasons = ['inappropriate', 'spam', 'incorrect_prediction', 'offensive', 'other'];
                if (!in_array($reason, $allowedReasons)) {
                    throw new Exception('Invalid reason');
                }
                
                $flagId = $this->submissionModel->flagSubmission($userId, $submissionId, $reason, $description);
                
                logMessage('INFO', 'Submission flagged', [
                    'user_id' => $userId,
                    'submission_id' => $submissionId,
                    'reason' => $reason
                ]);
                
                sendJsonResponse(['success' => true, 'flag_id' => $flagId]);
                
            } catch (Exception $e) {
                sendJsonResponse(['error' => $e->getMessage()], HTTP_BAD_REQUEST);
            }
        }
    }
    
    /**
     * Add comment to submission
     */
    public function addComment() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $userId = $_SESSION['user_id'];
                $submissionId = (int)($_POST['submission_id'] ?? 0);
                $comment = trim($_POST['comment'] ?? '');
                $rating = (int)($_POST['rating'] ?? 0);
                $csrfToken = $_POST['csrf_token'] ?? '';
                
                if (!validateCSRFToken($csrfToken)) {
                    throw new Exception('Invalid security token');
                }
                
                if (!$submissionId || empty($comment)) {
                    throw new Exception('Submission ID and comment are required');
                }
                
                if (strlen($comment) < 5) {
                    throw new Exception('Comment is too short');
                }
                
                if ($rating && ($rating < 1 || $rating > 5)) {
                    throw new Exception('Rating must be between 1 and 5');
                }
                
                $commentId = $this->submissionModel->addComment($userId, $submissionId, $comment, $rating ?: null);
                
                sendJsonResponse(['success' => true, 'comment_id' => $commentId]);
                
            } catch (Exception $e) {
                sendJsonResponse(['error' => $e->getMessage()], HTTP_BAD_REQUEST);
            }
        }
    }
    
    /**
     * Check submission rate limiting
     */
    private function checkSubmissionRateLimit($userId) {
        $key = 'submissions_' . $userId;
        $submissions = $_SESSION[$key] ?? [];
        $now = time();
        
        // Remove submissions older than 1 hour
        $submissions = array_filter($submissions, function($timestamp) use ($now) {
            return $now - $timestamp < 3600;
        });
        
        if (count($submissions) >= SUBMISSION_RATE_LIMIT) {
            throw new Exception('Too many submissions. Please wait before submitting again.');
        }
        
        // Add current submission
        $submissions[] = $now;
        $_SESSION[$key] = $submissions;
    }
    
    /**
     * Get trending keywords
     */
    public function getTrendingKeywords() {
        $category = $_GET['category'] ?? null;
        $limit = min((int)($_GET['limit'] ?? 10), 50);
        
        $keywords = $this->submissionModel->getTrendingKeywords($category, $limit);
        
        sendJsonResponse($keywords);
    }
}
?>