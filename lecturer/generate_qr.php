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

            <!-- Setup View (Creation Form) -->
            <div id="setup-view" style="display: none;">
                <div style="background: white; padding: 30px; border-radius: 35px; border: 1.5px solid var(--border); box-shadow: 0 15px 35px rgba(0,0,0,0.03);">
                    <div style="margin-bottom: 25px;">
                        <h3 style="font-size: 24px; font-weight: 900; color: var(--text-dark); letter-spacing: -0.5px;">Initialize Node</h3>
                        <p style="color: var(--text-muted); font-size: 14px; font-weight: 500;">Configure your attendance broadcast.</p>
                    </div>
                    
                    <form id="qrGenForm">
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

                        <!-- Geo-Fencing Toggle (Visual) -->
                        <div style="background: var(--bg-main); padding: 15px; border-radius: 20px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 36px; height: 36px; background: white; border-radius: 10px; display: flex; justify-content: center; align-items: center; color: var(--primary);">
                                    <i data-lucide="map-pin" style="width: 18px;"></i>
                                </div>
                                <div>
                                    <h4 style="font-size: 13px; font-weight: 800; color: var(--text-dark);">Geo-Fencing</h4>
                                    <p style="font-size: 10px; color: var(--text-muted); font-weight: 600;">Lock scan to this classroom</p>
                                </div>
                            </div>
                            <input type="checkbox" name="use_geo" checked style="width: 20px; height: 20px; accent-color: var(--primary);">
                        </div>

                        <button type="submit" class="btn-primary" id="genBtn" style="height: 65px; border-radius: 20px; font-weight: 900; font-size: 16px; background: var(--primary); box-shadow: 0 15px 30px var(--primary-glow); width: 100%;">
                            Deploy Broadcast Node
                        </button>
                    </form>
                </div>
            </div>

            <!-- Management Hub (Responsive) -->
            <div id="hub-view" style="display: none; width: 100%;">
                <div id="qr-main-container" style="position: relative; background: white; padding: 35px 20px; border-radius: 40px; text-align: center; box-shadow: 0 25px 60px rgba(0,0,0,0.05); border: 1.5px solid var(--border); overflow: hidden; margin-bottom: 25px;">
                    
                    <!-- Performance Glow Background -->
                    <div style="position: absolute; top: -100px; left: -100px; width: 250px; height: 250px; background: var(--primary-glow); filter: blur(80px); opacity: 0.5; z-index: 0; border-radius: 50%;"></div>

                    <!-- Dynamic Integrity Hub -->
                    <div id="qrcode-wrapper" style="position: relative; display: inline-block; padding: 25px; background: white; border-radius: 40px; border: 3px solid var(--bg-main); min-width: 240px; min-height: 240px; box-sizing: border-box; z-index: 1;">
                        <div id="qrcode" style="display: flex; justify-content: center; align-items: center; overflow: hidden; border-radius: 18px; background: #f8fafc; width: 220px; height: 220px;">
                            <div class="loader" style="border-color: var(--primary); border-bottom-color: transparent;"></div>
                        </div>
                        
                        <!-- Anti-Photo Rotation Ring -->
                        <div id="rotation-ring" style="position: absolute; top: -8px; left: -8px; right: -8px; bottom: -8px; border: 4px solid var(--primary); border-radius: 42px; border-top-color: transparent; border-left-color: transparent; animation: spin 30s linear infinite;"></div>
                    </div>

                    <div style="margin-top: 30px; position: relative; z-index: 1;">
                        <span id="liveCourseCode" style="font-size: 11px; font-weight: 800; color: var(--primary); background: var(--primary-glow); padding: 4px 12px; border-radius: 50px; text-transform: uppercase;">--</span>
                        <h3 id="liveTopicName" style="font-weight: 900; color: var(--text-dark); font-size: 24px; letter-spacing: -1px; margin: 12px 0 5px;">Topic Name</h3>
                        <p id="liveCourseName" style="font-size: 14px; font-weight: 600; color: var(--text-muted);">Course Title</p>
                        
                        <div style="display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 8px; margin-top: 15px;">
                            <button onclick="openBroadcastModal()" style="background: #000; color: #fff; padding: 6px 14px; border: none; border-radius: 20px; font-size: 10px; font-weight: 800; text-transform: uppercase; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                <i data-lucide="megaphone" style="width: 12px;"></i> SECURE BROADCAST
                            </button>
                            <span id="live-count-badge" style="background: var(--success); color: white; padding: 6px 14px; border-radius: 20px; font-size: 10px; font-weight: 800; border: none; display: flex; align-items: center; gap: 4px; transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
                                <i data-lucide="users" style="width: 12px;"></i>
                                <span id="attendee-count">0</span> PRESENT
                            </span>
                            <span id="session-id-badge" style="background: var(--surface); color: var(--text-muted); padding: 6px 14px; border-radius: 20px; font-size: 10px; font-weight: 800; border: 1.5px solid var(--border);">NODE: --</span>
                        </div>
                    </div>

                    <!-- Responsive Shield -->
                    <div id="pauseShield" style="display: none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.9); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); z-index: 100; flex-direction: column; justify-content: center; align-items: center; border-radius: 40px;">
                        <div style="width: 80px; height: 80px; background: var(--warning); border-radius: 28px; display: flex; justify-content: center; align-items: center; margin-bottom: 20px; box-shadow: 0 15px 35px var(--warning-glow); animation: pulseShield 2s infinite;">
                            <i data-lucide="pause" style="width: 40px; height: 40px; color: white;"></i>
                        </div>
                        <h2 style="font-weight: 900; color: #92400e; font-size: 22px; letter-spacing: -0.5px;">BROADCAST PAUSED</h2>
                        <button onclick="togglePause()" class="btn-primary" style="margin-top: 25px; background: var(--warning); width: 180px; height: 55px; border-radius: 18px; font-weight: 900; font-size: 15px; box-shadow: 0 10px 25px var(--warning-glow);">
                            Resume Feed
                        </button>
                    </div>
                </div>

                <!-- Control Grid -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                    <button id="toggleBtn" onclick="togglePause()" style="height: 75px; border-radius: 24px; border: 2px solid var(--warning-glow); background: #fffbeb; color: var(--warning); font-weight: 900; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 6px; cursor: pointer;">
                        <i data-lucide="pause-circle" style="width: 20px;"></i>
                        <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Toggle Flow</span>
                    </button>
                    <button onclick="handlePrint()" style="height: 75px; border-radius: 24px; border: none; background: var(--text-dark); color: white; font-weight: 900; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 6px; cursor: pointer; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">
                        <i data-lucide="printer" style="width: 20px;"></i>
                        <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Print Mode</span>
                    </button>
                </div>
                
                <button onclick="closeSession()" style="width: 100%; height: 65px; border-radius: 24px; background: #fee2e2; color: var(--danger); border: 2.5px dashed #fecaca; font-weight: 900; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; justify-content: center; gap: 10px; cursor: pointer; transition: 0.3s; margin-bottom: 30px;">
                    <i data-lucide="power" style="width: 18px;"></i> Kill Broadcast Node
                </button>
            </div>
        </div>

        <div style="height: 80px;"></div>
    </div>
