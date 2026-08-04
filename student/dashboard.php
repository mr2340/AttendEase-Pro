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

// Calculate Student Context
$score_stmt = $db->prepare("SELECT COUNT(*) FROM attendance WHERE student_id = ?");
$score_stmt->execute([$user_id]);
$total_scans = $score_stmt->fetchColumn();

$course_count_stmt = $db->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ?");
$course_count_stmt->execute([$user_id]);
$total_courses = $course_count_stmt->fetchColumn();

$user_stmt = $db->prepare("SELECT username, fullname FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

$username = $user['username'] ?? 'Student';
$fullname = $user['fullname'] ?? $username;
$first_name = explode(' ', $fullname)[0];
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

// Fetch Today's Classes (Filtered by Enrollment)
$day_now = date('w');
$sched_stmt = $db->prepare("
    SELECT s.*, c.course_name, c.course_code, u.fullname as lecturer_name
    FROM schedules s 
    JOIN courses c ON s.course_id = c.id 
    JOIN enrollments e ON c.id = e.course_id
    LEFT JOIN users u ON c.lecturer_id = u.id
    WHERE e.student_id = ? AND s.day_of_week = ? 
    ORDER BY s.start_time ASC
");
$sched_stmt->execute([$user_id, $day_now]);
$schedules = $sched_stmt->fetchAll();

// Fetch Last Check-In (Vital Sign)
$last_scan_stmt = $db->prepare("SELECT a.*, c.course_code FROM attendance a JOIN sessions s ON a.session_id = s.id JOIN courses c ON s.course_id = c.id WHERE a.student_id = ? ORDER BY a.marked_at DESC LIMIT 1");
$last_scan_stmt->execute([$user_id]);
$last_scan = $last_scan_stmt->fetch();
?>

<section id="main-dashboard" style="position: relative;">
    
    <!-- Premium Ambient Background Glows -->
    <div style="position: absolute; top: -10%; left: -5%; width: 400px; height: 400px; background: radial-gradient(circle, rgba(0, 102, 255, 0.08) 0%, transparent 70%); filter: blur(60px); z-index: -1;"></div>
    <div style="position: absolute; bottom: 20%; right: -10%; width: 500px; height: 500px; background: radial-gradient(circle, rgba(16, 185, 129, 0.05) 0%, transparent 70%); filter: blur(60px); z-index: -1;"></div>

    <!-- MOBILE PREMIUM VIEW -->
    <div class="mobile-only-layout" style="padding: 10px;">
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 10px;">
            <div>
                <p style="font-size: 11px; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 1px; margin: 0;"><?php echo date('l, d M Y'); ?></p>
                <h1 style="font-size: 24px; font-weight: 900; color: var(--text-dark); margin: 4px 0 0; letter-spacing: -0.5px;">Hi, <?php echo htmlspecialchars($first_name); ?> ✨</h1>
            </div>
            <div style="width: 45px; height: 45px; border-radius: 15px; overflow: hidden; border: 2px solid white; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">
                <img src="<?php echo $display_avatar; ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover;">
            </div>
        </header>

        <!-- Mobile Hero Card -->
        <div style="background: linear-gradient(135deg, #0062ff 0%, #00d2ff 100%); border-radius: 35px; padding: 30px; position: relative; overflow: hidden; box-shadow: 0 20px 40px rgba(0, 102, 255, 0.3); margin-bottom: 25px;">
            <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%); filter: blur(20px);"></div>
            
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: 11px; font-weight: 800; color: rgba(255,255,255,0.8); text-transform: uppercase; letter-spacing: 1.5px;">Performance</span>
                    <div style="font-size: 56px; font-weight: 900; color: white; line-height: 1; margin: 10px 0; letter-spacing: -2px;"><?php echo $attendance_score; ?>%</div>
                </div>
                <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 12px; border-radius: 20px;">
                    <i data-lucide="activity" style="color: white; width: 24px; height: 24px;"></i>
                </div>
            </div>

            <!-- Dynamic Ring Progress -->
            <div style="margin-top: 30px; display: flex; align-items: center; gap: 15px;">
                <svg width="40" height="40" viewBox="0 0 36 36" style="transform: rotate(-90deg);">
                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="4" />
                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="white" stroke-width="4" stroke-dasharray="<?php echo $attendance_score; ?>, 100" />
                </svg>
                <div style="flex: 1;">
                    <div style="font-size: 12px; color: #cbd5e1; font-weight: 600;">Status</div>
                    <div style="font-size: 14px; color: <?php echo $risk_color; ?>; font-weight: 800;"><?php echo $risk_level; ?> RISK</div>
                </div>
            </div>
        </div>

        <!-- Mobile Quick Stats -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
            <div style="background: white; border-radius: 25px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                <div style="width: 40px; height: 40px; background: #eff6ff; color: var(--primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                    <i data-lucide="book-open" style="width: 20px;"></i>
                </div>
                <h4 style="font-size: 24px; font-weight: 900; color: #0f172a; margin: 0;"><?php echo $total_courses; ?></h4>
                <p style="font-size: 12px; color: #64748b; font-weight: 600; margin: 4px 0 0;">Active Courses</p>
            </div>
            <div style="background: white; border-radius: 25px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                <div style="width: 40px; height: 40px; background: #ecfdf5; color: #10b981; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                    <i data-lucide="check-circle" style="width: 20px;"></i>
                </div>
                <h4 style="font-size: 24px; font-weight: 900; color: #0f172a; margin: 0;"><?php echo $total_scans; ?></h4>
                <p style="font-size: 12px; color: #64748b; font-weight: 600; margin: 4px 0 0;">Total Scans</p>
            </div>
        </div>

        <!-- Mobile Timeline -->
        <div style="padding: 0 20px 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-size: 16px; font-weight: 900; color: #0f172a; margin: 0; letter-spacing: -0.5px;">Timeline</h3>
                <a href="schedule" style="font-size: 11px; font-weight: 800; color: var(--primary); text-decoration: none; background: #eff6ff; padding: 6px 14px; border-radius: 100px;">View All</a>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php if(empty($schedules)): ?>
                    <div style="background: white; border-radius: 25px; padding: 30px 20px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.02); border: 1px dashed #cbd5e1;">
                        <i data-lucide="calendar-x" style="color: #cbd5e1; width: 32px; height: 32px; margin-bottom: 10px;"></i>
                        <h4 style="font-size: 14px; font-weight: 800; color: #1e293b; margin: 0 0 5px 0;">Clear Schedule</h4>
                        <p style="color: #94a3b8; font-size: 12px; font-weight: 500; margin: 0;">No active classes today.</p>
                    </div>
                <?php else: ?>
                    <?php foreach(array_slice($schedules, 0, 3) as $item): 
                        $is_live = (time() >= strtotime(date('Y-m-d') . ' ' . $item['start_time']) && time() <= strtotime(date('Y-m-d') . ' ' . $item['end_time']));
                    ?>
                    <div style="display: flex; gap: 15px; align-items: center; padding: 15px; background: <?php echo $is_live ? '#eff6ff' : 'white'; ?>; border: 1px solid <?php echo $is_live ? '#bfdbfe' : 'rgba(0,0,0,0.05)'; ?>; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.02);">
                        <div style="text-align: center; min-width: 45px;">
                            <div style="font-weight: 900; color: <?php echo $is_live ? 'var(--primary)' : '#1e293b'; ?>; font-size: 14px;"><?php echo date('H:i', strtotime($item['start_time'])); ?></div>
                            <div style="font-size: 9px; color: #94a3b8; font-weight: 800; text-transform: uppercase;"><?php echo date('A', strtotime($item['start_time'])); ?></div>
                        </div>
                        
                        <div style="width: 2px; height: 25px; background: #e2e8f0; border-radius: 2px;"></div>
                        
                        <div style="flex: 1;">
                            <div style="font-weight: 900; font-size: 14px; color: #0f172a; margin-bottom: 2px;"><?php echo htmlspecialchars($item['course_code']); ?></div>
                            <div style="font-size: 11px; color: #64748b; font-weight: 600; display: flex; align-items: center; gap: 4px;">
                                <i data-lucide="map-pin" style="width: 10px;"></i> <?php echo htmlspecialchars($item['location']); ?>
                            </div>
                        </div>
                        
                        <?php if($is_live): ?>
                            <div style="width: 10px; height: 10px; background: #3b82f6; border-radius: 50%; box-shadow: 0 0 10px rgba(59, 130, 246, 0.6); animation: pulse-shake 2s infinite;"></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Mobile Last Scan -->
        <div style="padding: 0 20px 100px;">
            <h3 style="font-size: 16px; font-weight: 900; color: #0f172a; margin: 0 0 15px 0; letter-spacing: -0.5px;">Last Check-In</h3>
            
            <?php if($last_scan): ?>
                <div style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); padding: 20px; border-radius: 25px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; background: white; border-radius: 15px; display: flex; justify-content: center; align-items: center; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                        <i data-lucide="scan-face" style="width: 24px; height: 24px; color: var(--primary);"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="font-size: 16px; font-weight: 900; color: #0f172a; margin: 0 0 3px 0; letter-spacing: -0.5px;"><?php echo htmlspecialchars($last_scan['course_code']); ?></h4>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <span style="font-size: 11px; color: #64748b; font-weight: 600;"><i data-lucide="clock" style="width: 12px; display: inline; vertical-align: -2px;"></i> <?php echo date('M d, h:i A', strtotime($last_scan['marked_at'])); ?></span>
                            <span style="font-size: 9px; background: #ecfdf5; color: #10b981; padding: 3px 8px; border-radius: 8px; font-weight: 800;">VERIFIED</span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div style="background: white; padding: 30px 20px; border-radius: 25px; border: 1px dashed #cbd5e1; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.02);">
                    <i data-lucide="history" style="width: 24px; height: 24px; color: #94a3b8; margin-bottom: 10px;"></i>
                    <h4 style="font-size: 14px; font-weight: 800; color: #1e293b; margin: 0 0 5px 0;">No History Found</h4>
                    <p style="font-size: 12px; color: #64748b; font-weight: 500; margin: 0;">Scan your first QR code to build your profile.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Mobile Floating Action Button -->
        <button onclick="AttendEase.startScanner()" style="position: fixed; bottom: 90px; right: 20px; width: 60px; height: 60px; background: linear-gradient(135deg, var(--primary) 0%, #3b82f6 100%); border-radius: 20px; color: white; border: none; box-shadow: 0 15px 35px rgba(0, 102, 255, 0.4); display: flex; align-items: center; justify-content: center; z-index: 100;">
            <i data-lucide="scan-line" style="width: 28px; height: 28px;"></i>
        </button>
    </div>

    <!-- DESKTOP PREMIUM BENTO VIEW -->
    <div class="desktop-only-layout" style="max-width: 1400px; margin: 0 auto;">
        
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
            <div>
                <h1 style="font-size: 42px; font-weight: 900; color: #0f172a; letter-spacing: -1.5px; margin: 0; line-height: 1.2;">
                    <?php echo $greeting; ?>, <span style="background: linear-gradient(90deg, var(--primary), #38bdf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo htmlspecialchars($first_name); ?></span> ✨
                </h1>
                <p style="color: #64748b; font-size: 16px; font-weight: 500; margin: 5px 0 0;">Here's your academic status for <?php echo date('l, F jS'); ?></p>
            </div>
            
            <div style="display: flex; align-items: center; gap: 20px;">
                <div style="background: white; padding: 12px 24px; border-radius: 100px; display: flex; align-items: center; gap: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05);">
                    <div style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; box-shadow: 0 0 10px #10b981; animation: pulse-shake 2s infinite;"></div>
                    <span style="font-size: 12px; font-weight: 800; color: #1e293b; letter-spacing: 1px;">SYSTEM ONLINE</span>
                </div>
            </div>
        </header>

        <div class="bento-grid">
            
            <!-- 1. Massive Hero Card (Span 8) -->
            <div class="bento-card bento-hero-card card-large" style="display: flex; flex-direction: column; justify-content: center; position: relative; overflow: hidden; padding: 40px;">
                <div style="position: absolute; right: -80px; top: -80px; opacity: 0.05; pointer-events: none;">
                    <i data-lucide="trending-up" style="width: 400px; height: 400px; color: white;"></i>
                </div>
                
                <div style="display: flex; gap: 50px; align-items: center; z-index: 1;">
                    <div style="position: relative; width: 180px; height: 180px; flex-shrink: 0;">
                        <svg viewBox="0 0 36 36" style="transform: rotate(-90deg); width: 100%; height: 100%;">
                            <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="2.5" />
                            <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="white" stroke-width="2.5" stroke-dasharray="<?php echo $attendance_score; ?>, 100" style="filter: drop-shadow(0 0 12px rgba(255, 255, 255, 0.4)); transition: stroke-dasharray 1s ease-out;" />
                        </svg>
                        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                            <span style="font-size: 42px; font-weight: 900; color: white; line-height: 1; letter-spacing: -2px;"><?php echo $attendance_score; ?><span style="font-size: 20px; color: rgba(255,255,255,0.8);">%</span></span>
                        </div>
                    </div>

                    <div style="flex: 1;">
                        <div style="display: inline-block; padding: 6px 14px; background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 100px; margin-bottom: 20px;">
                            <span style="font-size: 11px; font-weight: 800; color: white; text-transform: uppercase; letter-spacing: 1.5px;">Semester Trajectory</span>
                        </div>
                        
                        <div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); padding: 25px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.3); box-shadow: inset 0 0 20px rgba(255,255,255,0.1);">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                                <i data-lucide="sparkles" style="color: white; width: 20px; height: 20px;"></i>
                                <h4 style="color: white; font-size: 17px; font-weight: 800; margin: 0; letter-spacing: -0.5px;">AI Diagnostic</h4>
                            </div>
                            <p style="color: rgba(255,255,255,0.9); font-size: 15px; margin: 0; font-weight: 500; line-height: 1.6;">
                                <?php if($attendance_score >= 75): ?>
                                    Highly stable attendance pattern. You are safely above the examination threshold.
                                <?php else: ?>
                                    Critical: Your pattern is unstable. Immediate action required to restore examination eligibility.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Today's Schedule (Span 4) -->
            <div class="bento-card card-medium" style="background: white; display: flex; flex-direction: column;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h3 style="font-size: 20px; font-weight: 900; color: #0f172a; margin: 0; letter-spacing: -0.5px;">Timeline</h3>
                    <a href="schedule" style="font-size: 13px; font-weight: 800; color: var(--primary); text-decoration: none; background: #eff6ff; padding: 6px 14px; border-radius: 100px;">View All</a>
                </div>
                
                <div style="flex: 1; display: flex; flex-direction: column; gap: 15px; overflow-y: auto;">
                    <?php if(empty($schedules)): ?>
                        <div style="flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; padding: 20px;">
                            <div style="width: 80px; height: 80px; background: #f8fafc; border-radius: 25px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                                <i data-lucide="calendar-x" style="color: #cbd5e1; width: 40px; height: 40px;"></i>
                            </div>
                            <h4 style="font-size: 16px; font-weight: 800; color: #1e293b; margin: 0 0 5px 0;">Clear Schedule</h4>
                            <p style="color: #94a3b8; font-size: 13px; font-weight: 500; margin: 0; max-width: 200px;">You have no active classes mapped for today.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach(array_slice($schedules, 0, 4) as $item): 
                            $is_live = (time() >= strtotime(date('Y-m-d') . ' ' . $item['start_time']) && time() <= strtotime(date('Y-m-d') . ' ' . $item['end_time']));
                        ?>
                        <div style="display: flex; gap: 20px; align-items: center; padding: 18px; background: <?php echo $is_live ? '#eff6ff' : '#f8fafc'; ?>; border: 1px solid <?php echo $is_live ? '#bfdbfe' : 'transparent'; ?>; border-radius: 20px; transition: all 0.3s; cursor: pointer;">
                            <div style="text-align: center; min-width: 50px;">
                                <div style="font-weight: 900; color: <?php echo $is_live ? 'var(--primary)' : '#1e293b'; ?>; font-size: 16px;"><?php echo date('H:i', strtotime($item['start_time'])); ?></div>
                                <div style="font-size: 10px; color: #94a3b8; font-weight: 800; text-transform: uppercase;"><?php echo date('A', strtotime($item['start_time'])); ?></div>
                            </div>
                            
                            <div style="width: 2px; height: 30px; background: #e2e8f0; border-radius: 2px;"></div>
                            
                            <div style="flex: 1;">
                                <div style="font-weight: 900; font-size: 15px; color: #0f172a; margin-bottom: 3px;"><?php echo htmlspecialchars($item['course_code']); ?></div>
                                <div style="font-size: 12px; color: #64748b; font-weight: 600; display: flex; align-items: center; gap: 5px;">
                                    <i data-lucide="map-pin" style="width: 12px;"></i> <?php echo htmlspecialchars($item['location']); ?>
                                </div>
                            </div>
                            
                            <?php if($is_live): ?>
                                <div style="width: 12px; height: 12px; background: #3b82f6; border-radius: 50%; box-shadow: 0 0 10px rgba(59, 130, 246, 0.6); animation: pulse-shake 2s infinite;"></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 3. Pulse / Last Scan (Span 6) -->
            <div class="bento-card card-small" style="background: white; grid-column: span 6;">
                <h3 style="font-size: 20px; font-weight: 900; color: #0f172a; margin: 0 0 25px 0; letter-spacing: -0.5px;">Last Check-In</h3>
                
                <?php if($last_scan): ?>
                    <div style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); padding: 30px; border-radius: 25px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 25px;">
                        <div style="width: 70px; height: 70px; background: white; border-radius: 22px; display: flex; justify-content: center; align-items: center; box-shadow: 0 15px 35px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                            <i data-lucide="scan-face" style="width: 32px; height: 32px; color: var(--primary);"></i>
                        </div>
                        <div style="flex: 1;">
                            <h4 style="font-size: 24px; font-weight: 900; color: #0f172a; margin: 0 0 5px 0; letter-spacing: -0.5px;"><?php echo htmlspecialchars($last_scan['course_code']); ?></h4>
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <span style="font-size: 13px; color: #64748b; font-weight: 600;"><i data-lucide="clock" style="width: 14px; display: inline; vertical-align: -2px;"></i> <?php echo date('M d, h:i A', strtotime($last_scan['marked_at'])); ?></span>
                                <span style="font-size: 11px; background: #ecfdf5; color: #10b981; padding: 4px 10px; border-radius: 8px; font-weight: 800;">VERIFIED</span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="background: #f8fafc; padding: 40px 20px; border-radius: 25px; border: 1px dashed #cbd5e1; text-align: center;">
                        <i data-lucide="history" style="width: 32px; height: 32px; color: #94a3b8; margin-bottom: 15px;"></i>
                        <h4 style="font-size: 16px; font-weight: 800; color: #1e293b; margin: 0 0 5px 0;">No History Found</h4>
                        <p style="font-size: 13px; color: #64748b; font-weight: 500; margin: 0;">Scan your first QR code to build your profile.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 4. Quick Metrics / Trend (Span 6) -->
            <div class="bento-card card-small" style="background: white; grid-column: span 6; display: flex; gap: 20px;">
                <div style="flex: 1; background: #f8fafc; padding: 25px; border-radius: 25px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; justify-content: center;">
                    <span style="font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">Enrolled Courses</span>
                    <h2 style="font-size: 48px; font-weight: 900; color: #0f172a; margin: 10px 0 0 0; line-height: 1;"><?php echo $total_courses; ?></h2>
                </div>
                
                <div style="flex: 1; background: #f8fafc; padding: 25px; border-radius: 25px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; justify-content: center;">
                    <span style="font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">Total Validations</span>
                    <h2 style="font-size: 48px; font-weight: 900; color: var(--primary); margin: 10px 0 0 0; line-height: 1;"><?php echo $total_scans; ?></h2>
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
