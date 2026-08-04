<?php
require_once '../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Auth Check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'lecturer' && $_SESSION['role'] !== 'admin')) {
    die("Unauthorized access.");
}

$course_id = $_GET['course_id'] ?? null;
if (!$course_id) die("Course ID missing.");

$db = get_db_connection();

// Verify lecturer owns this course
$chk = $db->prepare("SELECT course_name, course_code FROM courses WHERE id = ? AND lecturer_id = ?");
$chk->execute([$course_id, $_SESSION['user_id']]);
$course = $chk->fetch();

if (!$course) die("Unauthorized course access.");

// Fetch Attendance Data
$stmt = $db->prepare("
    SELECT u.username, u.email, a.status, a.marked_at 
    FROM attendance a
    JOIN users u ON a.student_id = u.id
    JOIN sessions s ON a.session_id = s.id
    WHERE s.course_id = ?
    ORDER BY a.marked_at DESC
");
$stmt->execute([$course_id]);
$data = $stmt->fetchAll();

// CSV Headers
$filename = "Attendance_" . str_replace(' ', '_', $course['course_code']) . "_" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Student Name', 'Email', 'Status', 'Date/Time']);

foreach ($data as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;
