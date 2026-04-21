<?php
$page_title = "Dashboard";
include '../includes/header.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit;
}

$db = get_db_connection();
$user_id = $_SESSION['user_id'];
$user_stmt = $db->prepare("SELECT username, avatar_url FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

$username = $user['username'] ?? 'Student';
$avatar_url = $user['avatar_url'] ?? null;
$display_avatar = $avatar_url ? BASE_URL . $avatar_url : "https://api.dicebear.com/7.x/avataaars/svg?seed=" . htmlspecialchars($username);

// Greeting based on time
$hour = date('H');
$greeting = "Good Morning";
if ($hour >= 12 && $hour < 17) $greeting = "Good Afternoon";
if ($hour >= 17) $greeting = "Good Evening";

// Attendance Scoring
require_once '../includes/stat_engine.php';
$attendance_score = StatEngine::getStudentAttendanceScore($user_id);

// AI Risk Analysis
$risk_level = 'LOW';
$risk_color = '#10b981';
if($attendance_score < 75) {
    $risk_level = 'HIGH';
    $risk_color = '#ef4444';
} else if ($attendance_score < 80) {
    $risk_level = 'MEDIUM';
    $risk_color = '#f59e0b';
}

// Fetch Today's Classes
$day_now = date('w');
$sched_stmt = $db->prepare("
    SELECT s.*, c.course_name, c.course_code 
    FROM schedules s 
    JOIN courses c ON s.course_id = c.id 
    WHERE s.day_of_week = ? 
    ORDER BY s.start_time ASC
");
$sched_stmt->execute([$day_now]);
$schedules = $sched_stmt->fetchAll();
?>

<section id="main-dashboard">
    <!-- MOBILE CLASSIC VIEW (Matches Image 2) -->
    <div class="mobile-only-layout">
        <header class="dashboard-header-mobile">
            <div class="header-left">
                <span class="mobile-greeting-meta"><?php echo date('D, M d'); ?></span>
                <h1 class="mobile-greeting-name">Hi, <?php echo htmlspecialchars(explode(' ', $username)[0]); ?></h1>
            </div>
            <div class="header-right">
                <div class="mobile-avatar-frame">
                    <img src="<?php echo $display_avatar; ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover;">
                </div>
            </div>
        </header>

        <div class="mobile-content">
            <!-- Classic Hero Card -->
            <div class="mobile-hero-card">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <div>
                        <span style="font-size: 13px; color: #94a3b8; font-weight: 600;">Semester Attendance</span>
                        <div style="font-size: 48px; font-weight: 800; margin: 5px 0;"><?php echo $attendance_score; ?>%</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.1); padding: 12px; border-radius: 15px;">
                        <i data-lucide="trending-up" style="color: #38bdf8; width: 24px; height: 24px;"></i>
                    </div>
                </div>
                
                <div style="width: 100%; height: 8px; background: rgba(255,255,255,0.1); border-radius: 10px; margin-bottom: 15px; overflow: hidden;">
                    <div style="width: <?php echo $attendance_score; ?>%; height: 100%; background: #38bdf8; border-radius: 10px; box-shadow: 0 0 15px rgba(56, 189, 248, 0.5);"></div>
                </div>
                
                <p style="font-size: 13px; color: #cbd5e1; line-height: 1.5; margin: 0;">
                    <?php if($attendance_score >= 75): ?>
                        Great job! You are above the 75% threshold. Keep it up!
                    <?php else: ?>
                        Attention: Your attendance is below the minimum threshold.
                    <?php endif; ?>
                </p>
            </div>

            <!-- Recent Scan Pulse (Mobile Detail Restoration) -->
            <div style="padding: 0 20px 20px;">
                <h3 style="font-size: 12px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 1px;">Recent Vital Signs</h3>
                <?php 
                $last_scan_stmt = $db->prepare("SELECT a.*, c.course_code FROM attendance a JOIN sessions s ON a.session_id = s.id JOIN courses c ON s.course_id = c.id WHERE a.student_id = ? ORDER BY a.timestamp DESC LIMIT 1");
                $last_scan_stmt->execute([$user_id]);
                $last_scan = $last_scan_stmt->fetch();
                ?>
                <div style="background: white; border-radius: 30px; padding: 20px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.02);">
                    <div style="width: 48px; height: 48px; background: #ecfdf5; color: #10b981; border-radius: 16px; display: flex; justify-content: center; align-items: center;">
                        <i data-lucide="check-circle-2" style="width: 24px;"></i>
                    </div>
                    <div>
                        <?php if($last_scan): ?>
                            <h4 style="font-size: 15px; font-weight: 900; color: #0f172a;"><?php echo $last_scan['course_code']; ?> Validated</h4>
                            <p style="font-size: 12px; color: #64748b; font-weight: 600; margin-top: 2px;">Sync: <?php echo date('M d, h:i A', strtotime($last_scan['timestamp'])); ?></p>
                        <?php else: ?>
                            <h4 style="font-size: 15px; font-weight: 900; color: #0f172a;">No Active Pulse</h4>
                            <p style="font-size: 12px; color: #64748b; font-weight: 600; margin-top: 2px;">Start scanning to build history.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Today's Schedule -->
            <div style="padding: 10px 20px 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px;">
                    <h2 style="font-size: 19px; font-weight: 900; color: #1e293b; margin: 0; letter-spacing: -0.5px;">Today's Classes</h2>
                    <a href="schedule" style="font-size: 13px; font-weight: 800; color: var(--primary); text-decoration: none;">View Timeline</a>
                </div>

                <?php if(empty($schedules)): ?>
                    <div style="text-align: center; padding: 40px; background: #f8fafc; border-radius: 25px; border: 1.5px dashed #e2e8f0;">
                        <p style="color: #94a3b8; font-size: 14px; font-weight: 600;">No classes for today</p>
                    </div>
                <?php else: ?>
                    <?php foreach($schedules as $item): 
                        $is_live = (time() >= strtotime(date('Y-m-d') . ' ' . $item['start_time']) && time() <= strtotime(date('Y-m-d') . ' ' . $item['end_time']));
                    ?>
                    <div class="mobile-class-card">
                        <div class="class-time-box">
                            <div class="class-time-main"><?php echo date('H:i', strtotime($item['start_time'])); ?></div>
                            <div class="class-time-period"><?php echo date('A', strtotime($item['start_time'])); ?></div>
                        </div>
                        <div style="flex: 1;">
                            <h3 style="font-size: 15px; font-weight: 800; color: #1e293b; margin: 0;"><?php echo htmlspecialchars($item['course_name']); ?></h3>
                            <p style="font-size: 12px; color: #64748b; margin: 4px 0 0; font-weight: 500;">
                                <?php echo htmlspecialchars($item['location']); ?> • Prof. Davis
                            </p>
                        </div>
                        <?php if($is_live): ?>
                            <div style="width: 10px; height: 10px; background: #10b981; border-radius: 50%; box-shadow: 0 0 10px rgba(16, 185, 129, 0.4);"></div>
                        <?php else: ?>
                            <div style="width: 10px; height: 10px; background: #f59e0b; border-radius: 50%; opacity: 0.3;"></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- DESKTOP BENTO VIEW -->
    <div class="desktop-only-layout">
        <header class="desktop-header">
            <div class="header-breadcrumb">
                <span class="date-pill"><?php echo date('l, d M Y'); ?></span>
                <div class="clock-badge"><i data-lucide="zap"></i> <span id="dashboard-status">SYSTEM ACTIVE</span></div>
            </div>
            <h1 class="desktop-greeting"><?php echo $greeting; ?>, <?php echo htmlspecialchars($username); ?> ✨</h1>
        </header>

        <div class="bento-grid">
        <div class="bento-card bento-hero-card card-large">
            <div class="card-header-flex">
                <div>
                    <span class="card-meta" style="color: #38bdf8; font-weight: 800; font-size: 12px; letter-spacing: 2px;">SEMESTER PERFORMANCE</span>
                    <h2 class="metric-value"><?php echo $attendance_score; ?>%</h2>
                </div>
                <div class="risk-badge" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); padding: 15px 25px; border-radius: 20px; backdrop-filter: blur(10px);">
                    <span style="display: block; font-size: 10px; font-weight: 800; color: #94a3b8; margin-bottom: 5px; text-transform: uppercase;">AI Risk Analysis</span>
                    <div style="color: <?php echo $risk_color; ?>; font-size: 18px; font-weight: 900; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="shield-check" style="width: 20px; height: 20px;"></i>
                        <?php echo $risk_level; ?> RISK
                    </div>
                </div>
            </div>
            
            <div class="progress-track" style="background: rgba(255,255,255,0.05); height: 14px; border-radius: 20px; margin: 40px 0;">
                <div class="progress-fill" style="width: <?php echo $attendance_score; ?>%; background: linear-gradient(90deg, #0ea5e9, #38bdf8); height: 100%; border-radius: 20px; box-shadow: 0 0 30px rgba(14, 165, 233, 0.4);"></div>
            </div>
            
            <p style="color: #94a3b8; font-size: 16px; line-height: 1.6; font-weight: 500; max-width: 600px; margin: 0;">
                <?php if($attendance_score >= 75): ?>
                    Your attendance score is currently optimal. You are maintaining a highly stable trend across all courses for the current semester.
                <?php else: ?>
                    Priority Action Required: Your attendance has dropped below the safety threshold. Increase your presence to ensure exam eligibility.
                <?php endif; ?>
            </p>
        </div>

            <!-- Medium Card: Schedule -->
            <div class="bento-card card-medium">
                <div class="card-header-flex">
                    <h3 class="card-title">Today's Schedule</h3>
                    <a href="schedule" class="view-all">View All</a>
                </div>
                <div class="schedule-mini-list" style="margin-top: 20px; display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach(array_slice($schedules, 0, 3) as $item): ?>
                    <div class="schedule-item-alt" style="display: flex; gap: 15px; align-items: center; padding: 15px; background: #f8fafc; border-radius: 20px;">
                        <div style="font-weight: 800; color: #1e293b; font-size: 13px;"><?php echo date('H:i', strtotime($item['start_time'])); ?></div>
                        <div style="flex: 1;">
                            <div style="font-weight: 800; font-size: 14px; color: #0f172a;"><?php echo htmlspecialchars($item['course_code']); ?></div>
                            <div style="font-size: 11px; color: #64748b;"><?php echo htmlspecialchars($item['location']); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Small Card: Analytics -->
            <div class="bento-card card-small">
                <h3 class="card-title">Weekly Trend</h3>
                <div class="mini-chart-container" style="height: 100px; display: flex; align-items: flex-end; gap: 8px; margin-top: 20px;">
                    <div class="bar" style="flex:1; background: #e2e8f0; border-radius: 4px; height: 40%"></div>
                    <div class="bar" style="flex:1; background: #e2e8f0; border-radius: 4px; height: 60%"></div>
                    <div class="bar" style="flex:1; background: var(--primary); border-radius: 4px; height: 90%"></div>
                    <div class="bar" style="flex:1; background: #e2e8f0; border-radius: 4px; height: 50%"></div>
                    <div class="bar" style="flex:1; background: #e2e8f0; border-radius: 4px; height: 70%"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function updateClock() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const clockEl = document.getElementById('digital-clock');
    if (clockEl) clockEl.innerText = timeStr;
}
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') lucide.createIcons();
    setInterval(updateClock, 1000);
    updateClock();
});
</script>

<?php include '../includes/footer.php'; ?>
