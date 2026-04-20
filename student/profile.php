<?php
require_once '../includes/config.php';
require_once '../includes/stat_engine.php';

$page_title = "Identity Hub";
include '../includes/header.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$db = get_db_connection();
$user_id = $_SESSION['user_id'];

// Fetch detailed user info
$user_stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

$username = $user['username'] ?? '';
$fullname = $user['fullname'] ?? 'Student';
$email = $user['email'] ?? '';
$student_id_val = $user['student_id'] ?? 'N/A';
$role = $user['role'] ?? 'Student';
$bio_enabled = $user['bio_enabled'] ?? 0;
$dark_mode = $user['dark_mode'] ?? 0;
$avatar_url = $user['avatar_url'] ?? null;

// Stats for Profile
$attendance_score = StatEngine::getStudentAttendanceScore($user_id);
$is_verified = StatEngine::getIdentityVerificationStatus($user_id);

// Default Avatar
$display_avatar = $avatar_url ? BASE_URL . $avatar_url : "https://api.dicebear.com/7.x/avataaars/svg?seed=" . htmlspecialchars($username);
?>

<section class="screen" data-state="active">
    <div class="scrollable-content">
        <div style="padding: 40px 24px 20px; text-align: center; position: relative;">
            <!-- Dynamic Identity Avatar -->
            <div class="profile-avatar-wrapper" style="position: relative; width: 140px; height: 140px; margin: 0 auto 25px;">
                <div class="avatar-glow" style="position: absolute; inset: -10px; background: var(--primary); filter: blur(30px); opacity: 0.15; border-radius: 50%;"></div>
                <div class="profile-avatar" style="width: 140px; height: 140px; border-radius: 48px; border: 4px solid var(--surface); box-shadow: 0 15px 35px var(--primary-glow); position: relative; overflow: hidden; cursor: pointer; z-index: 1;" id="avatarUploadTrigger">
                    <img id="avatarPreview" src="<?php echo $display_avatar; ?>" width="100%" height="100%" alt="Profile" style="object-fit: cover;">
                    <div style="position: absolute; inset: 0; background: linear-gradient(180deg, transparent 60%, rgba(0,0,0,0.4) 100%);"></div>
                    <div style="position: absolute; bottom: 8px; right: 8px; background: var(--primary); color: white; width: 34px; height: 34px; border-radius: 14px; display: flex; justify-content: center; align-items: center; border: 3px solid var(--surface); box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                    </div>
                </div>
                
                <?php if($is_verified): ?>
                <!-- Verified Badge -->
                <div style="position: absolute; top: -10px; left: -10px; z-index: 2; background: var(--success); color: white; padding: 6px 14px; border-radius: 100px; font-size: 10px; font-weight: 900; letter-spacing: 0.5px; border: 3px solid var(--surface); box-shadow: 0 5px 15px rgba(16, 185, 129, 0.2); display: flex; align-items: center; gap: 5px; animation: badgePulse 2s infinite;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    VERIFIED
                </div>
                <?php endif; ?>
            </div>

            <h2 style="font-size: 28px; font-weight: 900; color: var(--text-dark); letter-spacing: -1px;"><?php echo htmlspecialchars($fullname); ?></h2>
            <p style="color: var(--text-muted); font-weight: 700; font-size: 13px; margin-top: 5px; text-transform: uppercase; letter-spacing: 1px;">ID: <?php echo htmlspecialchars($student_id_val); ?> • CS Major</p>
        </div>

        <!-- Identity Quick Stats -->
        <div style="padding: 0 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px;">
            <div style="background: var(--surface); padding: 20px; border-radius: 32px; border: 1.5px solid var(--border); text-align: center;">
                <p style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Reputation</p>
                <h3 style="font-size: 24px; font-weight: 900; color: var(--primary); margin-top: 5px;"><?php echo $attendance_score; ?>%</h3>
            </div>
            <div style="background: var(--surface); padding: 20px; border-radius: 32px; border: 1.5px solid var(--border); text-align: center; cursor: pointer;" id="digitalIdTrigger">
                <p style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Digital ID</p>
                <div style="margin-top: 5px; color: var(--text-dark);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><path d="M7 7h.01"/><path d="M17 7h.01"/><path d="M7 17h.01"/><path d="M17 17h.01"/><path d="M12 12h.01"/></svg>
                </div>
            </div>
        </div>

        <!-- Overlay Digital ID (Hidden initially) -->
        <div id="digitalIdOverlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(20px); z-index: 1000; justify-content: center; align-items: center; padding: 30px;">
            <div style="background: white; border-radius: 45px; padding: 40px; width: 100%; max-width: 350px; text-align: center; position: relative;">
                <button id="closeDigitalId" style="position: absolute; top: 20px; right: 20px; background: var(--bg-main); border: none; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; cursor: pointer;">&times;</button>
                <div style="width: 80px; height: 80px; border-radius: 24px; overflow: hidden; margin: 0 auto 20px; border: 3px solid var(--primary);">
                    <img src="<?php echo $display_avatar; ?>" width="100%" height="100%" style="object-fit: cover;">
                </div>
                <h3 style="font-size: 22px; font-weight: 900; color: #000;"><?php echo htmlspecialchars($fullname); ?></h3>
                <p style="font-size: 12px; color: #666; font-weight: 600; margin-bottom: 25px;"><?php echo htmlspecialchars($student_id_val); ?></p>
                
                <div id="idQrCode" style="background: white; padding: 15px; border-radius: 20px; border: 2px solid #f0f0f0; margin-bottom: 25px; display: flex; justify-content: center;"></div>
                
                <p style="font-size: 11px; color: #888; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Signed Identity Token</p>
                <p style="font-size: 10px; color: var(--success); font-weight: 800; margin-top: 5px;">GENUINE SECURE SCAN</p>
            </div>
        </div>

        <form id="profileForm" action="../includes/update_profile.php" method="POST" style="padding: 0 24px;">
            <input type="hidden" name="csrf_token" value="<?php echo AttendEaseSecurity::getCsrfToken(); ?>">
            
            <div class="section-title">
                <span>Personal Identity</span>
            </div>
            <div style="background: var(--surface); border-radius: 32px; padding: 25px; border: 1.5px solid var(--border); margin-bottom: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-size: 12px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Full Name</label>
                    <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($fullname); ?>" style="background: var(--bg-main); border: 1.5px solid var(--border); border-radius: 18px; padding: 14px 18px; font-weight: 700;">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label style="font-size: 12px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Academic Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required style="background: var(--bg-main); border: 1.5px solid var(--border); border-radius: 18px; padding: 14px 18px; font-weight: 700;">
                </div>
            </div>

            <div class="section-title">
                <span>Security Hub</span>
            </div>
            <div style="background: var(--surface); border-radius: 32px; border: 1.5px solid var(--border); margin-bottom: 24px; overflow: hidden;">
                <!-- Biometrics -->
                <label style="padding: 20px 25px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1.5px solid var(--bg-main); cursor: pointer;">
                    <div style="display: flex; align-items: center;">
                        <div style="width: 44px; height: 44px; border-radius: 16px; background: var(--primary-glow); color: var(--primary); display: flex; justify-content: center; align-items: center; margin-right: 18px;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"/><path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0-6 0"/></svg>
                        </div>
                        <div>
                            <h4 style="font-size: 15px; font-weight: 800;">Identity Lock</h4>
                            <p style="font-size: 11px; color: var(--text-muted); font-weight: 600;">Use device biometrics</p>
                        </div>
                    </div>
                    <div class="toggle-switch">
                        <input type="checkbox" name="bio_enabled" value="1" <?php echo $bio_enabled ? 'checked' : ''; ?> style="display: none;">
                        <div class="switch-bg" style="width: 48px; height: 26px; background: <?php echo $bio_enabled ? 'var(--primary)' : '#e2e8f0'; ?>; border-radius: 20px; position: relative; padding: 3px; transition: 0.3s;">
                            <div class="switch-dot" style="width: 20px; height: 20px; background: white; border-radius: 50%; position: absolute; <?php echo $bio_enabled ? 'right: 3px;' : 'left: 3px;'; ?> transition: 0.3s;"></div>
                        </div>
                    </div>
                </label>

                <!-- Active Session (Simulated) -->
                <div style="padding: 20px 25px; display: flex; align-items: center; gap: 18px;">
                    <div style="width: 44px; height: 44px; border-radius: 16px; background: var(--success-glow); color: var(--success); display: flex; justify-content: center; align-items: center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="font-size: 14px; font-weight: 800;">Current Session</h4>
                        <p style="font-size: 11px; color: var(--success); font-weight: 800; text-transform: uppercase;">Active Now • Lagos, NG</p>
                    </div>
                    <div style="font-size: 10px; font-weight: 900; color: var(--primary);">ACTIVE</div>
                </div>
            </div>

            <input type="hidden" id="avatar_url" name="avatar_url" value="<?php echo htmlspecialchars($avatar_url); ?>">
            <button type="submit" class="btn-primary" style="margin-bottom: 20px; height: 65px; border-radius: 24px; font-weight: 900; font-size: 16px; box-shadow: 0 15px 30px var(--primary-glow);">Synchronize Identity</button>

            <a href="logout.php" style="text-decoration: none;">
                <div style="background: rgba(239, 68, 68, 0.05); border: 1.5px solid rgba(239, 68, 68, 0.1); border-radius: 24px; padding: 22px; display: flex; align-items: center; justify-content: center; color: var(--danger); font-weight: 900; margin-bottom: 40px; font-size: 15px;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="margin-right: 12px;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    Terminate Session
                </div>
            </a>
        </form>

        <div style="height: 100px;"></div>
        <?php include '../includes/navbar.php'; ?>
    </div>
