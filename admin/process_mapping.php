<?php
require_once '../includes/config.php';
session_start();

header('Content-Type: application/json');

// Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin escalation required.']);
    exit;
}

$db = get_db_connection();
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

if ($method === 'POST') {
    $parent_id = $data['parent_id'] ?? null;
    $student_id = $data['student_id'] ?? null;

    if (!$parent_id || !$student_id) {
        echo json_encode(['success' => false, 'message' => 'Missing node identifiers.']);
        exit;
    }

    try {
        // Check if mapping already exists
        $stmt = $db->prepare("SELECT id FROM parent_student_map WHERE parent_id = ? AND student_id = ?");
        $stmt->execute([$parent_id, $student_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Association already exists in infrastructure.']);
            exit;
        }

        $stmt = $db->prepare("INSERT INTO parent_student_map (parent_id, student_id) VALUES (?, ?)");
        $stmt->execute([$parent_id, $student_id]);
        echo json_encode(['success' => true, 'message' => 'Association sealed successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Infrastructure error: ' . $e->getMessage()]);
    }
} else if ($method === 'DELETE') {
    $id = $data['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Missing record identifier.']);
        exit;
    }

    try {
        $stmt = $db->prepare("DELETE FROM parent_student_map WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Association severed.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'De-linking error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not supported.']);
}
