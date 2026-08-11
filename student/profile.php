<?php
require_once '../includes/config.php';
require_once '../includes/stat_engine.php';

$page_title = "Identity Hub";
include '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$db = get_db_connection();
$user_id = $_SESSION['user_id'];
$user_stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

$username = $user['username'] ?? '';
$fullname = $user['fullname'] ?? 'Student';
$email = $user['email'] ?? '';
$student_id_val = $user['student_id'] ?? 'N/A';
$avatar_url = $user['avatar_url'] ?? null;
$bio_enabled = $user['bio_enabled'] ?? 0;
$display_avatar = $avatar_url ? (strpos($avatar_url, 'http') === 0 ? $avatar_url : BASE_URL . $avatar_url) : "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($username);
$attendance_score = StatEngine::getStudentAttendanceScore($user_id);
?>

<div class="mobile-only-layout">
    <div style="height: 30px;"></div> <!-- Spacer for top visibility -->

    <div style="padding: 20px; text-align: center;">
        <div class="profile-avatar" style="width: 120px; height: 120px; margin: 0 auto 20px; border-radius: 45px; overflow: hidden; border: 4px solid var(--surface); box-shadow: 0 10px 25px var(--primary-glow);" id="avatarUploadTriggerMobile">
            <img src="<?php echo $display_avatar; ?>" width="100%" height="100%" style="object-fit: cover;">
        </div>
        <h3 style="font-size: 22px; font-weight: 900;"><?php echo htmlspecialchars($fullname); ?></h3>
        <p style="color: var(--text-muted); font-weight: 700; font-size: 12px; text-transform: uppercase;">ID: <?php echo htmlspecialchars($student_id_val); ?></p>
        
        <div style="margin-top: 20px; background: var(--primary-glow); padding: 8px 20px; border-radius: 100px; display: inline-flex; align-items: center; gap: 8px; border: 1.5px solid rgba(0, 102, 255, 0.1);">
            <i data-lucide="award" style="width: 14px; height: 14px; color: var(--primary);"></i>
            <span style="font-size: 12px; font-weight: 900; color: var(--primary);"><?php echo $attendance_score; ?>% Reputation</span>
        </div>
    </div>

    <form id="profileFormMobile" class="profile-form-ajax" action="../includes/update_profile.php" method="POST" style="padding: 0 20px;">
        <input type="hidden" name="csrf_token" value="<?php echo AttendEaseSecurity::getCsrfToken(); ?>">
        
        <div class="section-title"><span>Identity</span></div>
        <div style="background: var(--surface); border-radius: 25px; padding: 20px; border: 1.5px solid var(--border); margin-bottom: 20px;">
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="font-size: 11px; font-weight: 800; color: var(--text-muted);">FULL NAME</label>
                <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($fullname); ?>" style="border-radius: 15px;">
            </div>
            <div class="form-group">
                <label style="font-size: 11px; font-weight: 800; color: var(--text-muted);">EMAIL ADDRESS</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" style="border-radius: 15px;">
            </div>
        </div>

        <div class="section-title"><span>Security</span></div>
        <div style="background: var(--surface); border-radius: 25px; padding: 20px; border: 1.5px solid var(--border); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h4 style="font-size: 14px; font-weight: 800; margin: 0;">Identity Lock</h4>
                <p style="font-size: 10px; color: var(--text-muted); margin: 2px 0 0;">Secure biometric access</p>
            </div>
            <input type="checkbox" name="bio_enabled" <?php echo $bio_enabled ? 'checked' : ''; ?> style="width: 20px; height: 20px; accent-color: var(--primary);">
        </div>

        <button type="submit" class="btn-primary" style="height: 60px; border-radius: 20px; font-weight: 900; margin-bottom: 15px;">Sync Identity</button>
        <a href="logout.php" class="btn-primary" style="background: rgba(239, 68, 68, 0.05); color: #ef4444; height: 60px; border-radius: 20px; font-weight: 900; display: flex; align-items: center; justify-content: center; text-decoration: none; border: 1.5px solid rgba(239, 68, 68, 0.1);">Sign Out</a>
    </form>
