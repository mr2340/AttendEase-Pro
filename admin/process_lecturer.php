<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

if (!AttendEaseSecurity::validateCsrf($_POST['csrf_token'] ?? '')) {
    echo json_encode(['status' => 'error', 'message' => 'CSRF validation failed. Please refresh.']);
    exit;
}

$fullname = trim($_POST['fullname'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($fullname) || empty($username) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Full Name, Username, and Password are required.']);
    exit;
}

$db = get_db_connection();

// Check if username already exists
$stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    echo json_encode(['status' => 'error', 'message' => 'Username is already taken.']);
    exit;
}

// Check if email already exists (if provided)
if (!empty($email)) {
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Email address is already registered.']);
        exit;
    }
}

try {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (username, fullname, email, password, role) VALUES (?, ?, ?, ?, 'lecturer')");
    $stmt->execute([
        $username,
        $fullname,
        empty($email) ? null : $email,
        $hashed_password
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Lecturer account provisioned successfully.']);
} catch (PDOException $e) {
    error_log("Database Error in process_lecturer: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An internal database error occurred.']);
}
