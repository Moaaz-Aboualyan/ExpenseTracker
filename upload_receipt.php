<?php
/**
 * AJAX Handler for Receipt Image Upload
 */

// Set error handling to prevent output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start output buffering to catch any stray output
ob_start();

session_start();

// Clear any buffered output
ob_clean();

// Set JSON header early
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/ImageUploadHandler.php';
    require_once __DIR__ . '/DatabaseConfiguration.php';
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized access'
        ]);
        exit();
    }
    
    $userId = (int)$_SESSION['user_id'];
    
    // Check if file was uploaded
    if (!isset($_FILES['receipt_image'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No file uploaded'
        ]);
        exit();
    }
    
    $handler = new ImageUploadHandler();
    $pdo = db();
    $result = $handler->processUpload($_FILES['receipt_image'], $userId, $pdo);
    
    // Add image URL if successful
    if ($result['success'] && $result['filename']) {
        $result['image_url'] = $handler->getFileUrl($result['filename']);
    }
    
    // Return appropriate HTTP status
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
    
} catch (Throwable $e) {
    // Log the error but don't expose details
    error_log('Upload error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error processing receipt. Please try again or check logs.'
    ]);
}

?>