</div>

<div class="desktop-only-layout">
    <header class="desktop-header">
        <div class="header-breadcrumb">
            <span class="date-pill">IDENTITY HUB</span>
            <div class="clock-badge"><i data-lucide="shield-check"></i> SECURE ACCESS</div>
        </div>
        <h1 class="desktop-greeting">Account HQ</h1>
    </header>

    <div class="bento-grid">
        <!-- Identity Sidebar Card -->
        <div class="bento-card card-medium" style="text-align: center;">
            <div class="profile-avatar" style="width: 140px; height: 140px; margin: 0 auto 25px; border-radius: 40px; overflow: hidden; border: 4px solid rgba(255,255,255,0.2); cursor: pointer; transition: 0.3s;" id="avatarUploadTriggerDesktop">
                <img src="<?php echo $display_avatar; ?>" width="100%" height="100%" style="object-fit: cover;">
            </div>
            <h2 class="card-title" style="font-size: 24px;"><?php echo htmlspecialchars($fullname); ?></h2>
            <p style="color: #64748b; font-weight: 600; margin-top: 5px;"><?php echo htmlspecialchars($student_id_val); ?></p>
            
            <div style="margin-top: 30px; background: rgba(59, 130, 246, 0.05); padding: 15px; border-radius: 20px; border: 1px solid rgba(59, 130, 246, 0.1);">
                <span style="display: block; font-size: 11px; color: #3b82f6; font-weight: 800;">REPUTATION SCORE</span>
                <span style="font-size: 28px; font-weight: 900; color: #0f172a;"><?php echo $attendance_score; ?>%</span>
            </div>
        </div>

        <!-- Settings Main Form -->
        <div class="bento-card card-large">
            <h3 class="card-title" style="margin-bottom: 30px;">Personal Information</h3>
            <form id="profileFormDesktop" class="profile-form-ajax" action="../includes/update_profile.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo AttendEaseSecurity::getCsrfToken(); ?>">
                <input type="hidden" id="avatar_url" name="avatar_url" value="<?php echo htmlspecialchars($avatar_url); ?>">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                    <div class="form-group">
                        <label style="font-size: 11px; font-weight: 800; color: #64748b;">FULL LEGAL NAME</label>
                        <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($fullname); ?>" style="height: 55px; border-radius: 16px; background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.05);">
                    </div>
                    <div class="form-group">
                        <label style="font-size: 11px; font-weight: 800; color: #64748b;">ACADEMIC EMAIL</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" style="height: 55px; border-radius: 16px; background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.05);">
                    </div>
                </div>

                <div class="section-title"><span>Security Hub</span></div>
                <div style="background: rgba(0,0,0,0.02); border-radius: 20px; padding: 20px; margin-bottom: 30px; border: 1px solid rgba(0,0,0,0.05);">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h4 style="font-size: 15px; font-weight: 800; margin: 0;">Identity Lock</h4>
                            <p style="font-size: 11px; color: #64748b; margin: 2px 0 0;">Toggle device biometrics</p>
                        </div>
                        <input type="checkbox" name="bio_enabled" <?php echo $bio_enabled ? 'checked' : ''; ?> style="width: 24px; height: 24px; accent-color: #3b82f6;">
                    </div>
                </div>

                <button type="submit" class="btn-primary" style="height: 60px; border-radius: 20px; font-weight: 900; max-width: 300px;">Save Changes</button>
            </form>
        </div>

        <!-- Password Change Form -->
        <div class="bento-card card-large">
            <h3 class="card-title" style="margin-bottom: 30px;">Change Password</h3>
            <form id="passwordFormDesktop" class="profile-form-ajax" action="../includes/update_password.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo AttendEaseSecurity::getCsrfToken(); ?>">
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-size: 11px; font-weight: 800; color: #64748b;">CURRENT PASSWORD</label>
                    <input type="password" name="current_password" required class="form-control" style="height: 55px; border-radius: 16px; background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.05);" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                    <div class="form-group">
                        <label style="font-size: 11px; font-weight: 800; color: #64748b;">NEW PASSWORD</label>
                        <input type="password" name="new_password" required class="form-control" style="height: 55px; border-radius: 16px; background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.05);" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                    </div>
                    <div class="form-group">
                        <label style="font-size: 11px; font-weight: 800; color: #64748b;">CONFIRM NEW PASSWORD</label>
                        <input type="password" name="confirm_password" required class="form-control" style="height: 55px; border-radius: 16px; background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.05);" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                    </div>
                </div>

                <button type="submit" style="background: #0f172a; color: white; border: none; height: 60px; border-radius: 20px; font-weight: 900; padding: 0 40px; cursor: pointer;">Update Password</button>
            </form>
        </div>

        <!-- Session & Logout -->
        <div class="bento-card card-wide" style="display: flex; justify-content: space-between; align-items: center; border-color: rgba(239, 68, 68, 0.1);">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="width: 45px; height: 45px; background: rgba(239, 68, 68, 0.05); color: #ef4444; border-radius: 15px; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="log-out"></i>
                </div>
                <div>
                    <h4 style="font-size: 14px; font-weight: 800; margin: 0;">Active Session</h4>
                    <p style="font-size: 11px; color: #ef4444; font-weight: 600;">Secure logout will terminate all access.</p>
                </div>
            </div>
            <a href="logout.php" class="btn-primary" style="background: #ef4444; width: auto; padding: 0 30px; height: 50px; line-height: 50px;">Sign Out</a>
        </div>
    </div>
