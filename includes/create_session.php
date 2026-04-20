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

if (!$course_id) {
    echo json_encode(['success' => false, 'message' => 'Course ID is required']);
    exit;
}

try {
    $db = get_db_connection();
    $lecturer_id = $_SESSION['user_id'];

    // 1. Calculate expiration
    $expires_at = null;
    if ($duration > 0) {
        $expires_at = date('Y-m-d H:i:s', strtotime("+$duration minutes"));
    }

    // 2. Create New Session
    $stmt = $db->prepare("INSERT INTO sessions (course_id, topic, lecturer_id, status, scan_limit, expires_at, created_at) VALUES (?, ?, ?, 'active', ?, ?, NOW())");
    $stmt->execute([$course_id, $topic, $lecturer_id, $scan_limit, $expires_at]);
    
    $session_id = $db->lastInsertId();

    echo json_encode([
        'success' => true, 
        'session_id' => $session_id,
        'message' => 'Session initialized successfully'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
