<?php
require_once '../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if (!AttendEaseSecurity::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['status' => 'error', 'message' => 'Security token verification failed']);
    exit;
}

$db = get_db_connection();
$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $student_id = trim($_POST['student_id'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($student_id) || empty($fullname) || empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    try {
        // Check if student ID or username already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR student_id = ?");
        $stmt->execute([$username, $student_id]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Username or Matric Number already exists']);
            exit;
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (student_id, fullname, username, password, role) VALUES (?, ?, ?, ?, 'student')");
        $stmt->execute([$student_id, $fullname, $username, $hashed]);

        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
} elseif ($action === 'update') {
    $id = $_POST['id'] ?? 0;
    $student_id = trim($_POST['student_id'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($id) || empty($student_id) || empty($fullname) || empty($username)) {
        echo json_encode(['status' => 'error', 'message' => 'Required fields missing.']);
        exit;
    }

    try {
        // Check if student ID or username already exists for OTHER users
        $stmt = $db->prepare("SELECT id FROM users WHERE (username = ? OR student_id = ?) AND id != ?");
        $stmt->execute([$username, $student_id, $id]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Username or Matric Number already exists for another user.']);
            exit;
        }

        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET student_id = ?, fullname = ?, username = ?, password = ? WHERE id = ? AND role = 'student'");
            $stmt->execute([$student_id, $fullname, $username, $hashed, $id]);
        } else {
            $stmt = $db->prepare("UPDATE users SET student_id = ?, fullname = ?, username = ? WHERE id = ? AND role = 'student'");
            $stmt->execute([$student_id, $fullname, $username, $id]);
        }

        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
} elseif ($action === 'delete') {
    $id = $_POST['id'] ?? 0;
    try {
        // Only allow deleting students
        $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
