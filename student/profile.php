<?php
$page_title = "Profile";
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
$email = $user['email'] ?? '';
$student_id = $user['student_id'] ?? 'Not set';
$role = $user['role'] ?? 'Student';
$bio_enabled = $user['bio_enabled'] ?? 0;
$dark_mode = $user['dark_mode'] ?? 0;
$avatar_url = $user['avatar_url'] ?? null;

// Default Avatar if none uploaded
$display_avatar = $avatar_url ? BASE_URL . $avatar_url : "https://api.dicebear.com/7.x/avataaars/svg?seed=" . htmlspecialchars($username);
?>

<section class="screen" data-state="active">
    <div class="scrollable-content">
        <form id="profileForm" action="../includes/update_profile.php" method="POST">
            <div style="padding: 40px 24px 20px; text-align: center;">
                <div class="profile-avatar" style="width: 120px; height: 120px; margin: 0 auto 20px; border-radius: 40px; border: 4px solid var(--surface); box-shadow: 0 10px 30px var(--primary-glow); position: relative; cursor: pointer;" id="avatarUploadTrigger">
                    <img id="avatarPreview" src="<?php echo $display_avatar; ?>" width="100%" height="100%" alt="Profile" style="border-radius: 36px; object-fit: cover;">
                    <div style="position: absolute; bottom: -5px; right: -5px; background: var(--primary); color: white; width: 32px; height: 32px; border-radius: 12px; display: flex; justify-content: center; align-items: center; border: 3px solid var(--surface);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                    </div>
                    <input type="hidden" id="avatar_url" name="avatar_url" value="<?php echo htmlspecialchars($avatar_url); ?>">
                </div>
                <h2 style="font-size: 26px; font-weight: 800; color: var(--text-dark);"><?php echo htmlspecialchars($username); ?></h2>
                <p style="color: var(--text-muted); font-weight: 600; margin-top: 4px;"><?php echo ucfirst($role); ?> • Computer Science</p>
            </div>
            
            <!-- ... remaining form fields same ... -->

            <div style="padding: 0 24px;">
                <!-- Personal Info -->
                <div class="section-title">
                    <span>Personal Information</span>
                </div>
                <div style="background: var(--surface); border-radius: 28px; padding: 20px; border: 1px solid var(--border); margin-bottom: 24px; box-shadow: 0 10px 20px rgba(0,0,0,0.02);">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                </div>

                <!-- Security & Preferences -->
                <div class="section-title">
                    <span>Preferences</span>
                </div>
                <div style="background: var(--surface); border-radius: 28px; padding: 10px; border: 1px solid var(--border); margin-bottom: 24px;">
                    <!-- Biometrics Toggle -->
                    <label style="padding: 14px 15px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--bg-main); cursor: pointer;">
                        <div style="display: flex; align-items: center;">
                            <div style="width: 36px; height: 36px; border-radius: 12px; background: var(--primary-glow); color: var(--primary); display: flex; justify-content: center; align-items: center; margin-right: 15px;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"/><path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0-6 0"/></svg>
                            </div>
                            <h4 style="font-size: 15px; font-weight: 600;">Enable Biometrics</h4>
                        </div>
                        <div class="toggle-switch">
                            <input type="checkbox" name="bio_enabled" value="1" <?php echo $bio_enabled ? 'checked' : ''; ?> style="display: none;">
                            <div class="switch-bg" style="width: 44px; height: 24px; background: <?php echo $bio_enabled ? 'var(--primary)' : '#e2e8f0'; ?>; border-radius: 20px; position: relative; padding: 2px; transition: 0.3s;">
                                <div class="switch-dot" style="width: 20px; height: 20px; background: white; border-radius: 50%; position: absolute; <?php echo $bio_enabled ? 'right: 2px;' : 'left: 2px;'; ?> transition: 0.3s;"></div>
                            </div>
                        </div>
                    </label>

                    <!-- Dark Mode -->
                    <label style="padding: 14px 15px; display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                        <div style="display: flex; align-items: center;">
                            <div style="width: 36px; height: 36px; border-radius: 12px; background: #f1f5f9; color: #64748b; display: flex; justify-content: center; align-items: center; margin-right: 15px;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                            </div>
                            <h4 style="font-size: 15px; font-weight: 600;">Dark Mode</h4>
                        </div>
                        <div class="toggle-switch">
                            <input type="checkbox" name="dark_mode" id="darkModeInput" value="1" <?php echo $dark_mode ? 'checked' : ''; ?> style="display: none;">
                            <div class="switch-bg" style="width: 44px; height: 24px; background: <?php echo $dark_mode ? 'var(--primary)' : '#e2e8f0'; ?>; border-radius: 20px; position: relative; padding: 2px; transition: 0.3s;">
                                <div class="switch-dot" style="width: 20px; height: 20px; background: white; border-radius: 50%; position: absolute; <?php echo $dark_mode ? 'right: 2px;' : 'left: 2px;'; ?> transition: 0.3s;"></div>
                            </div>
                        </div>
                    </label>
                </div>

                <button type="submit" class="btn-primary" style="margin-bottom: 20px;">Save Changes</button>

                <a href="logout.php" style="text-decoration: none;">
                    <div style="background: rgba(239, 68, 68, 0.05); border: 1.5px solid rgba(239, 68, 68, 0.1); border-radius: 24px; padding: 20px; display: flex; align-items: center; justify-content: center; color: var(--danger); font-weight: 700; margin-bottom: 40px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 10px;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                        Logout Account
                    </div>
                </a>
            </div>
        </form>

        <div style="height: 100px;"></div>
        <?php include '../includes/navbar.php'; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const profileForm = document.getElementById('profileForm');
    const avatarUploadTrigger = document.getElementById('avatarUploadTrigger');
    const avatarPreview = document.getElementById('avatarPreview');
    const avatarUrlInput = document.getElementById('avatar_url');

    // Cloudinary Widget Initialization (Secure Signed Upload)
    const myWidget = cloudinary.createUploadWidget({
        cloudName: window.AttendEaseConfig.cloudinary.cloudName || 'demo',
        apiKey: window.AttendEaseConfig.cloudinary.apiKey,
        uploadSignature: (callback, params_to_sign) => {
            fetch('../includes/cloudinary_signature.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ params_to_sign: params_to_sign })
            })
            .then(response => response.json())
            .then(result => {
                if (result.signature) {
                    callback(result.signature);
                } else {
                    console.error('Signature generation failed:', result.error);
                }
            })
            .catch(error => console.error('Error fetching signature:', error));
        },
        sources: ['local', 'url', 'camera'],
        multiple: false,
        cropping: true,
        croppingAspectRatio: 1,
        showSkipCropButton: false,
        clientAllowedFormats: ['png', 'jpg', 'jpeg', 'webp'],
        maxFileSize: 2000000,
        styles: {
            palette: {
                window: "#ffffff",
                sourceBg: "#f4f4f5",
                windowBorder: "#90a0b3",
                tabIcon: "#0066ff",
                inactiveTabIcon: "#6e7072",
                menuIcons: "#555a5f",
                link: "#0066ff",
                action: "#3399ff",
                inProgress: "#0078ff",
                complete: "#20b832",
                error: "#ea3535",
                textDark: "#000000",
                textLight: "#ffffff"
            }
        }
    }, (error, result) => { 
        if (!error && result && result.event === "success") { 
            console.log('Done! Here is the image info: ', result.info); 
            const secureUrl = result.info.secure_url;
            avatarPreview.src = secureUrl;
            avatarUrlInput.value = secureUrl;
        }
    });

    if (avatarUploadTrigger) {
        avatarUploadTrigger.addEventListener('click', () => {
            if (!window.AttendEaseConfig.cloudinary.cloudName) {
                alert("Cloudinary not configured. Please add CLOUDINARY_CLOUD_NAME to .env");
                return;
            }
            myWidget.open();
        }, false);
    }
    
    // Toggle UI Handling
    document.querySelectorAll('.toggle-switch input').forEach(input => {
        input.addEventListener('change', function() {
            const bg = this.nextElementSibling;
            const dot = bg.querySelector('.switch-dot');
            if (this.checked) {
                bg.style.background = 'var(--primary)';
                dot.style.left = 'auto';
                dot.style.right = '2px';
                if (this.id === 'darkModeInput') document.body.classList.add('dark-mode');
            } else {
                bg.style.background = '#e2e8f0';
                dot.style.right = 'auto';
                dot.style.left = '2px';
                if (this.id === 'darkModeInput') document.body.classList.remove('dark-mode');
            }
        });
    });

    profileForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(profileForm);
        const submitBtn = profileForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerText;

        submitBtn.disabled = true;
        submitBtn.innerText = "Saving...";

        try {
            const response = await fetch(profileForm.action, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                alert(result.message);
                location.reload();
            } else {
                alert(result.message);
                submitBtn.disabled = false;
                submitBtn.innerText = originalText;
            }
        } catch (error) {
            alert("Connection error");
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
