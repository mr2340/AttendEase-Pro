<?php
/**
 * AttendEase Pro - Cloudinary Signature Generator
 * For Secure Signed Uploads without Presets
 */
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Security check: Only authenticated users
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get the parameters to sign from the request
$input = file_get_contents('php://input');
$params = json_decode($input, true);

if (!$params || !isset($params['params_to_sign'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters to sign']);
    exit;
}

$params_to_sign = $params['params_to_sign'];
$api_secret = CLOUDINARY_API_SECRET;

if (empty($api_secret)) {
    http_response_code(500);
    echo json_encode(['error' => 'Cloudinary API Secret not configured']);
    exit;
}

// Cloudinary signature logic:
// 1. Sort parameters alphabetically
// 2. Concatenate as key=value pairs separated by &
// 3. Append API Secret
// 4. SHA1 hash the result
ksort($params_to_sign);

$sign_string = [];
foreach ($params_to_sign as $key => $value) {
    if ($value === '') continue;
    $sign_string[] = "$key=$value";
}

$sign_string = implode('&', $sign_string);
$signature = sha1($sign_string . $api_secret);

echo json_encode(['signature' => $signature]);