</div>

<script src="https://widget.cloudinary.com/v2.0/global/all.js" type="text/javascript"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // Ajax Form Handling
    const ajaxForms = document.querySelectorAll('.profile-form-ajax');
    ajaxForms.forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerText;
            submitBtn.disabled = true;
            submitBtn.innerText = "Syncing...";

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form)
                });
                const result = await response.json();
                if (result.success) {
                    AttendEase.notify('success', 'Profile Updated', 'Identity synchronized successfully.');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    AttendEase.notify('error', 'Error', result.message || 'Operation failed.');
                    submitBtn.disabled = false;
                    submitBtn.innerText = originalText;
                }
            } catch (err) {
                AttendEase.notify('error', 'Network Error', 'Check connection.');
                submitBtn.disabled = false;
                submitBtn.innerText = originalText;
            }
        });
    });

    // Cloudinary Widget Initialization
    const setupCloudinary = () => {
        if (!window.AttendEaseConfig) return;
        const myWidget = cloudinary.createUploadWidget({
            cloudName: window.AttendEaseConfig.cloudinary.cloudName,
            apiKey: window.AttendEaseConfig.cloudinary.apiKey,
            uploadSignature: (callback, params_to_sign) => {
                fetch('../includes/cloudinary_signature.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ params_to_sign: params_to_sign })
                }).then(r => r.json()).then(res => callback(res.signature));
            },
            sources: ['local', 'url', 'camera'],
            multiple: false
        }, (error, result) => {
            if (!error && result && result.event === "success") {
                document.getElementById('avatar_url').value = result.info.secure_url;
                AttendEase.notify('success', 'Upload Complete', 'New identity image attached.');
            }
        });

        document.getElementById('avatarUploadTriggerMobile').addEventListener('click', () => myWidget.open());
        document.getElementById('avatarUploadTriggerDesktop').addEventListener('click', () => myWidget.open());
    };
    
    setTimeout(setupCloudinary, 500);
});
</script>

<div style="height: 100px;"></div>
<?php include '../includes/footer.php'; ?>
