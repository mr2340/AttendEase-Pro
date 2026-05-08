<?php
/**
 * AttendEase Pro - Advanced Session Creator
 */
require_once __DIR__ . '/config.php';
session_start();

header('Content-Type: application/json');

// Auth Check - Allow Lecturers and Admins
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'lecturer' && $_SESSION['role'] !== 'admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$course_id = $data['course_id'] ?? null;
$topic = $data['topic'] ?? 'General Session';
$duration = isset($data['duration']) ? (int)$data['duration'] : 30; // Minutes
$scan_limit = isset($data['scan_limit']) ? (int)$data['scan_limit'] : 0; // 0 = Unlimited
$lat = $data['lat'] ?? null;
$lng = $data['lng'] ?? null;

if (!$course_id) {
    echo json_encode(['success' => false, 'message' => 'Course ID is required']);
    exit;
}

try {
    $db = get_db_connection();
    $lecturer_id = $_SESSION['user_id'];
    // 1. Verify Course Ownership
    $stmt = $db->prepare("SELECT id FROM courses WHERE id = ? AND lecturer_id = ?");
    $stmt->execute([$course_id, $lecturer_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized: You do not have permission to manage this course.']);
        exit;
    }

    // 2. Calculate expiration and current timestamp
    $now = date('Y-m-d H:i:s');
    $expires_at = null;
    if ($duration > 0) {
        $expires_at = date('Y-m-d H:i:s', strtotime("+$duration minutes"));
    }

    // 3. Create New Session
    $stmt = $db->prepare("INSERT INTO sessions 
        (course_id, topic, lecturer_id, status, scan_limit, expires_at, session_date, created_at, latitude, longitude) 
        VALUES (?, ?, ?, 'active', ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $course_id, 
        $topic, 
        $lecturer_id, 
        $scan_limit, 
        $expires_at, 
        $now, // session_date
        $now, // created_at
        $lat, 
        $lng
    ]);
    
    $session_id = $db->lastInsertId();

    echo json_encode([
        'success' => true, 
        'session_id' => $session_id,
        'message' => 'Node successfully initialized on the academic matrix.'
    ]);

} catch (PDOException $e) {
    error_log("AttendEase Session Creation Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database integrity error. Check logs.']);
}
