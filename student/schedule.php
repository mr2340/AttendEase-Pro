<?php
require_once '../includes/config.php';
require_once '../includes/stat_engine.php';

$page_title = "My Academic Timeline";
include '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$db = get_db_connection();
$user_id = $_SESSION['user_id'];
$selected_day = isset($_GET['day']) ? (int)$_GET['day'] : (int)date('w');

// Fetch schedules for the selected day (Filtered by Enrollment)
$sched_stmt = $db->prepare("
    SELECT s.*, c.course_name, c.course_code, u.username as lecturer_name, u.avatar_url as lecturer_avatar,
    (SELECT status FROM sessions WHERE course_id = c.id AND status = 'active' ORDER BY created_at DESC LIMIT 1) as live_status
    FROM schedules s 
    JOIN courses c ON s.course_id = c.id 
    JOIN enrollments e ON c.id = e.course_id
    LEFT JOIN users u ON c.lecturer_id = u.id
    WHERE e.student_id = ? AND s.day_of_week = ?
    ORDER BY s.start_time ASC
");
$sched_stmt->execute([$user_id, $selected_day]);
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
    <div style="padding: 30px 20px 10px;">
        <h1 style="font-size: 32px; font-weight: 950; color: #0f172a; letter-spacing: -1.5px;">Weekly <span style="color: var(--primary);">Timeline</span></h1>
        <p style="color: #64748b; font-size: 14px; font-weight: 500;">Your synchronized academic nodes.</p>
    </div>

    <!-- Day Selector (Mobile) -->
    <div class="day-selector-container" style="overflow-x: auto; padding: 20px; scrollbar-width: none; -ms-overflow-style: none;">
        <div style="display: flex; gap: 12px; min-width: max-content;">
            <?php foreach ($ordered_indices as $idx): ?>
            <?php $d = $days_data[$idx]; ?>
            <a href="?day=<?php echo $idx; ?>" style="text-decoration: none;">
                <div class="day-card <?php echo $selected_day == $idx ? 'active' : ''; ?>" style="min-width: 70px; height: 95px; border-radius: 24px; display: flex; flex-direction: column; justify-content: center; align-items: center; background: <?php echo $selected_day == $idx ? 'var(--primary)' : 'white'; ?>; color: <?php echo $selected_day == $idx ? 'white' : '#0f172a'; ?>; border: 1.5px solid <?php echo $selected_day == $idx ? 'var(--primary)' : '#e2e8f0'; ?>; box-shadow: <?php echo $selected_day == $idx ? '0 10px 25px var(--primary-glow)' : 'none'; ?>; transition: all 0.3s ease;">
                    <span style="font-size: 11px; font-weight: 800; opacity: 0.7; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo $d['name']; ?></span>
                    <span style="font-size: 22px; font-weight: 950; margin-top: 4px;"><?php echo $d['num']; ?></span>
                    <?php if($d['is_today']): ?>
                        <div style="width: 5px; height: 5px; background: <?php echo $selected_day == $idx ? 'white' : 'var(--primary)'; ?>; border-radius: 50%; margin-top: 6px;"></div>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div style="padding: 0 20px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 25px;">
            <div style="width: 10px; height: 10px; background: var(--primary); border-radius: 50%;"></div>
            <h3 style="font-size: 13px; font-weight: 900; color: #64748b; text-transform: uppercase; letter-spacing: 1px;"><?php echo $days_data[$selected_day]['full_date']; ?></h3>
        </div>
        
        <?php if (empty($day_schedules)): ?>
            <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 40px; border: 2px dashed #e2e8f0;">
                <div style="width: 60px; height: 60px; background: #f8fafc; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; color: #94a3b8;">
                    <i data-lucide="coffee" style="width: 30px;"></i>
                </div>
                <p style="color: #0f172a; font-weight: 800; font-size: 16px;">Zero nodes detected.</p>
                <p style="color: #64748b; font-size: 13px; margin-top: 5px;">Enjoy your temporal buffer!</p>
            </div>
        <?php else: ?>
            <?php foreach ($day_schedules as $item): 
                $health = StatEngine::getAttendanceByCourse($user_id, $item['course_id']);
                $h_color = $health >= 75 ? '#10b981' : ($health >= 50 ? '#f59e0b' : '#ef4444');
                $is_live = $item['live_status'] === 'active';
            ?>
            <div class="schedule-node" data-start="<?php echo $item['start_time']; ?>" data-end="<?php echo $item['end_time']; ?>" style="background: white; border-radius: 32px; padding: 22px; border: 2px solid <?php echo $is_live ? 'var(--primary)' : '#e2e8f0'; ?>; margin-bottom: 20px; display: flex; gap: 18px; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                <?php if($is_live): ?>
                    <div style="position: absolute; top: -10px; right: 25px; background: var(--primary); color: white; padding: 4px 12px; border-radius: 10px; font-size: 10px; font-weight: 900; letter-spacing: 1px; box-shadow: 0 5px 15px var(--primary-glow);">LIVE NOW</div>
                <?php endif; ?>
                
                <div style="width: 65px; text-align: center; border-right: 2px solid #f8fafc; padding-right: 15px; display: flex; flex-direction: column; justify-content: center;">
                    <span style="font-size: 18px; font-weight: 950; color: #0f172a;"><?php echo date('h:i', strtotime($item['start_time'])); ?></span>
                    <span style="display: block; font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;"><?php echo date('A', strtotime($item['start_time'])); ?></span>
                </div>
                
                <div style="flex: 1;">
                    <span style="font-size: 11px; font-weight: 900; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo htmlspecialchars($item['course_code']); ?></span>
                    <h4 style="font-size: 17px; font-weight: 900; color: #0f172a; margin: 4px 0; letter-spacing: -0.5px;"><?php echo htmlspecialchars($item['course_name']); ?></h4>
                    <div style="display: flex; align-items: center; gap: 6px; margin-top: 6px;">
                        <i data-lucide="map-pin" style="width: 12px; color: #64748b;"></i>
                        <p style="font-size: 12px; color: #64748b; font-weight: 600;"><?php echo htmlspecialchars($item['location']); ?></p>
                    </div>
                </div>
                
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px;">
                    <div style="width: 40px; height: 40px; background: <?php echo $h_color; ?>15; color: <?php echo $h_color; ?>; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 950; border: 1.5px solid <?php echo $h_color; ?>20;">
                        <?php echo $health; ?>%
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="desktop-only-layout" style="background: #f8fafc; min-height: 100vh;">
    <header style="padding: 60px 80px 40px; display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                <div style="background: #3b82f6; width: 15px; height: 15px; border-radius: 4px; box-shadow: 0 0 15px rgba(59, 130, 246, 0.5);"></div>
                <span style="color: #64748b; font-size: 14px; font-weight: 900; letter-spacing: 2px; text-transform: uppercase;">Temporal Interface</span>
            </div>
            <h1 style="font-size: 64px; font-weight: 950; color: #0f172a; letter-spacing: -3px; line-height: 1;">Academic <span style="color: #3b82f6;">Journey</span></h1>
            <p style="color: #94a3b8; font-size: 20px; font-weight: 500; margin-top: 15px;">A synchronized visualization of your instructional nodes.</p>
        </div>
        <div style="text-align: right;">
            <p style="font-size: 14px; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">System Time</p>
            <p id="digital-clock" style="font-size: 32px; font-weight: 950; color: #0f172a; font-family: monospace;">00:00:00</p>
        </div>
    </header>

    <div style="padding: 0 80px 80px; display: grid; grid-template-columns: 350px 1fr; gap: 40px; align-items: start;">
        <!-- Day Selector Sidebar -->
        <div style="background: #0f172a; border-radius: 50px; padding: 45px; color: white; box-shadow: 0 30px 60px rgba(15, 23, 42, 0.2);">
            <h3 style="color: #3b82f6; font-size: 12px; font-weight: 900; letter-spacing: 2px; margin-bottom: 35px; text-transform: uppercase;">Temporal Navigation</h3>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php foreach ($ordered_indices as $idx): ?>
                <?php $d = $days_data[$idx]; ?>
                <a href="?day=<?php echo $idx; ?>" style="text-decoration: none;">
                    <div style="padding: 22px 25px; border-radius: 25px; background: <?php echo $selected_day == $idx ? 'rgba(255,255,255,0.1)' : 'transparent'; ?>; border: 2px solid <?php echo $selected_day == $idx ? 'rgba(255,255,255,0.15)' : 'transparent'; ?>; display: flex; align-items: center; justify-content: space-between; transition: all 0.3s ease;">
                        <span style="color: <?php echo $selected_day == $idx ? 'white' : 'rgba(255,255,255,0.5)'; ?>; font-weight: 800; font-size: 16px;"><?php echo $d['full_date']; ?></span>
                        <?php if($d['is_today']): ?>
                            <div style="width: 10px; height: 10px; background: #3b82f6; border-radius: 50%; box-shadow: 0 0 10px #3b82f6;"></div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Timeline Hub -->
        <div style="background: white; border-radius: 60px; padding: 60px; border: 1.5px solid #f1f5f9; box-shadow: 0 30px 80px rgba(0,0,0,0.02);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 50px;">
                <h2 style="font-size: 36px; font-weight: 950; color: #0f172a; letter-spacing: -1.5px;">Timeline <span style="color: #3b82f6;">Flow</span></h2>
                <div style="background: #f8fafc; padding: 10px 25px; border-radius: 20px; border: 1.5px solid #f1f5f9; font-size: 14px; font-weight: 800; color: #64748b;">
                    <?php echo count($day_schedules); ?> Nodes Programmed
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 20px;">
                <?php if (empty($day_schedules)): ?>
                    <div style="text-align: center; padding: 100px 40px;">
                        <i data-lucide="monitor-off" style="width: 64px; height: 64px; color: #e2e8f0; margin-bottom: 30px;"></i>
                        <p style="color: #94a3b8; font-weight: 700; font-size: 20px;">Buffer Empty: No sessions detected for this period.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($day_schedules as $item): 
                        $is_live = $item['live_status'] === 'active';
                    ?>
                    <div class="schedule-node" data-start="<?php echo $item['start_time']; ?>" data-end="<?php echo $item['end_time']; ?>" style="background: #fcfdfe; border-radius: 40px; padding: 35px; border: 2px solid <?php echo $is_live ? '#3b82f6' : '#f1f5f9'; ?>; display: flex; align-items: center; gap: 40px; transition: all 0.4s ease; position: relative;">
                        <?php if($is_live): ?>
                            <div style="position: absolute; top: 20px; right: 40px; display: flex; align-items: center; gap: 8px;">
                                <div style="width: 8px; height: 8px; background: #3b82f6; border-radius: 50%; animation: pulseShield 2s infinite;"></div>
                                <span style="font-size: 12px; font-weight: 900; color: #3b82f6; letter-spacing: 1px;">NODE LIVE</span>
                            </div>
                        <?php endif; ?>

                        <div style="font-size: 32px; font-weight: 950; color: #0f172a; width: 140px; letter-spacing: -1px; border-right: 3px solid #f1f5f9;"><?php echo date('H:i', strtotime($item['start_time'])); ?></div>
                        
                        <div style="flex: 1;">
                            <div style="font-size: 13px; font-weight: 900; color: #3b82f6; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 5px;"><?php echo htmlspecialchars($item['course_code']); ?></div>
                            <h4 style="font-size: 24px; font-weight: 950; color: #0f172a; letter-spacing: -0.5px;"><?php echo htmlspecialchars($item['course_name']); ?></h4>
                            <div style="display: flex; align-items: center; gap: 15px; margin-top: 15px;">
                                <div style="display: flex; align-items: center; gap: 6px; color: #64748b; font-weight: 700; font-size: 14px;">
                                    <i data-lucide="map-pin" style="width: 16px;"></i> <?php echo htmlspecialchars($item['location']); ?>
                                </div>
                                <div style="width: 1.5px; height: 12px; background: #e2e8f0;"></div>
                                <div style="display: flex; align-items: center; gap: 6px; color: #64748b; font-weight: 700; font-size: 14px;">
                                    <i data-lucide="user" style="width: 16px;"></i> <?php echo htmlspecialchars($item['lecturer_name']); ?>
                                </div>
                            </div>
                        </div>

                        <a href="scan" style="width: 60px; height: 60px; background: #0f172a; color: white; border-radius: 20px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; text-decoration: none;">
                            <i data-lucide="qr-code" style="width: 24px;"></i>
                        </a>
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
        const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
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
                    node.style.borderColor = 'var(--primary)';
                    node.style.background = '#fcfdfe';
                } else if (curSec > endSec) {
                    node.style.opacity = '0.6';
                    node.style.filter = 'grayscale(1)';
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

<?php include '../includes/footer.php'; ?>