</section>
</div>

<!-- Desktop: Faculty QR Hub (Projector Mode) -->
<div class="desktop-only-layout" style="background: #020617; min-height: 100vh; display: flex; flex-direction: column; overflow: hidden; position: relative;">
    <!-- Abstract Ambient Background -->
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
        <div style="display: flex; gap: 15px;">
            <button onclick="handlePrint()" style="background: rgba(255,255,255,0.03); color: white; border: 1.5px solid rgba(255,255,255,0.1); padding: 15px 30px; border-radius: 18px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 12px; transition: all 0.3s; height: 55px;">
                <i data-lucide="printer" style="width: 18px;"></i> Print Credentials
            </button>
            <button onclick="closeSession()" style="background: #ef4444; color: white; border: none; padding: 15px 35px; border-radius: 18px; font-weight: 950; cursor: pointer; box-shadow: 0 15px 30px rgba(239, 68, 68, 0.3); height: 55px; letter-spacing: -0.5px;">
                Terminate Node
            </button>
        </div>
    </header>

    <div style="flex: 1; display: grid; grid-template-columns: 7.5fr 4.5fr; gap: 50px; padding: 50px 60px;">
        <!-- Left: Cinematic QR Projection -->
        <div style="background: rgba(255,255,255,0.015); border-radius: 60px; border: 1px solid rgba(255,255,255,0.06); display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 80px; position: relative; box-shadow: inset 0 0 100px rgba(0,0,0,0.2);">
            
            <!-- Animated HUD elements -->
            <div style="position: absolute; top: 40px; left: 40px; color: rgba(255,255,255,0.1); font-size: 10px; font-weight: 900; letter-spacing: 2px;">SECURE_BROADCAST_SYSTEM_V.2</div>
            <div style="position: absolute; bottom: 40px; right: 40px; color: rgba(255,255,255,0.1); font-size: 10px; font-weight: 900; letter-spacing: 2px;">REAL_TIME_NODE_STABILITY [MAX]</div>

            <div id="dt-rotation-ring" style="position: absolute; width: 620px; height: 620px; border: 1px solid rgba(0, 102, 255, 0.1); border-radius: 50%; pointer-events: none; animation: spin 60s linear infinite;"></div>
            <div style="position: absolute; width: 500px; height: 500px; border: 1px dashed rgba(255,255,255,0.05); border-radius: 50%; pointer-events: none; animation: spin 40s linear infinite reverse;"></div>
            
            <div style="position: relative; padding: 50px; background: white; border-radius: 50px; box-shadow: 0 70px 140px rgba(0,0,0,0.6); z-index: 2; transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
                <div id="dt-qrcode" style="width: 480px; height: 480px; display: flex; justify-content: center; align-items: center;">
                    <div class="loader" style="width: 60px; height: 60px; border-width: 6px; border-color: var(--primary); border-bottom-color: transparent;"></div>
                </div>
                <!-- Interactive Glow -->
                <div style="position: absolute; -inset: 2px; border-radius: 50px; box-shadow: 0 0 50px var(--primary-glow); opacity: 0.3; pointer-events: none;"></div>
            </div>

            <div style="margin-top: 60px; text-align: center; z-index: 2;">
                <h1 id="dt-topic-name" style="color: white; font-size: 52px; font-weight: 950; letter-spacing: -3px; line-height: 1; margin-bottom: 15px;">Initializing Terminal</h1>
                <p id="dt-course-name" style="color: var(--primary); font-size: 20px; font-weight: 700; opacity: 0.8; letter-spacing: -0.5px;">Establishing secure telemetry feed...</p>
            </div>
        </div>

        <!-- Right: Telemetry Hub -->
        <div style="display: flex; flex-direction: column; gap: 35px;">
            <!-- Real-time Presence Monitor -->
            <div style="background: white; border-radius: 50px; padding: 50px; display: flex; flex-direction: column; align-items: center; text-align: center; position: relative; overflow: hidden; box-shadow: 0 30px 60px rgba(0,0,0,0.1);">
                <div style="position: absolute; top: 0; right: 0; width: 100%; height: 100%; background: linear-gradient(135deg, transparent 80%, rgba(0,102,255,0.03) 100%);"></div>
                
                <div style="width: 100px; height: 100px; background: var(--primary-glow); color: var(--primary); border-radius: 35px; display: flex; justify-content: center; align-items: center; margin-bottom: 30px;">
                    <i data-lucide="users" style="width: 45px; height: 45px;"></i>
                </div>
                
                <div style="display: flex; align-items: baseline; gap: 10px;">
                    <h3 style="font-size: 92px; font-weight: 950; color: #0f172a; line-height: 1; letter-spacing: -5px;" id="dt-attendee-count">0</h3>
                    <span style="font-size: 24px; font-weight: 800; color: #94a3b8;">/ OK</span>
                </div>
                <p style="color: #64748b; font-size: 13px; font-weight: 850; text-transform: uppercase; letter-spacing: 2.5px; margin-top: 10px;">Verified Synchronizations</p>
                
                <div style="width: 100%; height: 1px; background: #f1f5f9; margin: 40px 0;"></div>
                
                <div style="width: 100%; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div style="text-align: left; background: #f8fafc; padding: 20px; border-radius: 20px;">
                        <p style="font-size: 10px; font-weight: 900; color: #94a3b8; text-transform: uppercase; margin-bottom: 5px;">Node Descriptor</p>
                        <p style="font-size: 16px; font-weight: 950; color: #0f172a;" id="dt-id-label">#--</p>
                    </div>
                    <div style="text-align: left; background: #f8fafc; padding: 20px; border-radius: 20px;">
                        <p style="font-size: 10px; font-weight: 900; color: #94a3b8; text-transform: uppercase; margin-bottom: 5px;">Feed Integrity</p>
                        <p style="font-size: 16px; font-weight: 950; color: #10b981; display: flex; align-items: center; gap: 6px;">
                            <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%;"></span> HIGH
                        </p>
                    </div>
                </div>
            </div>

            <!-- Integrated Control Matrix -->
            <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 50px; padding: 50px; display: flex; flex-direction: column; gap: 30px;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <h3 style="color: white; font-size: 20px; font-weight: 900; letter-spacing: -0.5px;">Command Matrix</h3>
                    <div style="background: rgba(255,255,255,0.1); padding: 5px 12px; border-radius: 8px; font-size: 10px; font-weight: 800; color: rgba(255,255,255,0.5);">ADMIN_CONTROL</div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
                    <button onclick="openBroadcastModal()" style="background: var(--primary); color: white; border: none; height: 75px; border-radius: 24px; font-weight: 900; font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 15px; box-shadow: 0 15px 35px var(--primary-glow); transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
                        <i data-lucide="send" style="width: 22px;"></i> Broadcast Pulse Alert
                    </button>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <button id="dt-togglePause" onclick="togglePause()" style="background: rgba(255,255,255,0.04); border: 1.5px solid rgba(255,255,255,0.1); color: white; height: 70px; border-radius: 22px; font-weight: 850; cursor: pointer; font-size: 14px; transition: all 0.3s;">
                            Pause Telemetry
                        </button>
                        <button onclick="window.location.reload()" style="background: rgba(255,255,255,0.04); border: 1.5px solid rgba(255,255,255,0.1); color: white; height: 70px; border-radius: 22px; font-weight: 850; cursor: pointer; font-size: 14px;">
                            Refresh Sync
                        </button>
                    </div>
                </div>

                <div style="background: rgba(255,255,255,0.02); padding: 25px; border-radius: 25px; border: 1px solid rgba(255,255,255,0.04);">
                    <p style="font-size: 12px; color: rgba(255,255,255,0.4); line-height: 1.6; font-weight: 500;">
                        <i data-lucide="info" style="width: 14px; display: inline; vertical-align: middle; margin-right: 5px;"></i>
                        Projector Mode: Maximize your browser (F11) for optimal classroom visibility. QR node rotates every 15 seconds for enhanced session security.
                    </p>
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
let qrInstance = null;

