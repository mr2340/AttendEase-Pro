<?php
/**
 * AttendEase Pro - Configuration File
 */
date_default_timezone_set('Africa/Lagos'); // Synchronized with User Metadata

// Production Environment Detection
define('IS_PRODUCTION', false); // Toggle to false for dev

if (IS_PRODUCTION) {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Load .env file
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        // Strip quotes if they exist
        if (preg_match('/^"(.*)"$/', $value, $matches) || preg_match("/^'(.*)'$/", $value, $matches)) {
            $value = $matches[1];
        }
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}
loadEnv(__DIR__ . '/../.env');

// Helper to safely get env variables across all server configurations
function env($key, $default = '') {
    if (isset($_ENV[$key])) return $_ENV[$key];
    if (isset($_SERVER[$key])) return $_SERVER[$key];
    $val = getenv($key);
    return $val !== false ? $val : $default;
}

// Database Configuration
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'attendease_db'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

// App Configuration
define('APP_NAME', env('APP_NAME', 'AttendEase Pro'));

// Dynamically determine BASE_URL based on the request host to prevent connection timeouts when the IP changes.
if (php_sapi_name() === 'cli') {
    define('BASE_URL', 'http://127.0.0.1/sodex/');
} else {
    $scheme = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1) || 
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', $scheme . '://' . $host . '/sodex/');
}

// Security Settings
define('HASH_ALGO', PASSWORD_ARGON2ID);
define('SECURE_KEY', env('SECURE_KEY', '8f9e1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f'));

// FCM Configuration
define('FCM_SERVICE_ACCOUNT', __DIR__ . '/../service-account.json');
define('FCM_API_KEY', env('FCM_API_KEY'));
define('FCM_AUTH_DOMAIN', env('FCM_AUTH_DOMAIN'));
define('FCM_PROJECT_ID', env('FCM_PROJECT_ID'));
define('FCM_STORAGE_BUCKET', env('FCM_STORAGE_BUCKET'));
define('FCM_MESSAGING_SENDER_ID', env('FCM_MESSAGING_SENDER_ID'));
define('FCM_APP_ID', env('FCM_APP_ID'));
define('FCM_MEASUREMENT_ID', env('FCM_MEASUREMENT_ID'));
define('FCM_VAPID_KEY', env('FCM_VAPID_KEY'));
// Cloudinary Configuration
define('CLOUDINARY_CLOUD_NAME', env('CLOUDINARY_CLOUD_NAME'));
define('CLOUDINARY_UPLOAD_PRESET', env('CLOUDINARY_UPLOAD_PRESET'));
define('CLOUDINARY_API_KEY', env('CLOUDINARY_API_KEY'));
define('CLOUDINARY_API_SECRET', env('CLOUDINARY_API_SECRET'));

/**
 * Database Connection using PDO
 */
function get_db_connection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
// Connect
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Initialize Security & Rate Limiting
require_once __DIR__ . '/security.php';
?>
