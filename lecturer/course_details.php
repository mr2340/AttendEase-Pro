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

<div class="mobile-only-layout">
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
            <button onclick="window.location.href='manage_schedule?course_id=<?php echo $course_id; ?>'" style="flex: 1; padding: 14px; font-size: 14px; border-radius: 16px; background: #0f172a; color: white; border: none; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <i data-lucide="calendar" style="width: 18px;"></i>
                Schedule
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
</div>

<!-- Desktop Content: Advanced Course Intelligence -->
<div class="desktop-only-layout" style="background: #f8fafc; min-height: 100vh;">
    <header style="padding: 60px 80px 40px; display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
                <a href="<?php echo BASE_URL; ?>lecturer/courses" style="color: #64748b; margin-right: 10px;"><i data-lucide="arrow-left-circle" style="width: 24px;"></i></a>
                <span style="color: #64748b; font-size: 13px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase;">Analytical Intelligence</span>
            </div>
            <h1 style="font-size: 56px; font-weight: 950; color: #0f172a; letter-spacing: -2.5px;"><?php echo htmlspecialchars($course['course_code']); ?> <span style="color: var(--primary);">Insights</span></h1>
            <p style="color: #94a3b8; font-size: 18px; font-weight: 500; margin-top: 10px;"><?php echo htmlspecialchars($course['course_name']); ?></p>
        </div>
        <div style="display: flex; gap: 15px;">
             <a href="export_attendance?course_id=<?php echo $course_id; ?>" class="btn-primary" style="padding: 18px 30px; border-radius: 18px; font-weight: 800; display: flex; align-items: center; gap: 10px; text-decoration: none; color: white;">
                <i data-lucide="file-text" style="width: 20px;"></i> Master Export
            </a>
            <a href="generate_qr?course_id=<?php echo $course_id; ?>" style="background: white; color: #0f172a; border: 1.5px solid #e2e8f0; padding: 18px 30px; border-radius: 18px; text-decoration: none; font-weight: 850; display: flex; align-items: center; gap: 10px;">
                <i data-lucide="zap" style="width: 20px;"></i> Deploy Node
            </a>
            <a href="manage_schedule?course_id=<?php echo $course_id; ?>" style="background: #0f172a; color: white; border: none; padding: 18px 30px; border-radius: 18px; text-decoration: none; font-weight: 850; display: flex; align-items: center; gap: 10px;">
                <i data-lucide="calendar" style="width: 20px;"></i> Manage Schedule
            </a>
        </div>
    </header>

    <div style="padding: 0 80px 80px; display: grid; grid-template-columns: 7fr 3fr; gap: 40px; align-items: start;">
        <!-- Left: Performance Matrix -->
        <div style="background: white; border-radius: 45px; padding: 45px; border: 1.5px solid #f1f5f9; box-shadow: 0 20px 60px rgba(0,0,0,0.03);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
                <h3 style="font-size: 22px; font-weight: 900; color: #0f172a;">Student Performance Matrix</h3>
                <span style="background: #f8fafc; color: #64748b; padding: 8px 15px; border-radius: 12px; font-size: 12px; font-weight: 750; border: 1px solid #e2e8f0;"><?php echo count($students); ?> Verified Participants</span>
            </div>

            <table style="width: 100%; border-collapse: separate; border-spacing: 0 12px;">
                <thead>
                    <tr style="text-align: left;">
                        <th style="padding: 0 15px 15px; font-size: 11px; color: #94a3b8; font-weight: 850; text-transform: uppercase;">Participant</th>
                        <th style="padding: 0 15px 15px; font-size: 11px; color: #94a3b8; font-weight: 850; text-transform: uppercase; text-align: center;">Presence</th>
                        <th style="padding: 0 15px 15px; font-size: 11px; color: #94a3b8; font-weight: 850; text-transform: uppercase; text-align: center;">Vitals</th>
                        <th style="padding: 0 15px 15px; font-size: 11px; color: #94a3b8; font-weight: 850; text-transform: uppercase; text-align: right;">Engagement</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($students)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 50px; color: #94a3b8;">No enrollments found for this matrix.</td></tr>
                    <?php endif; ?>
                    <?php foreach($students as $s): 
                        $percent = ($s['total_sessions'] > 0) ? round(($s['total_attendance'] / $s['total_sessions']) * 100) : 0;
                        $status_color = ($percent >= 75) ? '#10b981' : (($percent >= 40) ? '#f59e0b' : '#ef4444');
                        $status_bg = ($percent >= 75) ? '#ecfdf5' : (($percent >= 40) ? '#fffbeb' : '#fef2f2');
                    ?>
                    <tr style="background: #fdfdfd; transition: all 0.2s;">
                        <td style="padding: 20px; border-radius: 20px 0 0 20px; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; border-left: 1px solid #f1f5f9;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div style="width: 45px; height: 45px; border-radius: 14px; background: #f8fafc; display: flex; align-items: center; justify-content: center; color: var(--primary);">
                                    <i data-lucide="user" style="width: 20px;"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 900; color: #0f172a; font-size: 15px;"><?php echo htmlspecialchars($s['username']); ?></div>
                                    <div style="font-size: 11px; color: #94a3b8; font-weight: 700;"><?php echo htmlspecialchars($s['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="text-align: center; font-weight: 900; color: #0f172a; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9;"><?php echo $s['total_attendance']; ?> / <?php echo $s['total_sessions']; ?></td>
                        <td style="text-align: center; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9;">
                            <span style="background: <?php echo $status_bg; ?>; color: <?php echo $status_color; ?>; padding: 6px 15px; border-radius: 10px; font-size: 12px; font-weight: 900;">
                                <?php echo ($percent >= 75) ? 'PASSIVE' : (($percent >= 40) ? 'CAUTION' : 'AT RISK'); ?>
                            </span>
                        </td>
                        <td style="padding: 20px; text-align: right; border-radius: 0 20px 20px 0; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; border-right: 1px solid #f1f5f9;">
                            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 15px;">
                                <div style="width: 100px; height: 8px; background: #f1f5f9; border-radius: 10px; overflow: hidden;">
                                    <div style="width: <?php echo $percent; ?>%; height: 100%; background: <?php echo $status_color; ?>; border-radius: 10px;"></div>
                                </div>
                                <span style="font-weight: 950; font-size: 16px; color: <?php echo $status_color; ?>;"><?php echo $percent; ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Right: Activity Hub -->
        <div style="display: flex; flex-direction: column; gap: 40px;">
            <div style="background: #0f172a; border-radius: 45px; padding: 45px; color: white;">
                <h3 style="font-size: 18px; font-weight: 900; margin-bottom: 30px;">Historical Nodes</h3>
                <?php foreach($sessions as $sess): ?>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; padding-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <div>
                            <h4 style="font-size: 14px; font-weight: 800;"><?php echo date('M d, Y', strtotime($sess['created_at'])); ?></h4>
                            <p style="font-size: 11px; opacity: 0.5; margin-top: 4px;">ID: <?php echo $sess['id']; ?></p>
                        </div>
                        <div style="text-align: right;">
                            <p style="font-size: 16px; font-weight: 900; color: var(--primary);"><?php echo $sess['count']; ?></p>
                            <p style="font-size: 10px; opacity: 0.5; text-transform: uppercase;">Scans</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
</script>

<?php include '../includes/footer.php'; ?>