async function updateLiveCount() {
    if (!currentSessionId || currentStatus !== 'active') return;

    try {
        const response = await fetch(`../includes/get_session_stats.php?session_id=${currentSessionId}`);
        const result = await response.json();

        if (result.success) {
            const countEl = document.getElementById('attendee-count');
            const dtCountEl = document.getElementById('dt-attendee-count');
            const badge = document.getElementById('live-count-badge');
            const newCount = result.count;

            if (newCount !== lastAttendeeCount) {
                if (countEl) countEl.innerText = newCount;
                if (dtCountEl) dtCountEl.innerText = newCount;
                
                // Visual Pulse Effect
                if (badge) {
                    badge.style.transform = 'scale(1.15)';
                    setTimeout(() => {
                        badge.style.transform = 'scale(1)';
                    }, 400);
                }

                lastAttendeeCount = newCount;
            }
        }
    } catch (err) {
        console.error("Stats update failed", err);
    }
}

async function updateQR() {
    if (typeof QRCode === 'undefined') {
        const qrEl = document.getElementById("qrcode");
        if (qrEl) qrEl.innerHTML = "<p style='font-size:10px; color:red;'>Library missing</p>";
        return;
    }

    try {
        const response = await fetch(`../includes/get_qr_token.php?session_id=${currentSessionId}`);
        if (!response.ok) throw new Error("HTTP " + response.status);
        
        const result = await response.json();
        if (!result.success) {
            console.error("Token error:", result.message);
            return;
        }
        
        const token = result.token;
        const qrEl = document.getElementById("qrcode");
        if (!qrEl) return;

        // Ensure container is ready
        requestAnimationFrame(() => {
            const parentContainer = document.getElementById('qr-main-container');
            const parentWidth = parentContainer ? parentContainer.offsetWidth : 300;
            // Adaptive sizing but never too small for scans
            const qrSize = Math.max(200, Math.min(260, parentWidth - 70));

            qrEl.style.width = qrSize + 'px';
            qrEl.style.height = qrSize + 'px';
            qrEl.innerHTML = ""; 

            // Sync Desktop QR
            const dtQrEl = document.getElementById("dt-qrcode");
            if (dtQrEl) {
                dtQrEl.innerHTML = "";
                new QRCode(dtQrEl, {
                    text: token,
                    width: 450,
                    height: 450,
                    colorDark : "#0f172a",
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.H
                });
            }

            try {
                new QRCode(qrEl, {
                    text: token,
                    width: qrSize,
                    height: qrSize,
                    colorDark : "#0f172a",
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.M
                });
                
                // Polish the result
                const img = qrEl.querySelector('img');
                const canvas = qrEl.querySelector('canvas');
                if (img) { img.style.borderRadius = "12px"; img.style.display = "block"; }
                if (canvas) { canvas.style.borderRadius = "12px"; canvas.style.display = "block"; }
            } catch (qrErr) {
                console.error("QRCode Render Error:", qrErr);
                qrEl.innerHTML = "<p style='font-size:10px;'>Render Error</p>";
            }
        });

    } catch (err) {
        console.error("QR Fetch Failure:", err);
        const qrEl = document.getElementById("qrcode");
        if (qrEl) qrEl.innerHTML = "<p style='font-size:10px; color:var(--danger);'>Sync Failure</p>";
    }
}

