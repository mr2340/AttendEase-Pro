<?php
$page_title = "Sign In";
include '../includes/header.php';
?>

<section id="auth" class="screen" data-state="active">
    <div class="intro-bg"></div>
    <div class="scrollable-content" style="z-index: 2; position: relative;">
        <div class="auth-container">
            <div class="auth-header">
                <h1>Welcome Back</h1>
                <p>Sign in to your student portal</p>
            </div>

            <form id="loginForm" action="../includes/login_process.php" method="POST">
                <div class="form-group">
                    <label>Student ID / Username</label>
                    <input type="text" name="identifier" class="form-control" placeholder="2024/CS/120" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                
                <div style="text-align: right; margin-bottom: 20px;">
                    <a href="#" style="font-size: 13px; color: var(--primary); font-weight: 600; text-decoration: none;">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-primary">Sign In</button>
            </form>
            
            <div style="text-align: center; margin-top: 30px;">
                <p style="font-size: 14px; color: var(--text-muted);">
                    Don't have an account? <a href="#" style="color: var(--primary); font-weight: 700; text-decoration: none;">Sign Up</a>
                </p>
            </div>

            <div style="margin-top: 40px; text-align: center;">
                 <button class="btn-primary" style="background: var(--surface); color: var(--text-dark); border: 1px solid var(--border); box-shadow: none;" onclick="alert('Biometric Login Coming Soon!')">
                    Use Fingerprint
                 </button>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
