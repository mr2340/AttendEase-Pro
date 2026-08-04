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

$course_code = strtoupper(trim($_POST['course_code'] ?? ''));
$course_name = trim($_POST['course_name'] ?? '');
$lecturer_id = $_POST['lecturer_id'] ?? '';

if (empty($course_code) || empty($course_name) || empty($lecturer_id)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}

$db = get_db_connection();

// Check if course code already exists
$stmt = $db->prepare("SELECT id FROM courses WHERE course_code = ?");
$stmt->execute([$course_code]);
if ($stmt->fetch()) {
    echo json_encode(['status' => 'error', 'message' => 'Course code is already mapped in the system.']);
    exit;
}

try {
    $stmt = $db->prepare("INSERT INTO courses (course_code, course_name, lecturer_id) VALUES (?, ?, ?)");
    $stmt->execute([$course_code, $course_name, $lecturer_id]);

    echo json_encode(['status' => 'success', 'message' => 'Course successfully created and assigned.']);
} catch (PDOException $e) {
    error_log("Database Error in process_course: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An internal database error occurred.']);
}
