<?php
/**
 * News Content Extractor
 * AI-Powered Fake News Detection System
 */

class NewsExtractor {
    private $timeout;
    private $userAgent;
    
    public function __construct($timeout = 30) {
        $this->timeout = $timeout;
        $this->userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
    }
    
    /**
     * Extract content from URL
     */
    public function extractFromUrl($url) {
        // Validate URL
        if (!$this->isValidUrl($url)) {
            throw new Exception('Invalid URL provided');
        }
        
        try {
            // Get page content
            $html = $this->fetchPage($url);
            
            // Extract metadata and content
            $content = $this->parseContent($html);
            
            // Clean and validate extracted content
            $cleanContent = $this->cleanContent($content['text']);
            
            if (strlen($cleanContent) < 50) {
                throw new Exception('Insufficient content extracted from URL');
            }
            
            return [
                'title' => $content['title'] ?: 'Article from URL',
                'text' => $cleanContent,
                'author' => $content['author'],
                'publish_date' => $content['publish_date'],
                'meta_description' => $content['meta_description'],
                'url' => $url,
                'word_count' => str_word_count($cleanContent),
                'extracted_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'URL extraction failed: ' . $e->getMessage(), ['url' => $url]);
            throw $e;
        }
    }
    
    /**
     * Fetch page content
     */
    private function fetchPage($url) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_ENCODING => 'gzip, deflate',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5',
                'Accept-Encoding: gzip, deflate',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1',
            ]
        ]);
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Failed to fetch page: $error");
        }
        
        if ($httpCode >= 400) {
            throw new Exception("HTTP Error $httpCode when fetching page");
        }
        
        if (!$html) {
            throw new Exception("Empty response from URL");
        }
        
        return $html;
    }
    
    /**
     * Parse content from HTML
     */
    private function parseContent($html) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        
        // Extract title
        $title = $this->extractTitle($xpath);
        
        // Extract main content
        $text = $this->extractMainContent($xpath);
        
        // Extract metadata
        $author = $this->extractAuthor($xpath);
        $publishDate = $this->extractPublishDate($xpath);
        $metaDescription = $this->extractMetaDescription($xpath);
        
        return [
            'title' => $title,
            'text' => $text,
            'author' => $author,
            'publish_date' => $publishDate,
            'meta_description' => $metaDescription
        ];
    }
    
    /**
     * Extract title from page
     */
    private function extractTitle($xpath) {
        // Try multiple selectors for title
        $titleSelectors = [
            '//h1[@class*="title"]',
            '//h1[@class*="headline"]',
            '//h1',
            '//title',
            '//meta[@property="og:title"]/@content',
            '//meta[@name="twitter:title"]/@content'
        ];
        
        foreach ($titleSelectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                $title = trim($nodes->item(0)->textContent ?? $nodes->item(0)->nodeValue);
                if ($title && strlen($title) > 5) {
                    return $title;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Extract main content from page
     */
    private function extractMainContent($xpath) {
        // Try multiple selectors for main content
        $contentSelectors = [
            '//article',
            '//div[@class*="content"]',
            '//div[@class*="article"]',
            '//div[@class*="post"]',
            '//div[@id*="content"]',
            '//div[@id*="article"]',
            '//main',
            '//section[@class*="content"]'
        ];
        
        $content = '';
        
        foreach ($contentSelectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                foreach ($nodes as $node) {
                    $text = $this->extractTextFromNode($node);
                    if (strlen($text) > strlen($content)) {
                        $content = $text;
                    }
                }
                
                if (strlen($content) > 200) {
                    break;
                }
            }
        }
        
        // Fallback: extract from paragraphs
        if (strlen($content) < 200) {
            $paragraphs = $xpath->query('//p');
            $paragraphTexts = [];
            
            foreach ($paragraphs as $p) {
                $text = trim($p->textContent);
                if (strlen($text) > 50) {
                    $paragraphTexts[] = $text;
                }
            }
            
            if (!empty($paragraphTexts)) {
                $content = implode("\n\n", $paragraphTexts);
            }
        }
        
        return $content;
    }
    
    /**
     * Extract text from DOM node
     */
    private function extractTextFromNode($node) {
        // Remove script and style tags
        $scripts = $node->getElementsByTagName('script');
        $styles = $node->getElementsByTagName('style');
        
        for ($i = $scripts->length - 1; $i >= 0; $i--) {
            $scripts->item($i)->parentNode->removeChild($scripts->item($i));
        }
        
        for ($i = $styles->length - 1; $i >= 0; $i--) {
            $styles->item($i)->parentNode->removeChild($styles->item($i));
        }
        
        return trim($node->textContent);
    }
    
    /**
     * Extract author information
     */
    private function extractAuthor($xpath) {
        $authorSelectors = [
            '//meta[@name="author"]/@content',
            '//meta[@property="article:author"]/@content',
            '//span[@class*="author"]',
            '//div[@class*="author"]',
            '//a[@class*="author"]'
        ];
        
        foreach ($authorSelectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                $author = trim($nodes->item(0)->textContent ?? $nodes->item(0)->nodeValue);
                if ($author && strlen($author) > 2 && strlen($author) < 100) {
                    return $author;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Extract publish date
     */
    private function extractPublishDate($xpath) {
        $dateSelectors = [
            '//meta[@property="article:published_time"]/@content',
            '//meta[@name="publish_date"]/@content',
            '//time/@datetime',
            '//span[@class*="date"]',
            '//div[@class*="date"]'
        ];
        
        foreach ($dateSelectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                $dateStr = trim($nodes->item(0)->textContent ?? $nodes->item(0)->nodeValue);
                
                // Try to parse the date
                $timestamp = strtotime($dateStr);
                if ($timestamp !== false) {
                    return date('Y-m-d H:i:s', $timestamp);
                }
            }
        }
        
        return null;
    }
    
    /**
     * Extract meta description
     */
    private function extractMetaDescription($xpath) {
        $descriptionSelectors = [
            '//meta[@name="description"]/@content',
            '//meta[@property="og:description"]/@content',
            '//meta[@name="twitter:description"]/@content'
        ];
        
        foreach ($descriptionSelectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                $description = trim($nodes->item(0)->nodeValue);
                if ($description && strlen($description) > 20) {
                    return $description;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Clean extracted content
     */
    private function cleanContent($text) {
        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Remove unwanted characters
        $text = preg_replace('/[^\w\s\.\,\!\?\;\:\-\'\"\(\)\[\]\/]/', '', $text);
        
        // Remove common website elements
        $unwantedPatterns = [
            '/Cookie Policy.*?$/i',
            '/Privacy Policy.*?$/i',
            '/Terms of Service.*?$/i',
            '/Subscribe.*?newsletter.*?$/i',
            '/Follow us.*?$/i',
            '/Share this.*?$/i',
            '/Advertisement.*?$/i'
        ];
        
        foreach ($unwantedPatterns as $pattern) {
            $text = preg_replace($pattern, '', $text);
        }
        
        return trim($text);
    }
    
    /**
     * Validate URL
     */
    private function isValidUrl($url) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        $parsedUrl = parse_url($url);
        return isset($parsedUrl['scheme']) && in_array($parsedUrl['scheme'], ['http', 'https']);
    }
    
    /**
     * Check if URL is accessible
     */
    public function isUrlAccessible($url) {
        if (!$this->isValidUrl($url)) {
            return false;
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => $this->userAgent
        ]);
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode >= 200 && $httpCode < 400;
    }
}
?>