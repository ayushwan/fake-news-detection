<?php
/**
 * API Client for ML Model Communication
 * AI-Powered Fake News Detection System
 */

class APIClient {
    private $baseUrl;
    private $timeout;
    private $apiKey;
    
    public function __construct($baseUrl = ML_API_URL, $timeout = ML_API_TIMEOUT, $apiKey = ML_API_KEY) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->apiKey = $apiKey;
    }
    
    /**
     * Send news content to ML API for analysis
     */
    public function analyzeNews($content, $contentType = 'text') {
        $startTime = microtime(true);
        
        $payload = [
            'news' => $content,
            'content_type' => $contentType,
            'timestamp' => time()
        ];
        
        try {
            $response = $this->makeRequest('/analyze', 'POST', $payload);
            $processingTime = microtime(true) - $startTime;
            
            if (isset($response['prediction']) && isset($response['confidence'])) {
                return [
                    'prediction' => strtoupper($response['prediction']),
                    'confidence' => (float)$response['confidence'],
                    'processing_time' => round($processingTime, 3),
                    'model_version' => $response['model_version'] ?? 'v1.0',
                    'features' => $response['features'] ?? null
                ];
            } else {
                throw new Exception('Invalid response from ML API');
            }
            
        } catch (Exception $e) {
            logMessage('ERROR', 'ML API request failed: ' . $e->getMessage(), ['content_length' => strlen($content)]);
            
            // Return fallback response
            return [
                'prediction' => 'UNCERTAIN',
                'confidence' => 0.5,
                'processing_time' => microtime(true) - $startTime,
                'model_version' => 'fallback',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get model statistics and health
     */
    public function getModelHealth() {
        try {
            return $this->makeRequest('/health', 'GET');
        } catch (Exception $e) {
            logMessage('ERROR', 'ML API health check failed: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'timestamp' => time()
            ];
        }
    }
    
    /**
     * Get model information
     */
    public function getModelInfo() {
        try {
            return $this->makeRequest('/info', 'GET');
        } catch (Exception $e) {
            logMessage('ERROR', 'ML API info request failed: ' . $e->getMessage());
            return [
                'model_name' => 'unknown',
                'version' => 'unknown',
                'accuracy' => 0.0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Batch analyze multiple news items
     */
    public function batchAnalyze($newsItems) {
        $payload = [
            'batch' => $newsItems,
            'timestamp' => time()
        ];
        
        try {
            return $this->makeRequest('/batch-analyze', 'POST', $payload);
        } catch (Exception $e) {
            logMessage('ERROR', 'Batch analysis failed: ' . $e->getMessage());
            
            // Return individual fallback responses
            $results = [];
            foreach ($newsItems as $index => $item) {
                $results[] = [
                    'index' => $index,
                    'prediction' => 'UNCERTAIN',
                    'confidence' => 0.5,
                    'error' => $e->getMessage()
                ];
            }
            return ['results' => $results];
        }
    }
    
    /**
     * Make HTTP request to ML API
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        
        // Set basic cURL options
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'FakeNewsDetector-PHP-Client/1.0',
        ]);
        
        // Set headers
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        
        if ($this->apiKey) {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
            $headers[] = 'X-API-Key: ' . $this->apiKey;
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Set method-specific options
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Handle cURL errors
        if ($error) {
            throw new Exception("cURL Error: $error");
        }
        
        // Handle HTTP errors
        if ($httpCode >= 400) {
            $errorMessage = "HTTP Error $httpCode";
            if ($response) {
                $errorData = json_decode($response, true);
                if (isset($errorData['error'])) {
                    $errorMessage .= ": " . $errorData['error'];
                }
            }
            throw new Exception($errorMessage);
        }
        
        // Parse JSON response
        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response: " . json_last_error_msg());
        }
        
        return $decodedResponse;
    }
    
    /**
     * Test API connection
     */
    public function testConnection() {
        try {
            $response = $this->makeRequest('/ping', 'GET');
            return [
                'success' => true,
                'response_time' => $response['response_time'] ?? null,
                'timestamp' => time()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => time()
            ];
        }
    }
    
    /**
     * Get API usage statistics
     */
    public function getUsageStats() {
        try {
            return $this->makeRequest('/stats', 'GET');
        } catch (Exception $e) {
            logMessage('ERROR', 'Failed to get API usage stats: ' . $e->getMessage());
            return [
                'requests_today' => 0,
                'requests_total' => 0,
                'avg_response_time' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send feedback to improve model
     */
    public function sendFeedback($submissionId, $actualLabel, $predictedLabel, $confidence, $userFeedback = null) {
        $payload = [
            'submission_id' => $submissionId,
            'actual_label' => $actualLabel,
            'predicted_label' => $predictedLabel,
            'confidence' => $confidence,
            'user_feedback' => $userFeedback,
            'timestamp' => time()
        ];
        
        try {
            return $this->makeRequest('/feedback', 'POST', $payload);
        } catch (Exception $e) {
            logMessage('ERROR', 'Failed to send feedback to ML API: ' . $e->getMessage());
            return false;
        }
    }
}
?>