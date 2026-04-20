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

        <div style="padding: 16px; max-width: 500px; margin: 0 auto;">
            <!-- Setup View -->
            <div id="setup-view" style="display: <?php echo empty($existing_sessions) ? 'block' : 'none'; ?>;">
                <div style="background: white; padding: 25px; border-radius: 30px; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                    <h3 style="font-size: 20px; font-weight: 900; color: #0f172a; margin-bottom: 5px;">New Session</h3>
                    <p style="color: #64748b; font-size: 13px; margin-bottom: 25px;">Initialize attendance node.</p>
                    
                    <form id="qrGenForm">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">Target Course</label>
                            <select name="course_id" class="form-control" style="height: 55px; border-radius: 16px; border: 2px solid #f1f5f9; background: #f8fafc; font-weight: 600; font-size: 14px;">
                                <?php foreach($courses as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['course_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div class="form-group">
                                <label style="font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">Expiry</label>
                                <select name="duration" class="form-control" style="height: 55px; border-radius: 16px; border: 2px solid #f1f5f9; background: #f8fafc; font-weight: 600; font-size: 14px;">
                                    <option value="15">15m</option>
                                    <option value="30" selected>30m</option>
                                    <option value="60">1h</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label style="font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">Cap</label>
                                <input type="number" name="scan_limit" class="form-control" value="0" style="height: 55px; border-radius: 16px; border: 2px solid #f1f5f9; background: #f8fafc; font-weight: 600; text-align: center; font-size: 14px;">
                            </div>
                        </div>

                        <button type="submit" class="btn-primary" id="genBtn" style="margin-top: 25px; height: 60px; border-radius: 18px; font-weight: 800; font-size: 15px; background: #0066ff; box-shadow: 0 10px 25px rgba(0, 102, 255, 0.2);">
                            Launch Secure Hub
                        </button>
                    </form>
                </div>

                <?php if (!empty($existing_sessions)): ?>
                    <div style="margin-top: 30px; margin-bottom: 12px; font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.5px; padding-left: 5px;">Active Nodes</div>
                    <?php foreach($existing_sessions as $sess): ?>
                        <div onclick="manageSession(<?php echo $sess['id']; ?>, '<?php echo htmlspecialchars($sess['course_name']); ?>', '<?php echo $sess['status']; ?>')" style="background: white; padding: 16px; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 40px; height: 40px; background: <?php echo $sess['status'] == 'active' ? '#ecfdf5' : '#fff7ed'; ?>; color: <?php echo $sess['status'] == 'active' ? '#10b981' : '#f59e0b'; ?>; border-radius: 12px; display: flex; justify-content: center; align-items: center;">
                                    <i data-lucide="<?php echo $sess['status'] == 'active' ? 'zap' : 'pause-circle'; ?>" style="width: 18px;"></i>
                                </div>
                                <div>
                                    <h4 style="font-weight: 700; font-size: 14px; color: #1e293b; margin: 0;"><?php echo htmlspecialchars($sess['course_name']); ?></h4>
                                    <p style="font-size: 10px; color: #94a3b8; font-weight: 600; margin: 2px 0 0;"><?php echo strtoupper($sess['status']); ?></p>
                                </div>
                            </div>
                            <i data-lucide="chevron-right" style="color: #cbd5e1; width: 16px;"></i>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Management Hub (Responsive) -->
            <div id="hub-view" style="display: none; width: 100%;">
                <div id="qr-main-container" style="position: relative; background: white; padding: 30px 20px; border-radius: 35px; text-align: center; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.05); border: 1px solid #f1f5f9; overflow: hidden; margin-bottom: 25px;">
                    
                    <!-- Dynamic Integrity Hub -->
                    <div id="qrcode-wrapper" style="position: relative; display: inline-block; padding: 15px; background: white; border-radius: 30px; border: 2px solid #f8fafc; max-width: 100%; box-sizing: border-box;">
                        <div id="qrcode" style="display: flex; justify-content: center; overflow: hidden; border-radius: 15px;"></div>
                        
                        <!-- Anti-Photo Rotation Ring -->
                        <div id="rotation-ring" style="position: absolute; top: -5px; left: -5px; right: -5px; bottom: -5px; border: 3px solid #0066ff; border-radius: 35px; border-top-color: transparent; border-left-color: transparent; animation: spin 30s linear infinite;"></div>
                    </div>

                    <div style="margin-top: 25px;">
                        <h3 id="liveCourseName" style="font-weight: 900; color: #0f172a; font-size: 22px; letter-spacing: -0.5px; margin: 0;">Course Name</h3>
                        <div style="display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 6px; margin-top: 10px;">
                            <span style="background: #0066ff10; color: #0066ff; padding: 5px 12px; border-radius: 20px; font-size: 9px; font-weight: 800; text-transform: uppercase;">Secure Node</span>
                            <span id="session-id-badge" style="background: #f8fafc; color: #64748b; padding: 5px 12px; border-radius: 20px; font-size: 9px; font-weight: 700; border: 1px solid #f1f5f9;">ID: --</span>
                        </div>
                    </div>

                    <!-- Responsive Shield -->
                    <div id="pauseShield" style="display: none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); z-index: 100; flex-direction: column; justify-content: center; align-items: center;">
                        <div style="width: 70px; height: 70px; background: #f59e0b; border-radius: 24px; display: flex; justify-content: center; align-items: center; margin-bottom: 20px; box-shadow: 0 15px 30px rgba(245, 158, 11, 0.25); animation: pulseShield 2s infinite;">
                            <i data-lucide="pause" style="width: 35px; height: 35px; color: white;"></i>
                        </div>
                        <h2 style="font-weight: 900; color: #92400e; font-size: 18px;">PAUSED</h2>
                        <button onclick="togglePause()" class="btn-primary" style="margin-top: 25px; background: #f59e0b; width: 160px; height: 50px; border-radius: 15px; font-weight: 800; font-size: 14px;">
                            Resume
                        </button>
                    </div>
                </div>

                <!-- Control Grid -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px;">
                    <button id="toggleBtn" onclick="togglePause()" class="btn-secondary" style="height: 65px; border-radius: 20px; border: 2px solid #f59e0b15; background: #fffbeb; color: #f59e0b; font-weight: 800; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 4px;">
                        <i data-lucide="pause-circle" style="width: 18px;"></i>
                        <span style="font-size: 10px; text-transform: uppercase;">Pause</span>
                    </button>
                    <button onclick="handlePrint()" class="btn-secondary" style="height: 65px; border-radius: 20px; border: 2px solid #f1f5f9; background: #0f172a; color: white; font-weight: 800; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 4px;">
                        <i data-lucide="printer" style="width: 18px;"></i>
                        <span style="font-size: 10px; text-transform: uppercase;">Print</span>
                    </button>
                </div>
                
                <button onclick="closeSession()" style="width: 100%; height: 60px; border-radius: 20px; background: #fff1f2; color: #e11d48; border: 2px solid #ffe4e6; font-weight: 800; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer;">
                    <i data-lucide="power" style="width: 16px;"></i> Terminate Hub
                </button>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="javascript:location.reload()" style="font-size: 12px; color: #94a3b8; font-weight: 700; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 6px;">
                        <i data-lucide="layout-grid" style="width: 14px;"></i> Back to Setup
                    </a>
                </div>
            </div>
        </div>

        <div style="height: 80px;"></div>
    </div>
</section>

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
let currentStatus = 'active';
let rotationInterval = null;
let qrInstance = null;

function generateSecureToken(sessionId) {
    const block = Math.floor(Date.now() / 30000);
    return btoa(sessionId + ":" + block);
}

function updateQR() {
    const token = generateSecureToken(currentSessionId);
    const qrEl = document.getElementById("qrcode");
    
    // Dynamic Resizing for Response
    const parentContainer = document.getElementById('qr-main-container');
    const parentWidth = parentContainer ? parentContainer.offsetWidth : 280;
    const qrSize = Math.max(150, Math.min(280, parentWidth - 80));
    
    if (!qrInstance) {
        qrInstance = new QRCode(qrEl, {
            text: token,
            width: qrSize,
            height: qrSize,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    } else {
        qrInstance.clear();
        qrInstance.makeCode(token);
    }
}

function manageSession(id, name, status) {
    currentSessionId = id;
    currentStatus = status || 'active';
    
    document.getElementById('setup-view').style.display = 'none';
    document.getElementById('hub-view').style.display = 'block';
    document.getElementById('liveCourseName').innerText = name;
    document.getElementById('session-id-badge').innerText = "NODE ID: " + id;
    
    // Ensure icons are rendered in the hub view
    if (typeof lucide !== 'undefined') lucide.createIcons();
    
    // Trigger QR with small delay to ensure container width is calculated
    setTimeout(updateQR, 100);
    updateUI();
    
    if (rotationInterval) clearInterval(rotationInterval);
    rotationInterval = setInterval(() => {
        if (currentStatus === 'active') updateQR();
    }, 15000);
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
            alert(result.message);
        }
    } catch (err) {
        currentStatus = originalStatus;
        updateUI();
    }
}

function updateUI() {
    const shield = document.getElementById('pauseShield');
    const toggleBtn = document.getElementById('toggleBtn');
    
    if (currentStatus === 'paused') {
        shield.style.display = 'flex';
        toggleBtn.innerHTML = '<i data-lucide="play-circle" style="width: 18px;"></i><span style="font-size: 10px;">Resume</span>';
    } else {
        shield.style.display = 'none';
        toggleBtn.innerHTML = '<i data-lucide="pause-circle" style="width: 18px;"></i><span style="font-size: 10px;">Pause</span>';
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
    if (!confirm("Terminate this session?")) return;
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

    btn.disabled = true;
    btn.innerHTML = '<span class="loader"></span>';

    try {
        const response = await fetch('../includes/create_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) {
            manageSession(result.session_id, courseName, 'active');
        } else {
            alert(result.message);
            btn.disabled = false;
            btn.innerText = 'Launch Secure Hub';
        }
    } catch (err) {
        btn.disabled = false;
    }
});

lucide.createIcons();

<?php if (!empty($existing_sessions)): ?>
    manageSession(
        <?php echo $existing_sessions[0]['id']; ?>, 
        '<?php echo addslashes($existing_sessions[0]['course_name']); ?>', 
        '<?php echo $existing_sessions[0]['status']; ?>'
    );
<?php endif; ?>

// Handle window resize to keep QR responsive
window.addEventListener('resize', () => {
    if (currentSessionId) updateQR();
});

// Final check to ensure icons are rendered
if (window.lucide) {
    lucide.createIcons();
}
</script>

<?php include '../includes/footer.php'; ?>
