<?php
$page_title = "Lecturer Login";
require_once '../includes/config.php';
session_start();

// Redirect if already logged in as lecturer
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'lecturer') {
    header("Location: dashboard");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Lecturer Access | AttendEase Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script>
        window.AttendEaseConfig = {
            baseUrl: '<?php echo BASE_URL; ?>',
            csrfToken: '<?php echo AttendEaseSecurity::getCsrfToken(); ?>'
        };
    </script>
</head>
<body class="dark-mode">
    <div id="app-container" style="display: flex; justify-content: center; align-items: center; background: radial-gradient(circle at top right, #1e293b, #0f172a);">
        <div class="auth-container" style="max-width: 400px; width: 90%;">
            <div style="text-align: center; margin-bottom: 40px;">
                <div style="width: 70px; height: 70px; background: var(--primary); border-radius: 22px; display: flex; justify-content: center; align-items: center; margin: 0 auto 20px; box-shadow: 0 15px 30px var(--primary-glow);">
                    <i data-lucide="graduation-cap" style="width: 35px; height: 35px; color: white;"></i>
                </div>
                <h1 style="font-size: 28px; font-weight: 800; color: white; letter-spacing: -0.5px;">Faculty Portal</h1>
                <p style="color: #94a3b8; font-size: 14px; margin-top: 5px;">Manage your classes and attendance</p>
            </div>

            <form id="loginForm" action="<?php echo BASE_URL; ?>includes/login_process.php" method="POST">
                <div class="form-group">
                    <label style="color: #cbd5e1;">Username / ID</label>
                    <input type="text" name="identifier" class="form-control" placeholder="dr_smith" required style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1);">
                </div>
                
                <div class="form-group" style="margin-top: 20px;">
                    <label style="color: #cbd5e1;">Security Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1);">
                </div>

                <button type="submit" class="btn-primary" style="margin-top: 40px; height: 55px; font-size: 16px;">
                    Authorize Access
                </button>
            </form>
            
            <div id="login-feedback" style="margin-top: 20px; text-align: center; display: none; padding: 12px; border-radius: 12px; font-size: 14px; font-weight: 600;"></div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="../student/login" style="font-size: 14px; color: var(--primary); font-weight: 600; text-decoration: none;">Student Login?</a>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        if (window.lucide) {
            lucide.createIcons();
        }
    </script>
</body>
</html>
