<?php
/**
 * AttendEase Pro - Secure QR Token Generator
 */
require_once __DIR__ . '/config.php';
// session_start() is already called in config.php -> security.php


header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'lecturer' && $_SESSION['role'] !== 'admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$session_id = $_GET['session_id'] ?? null;

if (!$session_id) {
    echo json_encode(['success' => false, 'message' => 'Missing session ID']);
    exit;
}

try {
    $db = get_db_connection();
    $stmt = $db->prepare("SELECT status FROM sessions WHERE id = ?");
    $stmt->execute([$session_id]);
    $session = $stmt->fetch();

    if (!$session || $session['status'] !== 'active') {
        echo json_encode(['success' => false, 'message' => 'Session is not active (Paused or Closed)']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$block = floor(time() / 30);
$raw_token = $session_id . ":" . $block;
$hmac = hash_hmac('sha256', $raw_token, SECURE_KEY);

// Combine for exchange: session_id : block : hmac
$final_token = base64_encode($session_id . ":" . $block . ":" . $hmac);

echo json_encode(['success' => true, 'token' => $final_token]);
?>