function showSetup() {
    document.getElementById('setup-view').style.display = 'block';
    document.getElementById('hub-view').style.display = 'none';
    
    // Update nav styling
    document.querySelectorAll('.session-nav-btn').forEach(btn => {
        btn.style.background = 'white';
        btn.style.borderColor = 'var(--border)';
    });
    document.getElementById('nav-new-sess').style.background = 'var(--primary)';
    
    if (rotationInterval) clearInterval(rotationInterval);
}

function manageSession(id, courseName, status, topic, courseId) {
    currentSessionId = id;
    currentCourseId = courseId;
    currentStatus = status || 'active';
    const finalTopic = topic || 'General Session';
    
    document.getElementById('setup-view').style.display = 'none';
    document.getElementById('hub-view').style.display = 'block';
    
    // Update live labels
    document.getElementById('liveTopicName').innerText = finalTopic;
    document.getElementById('liveCourseName').innerText = courseName;
    document.getElementById('liveCourseCode').innerText = "LIVE NODE"; 
    document.getElementById('session-id-badge').innerText = "NODE: " + id;

    // Update Desktop labels
    if (document.getElementById('dt-topic-name')) document.getElementById('dt-topic-name').innerText = finalTopic;
    if (document.getElementById('dt-course-name')) document.getElementById('dt-course-name').innerText = courseName;
    if (document.getElementById('dt-id-label')) document.getElementById('dt-id-label').innerText = "#" + id;
    if (document.getElementById('dt-session-id')) document.getElementById('dt-session-id').innerText = "ID: " + id;
    
    // Update nav styling
    document.querySelectorAll('.session-nav-btn').forEach(btn => {
        btn.style.background = 'white';
        btn.style.borderColor = 'var(--border)';
    });
    const activeBtn = document.getElementById('nav-sess-' + id);
    if (activeBtn) {
        activeBtn.style.background = 'var(--bg-main)';
        activeBtn.style.borderColor = 'var(--primary)';
    }
    document.getElementById('nav-new-sess').style.background = 'var(--text-dark)';

    if (typeof lucide !== 'undefined') lucide.createIcons();
    
    setTimeout(updateQR, 100);
    setTimeout(updateLiveCount, 200);
    updateUI();
    
    // Clear existing intervals
    if (rotationInterval) clearInterval(rotationInterval);
    if (statsInterval) clearInterval(statsInterval);

    lastAttendeeCount = 0;
    document.getElementById('attendee-count').innerText = "0";

    // Start New Intervals
    rotationInterval = setInterval(() => {
        if (currentStatus === 'active') updateQR();
    }, 15000);

    statsInterval = setInterval(() => {
        if (currentStatus === 'active') updateLiveCount();
    }, 5000);
}

