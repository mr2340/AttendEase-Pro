<?php
/**
 * AttendEase Pro - High Integrity Attendance Processor
 */
require_once __DIR__ . '/config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$token = $data['session_id'] ?? null;

if (!$token) {
    echo json_encode(['success' => false, 'message' => 'Integrity Check Failed: Missing Token.']);
    exit;
}

// --- INTELLIGENT DECODING ---
try {
    $decoded = base64_decode($token);
    if (!$decoded || strpos($decoded, ':') === false) {
        throw new Exception("Malformed Token");
    }
    
    list($session_id, $client_block) = explode(':', $decoded);
    $current_block = floor(time() / 30); // 30s rotation
    
    // Allow current block and 1 previous block (30s grace)
    if (abs($current_block - $client_block) > 1) {
        echo json_encode(['success' => false, 'message' => 'QR EXPIRED: This code is no longer valid. Please scan the current live QR.']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Security Error: Invalid Integrity Token.']);
    exit;
}

try {
    $db = get_db_connection();
    $user_id = $_SESSION['user_id'];

    // 1. Verify if the session exists and is active
    $stmt = $db->prepare("SELECT * FROM sessions WHERE id = ?");
    $stmt->execute([$session_id]);
    $session = $stmt->fetch();

    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'System Error: Session not found.']);
        exit;
    }
    
    if ($session['status'] !== 'active') {
        $msg = ($session['status'] === 'paused') ? 'Attendance is currently PAUSED by the lecturer.' : 'This session has been CLOSED.';
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }

    // 2. Check Expiration
    if ($session['expires_at'] && strtotime($session['expires_at']) < time()) {
        echo json_encode(['success' => false, 'message' => 'This class session has officially expired.']);
        exit;
    }

    // 3. Check Scan Limit
    if ($session['scan_limit'] > 0) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM attendance WHERE session_id = ?");
        $stmt->execute([$session_id]);
        if ($stmt->fetchColumn() >= $session['scan_limit']) {
            echo json_encode(['success' => false, 'message' => 'The maximum student capacity for this session has been reached.']);
            exit;
        }
    }

    // 4. Double-marking check
    $stmt = $db->prepare("SELECT * FROM attendance WHERE session_id = ? AND student_id = ?");
    $stmt->execute([$session_id, $user_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Integrity Check: Attendance already recorded.']);
        exit;
    }

    // 5. Finalize Attendance
    $stmt = $db->prepare("INSERT INTO attendance (session_id, student_id, status) VALUES (?, ?, 'present')");
    $stmt->execute([$session_id, $user_id]);

    // 6. Push Notification Integration (New)
    require_once __DIR__ . '/notifications.php';
    NotificationEngine::notifyAttendanceMarked($user_id, $session['course_name'] ?? 'Class');

    echo json_encode(['success' => true, 'message' => 'Verified: Attendance marked successfully!']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Infrastructure error: ' . $e->getMessage()]);
}
