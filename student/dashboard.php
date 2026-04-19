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
$username = $_SESSION['username'] ?? 'Student';

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
        <div class="dash-header">
            <div class="greeting">
                <p><?php echo $greeting; ?>,</p>
                <h2><?php echo htmlspecialchars($username); ?> 👋</h2>
            </div>
            <div class="profile-avatar" style="border: 2px solid var(--primary); padding: 2px;">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo $username; ?>" alt="Avatar" style="width: 100%; height: 100%; border-radius: 12px;">
            </div>
        </div>

        <!-- Attendance Overview -->
        <div class="stats-card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <p style="font-size: 13px; opacity: 0.8;">Overall Attendance</p>
                    <h3 style="font-size: 32px; font-weight: 800; margin-top: 5px;" id="scoreText">85%</h3>
                </div>
                <div style="background: rgba(255,255,255,0.2); padding: 10px; border-radius: 15px;">
                     <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
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
            <div style="background: var(--surface); padding: 15px; border-radius: 20px; border: 1px solid var(--border); margin-bottom: 10px; display: flex; gap: 15px; align-items: flex-start;">
                <div style="background: var(--primary-glow); color: var(--primary); padding: 8px; border-radius: 12px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                </div>
                <div>
                    <h4 style="font-size: 14px; font-weight: 700; color: var(--text-dark);"><?php echo htmlspecialchars($notif['title']); ?></h4>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;"><?php echo htmlspecialchars($notif['message']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Upcoming Classes -->
        <div class="section-title">
            <span>Today's Classes</span>
            <a href="schedule.php" style="font-size: 13px; color: var(--primary); text-decoration: none;">View All</a>
        </div>

        <div class="timeline">
            <?php if (empty($schedules)): ?>
                <div style="text-align: center; padding: 40px 20px;">
                    <p style="color: var(--text-muted); font-size: 14px;">No classes scheduled for today! 🎉</p>
                </div>
            <?php else: ?>
                <?php foreach($schedules as $item): ?>
                <div class="timeline-item">
                    <div class="time-box">
                        <p style="color: var(--primary); font-weight: 800;"><?php echo date('H:i', strtotime($item['start_time'])); ?></p>
                        <p style="font-size: 10px; opacity: 0.5;">AM</p>
                    </div>
                    <div class="class-info" style="margin-left: 15px;">
                        <h4 style="font-size: 15px; font-weight: 700; color: var(--text-dark);"><?php echo htmlspecialchars($item['course_name']); ?></h4>
                        <p style="font-size: 12px; color: var(--text-muted);"><?php echo htmlspecialchars($item['course_code']); ?> • <?php echo htmlspecialchars($item['location']); ?></p>
                    </div>
                    <div style="margin-left: auto;">
                        <div style="width: 10px; height: 10px; border-radius: 50%; background: var(--success);"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div style="height: 100px;"></div> <!-- Spacer for Nav -->

        <?php include '../includes/navbar.php'; ?>

    </div>
</section>

<?php include '../includes/footer.php'; ?>
