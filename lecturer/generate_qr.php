<?php
require_once '../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'lecturer' && $_SESSION['role'] !== 'admin')) {
    header("Location: login");
    exit;
}

$page_title = "Attendance Command Hub";
include '../includes/header.php';

$db = get_db_connection();
$user_id = $_SESSION['user_id'];

try {
    // Fetch Courses
    $stmt = $db->prepare("SELECT * FROM courses WHERE lecturer_id = ?");
    $stmt->execute([$user_id]);
    $courses = $stmt->fetchAll();

    // Fetch Active/Paused Sessions
    $stmt = $db->prepare("
        SELECT s.*, c.course_name 
        FROM sessions s 
        JOIN courses c ON s.course_id = c.id 
        WHERE s.lecturer_id = ? AND s.status IN ('active', 'paused')
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $existing_sessions = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<h1>Database Error</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}
?>

<div class="mobile-only-layout">
<section id="generate-qr" class="screen" data-state="active" style="background: #f8fafc; min-height: 100vh;">
    <div class="scrollable-content" style="padding: 0;">
        <!-- Sticky Header -->
        <div style="padding: 20px 24px; display: flex; align-items: center; justify-content: space-between; background: white; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 200;">
            <a href="dashboard" style="width: 40px; height: 40px; background: #f1f5f9; border-radius: 12px; display: flex; justify-content: center; align-items: center; color: #0f172a;">
                <i data-lucide="chevron-left" style="width: 20px;"></i>
            </a>
            <h2 style="font-weight: 850; font-size: 18px; letter-spacing: -0.5px; color: #0f172a; margin: 0;">Faculty Hub</h2>
            <div style="width: 40px;"></div>
        </div>

        <div style="padding: 16px; max-width: 600px; margin: 0 auto;">
            <!-- HUB NAVIGATION -->
            <div style="display: flex; gap: 10px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 5px; scrollbar-width: none;">
                <button onclick="showSetup()" id="nav-new-sess" style="background: var(--primary); color: white; border: none; padding: 12px 20px; border-radius: 100px; font-size: 13px; font-weight: 800; white-space: nowrap; display: flex; align-items: center; gap: 6px;">
                    <i data-lucide="plus-circle" style="width: 16px;"></i> New Class
                </button>
                <?php if (!empty($existing_sessions)): ?>
                    <?php foreach($existing_sessions as $sess): ?>
                        <button onclick="manageSession(<?php echo $sess['id']; ?>, '<?php echo addslashes($sess['course_name']); ?>', '<?php echo $sess['status']; ?>', '<?php echo addslashes($sess['topic'] ?? 'General Session'); ?>', <?php echo $sess['course_id']; ?>)" 
                                class="session-nav-btn" 
                                id="nav-sess-<?php echo $sess['id']; ?>"
                                style="background: white; color: var(--text-dark); border: 1.5px solid var(--border); padding: 12px 20px; border-radius: 100px; font-size: 13px; font-weight: 700; white-space: nowrap; display: flex; align-items: center; gap: 6px;">
                            <span style="width: 8px; height: 8px; background: <?php echo $sess['status'] == 'active' ? 'var(--success)' : 'var(--warning)'; ?>; border-radius: 50%;"></span>
                            <?php echo htmlspecialchars($sess['topic'] ?: $sess['course_name']); ?>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Mobile Setup View -->
            <div id="setup-view" style="display: none;">
                <div style="background: white; padding: 30px; border-radius: 35px; border: 1.5px solid var(--border); box-shadow: 0 15px 35px rgba(0,0,0,0.03);">
                    <div style="margin-bottom: 25px;">
                        <h3 style="font-size: 24px; font-weight: 900; color: var(--text-dark); letter-spacing: -0.5px;">Initialize Node</h3>
                        <p style="color: var(--text-muted); font-size: 14px; font-weight: 500;">Configure your attendance broadcast.</p>
                    </div>
                    
                    <form onsubmit="handleDeployment(event)">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1.5px; display: block; margin-bottom: 10px;">Session Topic</label>
                            <input type="text" name="topic" placeholder="e.g. Week 4: Introduction to AI" class="form-control" style="height: 55px; border-radius: 18px; border: 2px solid var(--bg-main); background: var(--bg-main); font-weight: 700; font-size: 14px; padding: 0 20px;" required>
                        </div>

                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1.5px; display: block; margin-bottom: 10px;">Target Course</label>
                            <select name="course_id" class="form-control" style="height: 55px; border-radius: 18px; border: 2px solid var(--bg-main); background: var(--bg-main); font-weight: 700; font-size: 14px; padding: 0 20px;">
                                <?php foreach($courses as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['course_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
                            <div class="form-group">
                                <label style="font-size: 10px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1.5px; display: block; margin-bottom: 8px;">Duration</label>
                                <select name="duration" class="form-control" style="height: 55px; border-radius: 18px; border: 2px solid var(--bg-main); background: var(--bg-main); font-weight: 700; font-size: 14px; padding: 0 10px; text-align: center;">
                                    <option value="15">15 min</option>
                                    <option value="30" selected>30 min</option>
                                    <option value="60">1 hour</option>
                                    <option value="0">Indefinite</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label style="font-size: 10px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1.5px; display: block; margin-bottom: 8px;">Scan Cap</label>
                                <input type="number" name="scan_limit" class="form-control" value="0" placeholder="Unlimited" style="height: 55px; border-radius: 18px; border: 2px solid var(--bg-main); background: var(--bg-main); font-weight: 700; font-size: 14px; text-align: center;">
                            </div>
                        </div>

                        <button type="submit" class="btn-primary" style="height: 65px; border-radius: 20px; font-weight: 900; font-size: 16px; background: var(--primary); box-shadow: 0 15px 30px var(--primary-glow); width: 100%;">
                            Deploy Broadcast Node
                        </button>
                    </form>
                </div>
            </div>

            <!-- Mobile Hub View -->
            <div id="hub-view" style="display: none; width: 100%;">
                <!-- QR Hub Content (Existing) -->
                <div id="qr-main-container" style="position: relative; background: white; padding: 35px 20px; border-radius: 40px; text-align: center; box-shadow: 0 25px 60px rgba(0,0,0,0.05); border: 1.5px solid var(--border); overflow: hidden; margin-bottom: 25px;">
                    <div style="position: absolute; top: -100px; left: -100px; width: 250px; height: 250px; background: var(--primary-glow); filter: blur(80px); opacity: 0.5; z-index: 0; border-radius: 50%;"></div>
                    <div id="qrcode-wrapper" style="position: relative; display: inline-block; padding: 25px; background: white; border-radius: 40px; border: 3px solid var(--bg-main); min-width: 240px; min-height: 240px; box-sizing: border-box; z-index: 1;">
                        <div id="qrcode" style="display: flex; justify-content: center; align-items: center; overflow: hidden; border-radius: 18px; background: #f8fafc; width: 220px; height: 220px;">
                            <div class="loader" style="border-color: var(--primary); border-bottom-color: transparent;"></div>
                        </div>
                        <div id="rotation-ring" style="position: absolute; top: -8px; left: -8px; right: -8px; bottom: -8px; border: 4px solid var(--primary); border-radius: 42px; border-top-color: transparent; border-left-color: transparent; animation: spin 30s linear infinite;"></div>
                    </div>
                    <div style="margin-top: 30px; position: relative; z-index: 1;">
                        <span id="liveCourseCode" style="font-size: 11px; font-weight: 800; color: var(--primary); background: var(--primary-glow); padding: 4px 12px; border-radius: 50px; text-transform: uppercase;">--</span>
                        <h3 id="liveTopicName" style="font-weight: 900; color: var(--text-dark); font-size: 24px; letter-spacing: -1px; margin: 12px 0 5px;">Topic Name</h3>
                        <p id="liveCourseName" style="font-size: 14px; font-weight: 600; color: var(--text-muted);">Course Title</p>
                    </div>
                    <div id="pauseShield" style="display: none; position: absolute; inset: 0; background: rgba(255,255,255,0.9); backdrop-filter: blur(15px); z-index: 100; flex-direction: column; justify-content: center; align-items: center; border-radius: 40px;">
                        <div style="width: 80px; height: 80px; background: var(--warning); border-radius: 28px; display: flex; justify-content: center; align-items: center; margin-bottom: 20px; box-shadow: 0 15px 35px var(--warning-glow);">
                            <i data-lucide="pause" style="width: 40px; height: 40px; color: white;"></i>
                        </div>
                        <h2 style="font-weight: 900; color: #92400e; font-size: 22px;">BROADCAST PAUSED</h2>
                        <button onclick="togglePause()" class="btn-primary" style="margin-top: 25px; background: var(--warning); width: 180px; height: 50px; border-radius: 14px;">Resume Feed</button>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <button id="toggleBtn" onclick="togglePause()" style="height: 70px; border-radius: 20px; border: 2px solid #fffbeb; background: #fff; color: var(--warning); font-weight: 800; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;">
                        <i data-lucide="pause-circle" style="width: 20px;"></i><span style="font-size: 10px;">Toggle</span>
                    </button>
                    <button onclick="closeSession()" style="height: 70px; border-radius: 20px; background: #fee2e2; color: #ef4444; border: none; font-weight: 800; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;">
                        <i data-lucide="power" style="width: 20px;"></i><span style="font-size: 10px;">Clear</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>
</div>

<!-- Desktop: Faculty QR Hub (Projector Mode) -->
<div class="desktop-only-layout" style="background: #020617; min-height: 100vh; display: flex; flex-direction: column; overflow: hidden; position: relative;">
    <div style="position: absolute; top: -10%; right: -10%; width: 60%; height: 60%; background: radial-gradient(circle, rgba(0, 102, 255, 0.1) 0%, transparent 70%); filter: blur(100px); pointer-events: none;"></div>
    <div style="position: absolute; bottom: -10%; left: -10%; width: 50%; height: 50%; background: radial-gradient(circle, rgba(16, 185, 129, 0.05) 0%, transparent 70%); filter: blur(100px); pointer-events: none;"></div>

    <header style="padding: 35px 60px; display: flex; justify-content: space-between; align-items: center; background: rgba(15, 23, 42, 0.3); backdrop-filter: blur(30px); border-bottom: 1px solid rgba(255,255,255,0.08); z-index: 100;">
        <div style="display: flex; align-items: center; gap: 25px;">
            <div style="background: var(--primary); padding: 15px; border-radius: 18px; box-shadow: 0 0 30px var(--primary-glow);">
                <i data-lucide="shield-check" style="color: white; width: 28px; height: 28px;"></i>
            </div>
            <div>
                <h2 style="color: white; font-size: 26px; font-weight: 950; letter-spacing: -1.5px;">Instructional <span style="font-weight: 400; font-family: 'Inter', sans-serif; opacity: 0.8;">Pulse</span></h2>
                <div style="display: flex; gap: 20px; margin-top: 5px;">
                    <span style="color: #64748b; font-size: 11px; font-weight: 850; text-transform: uppercase; letter-spacing: 1.5px; display: flex; align-items: center; gap: 6px;">
                        <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; box-shadow: 0 0 10px #10b981;"></span>
                        Operational Integrity
                    </span>
                    <span id="dt-session-id" style="color: rgba(255,255,255,0.4); font-size: 11px; font-weight: 800; text-transform: uppercase;">Node: #--</span>
                </div>
            </div>
        </div>
        <div id="desktop-actions-hub" style="display: flex; gap: 15px; display: none;">
            <button onclick="handlePrint()" style="background: rgba(255,255,255,0.03); color: white; border: 1.5px solid rgba(255,255,255,0.1); padding: 15px 30px; border-radius: 18px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 12px; transition: all 0.3s; height: 55px;">
                <i data-lucide="printer" style="width: 18px;"></i> Print Key
            </button>
            <button onclick="closeSession()" style="background: #ef4444; color: white; border: none; padding: 15px 35px; border-radius: 18px; font-weight: 950; cursor: pointer; box-shadow: 0 15px 30px rgba(239, 68, 68, 0.3); height: 55px;">
                Terminate Node
            </button>
        </div>
        <div id="desktop-setup-actions" style="display: flex; gap: 15px;">
             <?php if (!empty($existing_sessions)): ?>
                <button onclick="manageSession(<?php echo $existing_sessions[0]['id']; ?>, '<?php echo addslashes($existing_sessions[0]['course_name']); ?>', '<?php echo $existing_sessions[0]['status']; ?>', '<?php echo addslashes($existing_sessions[0]['topic'] ?? 'General Session'); ?>', <?php echo $existing_sessions[0]['course_id']; ?>)" style="background: var(--primary); color: white; border: none; padding: 15px 30px; border-radius: 18px; font-weight: 800; cursor: pointer;">
                    Join Active Node
                </button>
            <?php endif; ?>
        </div>
    </header>

    <div style="flex: 1; display: flex; padding: 50px 60px;">
        <!-- Desktop Setup Launchpad -->
        <div id="dt-setup-view" style="width: 100%; display: flex; justify-content: center; align-items: center; display: none;">
            <div style="background: white; border-radius: 50px; padding: 60px; width: 100%; max-width: 900px; display: grid; grid-template-columns: 1fr 1fr; gap: 60px; box-shadow: 0 50px 100px rgba(0,0,0,0.3);">
                <div>
                    <h2 style="font-size: 42px; font-weight: 950; color: #0f172a; letter-spacing: -2px; line-height: 1.1; margin-bottom: 20px;">Deploy Your <span style="color: var(--primary);">Attendance Node</span></h2>
                    <p style="color: #64748b; font-size: 18px; line-height: 1.6; font-weight: 500;">Configure the classroom broadcast. Students will sync with this terminal in real-time.</p>
                    
                    <div style="margin-top: 40px; display: flex; flex-direction: column; gap: 20px;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="width: 45px; height: 45px; background: #eff6ff; color: var(--primary); border-radius: 14px; display: flex; justify-content: center; align-items: center;"><i data-lucide="shield" style="width: 20px;"></i></div>
                            <p style="font-weight: 700; color: #0f172a;">Anti-Proxy Protection Active</p>
                        </div>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="width: 45px; height: 45px; background: #ecfdf5; color: #10b981; border-radius: 14px; display: flex; justify-content: center; align-items: center;"><i data-lucide="zap" style="width: 20px;"></i></div>
                            <p style="font-weight: 700; color: #0f172a;">Instant Sync Broadcast</p>
                        </div>
                    </div>
                </div>

                <form onsubmit="handleDeployment(event)" style="display: flex; flex-direction: column; gap: 25px;">
                    <div class="form-group">
                        <label style="font-size: 11px; font-weight: 850; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.5px; display: block; margin-bottom: 12px;">Session Objective</label>
                        <input type="text" name="topic" placeholder="e.g. Lab 4: Circuit Analysis" style="width: 100%; height: 65px; border-radius: 18px; border: 2.5px solid #f1f5f9; background: #f8fafc; padding: 0 25px; font-weight: 700; font-size: 16px;" required>
                    </div>

                    <div class="form-group">
                        <label style="font-size: 11px; font-weight: 850; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.5px; display: block; margin-bottom: 12px;">Academic Course</label>
                        <select name="course_id" style="width: 100%; height: 65px; border-radius: 18px; border: 2.5px solid #f1f5f9; background: #f8fafc; padding: 0 25px; font-weight: 700; font-size: 16px;">
                            <?php foreach($courses as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['course_name']); ?></option><?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                         <div class="form-group">
                            <label style="font-size: 11px; font-weight: 850; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 10px;">Window</label>
                            <select name="duration" style="width: 100%; height: 60px; border-radius: 18px; border: 2px solid #f1f5f9; background: #f8fafc; padding: 0 15px; font-weight: 700;">
                                <option value="15">15 Min</option><option value="30" selected>30 Min</option><option value="60">1 Hour</option><option value="0">Open</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label style="font-size: 11px; font-weight: 850; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 10px;">Headcount</label>
                            <input type="number" name="scan_limit" value="0" style="width: 100%; height: 60px; border-radius: 18px; border: 2px solid #f1f5f9; background: #f8fafc; padding: 0 15px; font-weight: 700; text-align: center;">
                        </div>
                    </div>

                    <button type="submit" style="background: var(--primary); color: white; border: none; height: 75px; border-radius: 24px; font-weight: 950; font-size: 18px; cursor: pointer; box-shadow: 0 20px 40px var(--primary-glow); margin-top: 15px;">Deploy Node</button>
                </form>
            </div>
        </div>

        <!-- Desktop Real-time Hub -->
        <div id="dt-hub-view" style="width: 100%; display: grid; grid-template-columns: 7.5fr 4.5fr; gap: 50px; display: none;">
            <div style="background: rgba(255,255,255,0.015); border-radius: 60px; border: 1px solid rgba(255,255,255,0.06); display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 80px; position: relative;">
                <div id="dt-rotation-ring" style="position: absolute; width: 620px; height: 620px; border: 1px solid rgba(0, 102, 255, 0.1); border-radius: 50%; pointer-events: none; animation: spin 60s linear infinite;"></div>
                <div style="position: relative; padding: 50px; background: white; border-radius: 50px; box-shadow: 0 70px 140px rgba(0,0,0,0.6); z-index: 2;">
                    <div id="dt-qrcode" style="width: 480px; height: 480px; display: flex; justify-content: center; align-items: center;"></div>
                </div>
                <div style="margin-top: 60px; text-align: center; z-index: 2;">
                    <h1 id="dt-topic-name" style="color: white; font-size: 52px; font-weight: 950; letter-spacing: -3px; line-height: 1; margin-bottom: 15px;">--</h1>
                    <p id="dt-course-name" style="color: var(--primary); font-size: 20px; font-weight: 700; opacity: 0.8;">--</p>
                </div>
                <!-- Pause Shield -->
                 <div id="dt-pauseShield" style="display: none; position: absolute; inset: 0; background: rgba(2, 6, 23, 0.9); backdrop-filter: blur(25px); z-index: 100; border-radius: 60px; flex-direction: column; justify-content: center; align-items: center; color: white;">
                    <button onclick="togglePause()" style="background: var(--warning); border: none; width: 120px; height: 120px; border-radius: 40px; cursor: pointer; margin-bottom: 30px; box-shadow: 0 20px 50px var(--warning-glow);"><i data-lucide="play" style="width: 60px; height: 60px; color: white;"></i></button>
                    <h2 style="font-size: 42px; font-weight: 950; letter-spacing: -2px;">NODE_BROADCAST_PAUSED</h2>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 35px;">
                <div style="background: white; border-radius: 50px; padding: 50px; display: flex; flex-direction: column; align-items: center; text-align: center; box-shadow: 0 30px 60px rgba(0,0,0,0.1);">
                    <div style="width: 100px; height: 100px; background: var(--primary-glow); color: var(--primary); border-radius: 35px; display: flex; justify-content: center; align-items: center; margin-bottom: 30px;"><i data-lucide="users" style="width: 45px; height: 45px;"></i></div>
                    <div style="display: flex; align-items: baseline; gap: 10px;"><h3 style="font-size: 92px; font-weight: 950; color: #0f172a; line-height: 1; letter-spacing: -5px;" id="dt-attendee-count">0</h3><span style="font-size: 24px; font-weight: 800; color: #94a3b8;">Present</span></div>
                    <div style="width: 100%; height: 1px; background: #f1f5f9; margin: 40px 0;"></div>
                    <button onclick="openBroadcastModal()" style="background: #0f172a; color: white; border: none; height: 75px; width: 100%; border-radius: 24px; font-weight: 900; font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 15px;">
                        <i data-lucide="send" style="width: 22px;"></i> Broadcast Alert
                    </button>
                    <button id="dt-side-toggle" onclick="togglePause()" style="width: 100%; height: 65px; margin-top: 15px; border-radius: 20px; border: 2.5px solid #f1f5f9; background: white; color: var(--warning); font-weight: 850;">Pause Broadcast</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
#qrcode canvas, #qrcode img { max-width: 100% !important; height: auto !important; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
@keyframes pulseShield { 0% { transform: scale(1); } 50% { transform: scale(1.05); } 100% { transform: scale(1); } }
.loader { width: 18px; height: 18px; border: 3px solid #FFF; border-bottom-color: transparent; border-radius: 50%; display: inline-block; animation: rotation 1s linear infinite; }
@keyframes rotation { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

@media print {
    @page { margin: 0; size: A4 portrait; }
    body * { visibility: hidden; background: white !important; }
    #hub-view, #hub-view * { visibility: visible; }
    #hub-view { position: fixed; left: 0; top: 0; width: 100vw; height: 100vh; display: flex !important; flex-direction: column; justify-content: center; align-items: center; background: white !important; }
    #qr-main-container { border: none !important; box-shadow: none !important; width: 85% !important; transform: scale(1.3); background: white !important; }
    #pauseShield, #hub-controls, button, a, #rotation-ring, #security-badge, .btn-secondary, [data-lucide] { display: none !important; }
    #qrcode-wrapper { border: 6px solid #000 !important; padding: 30px !important; background: white !important; border-radius: 0 !important; }
    #qrcode canvas { width: 400px !important; height: 400px !important; }
    #liveCourseName { font-size: 42px !important; margin-top: 40px !important; color: black !important; font-weight: 900 !important; }
    #session-id-badge { display: block !important; font-size: 18px !important; margin-top: 20px !important; border: 1px solid #000 !important; border-radius: 0 !important; }
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
let currentSessionId = null;
let currentCourseId = null;
let currentStatus = 'active';
let rotationInterval = null;
let statsInterval = null;
let lastAttendeeCount = 0;

async function updateLiveCount() {
    if (!currentSessionId || currentStatus !== 'active') return;
    try {
        const response = await fetch(`../includes/get_session_stats.php?session_id=${currentSessionId}`);
        const result = await response.json();
        if (result.success) {
            const countEl = document.getElementById('attendee-count');
            const dtCountEl = document.getElementById('dt-attendee-count');
            const newCount = result.count;
            if (newCount !== lastAttendeeCount) {
                if (countEl) countEl.innerText = newCount;
                if (dtCountEl) {
                    dtCountEl.innerText = newCount;
                    dtCountEl.style.transform = 'scale(1.2)';
                    setTimeout(() => dtCountEl.style.transform = 'scale(1)', 400);
                }
                lastAttendeeCount = newCount;
            }
        }
    } catch (err) { console.error("Stats update failed", err); }
}

async function updateQR() {
    if (typeof QRCode === 'undefined' || !currentSessionId || currentStatus !== 'active') return;
    try {
        const response = await fetch(`../includes/get_qr_token.php?session_id=${currentSessionId}`);
        const result = await response.json();
        if (!result.success) return;
        const token = result.token;
        const qrEl = document.getElementById("qrcode");
        if (qrEl) {
            qrEl.innerHTML = "";
            new QRCode(qrEl, { text: token, width: 220, height: 220, colorDark : "#0f172a", colorLight : "#ffffff", correctLevel : QRCode.CorrectLevel.M });
        }
        const dtQrEl = document.getElementById("dt-qrcode");
        if (dtQrEl) {
            dtQrEl.innerHTML = "";
            new QRCode(dtQrEl, { text: token, width: 480, height: 480, colorDark : "#020617", colorLight : "#ffffff", correctLevel : QRCode.CorrectLevel.H });
        }
    } catch (err) { console.error("QR Fetch Failure:", err); }
}

function startMonitoring() {
    if (rotationInterval) clearInterval(rotationInterval);
    if (statsInterval) clearInterval(statsInterval);
    lastAttendeeCount = 0;
    updateUI();
    updateQR();
    updateLiveCount();
    rotationInterval = setInterval(() => { if (currentStatus === 'active') updateQR(); }, 15000);
    statsInterval = setInterval(() => { if (currentStatus === 'active') updateLiveCount(); }, 5000);
}

function showSetup() {
    currentSessionId = null;
    document.getElementById('setup-view').style.display = 'block';
    document.getElementById('hub-view').style.display = 'none';
    document.getElementById('dt-setup-view').style.display = 'flex';
    document.getElementById('dt-hub-view').style.display = 'none';
    document.getElementById('desktop-actions-hub').style.display = 'none';
    document.getElementById('desktop-setup-actions').style.display = 'flex';
    if (rotationInterval) clearInterval(rotationInterval);
    if (statsInterval) clearInterval(statsInterval);
}

function manageSession(id, courseName, status, topic, courseId) {
    currentSessionId = id;
    currentCourseId = courseId;
    currentStatus = status;
    document.getElementById('setup-view').style.display = 'none';
    document.getElementById('hub-view').style.display = 'block';
    document.getElementById('dt-setup-view').style.display = 'none';
    document.getElementById('dt-hub-view').style.display = 'grid';
    document.getElementById('desktop-actions-hub').style.display = 'flex';
    document.getElementById('desktop-setup-actions').style.display = 'none';
    document.getElementById('liveTopicName').innerText = topic;
    document.getElementById('liveCourseName').innerText = courseName;
    document.getElementById('dt-topic-name').innerText = topic;
    document.getElementById('dt-course-name').innerText = courseName;
    document.getElementById('dt-session-id').innerText = 'Node: #' + id;
    startMonitoring();
}

async function handleDeployment(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerText;
    submitBtn.disabled = true;
    submitBtn.innerHTML = 'Initializing...';
    try {
        const response = await fetch('../includes/create_session.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
        const result = await response.json();
        if (result.success) {
            Swal.fire({ title: 'Node Deployed', text: 'Sync broadcast initialized successfully.', icon: 'success', timer: 1500, showConfirmButton: false }).then(() => location.reload());
        } else { Swal.fire('Deployment Error', result.message || 'Operation failed', 'error'); }
    } catch (err) { Swal.fire('Network Integrity', 'Could not establish connection.', 'error'); }
    finally { submitBtn.disabled = false; submitBtn.innerHTML = originalText; }
}

async function togglePause() {
    if (!currentSessionId) return;
    const action = currentStatus === 'active' ? 'pause' : 'resume';
    currentStatus = (action === 'pause') ? 'paused' : 'active';
    updateUI();
    try {
        const response = await fetch('../includes/toggle_session.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ session_id: currentSessionId, action: action }) });
        const result = await response.json();
        if (!result.success) { currentStatus = (action === 'pause') ? 'active' : 'paused'; updateUI(); }
    } catch (err) { currentStatus = (action === 'pause') ? 'active' : 'paused'; updateUI(); }
}

function updateUI() {
    const isPaused = currentStatus === 'paused';
    const mShield = document.getElementById('pauseShield');
    const dShield = document.getElementById('dt-pauseShield');
    const tBtn = document.getElementById('toggleBtn');
    const dtTBtn = document.getElementById('dt-side-toggle');
    if (mShield) mShield.style.display = isPaused ? 'flex' : 'none';
    if (dShield) dShield.style.display = isPaused ? 'flex' : 'none';
    if (tBtn) tBtn.innerHTML = isPaused ? '<i data-lucide="play-circle" style="width: 20px;"></i><span style="font-size: 10px;">Resume</span>' : '<i data-lucide="pause-circle" style="width: 20px;"></i><span style="font-size: 10px;">Pause</span>';
    if (dtTBtn) { dtTBtn.innerText = isPaused ? 'Resume Broadcast' : 'Pause Broadcast'; dtTBtn.style.background = isPaused ? 'var(--success)' : 'white'; dtTBtn.style.color = isPaused ? 'white' : 'var(--warning)'; }
    if (window.lucide) lucide.createIcons();
}

async function closeSession() {
    if (!currentSessionId) return;
    const res = await Swal.fire({ title: 'Terminate Node?', text: 'This will stop all attendance broadcasts immediately.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Kill Node', customClass: { confirmButton: 'btn-primary swal2-confirm', cancelButton: 'swal2-cancel' } });
    if (res.isConfirmed) {
        const response = await fetch('../includes/toggle_session.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ session_id: currentSessionId, action: 'close' }) });
        const result = await response.json();
        if (result.success) location.reload();
    }
}

function handlePrint() {
    const originalStatus = currentStatus;
    const mS = document.getElementById('pauseShield');
    const dS = document.getElementById('dt-pauseShield');
    if (mS) mS.style.display = 'none';
    if (dS) dS.style.display = 'none';
    window.print();
    if (originalStatus === 'paused') { if (mS) mS.style.display = 'flex'; if (dS) dS.style.display = 'flex'; }
}

function openBroadcastModal() {
    Swal.fire({
        title: 'Secure Broadcast',
        html: `<div style="text-align: left;"><p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">Sending instant notification to students.</p><input id="swal-title" class="swal2-input" placeholder="Title" style="margin-bottom: 15px; width: 100%; border-radius: 12px;"><textarea id="swal-message" class="swal2-textarea" placeholder="Message..." style="width: 100%; border-radius: 12px; height: 100px;"></textarea></div>`,
        showCancelButton: true, confirmButtonText: '🚀 Send Pulse', customClass: { confirmButton: 'btn-primary swal2-confirm', cancelButton: 'swal2-cancel' },
        preConfirm: () => { return { title: document.getElementById('swal-title').value, message: document.getElementById('swal-message').value } }
    }).then((result) => { if (result.isConfirmed) deployPulse(result.value.title, result.value.message); });
}

async function deployPulse(title, message) {
    if (!title || !message || !currentCourseId) return;
    try {
        const response = await fetch('../includes/process_broadcast.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo AttendEaseSecurity::getCsrfToken(); ?>' }, body: JSON.stringify({ course_id: currentCourseId, title: title, message: message }) });
        const result = await response.json();
        if (result.success) Swal.fire('Deployed', result.message, 'success');
        else Swal.fire('Failed', result.message, 'error');
    } catch (err) { Swal.fire('Network Error', 'Connection failed', 'error'); }
}

document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
    <?php if (!empty($existing_sessions)): ?>
        manageSession(<?php echo $existing_sessions[0]['id']; ?>, '<?php echo addslashes($existing_sessions[0]['course_name']); ?>', '<?php echo $existing_sessions[0]['status']; ?>', '<?php echo addslashes($existing_sessions[0]['topic'] ?? 'General Session'); ?>', <?php echo $existing_sessions[0]['course_id']; ?>);
    <?php else: ?> showSetup(); <?php endif; ?>
});

window.addEventListener('resize', () => { if (currentSessionId && currentStatus === 'active') updateQR(); });
</script>

<?php include '../includes/footer.php'; ?>
