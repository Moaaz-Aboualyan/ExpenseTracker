<?php
/**
 * Application Configuration
 * Loads from .env file only (simple and direct)
 */

// Load .env file - simple and reliable
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Remove quotes if present
            $value = trim($value, '\'"');
            if (!defined($key)) {
                define($key, $value);
            }
        }
    }
}

// Set defaults if not defined
if (!defined('GOOGLE_VISION_API_KEY')) {
    define('GOOGLE_VISION_API_KEY', null);
}
if (!defined('UPLOAD_MAX_SIZE')) {
    define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB default
}
if (!defined('UPLOAD_ALLOWED_TYPES')) {
    define('UPLOAD_ALLOWED_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']);
}
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', __DIR__ . '/uploads/receipts/');
}

// Ensure upload directory exists
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

?>
