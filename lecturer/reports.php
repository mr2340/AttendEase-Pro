<?php
$page_title = "Attendance Reports";
include '../includes/header.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'lecturer' && $_SESSION['role'] !== 'admin')) {
    header("Location: login");
    exit;
}

$db = get_db_connection();
$user_id = $_SESSION['user_id'];

// Fetch Lecturer's Courses
$stmt = $db->prepare("SELECT * FROM courses WHERE lecturer_id = ?");
$stmt->execute([$user_id]);
$courses = $stmt->fetchAll();

// Fetch Recent Sessions with Attendance Counts
$stmt = $db->prepare("
    SELECT s.*, c.course_name, c.course_code,
    (SELECT COUNT(*) FROM attendance WHERE session_id = s.id) as student_count
    FROM sessions s 
    JOIN courses c ON s.course_id = c.id 
    WHERE s.lecturer_id = ?
    ORDER BY s.created_at DESC
    LIMIT 20
");
$stmt->execute([$user_id]);
$recent_sessions = $stmt->fetchAll();

// Calculate Global Stats
$total_sessions = count($recent_sessions);
$total_attendance = 0;
foreach($recent_sessions as $rs) $total_attendance += $rs['student_count'];
$avg_attendance = ($total_sessions > 0) ? round($total_attendance / $total_sessions, 1) : 0;
?>

<section id="reports-portal" class="screen" data-state="active" style="background: #f8fafc;">
    <div class="scrollable-content">
        <!-- Header -->
        <div style="padding: 30px 24px 20px;">
            <h1 style="font-size: 32px; font-weight: 900; color: #0f172a; letter-spacing: -1px;">Intelligence <span style="color: var(--primary);">Reports</span></h1>
            <p style="color: #64748b; font-size: 14px; margin-top: 5px;">Comprehensive attendance analytics</p>
        </div>

        <!-- Stats Overview -->
        <div style="padding: 0 24px; margin-bottom: 30px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div style="background: white; padding: 20px; border-radius: 28px; border: 1px solid #e2e8f0; box-shadow: 0 10px 20px rgba(0,0,0,0.02);">
                    <div style="width: 40px; height: 40px; background: #ecfdf5; color: #10b981; border-radius: 12px; display: flex; justify-content: center; align-items: center; margin-bottom: 12px;">
                        <i data-lucide="bar-chart-3" style="width: 20px;"></i>
                    </div>
                    <h3 style="font-size: 24px; font-weight: 850; color: #0f172a;"><?php echo $total_sessions; ?></h3>
                    <p style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Total Sessions</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 28px; border: 1px solid #e2e8f0; box-shadow: 0 10px 20px rgba(0,0,0,0.02);">
                    <div style="width: 40px; height: 40px; background: #eff6ff; color: #3b82f6; border-radius: 12px; display: flex; justify-content: center; align-items: center; margin-bottom: 12px;">
                        <i data-lucide="users" style="width: 20px;"></i>
                    </div>
                    <h3 style="font-size: 24px; font-weight: 850; color: #0f172a;"><?php echo $avg_attendance; ?></h3>
                    <p style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Avg Presence</p>
                </div>
            </div>
        </div>

        <!-- Course Filters (Horizontal Scroll) -->
        <div style="overflow-x: auto; padding: 0 24px; margin-bottom: 30px; display: flex; gap: 10px; scrollbar-width: none;">
            <div style="background: #0f172a; color: white; padding: 10px 20px; border-radius: 100px; font-size: 13px; font-weight: 700; white-space: nowrap;">All Courses</div>
            <?php foreach($courses as $c): ?>
                <div style="background: white; color: #64748b; padding: 10px 20px; border-radius: 100px; font-size: 13px; font-weight: 700; white-space: nowrap; border: 1px solid #e2e8f0;">
                    <?php echo htmlspecialchars($c['course_code']); ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Recent Log -->
        <div class="section-title" style="padding: 0 24px; margin-bottom: 15px;">
            <span style="font-weight: 800; color: #0f172a;">Historical Sessions</span>
        </div>

        <div style="padding: 0 24px;">
            <?php if (empty($recent_sessions)): ?>
                <div style="background: white; padding: 40px 20px; border-radius: 30px; text-align: center; border: 1px dashed #cbd5e1;">
                    <i data-lucide="database-zap" style="width: 40px; height: 40px; color: #cbd5e1; margin-bottom: 15px;"></i>
                    <p style="color: #64748b; font-weight: 600;">No attendance logs found yet.</p>
                </div>
            <?php else: ?>
                <?php foreach($recent_sessions as $sess): ?>
                    <div style="background: white; padding: 20px; border-radius: 24px; border: 1px solid #e2e8f0; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="width: 45px; height: 45px; background: #f8fafc; color: #64748b; border-radius: 14px; display: flex; justify-content: center; align-items: center; font-size: 12px; font-weight: 800;">
                                <?php echo date('d', strtotime($sess['created_at'])); ?>
                                <?php echo date('M', strtotime($sess['created_at'])); ?>
                            </div>
                            <div>
                                <h4 style="font-weight: 700; font-size: 14px; color: #1e293b;"><?php echo htmlspecialchars($sess['course_name']); ?></h4>
                                <p style="font-size: 11px; color: #94a3b8; font-weight: 600;">
                                    <?php echo date('h:i A', strtotime($sess['created_at'])); ?> • <span style="color: #10b981;"><?php echo $sess['student_count']; ?> Students</span>
                                </p>
                            </div>
                        </div>
                        <a href="session_details?id=<?php echo $sess['id']; ?>" style="width: 35px; height: 35px; background: #f1f5f9; color: #0f172a; border-radius: 10px; display: flex; justify-content: center; align-items: center; text-decoration: none;">
                            <i data-lucide="external-link" style="width: 16px;"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div style="height: 120px;"></div>
    </div>

    <!-- Navigation -->
    <nav class="bottom-nav">
        <a href="dashboard" class="nav-item">
            <i data-lucide="layout-dashboard"></i>
            <span>Dashboard</span>
        </a>
        <a href="generate_qr" class="nav-item">
            <i data-lucide="plus-circle"></i>
            <span>New Class</span>
        </a>
        <a href="reports" class="nav-item active">
            <i data-lucide="file-text"></i>
            <span>Reports</span>
        </a>
        <a href="profile" class="nav-item">
            <i data-lucide="user-cog"></i>
            <span>Settings</span>
        </a>
    </nav>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<?php include '../includes/footer.php'; ?>
