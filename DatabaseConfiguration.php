<?php
// Minimal PDO database configuration and connection helper
// Adjust credentials as needed for your local MySQL

$DB_HOST = 'localhost';
$DB_NAME = 'expense_tracker';
$DB_USER = 'root';
$DB_PASS = '';

/**
 * Returns a shared PDO connection (singleton per request).
 * Exits with a simple message if connection fails (minimalism over robustness).
 */
function db() {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // Use globals defined above
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;
    $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (Exception $e) {
        http_response_code(500);
        echo '<!DOCTYPE html><html><body>';
        echo '<div style="max-width:700px;margin:40px auto;font-family:system-ui,Segoe UI,Arial;">';
        echo '<h2>Database connection failed</h2>';
        echo '<p>Please create the database and import install.sql, then update credentials in DatabaseConfiguration.php.</p>';
        echo '<pre style="background:#f6f6f6;padding:10px;border-radius:6px;white-space:pre-wrap;">' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '</div></body></html>';
        exit;
    }
}

?>
