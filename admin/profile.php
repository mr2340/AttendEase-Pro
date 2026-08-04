<?php
require_once '../includes/config.php';

$page_title = "Admin Profile";
include '../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../lecturer/login");
    exit;
}

$db = get_db_connection();
$user_id = $_SESSION['user_id'];
$user_stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

$username = $user['username'] ?? '';
$fullname = $user['fullname'] ?? 'Admin';
$email = $user['email'] ?? '';
$bio_enabled = $user['bio_enabled'] ?? 0;
$display_avatar = "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($username);
?>

<div class="mobile-only-layout">
    <div style="height: 30px;"></div>
    <div style="padding: 20px; text-align: center;">
        <div style="width: 120px; height: 120px; margin: 0 auto 20px; border-radius: 45px; overflow: hidden; border: 4px solid var(--surface); box-shadow: 0 10px 25px rgba(239, 68, 68, 0.2);">
            <img src="<?php echo $display_avatar; ?>" width="100%" height="100%" style="object-fit: cover;">
        </div>
        <h3 style="font-size: 22px; font-weight: 900;"><?php echo htmlspecialchars($fullname); ?></h3>
        <p style="color: #ef4444; font-weight: 800; font-size: 12px; text-transform: uppercase;">System Administrator</p>
    </div>

    <form class="profile-form-ajax" action="../includes/update_profile.php" method="POST" style="padding: 0 20px;">
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

        <button type="submit" class="btn-primary" style="background: #ef4444; height: 60px; border-radius: 20px; font-weight: 900; margin-bottom: 15px; width: 100%; border: none; color: white;">Sync Identity</button>
        <a href="../logout.php" class="btn-primary" style="background: rgba(239, 68, 68, 0.05); color: #ef4444; height: 60px; border-radius: 20px; font-weight: 900; display: flex; align-items: center; justify-content: center; text-decoration: none; border: 1.5px solid rgba(239, 68, 68, 0.1);">Sign Out</a>
    </form>
</div>

<div class="desktop-only-layout" style="background: #f1f5f9; min-height: 100vh;">
    <header style="padding: 60px 80px 40px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
            <div style="background: #ef4444; width: 12px; height: 12px; border-radius: 4px;"></div>
            <span style="color: #64748b; font-size: 13px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase;">Identity Hub</span>
        </div>
        <h1 style="font-size: 56px; font-weight: 950; color: #0f172a; letter-spacing: -2.5px;">Account <span style="color: #ef4444;">HQ</span></h1>
    </header>

    <div style="padding: 0 80px 80px; display: grid; grid-template-columns: 4fr 8fr; gap: 40px; align-items: start;">
        <div style="background: white; border-radius: 50px; padding: 50px; text-align: center; border: 1.5px solid #f1f5f9; box-shadow: 0 25px 60px rgba(0,0,0,0.03);">
            <div style="width: 140px; height: 140px; margin: 0 auto 25px; border-radius: 40px; overflow: hidden; border: 4px solid rgba(239, 68, 68, 0.1);">
                <img src="<?php echo $display_avatar; ?>" width="100%" height="100%" style="object-fit: cover;">
            </div>
            <h2 style="font-size: 24px; font-weight: 900; color: #0f172a;"><?php echo htmlspecialchars($fullname); ?></h2>
            <p style="color: #ef4444; font-weight: 800; margin-top: 5px; font-size: 14px;">SYSTEM ADMINISTRATOR</p>
        </div>

        <div style="background: white; border-radius: 50px; padding: 50px; border: 1.5px solid #f1f5f9; box-shadow: 0 25px 60px rgba(0,0,0,0.03);">
            <h3 style="font-size: 26px; font-weight: 950; color: #0f172a; letter-spacing: -1px; margin-bottom: 30px;">Personal Information</h3>
            
            <form class="profile-form-ajax" action="../includes/update_profile.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo AttendEaseSecurity::getCsrfToken(); ?>">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label style="font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase;">Username</label>
                        <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" style="border-radius: 15px; padding: 15px 20px; background: #f8fafc; border: 2px solid #e2e8f0; width: 100%; box-sizing: border-box; font-weight: 600; color: #0f172a;">
                    </div>
                    <div class="form-group">
                        <label style="font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase;">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" style="border-radius: 15px; padding: 15px 20px; background: #f8fafc; border: 2px solid #e2e8f0; width: 100%; box-sizing: border-box; font-weight: 600; color: #0f172a;">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 40px;">
                    <label style="font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase;">Full Name</label>
                    <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($fullname); ?>" style="border-radius: 15px; padding: 15px 20px; background: #f8fafc; border: 2px solid #e2e8f0; width: 100%; box-sizing: border-box; font-weight: 600; color: #0f172a;">
                </div>

                <div style="display: flex; gap: 20px;">
                    <button type="submit" style="background: #ef4444; color: white; padding: 18px 40px; border-radius: 100px; font-weight: 800; font-size: 15px; border: none; cursor: pointer; box-shadow: 0 10px 25px rgba(239, 68, 68, 0.3);">Save Changes</button>
                    <a href="../logout.php" style="background: rgba(239, 68, 68, 0.05); color: #ef4444; padding: 18px 40px; border-radius: 100px; font-weight: 800; font-size: 15px; text-decoration: none; border: 2px solid rgba(239, 68, 68, 0.1);">Sign Out</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.profile-form-ajax').forEach(form => {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerText;
        btn.innerHTML = '<i data-lucide="loader-2" class="spin"></i> Syncing...';
        lucide.createIcons();
        
        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            Swal.fire({
                title: result.status === 'success' ? 'Synced!' : 'Error',
                text: result.message,
                icon: result.status,
                confirmButtonColor: '#ef4444',
                customClass: {
                    popup: 'aura-popup'
                }
            });
        } catch (error) {
            Swal.fire('Error', 'Network communication failed.', 'error');
        } finally {
            btn.innerText = originalText;
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
