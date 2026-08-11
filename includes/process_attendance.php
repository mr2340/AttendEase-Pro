<?php
/**
 * AttendEase Pro - High Integrity Attendance Processor
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/GeoEngine.php';
// AttendEaseSecurity handles session_start safely

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login.']);
    exit;
}

// 0. CSRF Validation
$csrf_header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
AttendEaseSecurity::validateCsrf($csrf_header);

$data = json_decode(file_get_contents('php://input'), true);
$token = $data['session_id'] ?? null;
$student_lat = $data['lat'] ?? null;
$student_lng = $data['lng'] ?? null;

if (!$token) {
    echo json_encode(['success' => false, 'message' => 'Integrity Check Failed: Missing Token.']);
    exit;
}

// --- SECURE DECODING & ROUTING ---
try {
    $db = get_db_connection();
    $user_id = $_SESSION['user_id'];

    // Check if it's a Permanent Course Token
    if (strpos($token, 'COURSE_') === 0) {
        $stmt = $db->prepare("
            SELECT s.id 
            FROM sessions s 
            JOIN courses c ON s.course_id = c.id 
            WHERE c.permanent_token = ? AND s.status = 'active'
            ORDER BY s.created_at DESC LIMIT 1
        ");
        $stmt->execute([$token]);
        $session_row = $stmt->fetch();
        
        if (!$session_row) {
            echo json_encode(['success' => false, 'message' => 'No active attendance session found for this course. Please ask the lecturer to start a session.']);
            exit;
        }
        $session_id = $session_row['id'];
    } else {
        // Standard Secure Token Decoding
        $decoded = base64_decode($token);
        $parts = explode(':', $decoded);
        
        if (count($parts) !== 3) {
            throw new Exception("Malformed Secure Token");
        }
        
        list($session_id, $client_block, $client_hmac) = $parts;
        
        // 1. Verify Signature Integrity
        $expected_hmac = hash_hmac('sha256', $session_id . ":" . $client_block, SECURE_KEY);
        if (!hash_equals($expected_hmac, $client_hmac)) {
            throw new Exception("Security Alert: QR Signature Mismatch");
        }

        // 2. Verify Time Window expiration
        $current_block = floor(time() / 30);
        // Allow ±2 blocks (60s window) for better reliability against clock drift
        if (abs($current_block - $client_block) > 2) {
            echo json_encode(['success' => false, 'message' => 'QR EXPIRED: This code is no longer valid. Please scan the current live QR.']);
            exit;
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Security Error: ' . $e->getMessage()]);
    exit;
}

try {
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

    // 2.1 GEO-FENCING SECURITY CHECK (NEW)
    if (!empty($session['latitude']) && !empty($session['longitude'])) {
        if (empty($student_lat) || empty($student_lng)) {
            echo json_encode(['success' => false, 'message' => 'LOCATION REQUIRED: Please enable GPS and try again to verify you are in the classroom.']);
            exit;
        }

        if (!GeoEngine::isWithinRange($student_lat, $student_lng, $session['latitude'], $session['longitude'], 50)) {
            echo json_encode(['success' => false, 'message' => 'OUT OF BOUNDS: You must be physically present in the classroom to mark attendance.']);
            exit;
        }
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
    $stmt = $db->prepare("INSERT INTO attendance (session_id, student_id, status, latitude, longitude) VALUES (?, ?, 'present', ?, ?)");
    $stmt->execute([$session_id, $user_id, $student_lat, $student_lng]);

    // 5.1 Auto-Enrollment logic (New)
    // Ensures students are tracked in courses they attend for accurate StatEngine reports
    $stmt = $db->prepare("INSERT IGNORE INTO enrollments (student_id, course_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $session['course_id']]);

    // 6. Push Notification Integration (New)
    require_once __DIR__ . '/notifications.php';
    NotificationEngine::notifyAttendanceMarked($user_id, $session['course_name'] ?? 'Class');

    // 7. Admin Notification (New)
    // Fetch student username
    $stmtUser = $db->prepare("SELECT username FROM users WHERE id = ?");
    $stmtUser->execute([$user_id]);
    $username = $stmtUser->fetchColumn();
    $adminTitle = "New Attendance Scan";
    $adminMsg = "Student " . ($username ? $username : $user_id) . " scanned QR for Session " . $session_id;
    $stmtAdmin = $db->prepare("INSERT INTO admin_notifications (title, message) VALUES (?, ?)");
    $stmtAdmin->execute([$adminTitle, $adminMsg]);

    echo json_encode(['success' => true, 'message' => 'Verified: Attendance marked successfully!']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Infrastructure error: ' . $e->getMessage()]);
}