async function togglePause() {
    const action = currentStatus === 'active' ? 'pause' : 'resume';
    const originalStatus = currentStatus;
    currentStatus = (action === 'pause') ? 'paused' : 'active';
    updateUI();

    try {
        const response = await fetch('../includes/toggle_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ session_id: currentSessionId, action: action })
        });
        const result = await response.json();
        if (!result.success) {
            currentStatus = originalStatus;
            updateUI();
            AttendEase.notify('error', 'Sync Failed', result.message);
        }
    } catch (err) {
        currentStatus = originalStatus;
        updateUI();
    }
}

function updateUI() {
    const shield = document.getElementById('pauseShield');
    const toggleBtn = document.getElementById('toggleBtn');
    const dtToggleBtn = document.getElementById('dt-togglePause');
    
    if (currentStatus === 'paused') {
        if (shield) shield.style.display = 'flex';
        if (toggleBtn) toggleBtn.innerHTML = '<i data-lucide="play-circle" style="width: 20px;"></i><span style="font-size: 11px; text-transform: uppercase;">Resume</span>';
        if (dtToggleBtn) dtToggleBtn.innerText = 'Resume Feed';
    } else {
        if (shield) shield.style.display = 'none';
        if (toggleBtn) toggleBtn.innerHTML = '<i data-lucide="pause-circle" style="width: 20px;"></i><span style="font-size: 11px; text-transform: uppercase;">Pause</span>';
        if (dtToggleBtn) dtToggleBtn.innerText = 'Pause Feed';
    }
    lucide.createIcons();
}

