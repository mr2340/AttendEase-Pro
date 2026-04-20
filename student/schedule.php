<?php
require_once '../includes/config.php';
require_once '../includes/stat_engine.php';

$page_title = "Schedule";
include '../includes/header.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$db = get_db_connection();
$user_id = $_SESSION['user_id'];

// Current Selected Day (0-6)
$selected_day = isset($_GET['day']) ? (int)$_GET['day'] : (int)date('w');

// Fetch Schedules for Selected Day with Lecturer Info
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

// Calculate Dates for the Current Week
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

<section class="screen" data-state="active">
    <div class="scrollable-content">
        <div class="dash-header" style="padding-bottom: 20px;">
            <div class="greeting">
                <p style="font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 2px; font-size: 10px; margin-bottom: 8px;">Proximity Intelligence</p>
                <h2 style="font-size: 28px; font-weight: 800; letter-spacing: -1px;">Academic Journey</h2>
                <div id="next-session-pill" style="display: none; margin-top: 12px; background: var(--success-glow); color: var(--success); padding: 8px 16px; border-radius: 100px; font-size: 12px; font-weight: 700; width: fit-content; border: 1.5px solid rgba(16, 185, 129, 0.1);">
                    Next: <span id="next-class-name">Loading...</span> in <span id="next-class-timer">--</span>
                </div>
            </div>
        </div>

        <!-- Day Selector (Responsive Horizontal Scroll) -->
        <div class="day-selector-container" style="overflow-x: auto; scrollbar-width: none; -ms-overflow-style: none; padding-bottom: 10px;">
            <div class="day-selector" style="display: flex; gap: 12px; padding: 0 24px; min-width: max-content;">
                <?php foreach ($ordered_indices as $idx): ?>
                <?php $d = $days_data[$idx]; ?>
                <a href="?day=<?php echo $idx; ?>" style="text-decoration: none;">
                    <div class="day-card <?php echo $selected_day == $idx ? 'active' : ''; ?>" style="min-width: 65px; height: 85px; border-radius: 24px; display: flex; flex-direction: column; justify-content: center; align-items: center; background: <?php echo $selected_day == $idx ? 'var(--primary)' : 'var(--surface)'; ?>; color: <?php echo $selected_day == $idx ? 'white' : 'var(--text-dark)'; ?>; border: 1.5px solid <?php echo $selected_day == $idx ? 'var(--primary)' : 'var(--border)'; ?>; transition: 0.3s; box-shadow: <?php echo $selected_day == $idx ? '0 10px 20px var(--primary-glow)' : 'none'; ?>;">
                        <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; opacity: 0.8;"><?php echo $d['name']; ?></span>
                        <span style="font-size: 20px; font-weight: 900; margin-top: 4px;"><?php echo $d['num']; ?></span>
                        <?php if($d['is_today']): ?>
                            <div style="width: 5px; height: 5px; background: <?php echo $selected_day == $idx ? 'white' : 'var(--primary)'; ?>; border-radius: 50%; margin-top: 5px;"></div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="section-title" style="margin-top: 25px;">
            <span><?php echo $days_data[$selected_day]['full_date']; ?></span>
        </div>

        <div class="timeline" style="padding: 0 24px;">
            <?php if (empty($day_schedules)): ?>
                <div style="text-align: center; padding: 60px 24px; background: var(--surface); border-radius: 35px; border: 1.5px dashed var(--border); margin-bottom: 20px;">
                    <div style="background: var(--bg-main); width: 80px; height: 80px; border-radius: 28px; display: flex; justify-content: center; align-items: center; margin: 0 auto 20px; color: var(--primary);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v4"/><path d="M12 18v4"/><path d="M4.93 4.93l2.83 2.83"/><path d="M16.24 16.24l2.83 2.83"/><path d="M2 12h4"/><path d="M18 12h4"/><path d="M4.93 19.07l2.83-2.83"/><path d="M16.24 7.76l2.83-2.83"/></svg>
                    </div>
                    <h3 style="font-size: 20px; font-weight: 900; color: var(--text-dark); letter-spacing: -0.5px;">Scientific Buffer</h3>
                    <p style="color: var(--text-muted); font-size: 14px; margin-top: 8px; font-weight: 500;">No academic nodes detected for this temporal segment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($day_schedules as $item): 
                    $attendance_health = StatEngine::getAttendanceByCourse($user_id, $item['course_id']);
                    $health_color = $attendance_health >= 80 ? 'var(--success)' : ($attendance_health >= 75 ? 'var(--warning)' : 'var(--danger)');
                ?>
                <div class="schedule-node" 
                     data-start="<?php echo $item['start_time']; ?>" 
                     data-end="<?php echo $item['end_time']; ?>"
                     data-name="<?php echo htmlspecialchars($item['course_name']); ?>"
                     style="background: var(--surface); border-radius: 32px; padding: 20px; border: 1.5px solid var(--border); margin-bottom: 20px; display: flex; gap: 15px; position: relative; transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); overflow: hidden;">
                    
                    <div class="time-block" style="width: 65px; flex-shrink: 0; text-align: center; display: flex; flex-direction: column; justify-content: center; border-right: 1.5px solid var(--bg-main); padding-right: 10px;">
                        <span style="font-size: 15px; font-weight: 900; color: var(--text-dark);"><?php echo date('h:i', strtotime($item['start_time'])); ?></span>
                        <span style="font-size: 10px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;"><?php echo date('A', strtotime($item['start_time'])); ?></span>
                    </div>

                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                            <span style="font-size: 10px; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo htmlspecialchars($item['course_code']); ?></span>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <div style="width: 24px; height: 24px; background: <?php echo $health_color; ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 7px; color: white; font-weight: 900; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                    <?php echo $attendance_health; ?>%
                                </div>
                            </div>
                        </div>
                        <h4 style="font-size: 16px; font-weight: 800; color: var(--text-dark); margin: 0 0 5px; line-height: 1.3;"><?php echo htmlspecialchars($item['course_name']); ?></h4>
                        <div style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                            <div style="width: 32px; height: 32px; border-radius: 12px; background: var(--bg-main); overflow: hidden; border: 1.5px solid var(--border);">
                                <img src="<?php echo $item['lecturer_avatar'] ? BASE_URL . $item['lecturer_avatar'] : 'https://api.dicebear.com/7.x/initials/svg?seed=' . urlencode($item['lecturer_name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <div style="flex: 1;">
                                <p style="font-size: 12px; font-weight: 700; color: var(--text-dark); margin: 0;"><?php echo htmlspecialchars($item['lecturer_name'] ?: 'Faculty Member'); ?></p>
                                <p style="font-size: 11px; font-weight: 600; color: var(--text-muted); margin: 0;"><?php echo htmlspecialchars($item['location']); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Live Indicator (Injected via JS) -->
                    <div class="live-status-pill" style="display: none; position: absolute; top: 12px; right: 12px; background: var(--danger); color: white; font-size: 8px; font-weight: 900; padding: 4px 10px; border-radius: 50px; animation: pulse 2s infinite;">LIVE</div>
                    
                    <!-- Countdown Overlays (Injected via JS) -->
                    <div class="countdown-overlay" style="display: none; position: absolute; bottom: 12px; right: 12px; font-size: 10px; font-weight: 800; color: var(--text-muted);"></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div style="height: 100px;"></div>
        <?php include '../includes/navbar.php'; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const nodes = document.querySelectorAll('.schedule-node');
    const nextPill = document.getElementById('next-session-pill');
    const nextClassName = document.getElementById('next-class-name');
    const nextClassTimer = document.getElementById('next-class-timer');

    function updateIntelligence() {
        const now = new Date();
        const currentTime = now.getHours() * 3600 + now.getMinutes() * 60 + now.getSeconds();
        const currentDay = <?php echo date('w'); ?>;
        const selectedDay = <?php echo $selected_day; ?>;
        const isTodaySelected = (currentDay === selectedDay);

        let nextSession = null;

        nodes.forEach(node => {
            const startStr = node.getAttribute('data-start');
            const endStr = node.getAttribute('data-end');
            const name = node.getAttribute('data-name');
            
            const [sH, sM, sS] = startStr.split(':').map(Number);
            const [eH, eM, eS] = endStr.split(':').map(Number);
            
            const startSec = sH * 3600 + sM * 60 + sS;
            const endSec = eH * 3600 + eM * 60 + eS;

            const livePill = node.querySelector('.live-status-pill');
            const countOverlay = node.querySelector('.countdown-overlay');

            if (isTodaySelected) {
                if (currentTime >= startSec && currentTime <= endSec) {
                    // LIVE NOW
                    node.style.borderColor = 'var(--primary)';
                    node.style.boxShadow = '0 10px 30px var(--primary-glow)';
                    livePill.style.display = 'block';
                    
                    const remaining = endSec - currentTime;
                    const mins = Math.floor(remaining / 60);
                    countOverlay.innerText = mins + 'm remaining';
                    countOverlay.style.display = 'block';
                    countOverlay.style.color = 'var(--primary)';
                } else if (currentTime < startSec) {
                    // UPCOMING
                    node.style.borderColor = 'var(--border)';
                    node.style.boxShadow = 'none';
                    livePill.style.display = 'none';
                    
                    const tillStart = startSec - currentTime;
                    const hrs = Math.floor(tillStart / 3600);
                    const mins = Math.floor((tillStart % 3600) / 60);
                    
                    countOverlay.innerText = 'Starts in ' + (hrs > 0 ? hrs + 'h ' : '') + mins + 'm';
                    countOverlay.style.display = 'block';
                    countOverlay.style.color = 'var(--text-muted)';

                    if (!nextSession || startSec < nextSession.time) {
                        nextSession = { name: name, time: startSec, remaining: tillStart };
                    }
                } else {
                    // FINISHED
                    node.style.opacity = '0.6';
                    node.style.filter = 'grayscale(0.5)';
                    countOverlay.style.display = 'none';
                    livePill.style.display = 'none';
                }
            }
        });

        if (nextSession && isTodaySelected) {
            nextPill.style.display = 'block';
            nextClassName.innerText = nextSession.name;
            const m = Math.floor(nextSession.remaining / 60);
            const h = Math.floor(nextSession.remaining / 3600);
            nextClassTimer.innerText = h > 0 ? h + 'h ' + (m % 60) + 'm' : m + 'm';
        } else {
            nextPill.style.display = 'none';
        }
    }

    setInterval(updateIntelligence, 10000); // Update every 10s for performance
    updateIntelligence();

    // Initial icon creation if lucide is loaded
    if (window.lucide) {
        lucide.createIcons();
    }
});
</script>

<?php include '../includes/footer.php'; ?>
