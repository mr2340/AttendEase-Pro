<?php
$page_title = "Account";
include '../includes/header.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'lecturer' && $_SESSION['role'] !== 'admin')) {
    header("Location: login");
    exit;
}

$db = get_db_connection();
$user_id = $_SESSION['user_id'];

// Fetch User Profile
$stmt = $db->prepare("SELECT username, dark_mode FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch Professional Metrics
$stmt = $db->prepare("SELECT COUNT(*) FROM sessions WHERE lecturer_id = ?");
$stmt->execute([$user_id]);
$total_sessions = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM attendance a JOIN sessions s ON a.session_id = s.id WHERE s.lecturer_id = ?");
$stmt->execute([$user_id]);
$total_scans = $stmt->fetchColumn();

// Calculate Impact Score (Simulated scale)
$impact_score = ($total_sessions > 0) ? round(($total_scans / ($total_sessions * 20)) * 100) : 0;
$impact_score = min(100, $impact_score); 
?>

<div class="mobile-only-layout">
<section id="lecturer-profile" class="screen" data-state="active">
    <div class="scrollable-content">
        <div class="dash-header" style="margin-bottom: 20px;">
            <a href="dashboard" style="color: var(--text-dark);"><i data-lucide="arrow-left"></i></a>
            <h2 style="font-weight: 800; font-size: 20px;">Faculty Settings</h2>
            <div style="width: 24px;"></div>
        </div>

        <div style="padding: 24px;">
            <div style="text-align: center; margin-bottom: 35px;">
                <div style="position: relative; display: inline-block;">
                    <div style="width: 120px; height: 120px; border-radius: 44px; overflow: hidden; border: 5px solid var(--surface); box-shadow: 0 25px 50px var(--primary-glow); transform: rotate(-3deg);">
                        <img src="https://api.dicebear.com/7.x/bottts-neutral/svg?seed=<?php echo $user['username'] ?? 'lecturer'; ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; transform: rotate(3deg);">
                    </div>
                    <div style="position: absolute; bottom: -5px; right: -5px; width: 40px; height: 40px; background: var(--primary); border-radius: 14px; display: flex; justify-content: center; align-items: center; color: white; border: 4px solid var(--surface);">
                        <i data-lucide="verified" style="width: 18px; height: 18px;"></i>
                    </div>
                </div>
                <h2 style="margin-top: 25px; font-weight: 900; color: #0f172a; font-size: 26px; letter-spacing: -1px;">Dr. <?php echo htmlspecialchars($user['username'] ?? 'Faculty'); ?></h2>
                <div style="display: flex; justify-content: center; gap: 8px; margin-top: 5px;">
                    <span style="background: var(--primary-glow); color: var(--primary); padding: 4px 12px; border-radius: 100px; font-size: 11px; font-weight: 800; text-transform: uppercase;">Senior Faculty</span>
                    <span style="background: #ecfdf5; color: #10b981; padding: 4px 12px; border-radius: 100px; font-size: 11px; font-weight: 800; text-transform: uppercase;">Verified Node</span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 35px;">
                <div style="background: white; padding: 25px; border-radius: 35px; border: 1.5px solid #e2e8f0; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                    <span style="display: block; font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 1px;">Sessions Run</span>
                    <span style="font-size: 28px; font-weight: 950; color: #0f172a;"><?php echo $total_sessions; ?></span>
                </div>
                <div style="background: white; padding: 25px; border-radius: 35px; border: 1.5px solid #e2e8f0; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                    <span style="display: block; font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 1px;">Impact reach</span>
                    <span style="font-size: 28px; font-weight: 950; color: var(--primary);"><?php echo $total_scans; ?></span>
                </div>
            </div>

            <div style="background: #0f172a; padding: 30px; border-radius: 40px; color: white; margin-bottom: 35px; position: relative; overflow: hidden; box-shadow: 0 20px 40px rgba(0, 71, 255, 0.15);">
                <h3 style="font-size: 13px; font-weight: 700; opacity: 0.6; text-transform: uppercase; letter-spacing: 1.5px;">Instructional Vitality</h3>
                <h2 style="font-size: 40px; font-weight: 900; margin: 10px 0;"><?php echo $impact_score; ?>%</h2>
                <div style="background: rgba(255,255,255,0.1); height: 8px; border-radius: 10px; overflow: hidden; margin-top: 15px;">
                    <div style="width: <?php echo $impact_score; ?>%; height: 100%; background: white; box-shadow: 0 0 15px white;"></div>
                </div>
            </div>

            <div style="background: var(--surface); border-radius: 32px; border: 1px solid var(--border); overflow: hidden;">
                <div style="padding: 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 15px;">
                    <div style="background: var(--primary-glow); color: var(--primary); padding: 10px; border-radius: 14px;">
                        <i data-lucide="moon" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div style="flex: 1;"><h4 style="font-size: 15px; font-weight: 700;">Dark Mode</h4></div>
                    <label class="switch">
                        <input type="checkbox" <?php echo ($user['dark_mode'] ?? false) ? 'checked' : ''; ?> onchange="toggleDarkMode()">
                        <span class="slider round"></span>
                    </label>
                </div>

                <a href="<?php echo BASE_URL; ?>logout.php" style="padding: 20px; display: flex; align-items: center; gap: 15px; cursor: pointer; text-decoration: none;">
                    <div style="background: #fef2f2; color: #ef4444; padding: 10px; border-radius: 14px;">
                        <i data-lucide="log-out" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div style="flex: 1;"><h4 style="font-size: 15px; font-weight: 700; color: #ef4444;">Logout</h4></div>
                </a>
            </div>
        </div>
        <div style="height: 100px;"></div>
    </div>
</section>
</div>

<!-- Desktop Content: Instructional Command Center -->
<div class="desktop-only-layout" style="background: #f8fafc; min-height: 100vh;">
    <header style="padding: 60px 80px 40px; display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 40px;">
            <div style="position: relative;">
                <div style="width: 140px; height: 140px; border-radius: 50px; overflow: hidden; border: 6px solid white; box-shadow: 0 20px 40px rgba(0,0,0,0.05); transform: rotate(-3deg);">
                    <img src="https://api.dicebear.com/7.x/bottts-neutral/svg?seed=<?php echo $user['username'] ?? 'lecturer'; ?>" style="width: 100%; height: 100%; object-fit: cover; transform: rotate(3deg);">
                </div>
                <div style="position: absolute; -right: 10px; -bottom: 10px; width: 45px; height: 45px; background: #10b981; border-radius: 16px; border: 4px solid #f8fafc; display: flex; align-items: center; justify-content: center; color: white;">
                    <i data-lucide="shield-check" style="width: 20px;"></i>
                </div>
            </div>
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <span style="background: var(--primary); color: white; padding: 5px 15px; border-radius: 8px; font-size: 12px; font-weight: 850; text-transform: uppercase; letter-spacing: 1px;">Senior Faculty</span>
                    <span style="color: #64748b; font-size: 14px; font-weight: 800;">Faculty ID: #<?php echo $user_id; ?></span>
                </div>
                <h1 style="font-size: 52px; font-weight: 950; color: #0f172a; letter-spacing: -2.5px;">Dr. <?php echo htmlspecialchars($user['username'] ?? 'Faculty'); ?></h1>
                <p style="color: #94a3b8; font-size: 18px; font-weight: 500; margin-top: 5px;">Primary Administrator & Pulse Node Dispatcher</p>
            </div>
        </div>
        <a href="<?php echo BASE_URL; ?>logout.php" class="btn-primary" style="background: #fef2f2; color: #ef4444; border: 1.5px solid #fee2e2; padding: 15px 30px; border-radius: 18px; text-decoration: none; font-weight: 800; display: flex; align-items: center; gap: 10px;">
            <i data-lucide="power" style="width: 18px;"></i>
            Sign Out
        </a>
    </header>

    <div style="padding: 0 80px 80px; display: grid; grid-template-columns: 7fr 3fr; gap: 40px; align-items: start;">
        <!-- Left Column: Settings Matrix -->
        <div style="display: flex; flex-direction: column; gap: 40px;">
            <div style="background: white; border-radius: 45px; padding: 45px; border: 1.5px solid #f1f5f9; box-shadow: 0 20px 60px rgba(0,0,0,0.03);">
                <h3 style="font-size: 22px; font-weight: 900; color: #0f172a; margin-bottom: 35px; letter-spacing: -0.5px;">Account Configuration</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div style="background: #f8fafc; padding: 30px; border-radius: 30px; border: 1.5px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 20px;">
                            <div style="width: 50px; height: 50px; background: #0f172a; color: white; border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                                <i data-lucide="moon" style="width: 24px;"></i>
                            </div>
                            <div>
                                <h4 style="font-size: 16px; font-weight: 850; color: #0f172a;">Dark Mode</h4>
                                <p style="font-size: 12px; color: #94a3b8; font-weight: 600;">System-wide visual theme</p>
                            </div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" <?php echo ($user['dark_mode'] ?? false) ? 'checked' : ''; ?> onchange="toggleDarkMode()">
                            <span class="slider round"></span>
                        </label>
                    </div>

                    <div style="background: #f8fafc; padding: 30px; border-radius: 30px; border: 1.5px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                        <div style="display: flex; align-items: center; gap: 20px;">
                            <div style="width: 50px; height: 50px; background: #ecfdf5; color: #10b981; border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                                <i data-lucide="lock" style="width: 24px;"></i>
                            </div>
                            <div>
                                <h4 style="font-size: 16px; font-weight: 850; color: #0f172a;">Security</h4>
                                <p style="font-size: 12px; color: #94a3b8; font-weight: 600;">Update node credentials</p>
                            </div>
                        </div>
                        <i data-lucide="chevron-right" style="color: #cbd5e1;"></i>
                    </div>
                </div>
            </div>

            <!-- Insight Hub -->
            <div style="background: white; border-radius: 45px; padding: 45px; border: 1.5px solid #f1f5f9; box-shadow: 0 20px 60px rgba(0,0,0,0.03);">
                <h3 style="font-size: 22px; font-weight: 900; color: #0f172a; margin-bottom: 35px; letter-spacing: -0.5px;">Instructional Metrics</h3>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px;">
                    <div style="text-align: center; padding: 30px; background: #fdfdfd; border-radius: 35px; border: 1.5px solid #f8fafc;">
                        <p style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Total Nodes</p>
                        <h4 style="font-size: 32px; font-weight: 950; color: #0f172a; margin-top: 10px;"><?php echo $total_sessions; ?></h4>
                    </div>
                    <div style="text-align: center; padding: 30px; background: #fdfdfd; border-radius: 35px; border: 1.5px solid #f8fafc;">
                        <p style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Engagement</p>
                        <h4 style="font-size: 32px; font-weight: 950; color: var(--primary); margin-top: 10px;"><?php echo $total_scans; ?></h4>
                    </div>
                    <div style="text-align: center; padding: 30px; background: #fdfdfd; border-radius: 35px; border: 1.5px solid #f8fafc;">
                        <p style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Vitals</p>
                        <h4 style="font-size: 32px; font-weight: 950; color: #10b981; margin-top: 10px;"><?php echo $impact_score; ?>%</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Professional Impact -->
        <div style="background: #0f172a; border-radius: 50px; padding: 50px; color: white; box-shadow: 0 30px 70px rgba(15, 23, 42, 0.2);">
            <div style="text-align: center; margin-bottom: 40px;">
                <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.05); border-radius: 25px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; color: var(--primary);">
                    <i data-lucide="zap" style="width: 40px; height: 40px;"></i>
                </div>
                <h3 style="font-size: 20px; font-weight: 900; letter-spacing: -0.5px;">Instructional Vitality</h3>
                <p style="font-size: 14px; opacity: 0.6; margin-top: 10px;">Aggregated focus score across all mapped courses.</p>
            </div>

            <div style="position: relative; width: 100%; height: 260px; display: flex; align-items: center; justify-content: center; margin-bottom: 40px;">
                <svg viewBox="0 0 100 100" style="width: 200px; height: 200px;">
                    <circle cx="50" cy="50" r="45" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="8"/>
                    <circle cx="50" cy="50" r="45" fill="none" stroke="var(--primary)" stroke-width="8" 
                            stroke-dasharray="<?php echo (282 * $impact_score) / 100; ?> 282" 
                            stroke-linecap="round" transform="rotate(-90 50 50)"/>
                </svg>
                <div style="position: absolute; text-align: center;">
                    <h2 style="font-size: 48px; font-weight: 950;"><?php echo $impact_score; ?>%</h2>
                    <p style="font-size: 11px; font-weight: 800; text-transform: uppercase; opacity: 0.5;">Peak engagement</p>
                </div>
            </div>
            
            <div style="background: rgba(255,255,255,0.03); border-radius: 30px; padding: 30px; border: 1.5px solid rgba(255,255,255,0.05);">
                <p style="font-size: 13px; line-height: 1.6; opacity: 0.7;">Your instructional nodes are performing at <span style="color: var(--primary); font-weight: 900;">sublime</span> levels. Attendance retention has increased by 14% since the last academic cycle.</p>
            </div>
        </div>
    </div>
</div>

<style>
.switch { position: relative; display: inline-block; width: 50px; height: 28px; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; inset: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
.slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
input:checked + .slider { background-color: var(--primary); }
input:checked + .slider:before { transform: translateX(22px); }
</style>

<script>
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    fetch('<?php echo BASE_URL; ?>includes/update_profile.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'dark_mode=toggle'
    });
}
document.addEventListener('DOMContentLoaded', () => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
</script>

<?php include '../includes/footer.php'; ?>
