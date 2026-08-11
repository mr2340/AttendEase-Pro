<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!AttendEaseSecurity::verifyCsrfToken($csrf_token)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
    exit;
}

$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$user_id = $_SESSION['user_id'];

if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}

if ($new_password !== $confirm_password) {
    echo json_encode(['status' => 'error', 'message' => 'New passwords do not match.']);
    exit;
}

if (strlen($new_password) < 6) {
    echo json_encode(['status' => 'error', 'message' => 'New password must be at least 6 characters.']);
    exit;
}

try {
    $db = get_db_connection();
    
    // Fetch current user hash
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($current_password, $user['password'])) {
        echo json_encode(['status' => 'error', 'message' => 'Incorrect current password.']);
        exit;
    }
    
    // Hash new password
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password
    $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $updateStmt->execute([$new_hash, $user_id]);
    
    echo json_encode(['status' => 'success', 'message' => 'Password updated successfully.']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
}
