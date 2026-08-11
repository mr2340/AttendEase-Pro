<?php
require_once '../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!AttendEaseSecurity::verifyCsrfToken($csrf_token)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
    exit;
}

$course_code = trim($_POST['course_code'] ?? '');
$user_id = $_SESSION['user_id'];

if (empty($course_code)) {
    echo json_encode(['status' => 'error', 'message' => 'Course code is required.']);
    exit;
}

try {
    $db = get_db_connection();
    
    // Check if course exists
    $stmt = $db->prepare("SELECT id, course_code FROM courses WHERE course_code = ?");
    $stmt->execute([strtoupper($course_code)]);
    $course = $stmt->fetch();
    
    if (!$course) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid course code.']);
        exit;
    }
    
    $course_id = $course['id'];
    
    // Check if already enrolled
    $stmt = $db->prepare("SELECT student_id FROM enrollments WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$user_id, $course_id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'You are already enrolled in this course.']);
        exit;
    }
    
    // Insert enrollment
    $stmt = $db->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $course_id]);
    
    echo json_encode(['status' => 'success', 'course_code' => $course['course_code']]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
}
