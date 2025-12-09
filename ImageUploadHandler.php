<?php
/**
 * Image Upload Handler
 * Handles file upload validation, processing, and OCR extraction
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/GoogleVisionOCR.php';

class ImageUploadHandler {
    
    private $uploadDir;
    private $maxSize;
    private $allowedTypes;
    private $ocr;
    private $apiKeyError = null;
    
    public function __construct() {
        $this->uploadDir = UPLOAD_DIR;
        $this->maxSize = UPLOAD_MAX_SIZE;
        $this->allowedTypes = UPLOAD_ALLOWED_TYPES;
        
        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
        
        // Try to initialize OCR, but don't fail if API key isn't set
        try {
            $this->ocr = new GoogleVisionOCR();
        } catch (Exception $e) {
            $this->apiKeyError = $e->getMessage();
            $this->ocr = null;
        }
    }
    
    /**
     * Process uploaded receipt image
     * 
     * @param array $file $_FILES array element
     * @param int $userId User ID for category suggestion
     * @param PDO $pdo Database connection for fetching categories
     * @return array Success/error status and extracted data
     */
    public function processUpload($file, $userId = null, $pdo = null) {
        $response = [
            'success' => false,
            'message' => '',
            'data' => null,
            'filename' => null
        ];
        
        // Check if API key was configured during initialization
        if ($this->apiKeyError !== null) {
            $response['success'] = false;
            $response['message'] = $this->apiKeyError . '. Please configure GOOGLE_VISION_API_KEY in .env file.';
            return $response;
        }
        
        // Validate upload
        $validation = $this->validateUpload($file);
        if (!$validation['valid']) {
            $response['message'] = $validation['error'];
            return $response;
        }
        
        // Generate unique filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = $this->generateFilename($extension);
        $filepath = $this->uploadDir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            $response['message'] = 'Failed to save uploaded file';
            return $response;
        }
        
        // Process with OCR
        try {
            if ($this->ocr === null) {
                throw new Exception('OCR service not available - API key not configured');
            }
            
            $ocrData = $this->ocr->processReceipt($filepath);
            
            // Suggest category if user ID and PDO provided
            if ($userId && $pdo && !empty($ocrData['merchant'])) {
                $userCategories = $this->getUserCategories($userId, $pdo);
                $suggestedCategoryId = $this->ocr->suggestCategory($ocrData['merchant'], $userCategories);
                if ($suggestedCategoryId) {
                    $ocrData['suggested_category'] = $suggestedCategoryId;
                }
            }
            
            $response['success'] = true;
            $response['message'] = 'Receipt processed successfully';
            $response['data'] = $ocrData;
            $response['filename'] = $filename;
            
        } catch (Exception $e) {
            // Keep the image but return error
            $response['success'] = false;
            $response['message'] = 'OCR processing failed: ' . $e->getMessage();
            $response['filename'] = $filename;
            
            // Delete the file if OCR fails
            unlink($filepath);
        }
        
        return $response;
    }
    
    /**
     * Get user's categories for suggestion
     */
    private function getUserCategories($userId, $pdo) {
        try {
            $stmt = $pdo->prepare('SELECT id, name, type FROM categories WHERE user_id = ? ORDER BY name');
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Validate uploaded file
     */
    private function validateUpload($file) {
        $result = ['valid' => false, 'error' => ''];
        
        // Check for upload errors
        if (!isset($file['error']) || is_array($file['error'])) {
            $result['error'] = 'Invalid file upload';
            return $result;
        }
        
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $result['error'] = 'File exceeds maximum size limit';
                return $result;
            case UPLOAD_ERR_NO_FILE:
                $result['error'] = 'No file was uploaded';
                return $result;
            default:
                $result['error'] = 'Unknown upload error';
                return $result;
        }
        
        // Check file size
        if ($file['size'] > $this->maxSize) {
            $result['error'] = 'File size exceeds ' . ($this->maxSize / 1024 / 1024) . 'MB limit';
            return $result;
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            $result['error'] = 'Invalid file type. Only JPEG, PNG, and WEBP images are allowed';
            return $result;
        }
        
        // Check if it's actually an image
        if (@getimagesize($file['tmp_name']) === false) {
            $result['error'] = 'File is not a valid image';
            return $result;
        }
        
        $result['valid'] = true;
        return $result;
    }
    
    /**
     * Generate unique filename
     */
    private function generateFilename($extension) {
        return 'receipt_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    }
    
    /**
     * Delete uploaded file
     */
    public function deleteFile($filename) {
        $filepath = $this->uploadDir . $filename;
        
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }
    
    /**
     * Get file URL for display
     */
    public function getFileUrl($filename) {
        return 'uploads/receipts/' . $filename;
    }
}

?>
