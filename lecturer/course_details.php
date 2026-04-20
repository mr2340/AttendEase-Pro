<?php
require_once '../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth Check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'lecturer' && $_SESSION['role'] !== 'admin')) {
    header("Location: login");
    exit;
}

$page_title = "Course Details";
include '../includes/header.php';

$db = get_db_connection();
$user_id = $_SESSION['user_id'];
$course_id = $_GET['id'] ?? null;

if (!$course_id) {
    echo "<div class='container' style='padding: 50px; text-align: center; color: var(--text-dark);'><h1>Course ID Missing</h1><a href='dashboard'>Back to Dashboard</a></div>";
    include '../includes/footer.php';
    exit;
}

// Verify Ownership & Fetch Course Info
$stmt = $db->prepare("SELECT * FROM courses WHERE id = ? AND lecturer_id = ?");
$stmt->execute([$course_id, $user_id]);
$course = $stmt->fetch();

if (!$course) {
    echo "<div class='container' style='padding: 50px; text-align: center; color: var(--text-dark);'><h1>Unauthorized Access</h1><p>You do not have permission to view this course.</p><a href='dashboard'>Back to Dashboard</a></div>";
    include '../includes/footer.php';
    exit;
}

// Fetch Detailed Attendance Stats
$stmt = $db->prepare("
    SELECT u.username, u.email, COUNT(a.id) as total_attendance,
    (SELECT COUNT(*) FROM sessions WHERE course_id = ?) as total_sessions
    FROM users u
    JOIN enrollments e ON u.id = e.student_id
    LEFT JOIN attendance a ON u.id = a.student_id AND a.session_id IN (SELECT id FROM sessions WHERE course_id = ?)
    WHERE e.course_id = ?
    GROUP BY u.id
");
$stmt->execute([$course_id, $course_id, $course_id]);
$students = $stmt->fetchAll();

// Fetch Recent Sessions for this course
$stmt = $db->prepare("
    SELECT s.*, (SELECT COUNT(*) FROM attendance WHERE session_id = s.id) as count 
    FROM sessions s 
    WHERE course_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$course_id]);
$sessions = $stmt->fetchAll();
?>

<section id="course-details" class="screen" data-state="active">
    <div class="scrollable-content">
        <div class="dash-header" style="margin-bottom: 25px;">
            <div class="greeting">
                <p style="font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 1.5px; font-size: 11px; margin-bottom: 5px;">Analytical Intelligence</p>
                <h1 style="font-size: 28px; font-weight: 900; letter-spacing: -1px; color: var(--text-dark);"><?php echo htmlspecialchars($course['course_code']); ?></h1>
                <p style="color: var(--text-muted); font-size: 15px; font-weight: 600;"><?php echo htmlspecialchars($course['course_name']); ?></p>
            </div>
            <a href="dashboard" style="width: 45px; height: 45px; background: var(--surface); border: 1px solid var(--border); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--text-dark);">
                <i data-lucide="chevron-left" style="width: 24px;"></i>
            </a>
        </div>

        <div style="padding: 0 24px; margin-bottom: 30px; display: flex; gap: 10px;">
            <a href="export_attendance?course_id=<?php echo $course_id; ?>" class="btn-primary" style="flex: 1; padding: 14px; font-size: 14px; border-radius: 16px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <i data-lucide="download" style="width: 18px;"></i>
                Export Report
            </a>
             <button onclick="window.location.href='generate_qr?course_id=<?php echo $course_id; ?>'" style="flex: 1; padding: 14px; font-size: 14px; border-radius: 16px; background: var(--surface); color: var(--text-dark); border: 1.5px solid var(--border); font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <i data-lucide="plus" style="width: 18px;"></i>
                New Session
            </button>
        </div>

        <!-- Attendance Roster -->
        <div class="section-title">
            <span>Enrolled Students</span>
            <span style="background: var(--bg-main); color: var(--primary); padding: 2px 10px; border-radius: 50px; font-size: 10px;"><?php echo count($students); ?> Student(s)</span>
        </div>

        <div style="padding: 0 24px;">
            <?php if(empty($students)): ?>
                <div style="text-align: center; padding: 40px; background: var(--surface); border-radius: 28px; border: 1px dashed var(--border);">
                    <p style="color: var(--text-muted); font-weight: 600;">No students enrolled yet.</p>
                </div>
            <?php else: ?>
                <?php foreach($students as $s): 
                    $percent = ($s['total_sessions'] > 0) ? round(($s['total_attendance'] / $s['total_sessions']) * 100) : 0;
                    $status_color = ($percent >= 75) ? 'var(--success)' : (($percent >= 40) ? 'var(--warning)' : 'var(--danger)');
                ?>
                <div style="background: var(--surface); padding: 18px; border-radius: 24px; border: 1px solid var(--border); margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--bg-main); display: flex; align-items: center; justify-content: center;">
                                <i data-lucide="user" style="width: 20px; color: var(--primary);"></i>
                            </div>
                            <div>
                                <h4 style="font-size: 14px; font-weight: 800; color: var(--text-dark);"><?php echo htmlspecialchars($s['username']); ?></h4>
                                <p style="font-size: 11px; color: var(--text-muted);"><?php echo htmlspecialchars($s['email']); ?></p>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <p style="font-size: 16px; font-weight: 900; color: <?php echo $status_color; ?>;"><?php echo $percent; ?>%</p>
                            <p style="font-size: 9px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Score</p>
                        </div>
                    </div>
                    <div style="height: 6px; background: var(--bg-main); border-radius: 10px; overflow: hidden;">
                        <div style="width: <?php echo $percent; ?>%; height: 100%; background: <?php echo $status_color; ?>; border-radius: 10px;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div style="height: 100px;"></div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<?php include '../includes/footer.php'; ?>
