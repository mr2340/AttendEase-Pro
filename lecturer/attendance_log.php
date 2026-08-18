<?php
require_once '../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'lecturer' && $_SESSION['role'] !== 'admin')) {
    header("Location: login");
    exit;
}

$page_title = "Attendance Logs";
include '../includes/header.php';

$db = get_db_connection();
$user_id = $_SESSION['user_id'];

// Fetch Lecturer's Courses
$stmt = $db->prepare("SELECT * FROM courses WHERE lecturer_id = ?");
$stmt->execute([$user_id]);
$courses = $stmt->fetchAll();

$selected_course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : ($courses[0]['id'] ?? 0);
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$attendance_records = [];
$total_enrolled = 0;
$present_count = 0;
$session_details = null;

if ($selected_course_id) {
    // Check if there was a session on this date for this course
    $stmt = $db->prepare("SELECT * FROM sessions WHERE course_id = ? AND DATE(created_at) = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$selected_course_id, $selected_date]);
    $session_details = $stmt->fetch();

    if ($session_details) {
        $session_id = $session_details['id'];
        
        // Fetch all enrolled students and their attendance status for this session
        $stmt = $db->prepare("
            SELECT u.id, u.student_id, u.fullname, u.username, a.status, a.marked_at 
            FROM enrollments e
            JOIN users u ON e.student_id = u.id
            LEFT JOIN attendance a ON u.id = a.student_id AND a.session_id = ?
            WHERE e.course_id = ?
            ORDER BY u.fullname ASC
        ");
        $stmt->execute([$session_id, $selected_course_id]);
        $attendance_records = $stmt->fetchAll();
        
        $total_enrolled = count($attendance_records);
        foreach($attendance_records as $rec) {
            if ($rec['status'] === 'present') {
                $present_count++;
            }
        }
    } else {
        // No session found for this date. We can still show enrolled students but mark all as 'No Session'.
        $stmt = $db->prepare("
            SELECT u.id, u.student_id, u.fullname, u.username 
            FROM enrollments e
            JOIN users u ON e.student_id = u.id
            WHERE e.course_id = ?
            ORDER BY u.fullname ASC
        ");
        $stmt->execute([$selected_course_id]);
        $attendance_records = $stmt->fetchAll();
        $total_enrolled = count($attendance_records);
    }
}

?>

<div class="desktop-only-layout" style="background: #fbfcfd; min-height: 100vh;">
    <header style="padding: 50px 60px 30px; display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <div style="background: var(--primary); width: 12px; height: 12px; border-radius: 4px;"></div>
                <span style="color: #64748b; font-size: 13px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;">Detailed Logs</span>
            </div>
            <h1 style="font-size: 48px; font-weight: 950; color: #0f172a; letter-spacing: -2.5px;">Attendance <span style="color: var(--primary);">Ledger</span></h1>
            <p style="color: #94a3b8; font-size: 16px; font-weight: 500; margin-top: 8px;">Granular view of student presence by date and course.</p>
        </div>
        <div style="display: flex; gap: 15px;">
            <a href="export_attendance.php?course_id=<?php echo $selected_course_id; ?>" class="btn-primary" style="height: 55px; padding: 0 30px; border-radius: 18px; font-weight: 800; text-decoration: none; display: flex; align-items: center;">
                <i data-lucide="download-cloud" style="width: 18px; margin-right: 10px;"></i> Export Full Ledger
            </a>
        </div>
    </header>

    <div style="padding: 0 60px 60px;">
        
        <!-- Filter Controls -->
        <div style="background: white; border-radius: 35px; padding: 35px; border: 1.5px solid #f1f5f9; box-shadow: 0 15px 35px rgba(0,0,0,0.02); margin-bottom: 40px;">
            <form method="GET" action="" style="display: flex; gap: 20px; align-items: flex-end;">
                <div style="flex: 1;">
                    <label style="font-size: 12px; font-weight: 850; color: #94a3b8; text-transform: uppercase; margin-bottom: 10px; display: block;">Select Course</label>
                    <select name="course_id" style="width: 100%; padding: 18px 25px; border-radius: 20px; border: 2px solid #e2e8f0; background: #f8fafc; font-weight: 700; color: #0f172a; font-size: 15px; appearance: none;">
                        <?php foreach($courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $c['id'] == $selected_course_id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label style="font-size: 12px; font-weight: 850; color: #94a3b8; text-transform: uppercase; margin-bottom: 10px; display: block;">Filter Date</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($selected_date); ?>" style="width: 100%; padding: 18px 25px; border-radius: 20px; border: 2px solid #e2e8f0; background: #f8fafc; font-weight: 700; color: #0f172a; font-size: 15px; font-family: inherit;">
                </div>
                <button type="submit" style="background: #0f172a; color: white; border: none; padding: 0 40px; height: 60px; border-radius: 20px; font-weight: 800; font-size: 15px; cursor: pointer;">
                    View Ledger
                </button>
            </form>
        </div>

        <?php if ($session_details): ?>
            <!-- Session Stats -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 40px;">
                <div style="background: white; padding: 30px; border-radius: 35px; border: 1.5px solid #f1f5f9; box-shadow: 0 15px 35px rgba(0,0,0,0.02);">
                    <p style="font-size: 12px; font-weight: 850; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">Session Status</p>
                    <h2 style="font-size: 28px; font-weight: 950; color: #0f172a; text-transform: capitalize;"><?php echo htmlspecialchars($session_details['status']); ?></h2>
                    <p style="font-size: 13px; color: #64748b; font-weight: 600; margin-top: 5px;">Started at <?php echo date('h:i A', strtotime($session_details['created_at'])); ?></p>
                </div>
                <div style="background: white; padding: 30px; border-radius: 35px; border: 1.5px solid #f1f5f9; box-shadow: 0 15px 35px rgba(0,0,0,0.02);">
                    <p style="font-size: 12px; font-weight: 850; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">Total Present</p>
                    <h2 style="font-size: 40px; font-weight: 950; color: #10b981;"><?php echo $present_count; ?></h2>
                    <p style="font-size: 13px; color: #64748b; font-weight: 600; margin-top: 5px;">Out of <?php echo $total_enrolled; ?> enrolled</p>
                </div>
                <div style="background: white; padding: 30px; border-radius: 35px; border: 1.5px solid #f1f5f9; box-shadow: 0 15px 35px rgba(0,0,0,0.02);">
                    <p style="font-size: 12px; font-weight: 850; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">Topic Discussed</p>
                    <h2 style="font-size: 20px; font-weight: 800; color: #0f172a;"><?php echo htmlspecialchars($session_details['topic']); ?></h2>
                </div>
            </div>
        <?php else: ?>
            <div style="background: #fffbeb; border: 1.5px solid #fde68a; padding: 30px; border-radius: 30px; color: #b45309; font-weight: 700; margin-bottom: 40px; display: flex; align-items: center; gap: 15px;">
                <i data-lucide="alert-circle" style="width: 24px;"></i>
                No attendance session was recorded for this course on <?php echo date('M d, Y', strtotime($selected_date)); ?>. Showing enrolled students.
            </div>
        <?php endif; ?>

        <!-- Data Table -->
        <div style="background: white; border-radius: 45px; padding: 45px; border: 1.5px solid #f1f5f9; box-shadow: 0 20px 60px rgba(0,0,0,0.03);">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #f1f5f9;">
                        <th style="text-align: left; padding: 20px 10px; font-size: 12px; font-weight: 850; color: #94a3b8; text-transform: uppercase;">Matric Number</th>
                        <th style="text-align: left; padding: 20px 10px; font-size: 12px; font-weight: 850; color: #94a3b8; text-transform: uppercase;">Name</th>
                        <th style="text-align: left; padding: 20px 10px; font-size: 12px; font-weight: 850; color: #94a3b8; text-transform: uppercase;">Status</th>
                        <th style="text-align: right; padding: 20px 10px; font-size: 12px; font-weight: 850; color: #94a3b8; text-transform: uppercase;">Scan Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attendance_records)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 600;">No students enrolled in this course.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($attendance_records as $rec): ?>
                            <tr style="border-bottom: 1px solid #f8fafc; transition: background 0.2s;">
                                <td style="padding: 20px 10px; font-weight: 800; color: #0f172a; font-size: 14px;"><?php echo htmlspecialchars($rec['student_id'] ?? 'N/A'); ?></td>
                                <td style="padding: 20px 10px; font-weight: 700; color: #475569; font-size: 14px;"><?php echo htmlspecialchars($rec['fullname']); ?></td>
                                <td style="padding: 20px 10px;">
                                    <?php if (!$session_details): ?>
                                        <span style="background: #f1f5f9; color: #64748b; padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase;">No Session</span>
                                    <?php elseif ($rec['status'] === 'present'): ?>
                                        <span style="background: #ecfdf5; color: #10b981; padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase;">Present</span>
                                    <?php else: ?>
                                        <span style="background: #fef2f2; color: #ef4444; padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase;">Absent</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 20px 10px; text-align: right; font-weight: 600; color: #64748b; font-size: 13px;">
                                    <?php echo ($rec['marked_at'] ?? '') ? date('M d, Y h:i:s A', strtotime($rec['marked_at'])) : '-'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<?php include '../includes/footer.php'; ?>
