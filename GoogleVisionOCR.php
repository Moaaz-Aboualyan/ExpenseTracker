<?php
/**
 * Google Vision API OCR Service
 * Handles receipt image processing and text extraction
 */

require_once __DIR__ . '/config.php';

class GoogleVisionOCR {
    
    private $apiKey;
    private $apiUrl = 'https://vision.googleapis.com/v1/images:annotate';
    
    public function __construct($apiKey = null) {
        $this->apiKey = $apiKey ?? GOOGLE_VISION_API_KEY;
        
        if (empty($this->apiKey) || $this->apiKey === 'YOUR_GOOGLE_VISION_API_KEY_HERE') {
            throw new Exception('Google Vision API key not configured. Please set GOOGLE_VISION_API_KEY in your .env file');
        }
    }
    
    /**
     * Process image and extract text using Google Vision API
     * 
     * @param string $imagePath Path to the image file
     * @return array Extracted text and structured data
     */
    public function processReceipt($imagePath) {
        if (!file_exists($imagePath)) {
            throw new Exception('Image file not found');
        }
        
        // Read and encode image
        $imageContent = file_get_contents($imagePath);
        $base64Image = base64_encode($imageContent);
        
        // Prepare API request
        $requestData = [
            'requests' => [
                [
                    'image' => [
                        'content' => $base64Image
                    ],
                    'features' => [
                        [
                            'type' => 'TEXT_DETECTION',
                            'maxResults' => 1
                        ],
                        [
                            'type' => 'DOCUMENT_TEXT_DETECTION',
                            'maxResults' => 1
                        ]
                    ]
                ]
            ]
        ];
        
        // Make API call
        $response = $this->makeApiRequest($requestData);
        
        // Parse response
        return $this->parseResponse($response);
    }
    
