<?php
$page_title = "Dashboard";
include '../includes/header.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$db = get_db_connection();
$user_id = $_SESSION['user_id'];
$user_stmt = $db->prepare("SELECT username, avatar_url FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

$username = $user['username'] ?? 'Student';
$avatar_url = $user['avatar_url'] ?? null;

// Default Avatar if none uploaded
$display_avatar = $avatar_url ? BASE_URL . $avatar_url : "https://api.dicebear.com/7.x/avataaars/svg?seed=" . htmlspecialchars($username);

// Greeting based on time
$hour = date('H');
$greeting = "Good Morning";
if ($hour >= 12 && $hour < 17) $greeting = "Good Afternoon";
if ($hour >= 17) $greeting = "Good Evening";

// Fetch Notifications
$notif_stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
$notif_stmt->execute([$user_id]);
$notifications = $notif_stmt->fetchAll();

// Fetch Today's Schedule
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

<section id="main-dashboard" class="screen" data-state="active">
    <div class="scrollable-content">
        <div class="dash-header" style="margin-bottom: 35px; align-items: flex-start;">
            <div class="greeting">
                <p style="font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 2px; font-size: 10px; margin-bottom: 10px; opacity: 0.9;"><?php echo date('l, d M Y'); ?></p>
                <h2 id="live-greeting" style="font-size: 18px; color: var(--text-muted); font-weight: 500; margin-bottom: 0;"><?php echo $greeting; ?>,</h2>
                <h1 style="font-size: 44px; font-weight: 900; letter-spacing: -2px; line-height: 1; margin-bottom: 12px; display: flex; align-items: center; gap: 10px;">
                    <span style="background: linear-gradient(135deg, var(--text-dark) 40%, var(--primary) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo htmlspecialchars($username); ?></span>
                    <i data-lucide="sparkles" style="color: var(--primary); width: 32px; height: 32px;"></i>
                </h1>
                <div id="live-clock-pill" style="display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; color: var(--primary); background: var(--primary-glow); padding: 6px 14px; border-radius: 100px; border: 1px solid rgba(0, 102, 255, 0.1);">
                    <i data-lucide="clock" style="width: 14px; height: 14px;"></i>
                    <span id="live-clock"></span>
                </div>
            </div>
            <div class="profile-avatar" style="width: 70px; height: 70px; border-radius: 26px; overflow: hidden; border: 3px solid var(--surface); box-shadow: 0 15px 35px var(--primary-glow); position: relative;">
                <img src="<?php echo $display_avatar; ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                <div style="position: absolute; bottom: 0; right: 0; width: 14px; height: 14px; background: #22c55e; border: 2.5px solid var(--surface); border-radius: 50%;"></div>
            </div>
        </div>

        <!-- Attendance Overview -->
        <div class="stats-card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <p style="font-size: 13px; opacity: 0.8;">Overall Attendance</p>
                    <h3 style="font-size: 32px; font-weight: 800; margin-top: 5px;" id="scoreText">85%</h3>
                </div>
                <div style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 18px; display: flex; justify-content: center; align-items: center;">
                     <i data-lucide="check-circle-2" style="width: 24px; height: 24px;"></i>
                </div>
            </div>
            <div class="progress-container">
                <div class="progress-bar" id="progressBar" style="width: 85%;"></div>
            </div>
            <p style="font-size: 12px; opacity: 0.7;">You've attended 17/20 sessions this month.</p>
        </div>

        <!-- Quick Notifications -->
        <?php if (!empty($notifications)): ?>
        <div class="section-title">
            <span>Recent Alerts</span>
        </div>
        <div style="padding: 0 24px; margin-bottom: 24px;">
            <?php foreach($notifications as $notif): ?>
            <div style="background: var(--surface); padding: 18px; border-radius: 24px; border: 1px solid var(--border); margin-bottom: 12px; display: flex; gap: 15px; align-items: center;">
                <div style="background: var(--primary-glow); color: var(--primary); padding: 10px; border-radius: 14px;">
                    <i data-lucide="bell-ring" style="width: 20px; height: 20px;"></i>
                </div>
                <div style="flex: 1;">
                    <h4 style="font-size: 14px; font-weight: 700; color: var(--text-dark);"><?php echo htmlspecialchars($notif['title']); ?></h4>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;"><?php echo htmlspecialchars($notif['message']); ?></p>
                </div>
                <i data-lucide="chevron-right" style="width: 16px; height: 16px; color: var(--text-muted);"></i>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Upcoming Classes -->
        <div class="section-title">
            <span>Today's Classes</span>
            <a href="schedule.php" class="view-all" style="display: flex; align-items: center; gap: 4px; text-decoration: none;">
                View All <i data-lucide="arrow-right" style="width: 14px; height: 14px;"></i>
            </a>
        </div>

        <div class="timeline">
            <?php if ($day_now == 0 || $day_now == 6): ?>
                <div style="padding: 30px 24px; background: linear-gradient(135deg, rgba(0, 102, 255, 0.05) 0%, rgba(0, 102, 255, 0.02) 100%); border-radius: 32px; margin: 0 24px; border: 1px solid var(--primary-glow); text-align: center;">
                    <div style="width: 60px; height: 60px; background: var(--surface); border-radius: 20px; display: flex; justify-content: center; align-items: center; margin: 0 auto 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); color: var(--primary);">
                        <i data-lucide="coffee" style="width: 28px; height: 28px;"></i>
                    </div>
                    <h3 style="font-size: 20px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;">Weekend Mode On!</h3>
                    <p style="color: var(--text-muted); font-size: 15px; line-height: 1.6; font-weight: 500; max-width: 240px; margin: 0 auto;">
                        Read, rest, do your assignments and <span style="color: var(--primary); font-weight: 700;">prepare for the new week ahead.</span>
                    </p>
                </div>
            <?php elseif (empty($schedules)): ?>
                <div style="text-align: center; padding: 50px 24px; background: var(--surface); border-radius: 28px; margin: 0 24px; border: 1px solid var(--border);">
                    <div style="width: 50px; height: 50px; background: var(--bg-main); border-radius: 50%; display: flex; justify-content: center; align-items: center; margin: 0 auto 15px;">
                        <i data-lucide="calendar-x" style="width: 24px; height: 24px; color: var(--text-muted);"></i>
                    </div>
                    <p style="color: var(--text-muted); font-size: 14px; font-weight: 600;">No classes scheduled for today!</p>
                </div>
            <?php else: ?>
                <?php foreach($schedules as $item): ?>
                <div class="timeline-item">
                    <div class="time-box">
                        <p style="color: var(--primary); font-weight: 800; font-size: 15px;"><?php echo date('H:i', strtotime($item['start_time'])); ?></p>
                        <p style="font-size: 9px; opacity: 0.6; font-weight: 700;"><?php echo date('A', strtotime($item['start_time'])); ?></p>
                    </div>
                    <div class="class-info" style="margin-left: 15px; flex: 1;">
                        <h4 style="font-size: 15px; font-weight: 700; color: var(--text-dark);"><?php echo htmlspecialchars($item['course_name']); ?></h4>
                        <p style="font-size: 12px; color: var(--text-muted);"><?php echo htmlspecialchars($item['course_code']); ?> • <?php echo htmlspecialchars($item['location']); ?></p>
                    </div>
                    <div style="width: 40px; height: 40px; background: var(--bg-main); border-radius: 12px; display: flex; justify-content: center; align-items: center; color: var(--success);">
                        <i data-lucide="check" style="width: 20px; height: 20px;"></i>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div style="height: 120px;"></div>

        <?php include '../includes/navbar.php'; ?>

    </div>
</section>

<script>
function updateClock() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const clockEl = document.getElementById('live-clock');
    if (clockEl) clockEl.innerText = timeStr;
}

document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
    setInterval(updateClock, 1000);
    updateClock();
});
</script>

<?php include '../includes/footer.php'; ?>
