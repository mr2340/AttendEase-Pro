<?php
/**
 * AttendEase Pro - Configuration File
 */

// Production Environment Detection
define('IS_PRODUCTION', true); // Toggle to false for dev

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

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'attendease_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// App Configuration
define('APP_NAME', getenv('APP_NAME') ?: 'AttendEase Pro');
define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost/sodex/');

// Security Settings
define('HASH_ALGO', PASSWORD_ARGON2ID);
define('SECURE_KEY', getenv('SECURE_KEY') ?: '8f9e1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f');

// FCM Configuration
define('FCM_SERVICE_ACCOUNT', __DIR__ . '/../service-account.json');
define('FCM_API_KEY', getenv('FCM_API_KEY'));
define('FCM_AUTH_DOMAIN', getenv('FCM_AUTH_DOMAIN'));
define('FCM_PROJECT_ID', getenv('FCM_PROJECT_ID'));
define('FCM_STORAGE_BUCKET', getenv('FCM_STORAGE_BUCKET'));
define('FCM_MESSAGING_SENDER_ID', getenv('FCM_MESSAGING_SENDER_ID'));
define('FCM_APP_ID', getenv('FCM_APP_ID'));
define('FCM_MEASUREMENT_ID', getenv('FCM_MEASUREMENT_ID'));
define('FCM_VAPID_KEY', getenv('FCM_VAPID_KEY'));
// Cloudinary Configuration
define('CLOUDINARY_CLOUD_NAME', getenv('CLOUDINARY_CLOUD_NAME'));
define('CLOUDINARY_UPLOAD_PRESET', getenv('CLOUDINARY_UPLOAD_PRESET'));
define('CLOUDINARY_API_KEY', getenv('CLOUDINARY_API_KEY'));
define('CLOUDINARY_API_SECRET', getenv('CLOUDINARY_API_SECRET'));

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
