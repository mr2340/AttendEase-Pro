<?php
require_once '../includes/config.php';
require_once '../includes/stat_engine.php';

$page_title = "Schedule";
include '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$db = get_db_connection();
$user_id = $_SESSION['user_id'];
$selected_day = isset($_GET['day']) ? (int)$_GET['day'] : (int)date('w');

// Fetch schedules for the selected day
$sched_stmt = $db->prepare("
    SELECT s.*, c.course_name, c.course_code, u.username as lecturer_name, u.avatar_url as lecturer_avatar
    FROM schedules s 
    JOIN courses c ON s.course_id = c.id 
    LEFT JOIN users u ON c.lecturer_id = u.id
    WHERE s.day_of_week = ?
    ORDER BY s.start_time ASC
");
$sched_stmt->execute([$selected_day]);
$day_schedules = $sched_stmt->fetchAll();

// Calculate dates for the week
$days_data = [];
$start_of_week = strtotime('monday this week');
for ($i = 0; $i < 7; $i++) {
    $time = strtotime("+$i days", $start_of_week);
    $idx = (int)date('w', $time);
    $days_data[$idx] = [
        'name' => date('D', $time),
        'num' => date('d', $time),
        'full_date' => date('l, d M', $time),
        'is_today' => date('Y-m-d', $time) === date('Y-m-d')
    ];
}
$ordered_indices = [1, 2, 3, 4, 5, 6, 0]; 
?>

<div class="mobile-only-layout">
    <!-- Day Selector (Mobile) -->
    <div class="day-selector-container" style="overflow-x: auto; padding: 0 20px 20px; scrollbar-width: none;">
        <div style="display: flex; gap: 12px; min-width: max-content;">
            <?php foreach ($ordered_indices as $idx): ?>
            <?php $d = $days_data[$idx]; ?>
            <a href="?day=<?php echo $idx; ?>" style="text-decoration: none;">
                <div class="day-card <?php echo $selected_day == $idx ? 'active' : ''; ?>" style="min-width: 65px; height: 85px; border-radius: 20px; display: flex; flex-direction: column; justify-content: center; align-items: center; background: <?php echo $selected_day == $idx ? 'var(--primary)' : 'var(--surface)'; ?>; color: <?php echo $selected_day == $idx ? 'white' : 'var(--text-dark)'; ?>; border: 1.5px solid <?php echo $selected_day == $idx ? 'var(--primary)' : 'var(--border)'; ?>;">
                    <span style="font-size: 10px; font-weight: 800; opacity: 0.8;"><?php echo $d['name']; ?></span>
                    <span style="font-size: 18px; font-weight: 900;"><?php echo $d['num']; ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div style="padding: 0 20px;">
        <h3 style="font-size: 12px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 20px;"><?php echo $days_data[$selected_day]['full_date']; ?></h3>
        
        <?php if (empty($day_schedules)): ?>
            <div style="text-align: center; padding: 40px; background: var(--surface); border-radius: 30px; border: 1.5px dashed var(--border);">
                <p style="color: var(--text-muted); font-weight: 600;">No classes scheduled for today.</p>
            </div>
        <?php else: ?>
            <?php foreach ($day_schedules as $item): 
                $health = StatEngine::getAttendanceByCourse($user_id, $item['course_id']);
                $h_color = $health >= 75 ? 'var(--success)' : 'var(--danger)';
            ?>
            <div class="schedule-node" data-start="<?php echo $item['start_time']; ?>" data-end="<?php echo $item['end_time']; ?>" style="background: var(--surface); border-radius: 28px; padding: 18px; border: 1.5px solid var(--border); margin-bottom: 15px; display: flex; gap: 15px; position: relative;">
                <div style="width: 55px; text-align: center; border-right: 1.5px solid var(--bg-main); padding-right: 10px;">
                    <span style="font-size: 14px; font-weight: 900;"><?php echo date('h:i', strtotime($item['start_time'])); ?></span>
                    <span style="display: block; font-size: 10px; font-weight: 700; color: var(--text-muted);"><?php echo date('A', strtotime($item['start_time'])); ?></span>
                </div>
                <div style="flex: 1;">
                    <span style="font-size: 10px; font-weight: 800; color: var(--primary);"><?php echo htmlspecialchars($item['course_code']); ?></span>
                    <h4 style="font-size: 15px; font-weight: 800; margin: 3px 0;"><?php echo htmlspecialchars($item['course_name']); ?></h4>
                    <p style="font-size: 11px; color: var(--text-muted); margin: 0; font-weight: 600;"><?php echo htmlspecialchars($item['location']); ?></p>
                </div>
                <div style="width: 30px; height: 30px; background: <?php echo $h_color; ?>10; color: <?php echo $h_color; ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 8px; font-weight: 900;"><?php echo $health; ?>%</div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="desktop-only-layout">
    <header class="desktop-header">
        <div class="header-breadcrumb">
            <span class="date-pill"><?php echo date('l, d M Y'); ?></span>
            <div class="clock-badge"><i data-lucide="calendar"></i> <span id="schedule-node">NODE ACTIVE</span></div>
        </div>
        <h1 class="desktop-greeting">Academic Journey ✨</h1>
    </header>

    <div class="bento-grid">
        <!-- Day Selector Sidebar -->
        <div class="bento-card bento-hero-card card-medium" style="padding: 30px;">
            <h3 style="color: #38bdf8; font-size: 12px; font-weight: 800; letter-spacing: 2px; margin-bottom: 25px;">TEMPORAL NAV</h3>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <?php foreach ($ordered_indices as $idx): ?>
                <?php $d = $days_data[$idx]; ?>
                <a href="?day=<?php echo $idx; ?>" style="text-decoration: none;">
                    <div style="padding: 15px 20px; border-radius: 20px; background: <?php echo $selected_day == $idx ? 'rgba(255,255,255,0.1)' : 'transparent'; ?>; border: 1px solid <?php echo $selected_day == $idx ? 'rgba(255,255,255,0.2)' : 'transparent'; ?>; display: flex; align-items: center; justify-content: space-between; transition: 0.3s;">
                        <span style="color: <?php echo $selected_day == $idx ? 'white' : '#94a3b8'; ?>; font-weight: <?php echo $selected_day == $idx ? '800' : '600'; ?>;"><?php echo $d['full_date']; ?></span>
                        <?php if($d['is_today']): ?>
                            <span style="width: 8px; height: 8px; background: #38bdf8; border-radius: 50%;"></span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Timeline Hub -->
        <div class="bento-card card-large">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2 class="card-title">Day Timeline</h2>
                <div id="next-session-pill" style="display: none; background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 8px 16px; border-radius: 100px; font-size: 12px; font-weight: 800; border: 1px solid rgba(16, 185, 129, 0.2);">
                    Live: <span id="next-class-name">...</span>
                </div>
            </div>

            <div class="desktop-timeline">
                <?php if (empty($day_schedules)): ?>
                    <div style="text-align: center; padding: 60px;">
                        <p style="color: #94a3b8; font-weight: 500;">Temporal buffer reached. No sessions detected.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($day_schedules as $item): ?>
                    <div class="schedule-node" data-start="<?php echo $item['start_time']; ?>" data-end="<?php echo $item['end_time']; ?>" style="background: rgba(15, 23, 42, 0.03); border-radius: 25px; padding: 25px; border: 1px solid rgba(15, 23, 42, 0.05); margin-bottom: 15px; display: flex; align-items: center; gap: 25px; transition: 0.4s;">
                        <div style="font-size: 20px; font-weight: 900; color: #0f172a; width: 100px;"><?php echo date('H:i', strtotime($item['start_time'])); ?></div>
                        <div style="flex: 1;">
                            <div style="font-size: 11px; font-weight: 800; color: #3b82f6; text-transform: uppercase;"><?php echo htmlspecialchars($item['course_code']); ?></div>
                            <h4 style="font-size: 18px; font-weight: 800; color: #0f172a;"><?php echo htmlspecialchars($item['course_name']); ?></h4>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-weight: 700; font-size: 14px;"><?php echo htmlspecialchars($item['location']); ?></div>
                            <div style="font-size: 12px; color: #64748b; margin-top: 4px;"><?php echo htmlspecialchars($item['lecturer_name']); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    function updateClock() {
        const now = new Date();
        const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        const clockEl = document.getElementById('digital-clock');
        if (clockEl) clockEl.innerText = timeStr;
    }
    
    const nodes = document.querySelectorAll('.schedule-node');
    function updateIntelligence() {
        const now = new Date();
        const curSec = now.getHours() * 3600 + now.getMinutes() * 60 + now.getSeconds();
        const isToday = <?php echo (int)(date('w') == $selected_day); ?>;

        nodes.forEach(node => {
            const start = node.getAttribute('data-start');
            const end = node.getAttribute('data-end');
            const [sH, sM, sS] = start.split(':').map(Number);
            const [eH, eM, eS] = end.split(':').map(Number);
            const startSec = sH * 3600 + sM * 60 + sS;
            const endSec = eH * 3600 + eM * 60 + eS;

            if (isToday) {
                if (curSec >= startSec && curSec <= endSec) {
                    node.style.borderColor = '#3b82f6';
                    node.style.background = 'rgba(59, 130, 246, 0.05)';
                    node.style.boxShadow = '0 10px 30px rgba(59, 130, 246, 0.1)';
                } else if (curSec > endSec) {
                    node.style.opacity = '0.5';
                }
            }
        });
    }

    if (typeof lucide !== 'undefined') lucide.createIcons();
    setInterval(updateClock, 1000);
    setInterval(updateIntelligence, 30000);
    updateClock();
    updateIntelligence();
});
</script>

<div style="height: 100px;"></div>
<?php include '../includes/footer.php'; ?>
