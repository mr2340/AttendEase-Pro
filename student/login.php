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

            <form id="loginForm" action="<?php echo BASE_URL; ?>includes/login_process.php" method="POST">
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
                 <button class="btn-primary" style="background: var(--surface); color: var(--text-dark); border: 1px solid var(--border); box-shadow: none;" onclick="AttendEase.notify('info', 'Secure Auth', 'Biometric Login Coming Soon!')">
                    Use Fingerprint
                 </button>
            </div>
        </div>
    </div>
    <div id="login-feedback" style="margin-top: 20px; text-align: center; display: none; padding: 12px; border-radius: 12px; font-size: 14px; font-weight: 600;"></div>
    
    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const feedback = document.getElementById('login-feedback');
            const submitBtn = form.querySelector('button');
            const formData = new FormData(form);
            
            submitBtn.disabled = true;
            submitBtn.innerText = 'Verifying...';
            feedback.style.display = 'none';

            try {
                const response = await fetch(form.getAttribute('action'), {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    if (result.role === 'student' || result.role === 'admin') {
                        submitBtn.innerText = 'Access Granted...';
                        feedback.innerText = 'Welcome back! Redirecting to student dashboard...';
                        feedback.style.background = 'rgba(16, 185, 129, 0.1)';
                        feedback.style.color = '#10b981';
                        feedback.style.display = 'block';
                        
                        window.location.replace('dashboard');
                    } else {
                        AttendEase.notify('warning', 'Access Denied', 'Unauthorized: This portal is for students only.');
                        window.location.href = '../lecturer/dashboard';
                    }
                } else {
                    AttendEase.notify('error', 'Login Failed', result.message || 'Invalid credentials.');
                    submitBtn.disabled = false;
                    submitBtn.innerText = 'Sign In';
                }
            } catch (err) {
                console.error(err);
                AttendEase.notify('error', 'Fault', 'Authentication system error.');
                submitBtn.disabled = false;
                submitBtn.innerText = 'Sign In';
            }
        });
    </script>
</section>

<?php include '../includes/footer.php'; ?>
