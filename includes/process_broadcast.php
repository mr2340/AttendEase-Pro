<?php
/**
 * AttendEase Pro - Broadcast Intelligence Processor
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/notifications.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'lecturer' && $_SESSION['role'] !== 'admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// CSRF Validation
$csrf_header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
// Note: If security.php isn't auto-included in config.php, we might need it.
// Checking config.php...
if (!class_exists('AttendEaseSecurity')) {
    require_once __DIR__ . '/security.php';
}

AttendEaseSecurity::validateCsrf($csrf_header);

$data = json_decode(file_get_contents('php://input'), true);

$course_id = $data['course_id'] ?? null;
$title = $data['title'] ?? '';
$message = $data['message'] ?? '';
$lecturer_id = $_SESSION['user_id'];

if (!$course_id || !$title || !$message) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

try {
    $db = get_db_connection();
    
    // 1. Save to Database
    $stmt = $db->prepare("INSERT INTO announcements (course_id, lecturer_id, title, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$course_id, $lecturer_id, $title, $message]);
    
    // 2. Trigger Pulse Broadcast
    $broadcast = NotificationEngine::broadcastToCourse($course_id, $title, $message);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Broadcast deployed successfully!',
        'stats' => $broadcast
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
}
