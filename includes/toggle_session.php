<?php
header('Content-Type: application/json');
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$session_id = $data['session_id'] ?? null;
$action = $data['action'] ?? ''; // 'pause', 'resume', 'close', 'delete'

if (!$session_id || !in_array($action, ['pause', 'resume', 'close', 'delete'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $db = get_db_connection();
    
    // Auth Check: Ensure session belongs to this lecturer
    $stmt = $db->prepare("SELECT lecturer_id FROM sessions WHERE id = ?");
    $stmt->execute([$session_id]);
    $session = $stmt->fetch();
    
    if (!$session || $session['lecturer_id'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    if ($action === 'delete') {
        $db->prepare("DELETE FROM attendance WHERE session_id = ?")->execute([$session_id]);
        $db->prepare("DELETE FROM sessions WHERE id = ?")->execute([$session_id]);
        echo json_encode(['success' => true, 'message' => 'Session deleted']);
        exit;
    }

    $new_status = 'active';
    if ($action === 'pause') $new_status = 'paused';
    if ($action === 'close') $new_status = 'closed';

    $stmt = $db->prepare("UPDATE sessions SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $session_id]);

    echo json_encode(['success' => true, 'new_status' => $new_status]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
