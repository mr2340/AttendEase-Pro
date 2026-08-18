<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$student_reg_id = $data['student_id'] ?? null;

if (!$student_reg_id) {
    echo json_encode(['success' => false, 'message' => 'Matric Number is required']);
    exit;
}

try {
    $db = get_db_connection();
    $parent_id = $_SESSION['user_id'];

    // 1. Find the student by their registration ID
    $stmt = $db->prepare("SELECT id FROM users WHERE student_id = ? AND role = 'student'");
    $stmt->execute([$student_reg_id]);
    $student = $stmt->fetch();

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'No student found with that Registration ID.']);
        exit;
    }

    $student_id = $student['id'];

    // 2. Check if already linked
    $stmt = $db->prepare("SELECT id FROM parent_student_map WHERE parent_id = ? AND student_id = ?");
    $stmt->execute([$parent_id, $student_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'This student is already linked to your profile.']);
        exit;
    }

    // 3. Create the link
    $stmt = $db->prepare("INSERT INTO parent_student_map (parent_id, student_id) VALUES (?, ?)");
    $stmt->execute([$parent_id, $student_id]);

    echo json_encode(['success' => true, 'message' => 'Linked successfully']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Infrastructure error: ' . $e->getMessage()]);
}
