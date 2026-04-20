<?php
/**
 * AttendEase Pro - FCM Token Persistence
 */
require_once __DIR__ . '/config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$token = $data['token'] ?? null;

if (!$token) {
    echo json_encode(['success' => false, 'message' => 'Token missing']);
    exit;
}

try {
    $db = get_db_connection();
    $stmt = $db->prepare("UPDATE users SET fcm_token = ? WHERE id = ?");
    $stmt->execute([$token, $_SESSION['user_id']]);

    echo json_encode(['success' => true, 'message' => 'Token saved']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error']);
}
