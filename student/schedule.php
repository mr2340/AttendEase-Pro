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

// Calculate Dates for the Current Week
$days_data = [];
$start_of_week = strtotime('monday this week');
for ($i = 0; $i < 7; $i++) {
    $time = strtotime("+$i days", $start_of_week);
    $days_data[$i+1 > 6 ? 0 : $i+1] = [ // Handle Sunday correctly (0)
        'name' => date('D', $time),
        'num' => date('d', $time),
        'full_date' => date('l, d M', $time),
        'db_idx' => date('w', $time)
    ];
}

// Re-sort to show Mon-Sun or Sun-Sat. Let's do Mon-Sun as it's more common for school.
$ordered_indices = [1, 2, 3, 4, 5, 6, 0]; 

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
                <p style="color: var(--text-muted); font-weight: 500;">Academic Calendar <?php echo date('Y'); ?></p>
            </div>
        </div>

        <!-- Day Selector (Real Dates) -->
        <div class="day-selector">
            <?php foreach ($ordered_indices as $idx): ?>
            <?php $d = $days_data[$idx]; ?>
            <a href="?day=<?php echo $idx; ?>" style="text-decoration: none;">
                <div class="day-card <?php echo $selected_day == $idx ? 'active' : ''; ?>">
                    <span class="day-name"><?php echo $d['name']; ?></span>
                    <span class="day-num"><?php echo $d['num']; ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="section-title">
            <span><?php echo $days_data[$selected_day]['full_date']; ?></span>
        </div>

        <div class="timeline">
            <?php if (empty($day_schedules)): ?>
                <div style="text-align: center; padding: 60px 24px;">
                    <div style="background: var(--bg-main); width: 80px; height: 80px; border-radius: 50%; display: flex; justify-content: center; align-items: center; margin: 0 auto 20px; color: var(--text-muted);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 700; color: var(--text-dark);">Free Day!</h3>
                    <p style="color: var(--text-muted); font-size: 14px; margin-top: 8px;">No scheduled sessions found.</p>
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
                        <p><?php echo htmlspecialchars($item['location']); ?> • <?php echo ($selected_day == 0 || $selected_day == 6) ? 'Personal Task' : 'Dr. Peters'; ?></p>
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
