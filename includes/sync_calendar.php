<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth Check
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$sched_id = $_GET['id'] ?? null;
if (!$sched_id) die("Schedule ID missing.");

$db = get_db_connection();
$stmt = $db->prepare("
    SELECT s.*, c.course_name, c.course_code 
    FROM schedules s 
    JOIN courses c ON s.course_id = c.id 
    WHERE s.id = ?
");
$stmt->execute([$sched_id]);
$item = $stmt->fetch();

if (!$item) die("Schedule not found.");

// Calendar Event Logic (ICS format)
$start = date('Ymd\THis', strtotime('next ' . date('l', strtotime("Sunday +{$item['day_of_week']} days")) . ' ' . $item['start_time']));
$end = date('Ymd\THis', strtotime('next ' . date('l', strtotime("Sunday +{$item['day_of_week']} days")) . ' ' . $item['end_time']));

$ics_content = "BEGIN:VCALENDAR\r\n";
$ics_content .= "VERSION:2.0\r\n";
$ics_content .= "PRODID:-//AttendEase Pro//NONSGML v1.0//EN\r\n";
$ics_content .= "BEGIN:VEVENT\r\n";
$ics_content .= "UID:" . uniqid() . "@attendease.pro\r\n";
$ics_content .= "DTSTAMP:" . date('Ymd\THis') . "\r\n";
$ics_content .= "DTSTART:" . $start . "\r\n";
$ics_content .= "DTEND:" . $end . "\r\n";
$ics_content .= "SUMMARY:" . $item['course_name'] . " (" . $item['course_code'] . ")\r\n";
$ics_content .= "LOCATION:" . $item['location'] . "\r\n";
$ics_content .= "DESCRIPTION:Scheduled Lecture via AttendEase Pro\r\n";
$ics_content .= "RRULE:FREQ=WEEKLY;BYDAY=" . strtoupper(substr(date('D', strtotime("Sunday +{$item['day_of_week']} days")), 0, 2)) . "\r\n";
$ics_content .= "END:VEVENT\r\n";
$ics_content .= "END:VCALENDAR\r\n";

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="lecture_sync.ics"');

echo $ics_content;
exit;
