<?php
/**
 * AttendEase Pro - Real-time Session Intel
 * Returns current attendee count for the polling service
 */
require_once __DIR__ . '/config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$session_id = $_GET['session_id'] ?? null;

if (!$session_id) {
    echo json_encode(['success' => false, 'message' => 'Missing Session ID']);
    exit;
}

try {
    $db = get_db_connection();
    
    // Verify ownership and get count in one efficient query
    $stmt = $db->prepare("
        SELECT 
            (SELECT COUNT(*) FROM attendance WHERE session_id = ?) as attendee_count,
            lecturer_id
        FROM sessions 
        WHERE id = ? 
        LIMIT 1
    ");
    $stmt->execute([$session_id, $session_id]);
    $result = $stmt->fetch();

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Session not found']);
        exit;
    }

    // Security check: Only the lecturer who created the session can see the stats
    if ($result['lecturer_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Security Access Denied']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'count' => (int)$result['attendee_count']
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