function handlePrint() {
    const originalStatus = currentStatus;
    const shield = document.getElementById('pauseShield');
    shield.style.display = 'none';
    window.print();
    if (originalStatus === 'paused') shield.style.display = 'flex';
}

async function closeSession() {
    const confirmClose = await Swal.fire({
        title: 'Terminate Node?',
        text: 'This will stop all attendance broadcasts for this session immediately.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Kill Node',
        cancelButtonText: 'Cancel',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'btn-primary swal2-confirm',
            cancelButton: 'swal2-cancel'
        }
    });

    if (!confirmClose.isConfirmed) return;
    const response = await fetch('../includes/toggle_session.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ session_id: currentSessionId, action: 'close' })
    });
    const result = await response.json();
    if (result.success) location.reload();
}

document.getElementById('qrGenForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('genBtn');
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    const courseName = e.target.course_id.options[e.target.course_id.selectedIndex].text;
    const topic = data.topic;

    btn.disabled = true;
    btn.innerHTML = '<span class="loader"></span> Validating...';

    // 📍 FETCH LOCATION FOR GEO-FENCING
    if (data.use_geo) {
        try {
            console.log("Requesting location for session lock...");
            const pos = await AttendEase.getLocation();
            data.lat = pos.lat;
            data.lng = pos.lng;
        } catch (err) {
            console.warn("Location fetch failing for lecturer:", err);
            const proceedWithoutGeo = await Swal.fire({
                title: 'Location Failed',
                text: 'We couldn\'t get your current location. Create session without geo-fencing?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Deploy Anyway',
                cancelButtonText: 'Cancel'
            });
            if (!proceedWithoutGeo.isConfirmed) {
                btn.disabled = false;
                btn.innerText = 'Deploy Broadcast Node';
                return;
            }
        }
    }

    try {
        btn.innerHTML = '<span class="loader"></span> Deploying Node...';
        const response = await fetch('../includes/create_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) {
            // Fresh reload to update the multi-session header
            location.reload();
        } else {
            AttendEase.notify('error', 'Deployment Failed', result.message);
            btn.disabled = false;
            btn.innerText = 'Deploy Broadcast Node';
        }
    } catch (err) {
        btn.disabled = false;
    }
});

