<?php
/**
 * AttendEase Pro - Login Processor
 */
require_once __DIR__ . '/config.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $_POST['identifier'] ?? ''; // Can be student_id or username
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all fields.']);
        exit;
    }

    $db = get_db_connection();
    $stmt = $db->prepare("SELECT * FROM users WHERE student_id = ? OR username = ? LIMIT 1");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Login success
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        echo json_encode(['success' => true, 'message' => 'Login successful!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