    /**
     * Make HTTP request to Google Vision API
     */
    private function makeApiRequest($data) {
        $url = $this->apiUrl . '?key=' . $this->apiKey;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('API request failed: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? 'Unknown error';
            throw new Exception('API error: ' . $errorMessage);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Parse Google Vision API response and extract transaction data
     */
    private function parseResponse($response) {
        $result = [
            'full_text' => '',
            'amount' => null,
            'date' => null,
            'merchant' => null,
            'items' => [],
            'confidence' => 0
        ];
        
        if (!isset($response['responses'][0]['textAnnotations'])) {
            return $result;
        }
        
        $annotations = $response['responses'][0]['textAnnotations'];
        
        // First annotation contains full text
        if (isset($annotations[0]['description'])) {
            $result['full_text'] = $annotations[0]['description'];
        }
        
        // Extract structured data
        $result['amount'] = $this->extractAmount($result['full_text']);
        $result['date'] = $this->extractDate($result['full_text']);
        $result['merchant'] = $this->extractMerchant($result['full_text']);
        $result['items'] = $this->extractItems($result['full_text']);
        
        return $result;
    }
    
    /**
     * Extract monetary amounts from text
     */
    private function extractAmount($text) {
        $lines = explode("\n", $text);
        
        // Find all lines containing "total" (excluding subtotal)
        $totalLines = [];
        
        foreach ($lines as $index => $line) {
            $lowerLine = strtolower($line);
            
            // Only lines with "total" but not "subtotal"
            if (preg_match('/total/i', $lowerLine) && !preg_match('/sub\s*total/i', $lowerLine)) {
                // Extract amount from this line
                if (preg_match('/[\$£€₹¥]?\s*(\d{1,10}[,.]?\d{0,3}\.\d{2})\b/', $line, $matches)) {
                    $amount = (float)preg_replace('/[^\d.]/', '', $matches[1]);
                    if ($amount >= 0.01 && $amount <= 999999.99) {
                        $totalLines[] = [
                            'amount' => $amount,
                            'position' => $index  // Line number = vertical position
                        ];
                    }
                }
            }
        }
        
        // Return the amount from the LAST (lowest/bottom-most) total line
        if (!empty($totalLines)) {
            // Sort by position descending (highest index = lowest on screen)
            usort($totalLines, function($a, $b) {
                return $b['position'] - $a['position'];
            });
            
            return $totalLines[0]['amount'];
        }
        
        // Fallback: no "total" found, return largest amount
        if (preg_match_all('/[\$£€₹¥]?\s*(\d{1,10}[,.]?\d{0,3}\.\d{2})\b/', $text, $matches)) {
            $amounts = array_map(function($m) {
                return (float)preg_replace('/[^\d.]/', '', $m);
            }, $matches[1]);
            
            $amounts = array_filter($amounts, function($a) {
                return $a >= 0.01 && $a <= 999999.99;
            });
            
            if (!empty($amounts)) {
                return max($amounts);
            }
        }
        
        return null;
    }
    
    /**
     * Extract date from text
     */
    private function extractDate($text) {
        $patterns = [
            // MM/DD/YYYY or DD/MM/YYYY
            '/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/',
            // YYYY-MM-DD
            '/(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})/',
            // Month DD, YYYY
            '/(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\s+(\d{1,2}),?\s+(\d{4})/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                try {
                    // Try to parse date
                    $dateStr = $matches[0];
                    $timestamp = strtotime($dateStr);
                    
                    if ($timestamp !== false && $timestamp > 0) {
                        return date('Y-m-d', $timestamp);
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
        }
        
        return date('Y-m-d'); // Default to today
    }
    
    /**
     * Extract merchant/vendor name (usually first few lines)
     */
    private function extractMerchant($text) {
        $lines = explode("\n", $text);
        
        // Look for merchant name in first 3 lines
        for ($i = 0; $i < min(3, count($lines)); $i++) {
            $line = trim($lines[$i]);
            
            // Skip lines that are only numbers or very short
            if (strlen($line) < 3 || preg_match('/^\d+$/', $line)) {
                continue;
            }
            
            // Skip lines that look like dates
            if (preg_match('/\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/', $line)) {
                continue;
            }
            
            // This is likely the merchant name
            return $line;
        }
        
        return null;
    }
    
    /**
     * Extract line items from receipt
     */
    private function extractItems($text) {
        $items = [];
        $lines = explode("\n", $text);
        
        foreach ($lines as $line) {
            // Look for lines with item description and price
            // Pattern: "Item Name ... $XX.XX" or "Item Name XX.XX"
            if (preg_match('/^(.+?)\s+[\$£€₹¥]?\s*(\d+\.\d{2})$/', trim($line), $matches)) {
                $itemName = trim($matches[1]);
                $itemPrice = (float)$matches[2];
                
                // Filter out lines that are totals or subtotals
                if (!preg_match('/(total|subtotal|tax|tip|amount)/i', $itemName)) {
                    $items[] = [
                        'name' => $itemName,
                        'price' => $itemPrice
                    ];
                }
            }
        }
        
        return $items;
    }
    
    /**
     * Suggest category based on merchant name
     * 
     * @param string $merchant Merchant/vendor name
     * @param array $userCategories Array of user's categories with id, name, type
     * @return int|null Category ID or null if no match
     */
    public function suggestCategory($merchant, $userCategories) {
        if (empty($merchant) || empty($userCategories)) {
            return null;
        }
        
        $merchant = strtolower($merchant);
        
        // Category keywords mapping - maps keywords to category types
        $categoryKeywords = [
            'food' => ['restaurant', 'cafe', 'coffee', 'pizza', 'burger', 'food', 'diner', 'grill', 'kitchen', 'bistro', 'mcdonald', 'subway', 'starbucks', 'kfc', 'taco', 'wendy', 'domino', 'chipotle', 'panera', 'chick-fil-a', 'dunkin', 'sonic', 'arbys', 'popeyes'],
            'grocery' => ['walmart', 'costco', 'target', 'safeway', 'kroger', 'publix', 'aldi', 'whole foods', 'trader joe', 'stop & shop', 'food lion', 'market', 'grocery', 'supermarket', 'supercenter'],
            'gas' => ['gas', 'fuel', 'shell', 'chevron', 'exxon', 'bp', 'mobil', 'petroleum', 'station', '76', 'arco', 'sunoco', 'marathon', 'citgo', 'valero', 'phillips 66'],
            'transport' => ['uber', 'lyft', 'taxi', 'transit', 'metro', 'bus', 'train', 'parking', 'toll', 'subway', 'mta'],
            'shopping' => ['amazon', 'ebay', 'mall', 'store', 'shop', 'retail', 'fashion', 'clothing', 'nike', 'adidas', 'gap', 'old navy', 'macys', 'nordstrom', 'kohls', 'tj maxx', 'ross', 'marshalls', 'best buy'],
            'entertainment' => ['cinema', 'theater', 'movie', 'netflix', 'spotify', 'game', 'entertainment', 'amc', 'regal', 'xbox', 'playstation', 'steam'],
            'healthcare' => ['pharmacy', 'hospital', 'clinic', 'medical', 'doctor', 'cvs', 'walgreens', 'rite aid', 'health', 'dental', 'urgent care'],
            'utilities' => ['electric', 'water', 'internet', 'phone', 'utility', 'telecom', 'verizon', 'att', 'comcast', 'spectrum', 'tmobile', 'sprint'],
            'home' => ['home depot', 'lowes', 'ikea', 'furniture', 'hardware', 'home improvement', 'bed bath', 'wayfair']
        ];
        
        // Try to match merchant with keywords
        $bestMatch = null;
        $bestScore = 0;
        $matchedType = null;
        
        // First, find which category type the merchant belongs to
        foreach ($categoryKeywords as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($merchant, $keyword) !== false) {
                    $matchedType = $type;
                    $bestScore = 85; // High score for keyword match
                    break 2;
                }
            }
        }
        
        // Now match against user's categories
        foreach ($userCategories as $category) {
            $categoryName = strtolower($category['name']);
            $score = 0;
            
            // Direct merchant name match in category name
            if (strpos($merchant, $categoryName) !== false || strpos($categoryName, $merchant) !== false) {
                $score = 100;
            }
            // If we found a type match, check if category name contains that type
            else if ($matchedType && strpos($categoryName, $matchedType) !== false) {
                $score = 90;
            }
            // Check if any keyword from the matched type appears in category name
            else if ($matchedType && isset($categoryKeywords[$matchedType])) {
                foreach ($categoryKeywords[$matchedType] as $keyword) {
                    if (strpos($categoryName, $keyword) !== false) {
                        $score = 85;
                        break;
                    }
                }
            }
            
            // Update best match
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $category['id'];
            }
        }
        
        // Return match if confidence is high enough (lowered threshold)
        return ($bestScore >= 75) ? $bestMatch : null;
    }
}

?>
