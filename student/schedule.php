<?php
$page_title = "Schedule";
include '../includes/header.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$db = get_db_connection();

// Current Selected Day (0-6)
$selected_day = isset($_GET['day']) ? (int)$_GET['day'] : (int)date('w');
// Make sure Sunday (0) and Saturday (6) are handled or default to Monday if weekend
if ($selected_day == 0 || $selected_day == 6) {
    // For demo purposes, let's allow weekends but ideally default to Monday
}

// Fetch Schedules for Selected Day
$sched_stmt = $db->prepare("
    SELECT s.*, c.course_name, c.course_code 
    FROM schedules s 
    JOIN courses c ON s.course_id = c.id 
    WHERE s.day_of_week = ?
    ORDER BY s.start_time ASC
");
$sched_stmt->execute([$selected_day]);
$day_schedules = $sched_stmt->fetchAll();

// Generate Day Cards (Mon-Fri)
$days_data = [
    1 => ['name' => 'Mon', 'num' => 12],
    2 => ['name' => 'Tue', 'num' => 13],
    3 => ['name' => 'Wed', 'num' => 14],
    4 => ['name' => 'Thu', 'num' => 15],
    5 => ['name' => 'Fri', 'num' => 16]
];

// Day Names for Header
$day_names_full = [
    0 => "Sunday", 1 => "Monday", 2 => "Tuesday", 3 => "Wednesday", 
    4 => "Thursday", 5 => "Friday", 6 => "Saturday"
];
?>

<section class="screen" data-state="active">
    <div class="scrollable-content">
        <div class="dash-header" style="padding-bottom: 10px;">
            <div class="greeting">
                <h2 style="font-size: 28px; font-weight: 800;">Schedule</h2>
                <p style="color: var(--text-muted); font-weight: 500;">Your weekly timetable</p>
            </div>
        </div>

        <!-- Day Selector -->
        <div class="day-selector">
            <?php foreach ($days_data as $idx => $d): ?>
            <a href="?day=<?php echo $idx; ?>" style="text-decoration: none;">
                <div class="day-card <?php echo $selected_day == $idx ? 'active' : ''; ?>">
                    <span class="day-name"><?php echo $d['name']; ?></span>
                    <span class="day-num"><?php echo $d['num']; ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="section-title">
            <span><?php echo $day_names_full[$selected_day]; ?>, <?php echo $days_data[$selected_day]['num'] ?? date('d'); ?> Oct</span>
        </div>

        <div class="timeline">
            <?php if (empty($day_schedules)): ?>
                <div style="text-align: center; padding: 60px 24px;">
                    <div style="background: var(--bg-main); width: 80px; height: 80px; border-radius: 50%; display: flex; justify-content: center; align-items: center; margin: 0 auto 20px; color: var(--text-muted);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 700; color: var(--text-dark);">No Classes Today</h3>
                    <p style="color: var(--text-muted); font-size: 14px; margin-top: 8px;">Enjoy your free time!</p>
                </div>
            <?php else: ?>
                <?php foreach ($day_schedules as $item): ?>
                <div class="timeline-item">
                    <div class="time-box">
                        <div class="time-main"><?php echo date('h:i', strtotime($item['start_time'])); ?></div>
                        <div class="time-ampm"><?php echo date('A', strtotime($item['start_time'])); ?></div>
                    </div>
                    <div class="class-info">
                        <h4><?php echo htmlspecialchars($item['course_name']); ?></h4>
                        <p><?php echo htmlspecialchars($item['location']); ?> • Dr. Peters</p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div style="height: 100px;"></div>
        <?php include '../includes/navbar.php'; ?>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
