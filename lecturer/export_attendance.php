<?php
require_once '../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'lecturer' && $_SESSION['role'] !== 'admin')) {
    header("Location: login");
    exit;
}

$db = get_db_connection();
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if (!$course_id) {
    die("Invalid course selection.");
}

// Get course details for the filename
$stmt = $db->prepare("SELECT course_code, course_name, lecturer_id FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course) {
    die("Course not found.");
}

// Verify lecturer owns this course (if not admin)
if ($_SESSION['role'] === 'lecturer' && $course['lecturer_id'] != $_SESSION['user_id']) {
    die("Unauthorized course access.");
}

$filename = "attendance_ledger_" . preg_replace('/[^a-zA-Z0-9]+/', '_', $course['course_code']) . "_" . date('Y-m-d') . ".csv";

// Output headers to trigger a file download
header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

$output = fopen("php://output", "w");

// Header row
fputcsv($output, ['Date', 'Session Topic', 'Student ID', 'Full Name', 'Status', 'Timestamp']);

// Fetch all sessions for this course
$stmt = $db->prepare("
    SELECT id, topic, DATE(created_at) as session_date
    FROM sessions 
    WHERE course_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$course_id]);
$sessions = $stmt->fetchAll();

foreach ($sessions as $session) {
    // Fetch all enrolled students and their attendance for this session
    $stmt_att = $db->prepare("
        SELECT u.student_id, u.fullname, a.status, a.marked_at 
        FROM enrollments e
        JOIN users u ON e.student_id = u.id
        LEFT JOIN attendance a ON u.id = a.student_id AND a.session_id = ?
        WHERE e.course_id = ?
        ORDER BY u.fullname ASC
    ");
    $stmt_att->execute([$session['id'], $course_id]);
    $records = $stmt_att->fetchAll();

    foreach ($records as $rec) {
        $status = $rec['status'] === 'present' ? 'Present' : 'Absent';
        $time = $rec['marked_at'] ? date('h:i:s A', strtotime($rec['marked_at'])) : '-';
        
        fputcsv($output, [
            $session['session_date'],
            $session['topic'],
            $rec['student_id'] ?? 'N/A',
            $rec['fullname'],
            $status,
            $time
        ]);
    }
}

fclose($output);
exit;
