<?php
require_once '../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth Check - Allow Lecturers and Admins
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'lecturer' && $_SESSION['role'] !== 'admin')) {
    header("Location: login");
    exit;
}

$page_title = "Home";
include '../includes/header.php';

$db = get_db_connection();
$user_id = $_SESSION['user_id'];

// Fetch Courses managed by this lecturer
$stmt = $db->prepare("SELECT * FROM courses WHERE lecturer_id = ?");
$stmt->execute([$user_id]);
$courses = $stmt->fetchAll();

// Fetch Active or Paused Sessions
$stmt = $db->prepare("
    SELECT s.*, c.course_name 
    FROM sessions s 
    JOIN courses c ON s.course_id = c.id 
    WHERE s.lecturer_id = ? AND s.status IN ('active', 'paused')
");
$stmt->execute([$user_id]);
$active_sessions = $stmt->fetchAll();
// Intelligence Engine - Dynamic Stats
require_once '../includes/stat_engine.php';
$stats = StatEngine::getLecturerStats($user_id);
?>

<div class="mobile-only-layout">
<section id="lecturer-dashboard" class="screen" data-state="active">
    <div class="scrollable-content">
        <div class="dash-header" style="margin-bottom: 30px;">
            <div class="greeting">
                <p style="font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 1.5px; font-size: 11px; margin-bottom: 5px;">Faculty Portal</p>
                <h1 style="font-size: 32px; font-weight: 900; letter-spacing: -1px; color: var(--text-dark);">
                    Welcome, <span style="color: var(--primary);">Dr. <?php echo htmlspecialchars(explode('_', $_SESSION['username'] ?? 'Lecturer_Name')[1] ?? 'Lecturer'); ?></span>
                </h1>
            </div>
            <div class="profile-avatar" style="width: 60px; height: 60px; border-radius: 20px; border: 2px solid var(--surface); box-shadow: 0 10px 30px var(--primary-glow);">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Lecturer" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        </div>

        <!-- Quick Stats -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; padding: 0 24px; margin-bottom: 30px;">
            <div style="background: var(--surface); padding: 20px; border-radius: 24px; border: 1px solid var(--border);">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 10px;"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="M12 6v8"/><path d="M9 22h6"/></svg>
                <h3 style="font-size: 24px; font-weight: 800;"><?php echo $stats['total_sessions']; ?></h3>
                <p style="font-size: 12px; color: var(--text-muted);">Sessions Held</p>
            </div>
            <div style="background: var(--surface); padding: 20px; border-radius: 24px; border: 1px solid var(--border);">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 10px;"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <h3 style="font-size: 24px; font-weight: 800;"><?php echo $stats['total_students']; ?></h3>
                <p style="font-size: 12px; color: var(--text-muted);">Total Reach</p>
            </div>
        </div>

        <!-- Active QR Sessions -->
        <div class="section-title">
            <span>Live Attendance</span>
        </div>
        
        <div style="padding: 0 24px; margin-bottom: 30px;">
            <?php if (empty($active_sessions)): ?>
                <div style="background: var(--primary-glow); padding: 25px; border-radius: 28px; text-align: center; border: 1px dashed var(--primary);">
                    <p style="color: var(--primary); font-weight: 600; margin-bottom: 15px;">No active attendance sessions.</p>
                    <button onclick="window.location.href='generate_qr'" class="btn-primary" style="padding: 12px 24px; font-size: 14px; width: auto;">
                        Start New Session
                    </button>
                </div>
            <?php else: ?>
                <?php foreach($active_sessions as $sess): 
                    $is_paused = $sess['status'] === 'paused';
                ?>
                    <div style="background: var(--surface); padding: 20px; border-radius: 24px; border: 2px solid <?php echo $is_paused ? 'var(--warning)' : 'var(--primary)'; ?>; margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <h4 style="font-weight: 800;"><?php echo htmlspecialchars($sess['course_name']); ?></h4>
                                <span style="font-size: 10px; font-weight: 900; padding: 2px 8px; border-radius: 6px; background: <?php echo $is_paused ? 'var(--warning)' : 'var(--primary)'; ?>; color: white; text-transform: uppercase;">
                                    <?php echo $sess['status']; ?>
                                </span>
                            </div>
                            <p style="font-size: 12px; color: var(--text-muted);">Session ID: <?php echo $sess['id']; ?></p>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button onclick="toggleSession(<?php echo $sess['id']; ?>, '<?php echo $sess['status']; ?>')" style="background: <?php echo $is_paused ? 'var(--success)' : 'var(--warning)'; ?>; color: white; border: none; padding: 10px 15px; border-radius: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; font-weight: 700; font-size: 12px;">
                                <?php if ($is_paused): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                    RESUME
                                <?php else: ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/></svg>
                                    PAUSE
<?php endif; ?>
                            </button>
                            <a href="generate_qr" style="background: <?php echo $is_paused ? 'var(--warning)' : 'var(--primary)'; ?>; color: white; padding: 10px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/><path d="M21 21v.01"/><path d="M12 7v3a2 2 0 0 1-2 2H7"/><path d="M3 12h.01"/><path d="M12 3h.01"/><path d="M12 16h.01"/><path d="M16 12h1"/><path d="M21 12v.01"/><path d="M12 21v-1"/></svg>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Broadcast Alert System -->
        <div class="section-title">
            <span>Broadcast Intelligence</span>
        </div>
        <div style="padding: 0 24px; margin-bottom: 30px;">
            <div style="background: var(--surface); padding: 24px; border-radius: 32px; border: 1.5px solid var(--border); box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                    <div style="width: 40px; height: 40px; background: var(--primary-glow); color: var(--primary); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>
                    </div>
                    <h3 style="font-size: 16px; font-weight: 800;">Send Instant Announcement</h3>
                </div>
                
                <form id="announcementForm" style="display: flex; flex-direction: column; gap: 15px;">
                    <select name="course_id" class="form-control" style="padding: 12px; border-radius: 14px; font-size: 13px;" required>
                        <option value="">Select Target Course...</option>
                        <?php foreach($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['course_code']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="title" placeholder="Announcement Title (e.g. Class Moved)" class="form-control" style="padding: 12px; border-radius: 14px; font-size: 13px;" required>
                    <textarea name="message" placeholder="Message content..." style="width: 100%; height: 100px; padding: 15px; border-radius: 16px; border: 1.5px solid var(--border); font-size: 13px; outline: none;"></textarea>
                    
                    <button type="submit" class="btn-primary" style="padding: 14px; border-radius: 16px; font-size: 14px; font-weight: 800; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                        Send to All Students
                    </button>
                </form>
            </div>
        </div>

        <!-- Course List -->
        <div class="section-title">
            <span>Course Catalog</span>
        </div>
        
        <div style="padding: 0 24px;">
            <?php foreach($courses as $course): ?>
                <div style="background: var(--surface); padding: 18px; border-radius: 24px; border: 1px solid var(--border); margin-bottom: 12px; display: flex; align-items: center; gap: 15px;">
                    <div style="width: 45px; height: 45px; background: var(--bg-main); border-radius: 12px; display: flex; justify-content: center; align-items: center; color: var(--primary);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="font-size: 14px; font-weight: 700;"><?php echo htmlspecialchars($course['course_name']); ?></h4>
                        <p style="font-size: 11px; color: var(--text-muted);"><?php echo htmlspecialchars($course['course_code']); ?></p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button onclick="showCourseQR('<?php echo $course['permanent_token']; ?>', '<?php echo addslashes($course['course_code']); ?>', '<?php echo addslashes($course['course_name']); ?>')" style="width: 36px; height: 36px; background: var(--primary); color: white; border: none; border-radius: 10px; display: flex; align-items: center; justify-content: center;" title="View Master QR">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/><path d="M21 21v.01"/><path d="M12 7v3a2 2 0 0 1-2 2H7"/><path d="M3 12h.01"/><path d="M12 3h.01"/><path d="M12 16h.01"/><path d="M16 12h1"/><path d="M21 12v.01"/><path d="M12 21v-1"/></svg>
                        </button>
                        <a href="export_attendance?course_id=<?php echo $course['id']; ?>" style="width: 36px; height: 36px; background: #22c55e; color: white; border-radius: 10px; display: flex; align-items: center; justify-content: center;" title="Export CSV">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                        </a>
                        <a href="course_details?id=<?php echo $course['id']; ?>" style="width: 36px; height: 36px; background: var(--bg-main); color: var(--text-muted); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="height: 100px;"></div>
    </div>

    <!-- Navigation -->
        <!-- Navigation is handled by footer.php -->
</section>
</div>

<!-- Desktop Content: Master Command Dashboard -->
<div class="desktop-only-layout" style="background: #f8fafc; min-height: 100vh;">
    <header style="padding: 50px 60px 20px; display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
                <div style="background: var(--primary); width: 12px; height: 12px; border-radius: 4px;"></div>
                <span style="color: #64748b; font-size: 13px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase;">Faculty Terminal</span>
            </div>
            <h1 style="font-size: 52px; font-weight: 950; color: #0f172a; letter-spacing: -2.5px;">Welcome Back, <span style="color: var(--primary);">Dr. <?php echo explode('_', $_SESSION['username'])[1] ?? 'Faculty'; ?></span></h1>
            <p style="color: #94a3b8; font-size: 18px; font-weight: 500; margin-top: 10px;">System synchronized. Intelligence nodes active.</p>
        </div>
        <div style="display: flex; gap: 20px; align-items: center;">
            <div style="text-align: right;">
                <p style="font-size: 12px; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Session Uptime</p>
                <p style="font-size: 18px; font-weight: 900; color: var(--primary);">100% ONLINE</p>
            </div>
            <div style="width: 70px; height: 70px; border-radius: 24px; border: 3px solid white; box-shadow: 0 15px 35px rgba(0,0,0,0.05); overflow: hidden;">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Lecturer" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        </div>
    </header>

    <!-- Stat Tier (Bento) -->
    <div style="padding: 0 60px 40px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
        <div style="background: white; padding: 35px; border-radius: 40px; border: 1.5px solid #f1f5f9; box-shadow: 0 15px 35px rgba(0,0,0,0.02);">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px;">
                <div style="width: 55px; height: 55px; background: #eff6ff; color: var(--primary); border-radius: 18px; display: flex; justify-content: center; align-items: center;">
                    <i data-lucide="book-open" style="width: 26px;"></i>
                </div>
                <h4 style="font-size: 14px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">Total Courses</h4>
            </div>
            <h2 style="font-size: 44px; font-weight: 950; color: #0f172a;"><?php echo count($courses); ?></h2>
        </div>
        
        <div style="background: white; padding: 35px; border-radius: 40px; border: 1.5px solid #f1f5f9; box-shadow: 0 15px 35px rgba(0,0,0,0.02);">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px;">
                <div style="width: 55px; height: 55px; background: #ecfdf5; color: #10b981; border-radius: 18px; display: flex; justify-content: center; align-items: center;">
                    <i data-lucide="users" style="width: 26px;"></i>
                </div>
                <h4 style="font-size: 14px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">Total Students</h4>
            </div>
            <h2 style="font-size: 44px; font-weight: 950; color: #0f172a;"><?php echo $stats['total_students']; ?></h2>
        </div>

        <div style="background: var(--primary); padding: 35px; border-radius: 40px; color: white; box-shadow: 0 20px 40px var(--primary-glow); position: relative; overflow: hidden;">
            <div style="position: absolute; right: -20px; top: -20px; opacity: 0.1;">
                <i data-lucide="zap" style="width: 150px; height: 150px;"></i>
            </div>
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px; position: relative;">
                <div style="width: 55px; height: 55px; background: rgba(255,255,255,0.2); border-radius: 18px; display: flex; justify-content: center; align-items: center;">
                    <i data-lucide="calendar" style="width: 26px;"></i>
                </div>
                <h4 style="font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Active Hubs</h4>
            </div>
            <h2 style="font-size: 44px; font-weight: 950; position: relative;"><?php echo count($active_sessions); ?></h2>
        </div>
    </div>

    <!-- Main Grid -->
    <div style="padding: 0 60px 60px; display: grid; grid-template-columns: 8fr 4fr; gap: 40px; align-items: start;">
        
        <!-- Left: Management Console -->
        <div style="display: flex; flex-direction: column; gap: 40px;">
            <div style="background: white; border-radius: 50px; padding: 50px; border: 1.5px solid #f1f5f9; box-shadow: 0 25px 60px rgba(0,0,0,0.03);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
                    <h3 style="font-size: 26px; font-weight: 950; color: #0f172a; letter-spacing: -1px;">Broadcast Performance</h3>
                    <button onclick="window.location.href='generate_qr'" class="btn-primary" style="height: 50px; padding: 0 25px; border-radius: 16px; font-weight: 800;">
                        Initialize QR Node
                    </button>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                    <?php if (empty($active_sessions)): ?>
                        <div style="grid-column: 1 / -1; background: #f8fafc; border: 2px dashed #e2e8f0; padding: 60px; border-radius: 35px; text-align: center;">
                            <i data-lucide="monitor-off" style="width: 48px; height: 48px; color: #94a3b8; margin-bottom: 20px;"></i>
                            <p style="color: #64748b; font-weight: 700; font-size: 18px;">No active attendance nodes detected.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($active_sessions as $sess): 
                            $is_paused = $sess['status'] === 'paused';
                        ?>
                            <div style="background: #fdfdfd; padding: 30px; border-radius: 35px; border: 2px solid <?php echo $is_paused ? 'var(--warning)' : 'var(--primary)'; ?>; position: relative;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                                    <div>
                                        <h4 style="font-size: 18px; font-weight: 900; color: #0f172a;"><?php echo htmlspecialchars($sess['course_name']); ?></h4>
                                        <p style="font-size: 12px; color: <?php echo $is_paused ? 'var(--warning)' : 'var(--primary)'; ?>; font-weight: 800; margin-top: 5px;">
                                            <?php echo $is_paused ? 'PAUSED NODE' : 'LIVE NODE'; ?>: #<?php echo $sess['id']; ?>
                                        </p>
                                    </div>
                                    <div style="width: 12px; height: 12px; background: <?php echo $is_paused ? 'var(--warning)' : 'var(--primary)'; ?>; border-radius: 50%; <?php echo $is_paused ? '' : 'animation: pulseShield 2s infinite;'; ?>"></div>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <button onclick="toggleSession(<?php echo $sess['id']; ?>, '<?php echo $sess['status']; ?>')" style="background: <?php echo $is_paused ? 'var(--success)' : 'var(--warning)'; ?>; color: white; border: none; padding: 10px 20px; border-radius: 12px; cursor: pointer; font-weight: 800; font-size: 13px; display: flex; align-items: center; gap: 8px;">
                                        <?php if ($is_paused): ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                            RESUME BROADCAST
                                        <?php else: ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/></svg>
                                            PAUSE BROADCAST
                                        <?php endif; ?>
                                    </button>
                                    <a href="generate_qr" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; background: <?php echo $is_paused ? 'var(--warning)' : 'var(--primary)'; ?>; color: white; height: 48px; border-radius: 14px; font-weight: 800; font-size: 13px; text-decoration: none;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M9 21H3v-6"/><path d="M21 3l-7 7"/><path d="M3 21l7-7"/></svg>
                                        HUB VIEW
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Courses Grid -->
            <div style="background: #0f172a; border-radius: 50px; padding: 50px; color: white;">
                <h3 style="font-size: 26px; font-weight: 950; margin-bottom: 35px; letter-spacing: -1px;">Managed Portfolio</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                    <?php foreach($courses as $c): ?>
                        <div style="background: rgba(255,255,255,0.05); padding: 30px; border-radius: 35px; border: 1px solid rgba(255,255,255,0.1); transition: transform 0.3s; cursor: pointer;">
                            <h4 style="font-size: 17px; font-weight: 850; margin-bottom: 10px;"><?php echo htmlspecialchars($c['course_name']); ?></h4>
                            <p style="font-size: 12px; color: rgba(255,255,255,0.5); font-weight: 700;"><?php echo $c['course_code']; ?></p>
                            <div style="margin-top: 25px; display: flex; align-items: center; justify-content: space-between;">
                                <div onclick="window.location.href='course_details?id=<?php echo $c['id']; ?>'" style="display: flex; align-items: center; gap: 8px; color: var(--primary); font-size: 13px; font-weight: 800;">
                                    View Analytics <i data-lucide="arrow-right" style="width: 16px;"></i>
                                </div>
                                <button onclick="showCourseQR('<?php echo $c['permanent_token']; ?>', '<?php echo addslashes($c['course_code']); ?>', '<?php echo addslashes($c['course_name']); ?>')" style="background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 12px; font-weight: 800; font-size: 12px; display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="qr-code" style="width: 14px;"></i> Master Node
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Right: Broadcast Node Control -->
        <div style="background: white; border-radius: 50px; padding: 45px; border: 1.5px solid #f1f5f9; box-shadow: 0 25px 60px rgba(0,0,0,0.03); position: sticky; top: 40px;">
            <h3 style="font-size: 24px; font-weight: 950; color: #0f172a; letter-spacing: -0.5px; margin-bottom: 35px;">Security Dispatch</h3>
            
            <form id="desktopBroadcastForm" style="display: flex; flex-direction: column; gap: 25px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 850; color: #94a3b8; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 1px;">Target Course</label>
                    <select name="course_id" style="width: 100%; height: 55px; background: #f8fafc; border: 1.5px solid #f1f5f9; border-radius: 18px; padding: 0 20px; font-weight: 700; font-family: inherit; font-size: 15px; color: #0f172a;">
                        <?php foreach($courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['course_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 850; color: #94a3b8; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 1px;">Dispatch Message</label>
                    <textarea name="message" placeholder="Initialize emergency broadcast or pulse..." style="width: 100%; height: 160px; background: #f8fafc; border: 1.5px solid #f1f5f9; border-radius: 24px; padding: 25px; font-weight: 700; font-family: inherit; font-size: 15px; color: #0f172a; resize: none;"></textarea>
                </div>

                <div style="background: #eff6ff; padding: 25px; border-radius: 28px; border: 1.5px solid #dbeafe;">
                    <p style="font-size: 13px; font-weight: 700; color: #1e40af; line-height: 1.6;">
                        <i data-lucide="info" style="width: 16px; margin-right: 5px; vertical-align: middle;"></i>
                        Pulse will be deployed to all mapped student devices via Firebase cloud nodes.
                    </p>
                </div>

                <button type="submit" class="btn-primary" style="height: 65px; border-radius: 20px; font-size: 16px; font-weight: 900; box-shadow: 0 15px 35px var(--primary-glow);">
                    Deploy Pulse Wave
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Broadcast Intelligence - Pulse Engine
    const handleBroadcast = async (e) => {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Default title if missing (desktop form might not have it)
        if (!data.title) data.title = "Instructional Pulse";

        // Show Loading
        const originalBtnText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<div class="loader" style="width:16px; height:16px; border-width:2px;"></div> &nbsp; Deploying Pulse...';

        try {
            const response = await fetch('../includes/process_broadcast.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '<?php echo AttendEaseSecurity::getCsrfToken(); ?>'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    title: 'Pulse Deployed!',
                    text: result.message + (result.stats ? " (Sent to " + result.stats.sent + " students)" : ""),
                    icon: 'success',
                    confirmButtonText: 'Acknowledged',
                    buttonsStyling: false,
                    customClass: { confirmButton: 'btn-primary swal2-confirm' }
                });
                form.reset();
            } else {
                Swal.fire({
                    title: 'Deployment Failed',
                    text: result.message,
                    icon: 'error',
                    confirmButtonText: 'Try Again',
                    buttonsStyling: false,
                    customClass: { confirmButton: 'btn-primary swal2-confirm' }
                });
            }
        } catch (err) {
            console.error("Pulse error:", err);
            Swal.fire('Network Integrity Error', 'Connection to broadcast node failed.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalBtnText;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    };

    const mobileForm = document.getElementById('announcementForm');
    const desktopForm = document.getElementById('desktopBroadcastForm');
    if (mobileForm) mobileForm.addEventListener('submit', handleBroadcast);
    if (desktopForm) desktopForm.addEventListener('submit', handleBroadcast);
});

async function toggleSession(sessionId, currentStatus) {
    const action = currentStatus === 'active' ? 'pause' : 'resume';
    try {
        const response = await fetch('../includes/toggle_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ session_id: sessionId, action: action })
        });
        const result = await response.json();
        if (result.success) {
            location.reload();
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    } catch (err) {
        Swal.fire('Error', 'Connection failed', 'error');
    }
}

function showCourseQR(token, code, name) {
    Swal.fire({
        title: `<div style="font-weight:900; letter-spacing:-1px;">Master Gateway: ${code}</div>`,
        html: `
            <div style="text-align: center; padding: 20px;">
                <p style="color: #64748b; font-size: 14px; margin-bottom: 25px; font-weight: 600;">Students scan this permanent code to mark attendance when a session is live.</p>
                <div id="swal-qrcode" style="display: flex; justify-content: center; margin-bottom: 25px; background: #f8fafc; padding: 20px; border-radius: 30px; border: 2.5px solid #e2e8f0;"></div>
                <div style="background: #eff6ff; padding: 15px; border-radius: 15px; border: 1.5px solid #dbeafe; color: #1e40af; font-size: 13px; font-weight: 700;">
                    Status: <span style="color: #22c55e;">● ACTIVE</span> (Routing to latest node)
                </div>
            </div>
        `,
        width: 500,
        showConfirmButton: true,
        confirmButtonText: 'Close Terminal',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'btn-primary swal2-confirm'
        },
        didOpen: () => {
            if (typeof QRCode !== 'undefined') {
                new QRCode(document.getElementById("swal-qrcode"), {
                    text: token,
                    width: 250,
                    height: 250,
                    colorDark: "#0f172a",
                    colorLight: "#f8fafc",
                    correctLevel: QRCode.CorrectLevel.H
                });
            }
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>
