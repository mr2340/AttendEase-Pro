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

// Intelligence Engine - Dynamic Attendance Scoring
require_once '../includes/stat_engine.php';
$attendance_score = StatEngine::getStudentAttendanceScore($user_id);

// AI Risk Analysis
$risk_level = 'LOW';
$risk_color = 'var(--success)';
if($attendance_score < 75) {
    $risk_level = 'HIGH';
    $risk_color = 'var(--danger)';
} else if ($attendance_score < 80) {
    $risk_level = 'MEDIUM';
    $risk_color = 'var(--warning)';
}

// Fetch Today's Schedule
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkles"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/><path d="M5 3v4"/><path d="M19 17v4"/><path d="M3 5h4"/><path d="M17 19h4"/></svg>
                </h1>
                <div id="live-clock-pill" style="display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; color: var(--primary); background: var(--primary-glow); padding: 6px 14px; border-radius: 100px; border: 1px solid rgba(0, 102, 255, 0.1);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span id="live-clock"></span>
                </div>
            </div>
            <div class="profile-avatar" style="width: 70px; height: 70px; border-radius: 26px; overflow: hidden; border: 3px solid var(--surface); box-shadow: 0 15px 35px var(--primary-glow); position: relative;">
                <img src="<?php echo $display_avatar; ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                <div style="position: absolute; bottom: 0; right: 0; width: 14px; height: 14px; background: #22c55e; border: 2.5px solid var(--surface); border-radius: 50%;"></div>
            </div>
        </div>

        <!-- Full-Width Modernized Attendance Card -->
        <div style="padding: 0 24px; margin-bottom: 24px;">
            <div class="stats-card" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 30px; border-radius: 35px; color: white; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.2); position: relative; overflow: hidden; margin: 0; width: 100%;">
                <!-- Decorative Glow -->
                <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: var(--primary); filter: blur(70px); opacity: 0.3;"></div>
                
                <div style="display: flex; justify-content: space-between; align-items: flex-start; position: relative; z-index: 2;">
                    <div>
                        <p style="font-size: 13px; font-weight: 700; opacity: 0.7; margin-bottom: 8px; letter-spacing: 0.5px;">Semester Attendance</p>
                        <h3 style="font-size: 52px; font-weight: 900; line-height: 1; letter-spacing: -2px;"><?php echo $attendance_score; ?>%</h3>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; opacity: 0.6; display: block; margin-bottom: 6px;">AI RISK ANALYSIS</span>
                        <div style="background: <?php echo $risk_color; ?>; color: white; padding: 6px 12px; border-radius: 50px; font-size: 11px; font-weight: 900; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 8px 16px rgba(0,0,0,0.2);">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                            <?php echo $risk_level; ?> RISK
                        </div>
                    </div>
                </div>
                
                <div style="background: rgba(255,255,255,0.1); height: 10px; border-radius: 10px; margin: 25px 0 15px; overflow: hidden; position: relative; z-index: 2;">
                    <div style="width: <?php echo $attendance_score; ?>%; background: var(--secondary); height: 100%; border-radius: 10px; box-shadow: 0 0 15px var(--secondary);"></div>
                </div>
                
                <p style="font-size: 12px; opacity: 0.9; font-weight: 600; line-height: 1.5; position: relative; z-index: 2;">
                    <?php if($risk_level == 'HIGH'): ?>
                        ⚠️ Threshold alert! You need more sessions to stay safe.
                    <?php else: ?>
                        Great job! You are currently safe. Keep maintain your attendance trend.
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- NEW: Lecturer Announcements Hub -->
        <?php
        try {
            $ann_stmt = $db->query("SELECT a.*, u.username as lecturer_name FROM announcements a JOIN users u ON a.lecturer_id = u.id ORDER BY a.created_at DESC LIMIT 2");
            $announcements = $ann_stmt->fetchAll();
        } catch (PDOException $e) {
            $announcements = []; // Suppress error if table doesn't exist yet
        }
        ?>
        <?php if (!empty($announcements)): ?>
        <div class="section-title">
            <span>Faculty Announcements</span>
            <span style="background: var(--danger); color: white; font-size: 10px; padding: 2px 8px; border-radius: 50px;">NEW</span>
        </div>
        <div style="padding: 0 24px; margin-bottom: 24px;">
            <?php foreach($announcements as $ann): ?>
            <div style="background: linear-gradient(135deg, var(--surface) 0%, var(--bg-main) 100%); padding: 20px; border-radius: 28px; border: 1.5px dashed var(--primary-glow); margin-bottom: 12px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 32px; height: 32px; background: var(--primary); color: white; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>
                        </div>
                        <span style="font-size: 13px; font-weight: 700; color: var(--text-dark);"><?php echo htmlspecialchars($ann['lecturer_name']); ?></span>
                    </div>
                    <span style="font-size: 10px; color: var(--text-muted); font-weight: 600;"><?php echo date('M d, H:i', strtotime($ann['created_at'])); ?></span>
                </div>
                <h4 style="font-size: 15px; font-weight: 800; color: var(--text-dark); margin-bottom: 6px;"><?php echo htmlspecialchars($ann['title']); ?></h4>
                <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5;"><?php echo htmlspecialchars($ann['message']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Quick Notifications (Renamed to Recent Alerts) -->
        <?php if (!empty($notifications)): ?>
        <div class="section-title">
            <span>System Log</span>
        </div>
        <div style="padding: 0 24px; margin-bottom: 24px;">
            <?php foreach($notifications as $notif): ?>
            <div style="background: var(--surface); padding: 18px; border-radius: 24px; border: 1px solid var(--border); margin-bottom: 12px; display: flex; gap: 15px; align-items: center;">
                <div style="background: var(--primary-glow); color: var(--primary); padding: 10px; border-radius: 14px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                </div>
                <div style="flex: 1;">
                    <h4 style="font-size: 13px; font-weight: 700; color: var(--text-dark);"><?php echo htmlspecialchars($notif['title']); ?></h4>
                    <p style="font-size: 11px; color: var(--text-muted); margin-top: 2px;"><?php echo htmlspecialchars($notif['message']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Upcoming Classes -->
        <div class="section-title">
            <span>Today's Classes</span>
            <a href="schedule" class="view-all" style="display: flex; align-items: center; gap: 4px; text-decoration: none;">
                View All <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </a>
        </div>

        <div class="timeline">
            <?php if ($day_now == 0 || $day_now == 6): ?>
                <div style="padding: 30px 24px; background: linear-gradient(135deg, rgba(0, 102, 255, 0.05) 0%, rgba(0, 102, 255, 0.02) 100%); border-radius: 32px; margin: 0 24px; border: 1px solid var(--primary-glow); text-align: center;">
                    <div style="width: 60px; height: 60px; background: var(--surface); border-radius: 20px; display: flex; justify-content: center; align-items: center; margin: 0 auto 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); color: var(--primary);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/><line x1="6" x2="6" y1="2" y2="4"/><line x1="10" x2="10" y1="2" y2="4"/><line x1="14" x2="14" y1="2" y2="4"/></svg>
                    </div>
                    <h3 style="font-size: 20px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;">Weekend Mode On!</h3>
                    <p style="color: var(--text-muted); font-size: 15px; line-height: 1.6; font-weight: 500; max-width: 240px; margin: 0 auto;">
                        Read, rest, do your assignments and <span style="color: var(--primary); font-weight: 700;">prepare for the new week ahead.</span>
                    </p>
                </div>
            <?php elseif (empty($schedules)): ?>
                <div style="text-align: center; padding: 50px 24px; background: var(--surface); border-radius: 28px; margin: 0 24px; border: 1px solid var(--border);">
                    <div style="width: 50px; height: 50px; background: var(--bg-main); border-radius: 50%; display: flex; justify-content: center; align-items: center; margin: 0 auto 15px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="m9 16 3 3 3-3"/></svg>
                    </div>
                    <p style="color: var(--text-muted); font-size: 14px; font-weight: 600;">No classes scheduled for today!</p>
                </div>
            <?php else: ?>
                <?php 
                $current_time = time();
                foreach($schedules as $item): 
                    $start_timestamp = strtotime(date('Y-m-d') . ' ' . $item['start_time']);
                    $end_timestamp = strtotime(date('Y-m-d') . ' ' . $item['end_time']);
                    
                    $is_live = ($current_time >= $start_timestamp && $current_time <= $end_timestamp);
                    $is_done = ($current_time > $end_timestamp);
                    
                    $status_text = 'Upcoming';
                    $status_color = 'var(--text-muted)';
                    $border_style = '1px solid var(--border)';
                    
                    if ($is_live) {
                        $status_text = 'LIVE NOW';
                        $status_color = 'var(--danger)';
                        $border_style = '2px solid var(--primary)';
                    } elseif ($is_done) {
                        $status_text = 'FINISHED';
                        $status_color = 'var(--success)';
                        $border_style = '1px solid var(--border)';
                    }
                ?>
                <div class="timeline-item" style="border: <?php echo $border_style; ?>; position: relative; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer;" onclick="this.style.transform='scale(0.98)'; setTimeout(() => this.style.transform='scale(1)', 100);">
                    <?php if($is_live): ?>
                        <div style="position: absolute; top: -10px; left: 24px; background: var(--danger); color: white; font-size: 9px; font-weight: 800; padding: 2px 10px; border-radius: 50px; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3); animation: pulse 2s infinite;">
                            <?php echo $status_text; ?>
                        </div>
                    <?php endif; ?>

                    <div class="time-box" style="background: <?php echo $is_live ? 'var(--primary-glow)' : 'var(--bg-main)'; ?>;">
                        <p style="color: <?php echo $is_live ? 'var(--primary)' : 'var(--text-dark)'; ?>; font-weight: 800; font-size: 15px;"><?php echo date('H:i', strtotime($item['start_time'])); ?></p>
                        <p style="font-size: 9px; opacity: 0.6; font-weight: 700;"><?php echo date('A', strtotime($item['start_time'])); ?></p>
                    </div>
                    
                    <div class="class-info" style="margin-left: 15px; flex: 1;">
                        <span style="font-size: 9px; font-weight: 800; color: <?php echo $status_color; ?>; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo $status_text; ?></span>
                        <h4 style="font-size: 15px; font-weight: 800; color: var(--text-dark); margin-top: 2px;"><?php echo htmlspecialchars($item['course_name']); ?></h4>
                        <p style="font-size: 12px; color: var(--text-muted); font-weight: 500;"><?php echo htmlspecialchars($item['course_code']); ?> • <?php echo htmlspecialchars($item['location']); ?></p>
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <button class="sync-btn" style="width: 40px; height: 40px; background: var(--bg-main); border: none; border-radius: 14px; display: flex; justify-content: center; align-items: center; color: var(--primary); cursor: pointer;" 
                                onclick="openCalendarOptions('<?php echo addslashes($item['course_name']); ?>', '<?php echo date('Ymd\THis', $start_timestamp); ?>', '<?php echo date('Ymd\THis', $end_timestamp); ?>', '<?php echo addslashes($item['location']); ?>')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="m9 16 3 3 3-3"/></svg>
                        </button>
                        <div style="width: 40px; height: 40px; background: <?php echo $is_done ? 'var(--success-glow)' : 'var(--bg-main)'; ?>; border-radius: 14px; display: flex; justify-content: center; align-items: center; color: <?php echo $is_done ? 'var(--success)' : 'var(--border)'; ?>;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Modern Calendar Action Sheet -->
        <div id="calendar-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); z-index: 3000; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; display: flex; align-items: flex-end;">
            <div id="calendar-sheet" style="width: 100%; background: var(--surface); border-radius: 32px 32px 0 0; padding: 24px; transform: translateY(100%); transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
                <div style="width: 40px; height: 4px; background: var(--border); border-radius: 2px; margin: 0 auto 20px;"></div>
                <h3 style="font-size: 18px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;">Sync to Calendar</h3>
                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 24px;">Choose your preferred calendar service</p>
                
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <a id="google-cal-link" target="_blank" style="display: flex; align-items: center; gap: 12px; padding: 16px; background: #fef2f2; border-radius: 20px; text-decoration: none; border: 1.5px solid rgba(239, 68, 68, 0.1);">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Google_Calendar_icon_%282020%29.svg" width="24" height="24">
                        <span style="font-weight: 700; color: #b91c1c;">Google Calendar</span>
                    </a>
                    
                    <a id="outlook-cal-link" target="_blank" style="display: flex; align-items: center; gap: 12px; padding: 16px; background: #eff6ff; border-radius: 20px; text-decoration: none; border: 1.5px solid rgba(59, 130, 246, 0.1);">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/d/df/Microsoft_Office_Outlook_%282018%E2%80%93present%29.svg" width="24" height="24">
                        <span style="font-weight: 700; color: #1d4ed8;">Outlook / Office 365</span>
                    </a>
                </div>
                
                <button onclick="closeCalendarOptions()" style="width: 100%; margin-top: 20px; padding: 16px; background: var(--bg-main); border: none; border-radius: 20px; font-weight: 700; color: var(--text-muted); cursor: pointer;">Cancel</button>
                <div style="height: 20px;"></div>
            </div>
        </div>

        <script>
        function openCalendarOptions(title, start, end, location) {
            const modal = document.getElementById('calendar-modal');
            const sheet = document.getElementById('calendar-sheet');
            
            // Build Google Link
            const googleUrl = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(title)}&dates=${start}/${end}&location=${encodeURIComponent(location)}&details=Lecture via AttendEase Pro`;
            document.getElementById('google-cal-link').href = googleUrl;
            
            // Build Outlook Link
            const outlookUrl = `https://outlook.office.com/calendar/0/deeplink/compose?subject=${encodeURIComponent(title)}&startdt=${start}&enddt=${end}&location=${encodeURIComponent(location)}&body=Lecture via AttendEase Pro`;
            document.getElementById('outlook-cal-link').href = outlookUrl;

            modal.style.opacity = '1';
            modal.style.pointerEvents = 'auto';
            sheet.style.transform = 'translateY(0)';
        }

        function closeCalendarOptions() {
            const modal = document.getElementById('calendar-modal');
            const sheet = document.getElementById('calendar-sheet');
            modal.style.opacity = '0';
            modal.style.pointerEvents = 'none';
            sheet.style.transform = 'translateY(100%)';
        }
        </script>

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