</section>

<script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const digitalIdTrigger = document.getElementById('digitalIdTrigger');
    const digitalIdOverlay = document.getElementById('digitalIdOverlay');
    const closeDigitalId = document.getElementById('closeDigitalId');
    const idQrContainer = document.getElementById('idQrCode');

    // Generate Identity QR
    const userId = "<?php echo $user_id; ?>";
    const studentCode = "<?php echo $student_id_val; ?>";
    const qrPayload = JSON.stringify({
        type: 'identity_verification',
        id: userId,
        code: studentCode,
        ts: Date.now()
    });

    new QRCode(idQrContainer, {
        text: qrPayload,
        width: 200,
        height: 200,
        colorDark : "#000000",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.H
    });

    digitalIdTrigger.addEventListener('click', () => {
        digitalIdOverlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    });

    closeDigitalId.addEventListener('click', () => {
        digitalIdOverlay.style.display = 'none';
        document.body.style.overflow = 'auto';
    });

    // Profile Form Handling
    const profileForm = document.getElementById('profileForm');
    const avatarUploadTrigger = document.getElementById('avatarUploadTrigger');
    const avatarPreview = document.getElementById('avatarPreview');
    const avatarUrlInput = document.getElementById('avatar_url');

    // Cloudinary Widget Initialization
    const myWidget = cloudinary.createUploadWidget({
        cloudName: window.AttendEaseConfig.cloudinary.cloudName,
        apiKey: window.AttendEaseConfig.cloudinary.apiKey,
        uploadSignature: (callback, params_to_sign) => {
            fetch('../includes/cloudinary_signature.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ params_to_sign: params_to_sign })
            })
            .then(response => response.json())
            .then(result => callback(result.signature))
            .catch(error => console.error('Error fetching signature:', error));
        },
        sources: ['local', 'url', 'camera'],
        multiple: false,
        cropping: true,
        croppingAspectRatio: 1,
        showSkipCropButton: false,
        clientAllowedFormats: ['png', 'jpg', 'jpeg', 'webp'],
        maxFileSize: 2000000
    }, (error, result) => { 
        if (!error && result && result.event === "success") { 
            const secureUrl = result.info.secure_url;
            avatarPreview.src = secureUrl;
            avatarUrlInput.value = secureUrl;
        }
    });

    if (avatarUploadTrigger) {
        avatarUploadTrigger.addEventListener('click', () => myWidget.open(), false);
    }

    profileForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(profileForm);
        const submitBtn = profileForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerText;

        submitBtn.disabled = true;
        submitBtn.innerText = "Synchronizing...";

        try {
            const response = await fetch(profileForm.action, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                await AttendEase.notify('success', 'Identity Synchronized', result.message);
                location.reload();
            } else {
                AttendEase.notify('error', 'Sync Failed', result.message);
                submitBtn.disabled = false;
                submitBtn.innerText = originalText;
            }
        } catch (error) {
            AttendEase.notify('error', 'Network Error', 'Identity hub disconnected.');
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
        }
    });
});
</script>

<style>
@keyframes badgePulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.05); opacity: 0.9; }
    100% { transform: scale(1); opacity: 1; }
}
.profile-avatar-wrapper:hover .avatar-glow {
    opacity: 0.3;
    filter: blur(40px);
}
</style>

<?php include '../includes/footer.php'; ?>
