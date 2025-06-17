<?php
/**
 * Submission Model
 * AI-Powered Fake News Detection System
 */

require_once 'Database.php';

class Submission extends Database {
    protected $table = 'submissions';
    
    /**
     * Create a new submission
     */
    public function createSubmission($userId, $type, $content, $originalUrl = null, $imagePath = null) {
        $submissionData = [
            'user_id' => $userId,
            'submission_type' => $type,
            'content' => $content,
            'original_url' => $originalUrl,
            'image_path' => $imagePath,
            'prediction' => 'PENDING',
            'confidence' => 0.0,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'submitted_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->create($submissionData);
    }
    
    /**
     * Update submission with ML prediction results
     */
    public function updatePrediction($submissionId, $prediction, $confidence, $processingTime = null) {
        $updateData = [
            'prediction' => $prediction,
            'confidence' => $confidence,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($processingTime !== null) {
            $updateData['processing_time'] = $processingTime;
        }
        
        return $this->update($submissionId, $updateData);
    }
    
    /**
     * Get user submissions with pagination
     */
    public function getUserSubmissions($userId, $page = 1, $perPage = ITEMS_PER_PAGE) {
        $conditions = ['user_id' => $userId];
        return $this->paginate($page, $perPage, $conditions, 'submitted_at', 'DESC');
    }
    
    /**
     * Get all submissions with user info
     */
    public function getAllSubmissionsWithUsers($page = 1, $perPage = ITEMS_PER_PAGE) {
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $totalCount = $this->count();
        
        // Get submissions with user info
        $query = "
            SELECT 
                s.*,
                u.name as user_name,
                u.email as user_email
            FROM {$this->table} s
            JOIN users u ON s.user_id = u.id
            ORDER BY s.submitted_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $this->execute($query, [$perPage, $offset]);
        $results = $stmt->fetchAll();
        
        return [
            'data' => $results,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalCount,
                'total_pages' => ceil($totalCount / $perPage),
                'has_next' => $page < ceil($totalCount / $perPage),
                'has_prev' => $page > 1
            ]
        ];
    }
    
    /**
     * Search submissions by content
     */
    public function searchSubmissions($searchTerm, $limit = 20) {
        $query = "
            SELECT 
                s.*,
                u.name as user_name,
                u.email as user_email
            FROM {$this->table} s
            JOIN users u ON s.user_id = u.id
            WHERE s.content LIKE ? OR s.original_url LIKE ?
            ORDER BY s.submitted_at DESC
            LIMIT ?
        ";
        
        $searchParam = "%$searchTerm%";
        $stmt = $this->execute($query, [$searchParam, $searchParam, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get submission statistics
     */
    public function getSubmissionStats() {
        $stats = [];
        
        // Total submissions
        $stats['total_submissions'] = $this->count();
        
        // Submissions by prediction
        $query = "SELECT prediction, COUNT(*) as count FROM {$this->table} WHERE prediction != 'PENDING' GROUP BY prediction";
        $stmt = $this->execute($query);
        $predictionCounts = $stmt->fetchAll();
        
        foreach ($predictionCounts as $row) {
            $stats['by_prediction'][$row['prediction']] = (int)$row['count'];
        }
        
        // Submissions by type
        $query = "SELECT submission_type, COUNT(*) as count FROM {$this->table} GROUP BY submission_type";
        $stmt = $this->execute($query);
        $typeCounts = $stmt->fetchAll();
        
        foreach ($typeCounts as $row) {
            $stats['by_type'][$row['submission_type']] = (int)$row['count'];
        }
        
        // Average confidence
        $query = "SELECT AVG(confidence) as avg_confidence FROM {$this->table} WHERE prediction != 'PENDING'";
        $stmt = $this->execute($query);
        $result = $stmt->fetch();
        $stats['avg_confidence'] = round((float)$result['avg_confidence'], 2);
        
        // Recent submissions (last 24 hours)
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $stmt = $this->execute($query);
        $result = $stmt->fetch();
        $stats['recent_24h'] = (int)$result['count'];
        
        // Daily submissions for the last 7 days
        $query = "
            SELECT 
                DATE(submitted_at) as date,
                COUNT(*) as count,
                SUM(CASE WHEN prediction = 'FAKE' THEN 1 ELSE 0 END) as fake_count,
                SUM(CASE WHEN prediction = 'REAL' THEN 1 ELSE 0 END) as real_count
            FROM {$this->table}
            WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(submitted_at)
            ORDER BY date DESC
        ";
        $stmt = $this->execute($query);
        $stats['daily_stats'] = $stmt->fetchAll();
        
        return $stats;
    }
    
    /**
     * Get trending keywords
     */
    public function getTrendingKeywords($category = null, $limit = 10) {
        $query = "SELECT keyword, frequency, category FROM keyword_trends";
        $params = [];
        
        if ($category) {
            $query .= " WHERE category = ?";
            $params[] = $category;
        }
        
        $query .= " ORDER BY frequency DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->execute($query, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get submission details with related data
     */
    public function getSubmissionDetails($submissionId) {
        $query = "
            SELECT 
                s.*,
                u.name as user_name,
                u.email as user_email,
                u.profile_image as user_profile_image
            FROM {$this->table} s
            JOIN users u ON s.user_id = u.id
            WHERE s.id = ?
        ";
        
        $stmt = $this->execute($query, [$submissionId]);
        $submission = $stmt->fetch();
        
        if (!$submission) {
            return null;
        }
        
        // Get comments for this submission
        $commentsQuery = "
            SELECT 
                c.*,
                u.name as commenter_name
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.submission_id = ? AND c.status = 'active'
            ORDER BY c.created_at DESC
        ";
        $stmt = $this->execute($commentsQuery, [$submissionId]);
        $submission['comments'] = $stmt->fetchAll();
        
        // Get flags for this submission
        $flagsQuery = "
            SELECT 
                f.*,
                u.name as flagger_name
            FROM flags f
            JOIN users u ON f.user_id = u.id
            WHERE f.submission_id = ?
            ORDER BY f.created_at DESC
        ";
        $stmt = $this->execute($flagsQuery, [$submissionId]);
        $submission['flags'] = $stmt->fetchAll();
        
        return $submission;
    }
    
    /**
     * Flag a submission
     */
    public function flagSubmission($userId, $submissionId, $reason, $description = null) {
        $flagData = [
            'user_id' => $userId,
            'submission_id' => $submissionId,
            'reason' => $reason,
            'description' => $description,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Check if user already flagged this submission
        $existingFlag = $this->query(
            "SELECT id FROM flags WHERE user_id = ? AND submission_id = ?",
            [$userId, $submissionId]
        )->fetch();
        
        if ($existingFlag) {
            throw new Exception('You have already flagged this submission');
        }
        
        $flagsTable = new Database();
        $flagsTable->table = 'flags';
        return $flagsTable->create($flagData);
    }
    
    /**
     * Add comment to submission
     */
    public function addComment($userId, $submissionId, $comment, $rating = null) {
        $commentData = [
            'user_id' => $userId,
            'submission_id' => $submissionId,
            'comment' => $comment,
            'rating' => $rating,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $commentsTable = new Database();
        $commentsTable->table = 'comments';
        return $commentsTable->create($commentData);
    }
}
?>