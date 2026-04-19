<?php
/**
 * AttendEase Pro - Attendance Processor
 */
require_once __DIR__ . '/config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get JSON Input
$data = json_decode(file_get_contents('php://input'), true);
$session_id = $data['session_id'] ?? null;

if (!$session_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid QR Code.']);
    exit;
}

try {
    $db = get_db_connection();
    $user_id = $_SESSION['user_id'];

    // 1. Verify if the session exists and is active
    $stmt = $db->prepare("SELECT * FROM sessions WHERE id = ? AND status = 'active'");
    $stmt->execute([$session_id]);
    $session = $stmt->fetch();

    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired session.']);
        exit;
    }

    // 2. Check if student already marked attendance for this session
    $stmt = $db->prepare("SELECT * FROM attendance WHERE session_id = ? AND student_id = ?");
    $stmt->execute([$session_id, $user_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Attendance already marked for this session.']);
        exit;
    }

    // 3. Mark Attendance
    $stmt = $db->prepare("INSERT INTO attendance (session_id, student_id, status) VALUES (?, ?, 'present')");
    $stmt->execute([$session_id, $user_id]);

    // 4. Create Notification
    $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Attendance Marked', 'You have successfully marked attendance for class.')");
    $stmt->execute([$user_id]);

    echo json_encode(['success' => true, 'message' => 'Attendance marked successfully!']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