// Init
lucide.createIcons();

<?php if (!empty($existing_sessions)): ?>
    manageSession(
        <?php echo $existing_sessions[0]['id']; ?>, 
        '<?php echo addslashes($existing_sessions[0]['course_name']); ?>', 
        '<?php echo $existing_sessions[0]['status']; ?>',
        '<?php echo addslashes($existing_sessions[0]['topic'] ?? 'General Session'); ?>',
        <?php echo $existing_sessions[0]['course_id']; ?>
    );
<?php else: ?>
    showSetup();
<?php endif; ?>

window.addEventListener('resize', () => {
    if (currentSessionId) updateQR();
});

// Broadcast Intelligence
function openBroadcastModal() {
    Swal.fire({
        title: 'Secure Broadcast',
        html: `
            <div style="text-align: left;">
                <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">Sending instant push notification to all students enrolled in this course.</p>
                <input id="swal-title" class="swal2-input" placeholder="Pulse Title (e.g. Class Update)" style="margin: 0 0 15px; width: 100%; border-radius: 12px;">
                <textarea id="swal-message" class="swal2-textarea" placeholder="Message content..." style="margin: 0; width: 100%; border-radius: 12px; height: 100px;"></textarea>
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: '🚀 Send Pulse',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'btn-primary swal2-confirm',
            cancelButton: 'swal2-cancel'
        },
        preConfirm: () => {
            return {
                title: document.getElementById('swal-title').value,
                message: document.getElementById('swal-message').value
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            deployPulse(result.value.title, result.value.message);
        }
    });
}

async function deployPulse(title, message) {
    if (!title || !message) return;
    
    // Use the tracked currentCourseId or fallback to form selection
    const courseId = currentCourseId || document.getElementsByName('course_id')[0].value;

    try {
        const response = await fetch('../includes/process_broadcast.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '<?php echo AttendEaseSecurity::getCsrfToken(); ?>'
            },
            body: JSON.stringify({ 
                course_id: courseId,
                title: title,
                message: message
            })
        });
        const result = await response.json();
        if (result.success) {
            AttendEase.notify('success', 'Broadcast Deployed', result.message);
        } else {
            AttendEase.notify('error', 'Pulse Failed', result.message);
        }
    } catch (err) {
        AttendEase.notify('error', 'Network Error', 'Could not reach broadcast node.');
    }
}

// Final check to ensure icons are rendered
if (window.lucide) {
    lucide.createIcons();
}
</script>

<?php include '../includes/footer.php'; ?>
