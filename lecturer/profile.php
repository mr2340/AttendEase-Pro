<?php
$page_title = "Account";
include '../includes/header.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'lecturer' && $_SESSION['role'] !== 'admin')) {
    header("Location: login");
    exit;
}

$db = get_db_connection();
$user_id = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<section id="lecturer-profile" class="screen" data-state="active">
    <div class="scrollable-content">
        <div class="dash-header" style="margin-bottom: 20px;">
            <a href="dashboard.php" style="color: var(--text-dark);"><i data-lucide="arrow-left"></i></a>
            <h2 style="font-weight: 800; font-size: 20px;">Faculty Settings</h2>
            <div style="width: 24px;"></div>
        </div>

        <div style="padding: 24px;">
            <div style="text-align: center; margin-bottom: 35px;">
                <div style="position: relative; display: inline-block;">
                    <div style="width: 110px; height: 110px; border-radius: 40px; overflow: hidden; border: 4px solid var(--surface); box-shadow: 0 20px 40px var(--primary-glow);">
                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Lecturer" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div style="position: absolute; bottom: -5px; right: -5px; width: 35px; height: 35px; background: var(--primary); border-radius: 12px; display: flex; justify-content: center; align-items: center; color: white; border: 3px solid var(--surface);">
                        <i data-lucide="camera" style="width: 16px; height: 16px;"></i>
                    </div>
                </div>
                <h2 style="margin-top: 20px; font-weight: 800; color: var(--text-dark);">Dr. <?php echo explode('_', $user['username'])[1] ?? 'Lecturer'; ?></h2>
                <p style="color: var(--text-muted); font-size: 14px;"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>

            <div style="background: var(--surface); border-radius: 32px; border: 1px solid var(--border); overflow: hidden;">
                <div style="padding: 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 15px;">
                    <div style="background: var(--primary-glow); color: var(--primary); padding: 10px; border-radius: 14px;">
                        <i data-lucide="moon" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="font-size: 15px; font-weight: 700;">Dark Mode</h4>
                    </div>
                    <label class="switch">
                        <input type="checkbox" <?php echo $user['dark_mode'] ? 'checked' : ''; ?> onchange="toggleDarkMode()">
                        <span class="slider round"></span>
                    </label>
                </div>

                <div style="padding: 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 15px; cursor: pointer;">
                    <div style="background: #ecfdf5; color: #10b981; padding: 10px; border-radius: 14px;">
                        <i data-lucide="shield-check" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="font-size: 15px; font-weight: 700;">Security & Password</h4>
                    </div>
                    <i data-lucide="chevron-right" style="color: var(--text-muted); width: 18px;"></i>
                </div>

                <a href="../includes/logout.php" style="padding: 20px; display: flex; align-items: center; gap: 15px; cursor: pointer; text-decoration: none;">
                    <div style="background: #fef2f2; color: #ef4444; padding: 10px; border-radius: 14px;">
                        <i data-lucide="log-out" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="font-size: 15px; font-weight: 700; color: #ef4444;">Logout</h4>
                    </div>
                </a>
            </div>
        </div>

        <div style="height: 100px;"></div>
    </div>

    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>
</section>

<style>
.switch { position: relative; display: inline-block; width: 44px; height: 24px; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; inset: 0; background-color: #e2e8f0; transition: .4s; }
.slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; }
input:checked + .slider { background-color: var(--primary); }
input:checked + .slider:before { transform: translateX(20px); }
.slider.round { border-radius: 34px; }
.slider.round:before { border-radius: 50%; }
</style>

<script>
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    fetch('../includes/update_profile.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'dark_mode=toggle'
    });
}
</script>

<?php include '../includes/footer.php'; ?>
