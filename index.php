<?php
$page_title = "Welcome";
include 'includes/header.php';

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>

<!-- ================= WELCOME / ONBOARDING ================= -->
<section id="intro-1" class="screen" data-state="active">
    <div class="intro-bg"></div>
    <div class="welcome-content">
        <div class="illustration">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 22h14a2 2 0 0 0 2-2V7.5L14.5 2H6a2 2 0 0 0-2 2v4"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <path d="M2 15h10"></path>
                <path d="M5 12l-3 3 3 3"></path>
            </svg>
        </div>
        <div class="text-group">
            <h1>Say Goodbye to Paper</h1>
            <p>AttendEase brings attendance tracking to the 21st century. Fast, secure, and fully digital.</p>
        </div>
    </div>
    <div class="welcome-footer">
        <div class="pagination">
            <div class="dot active"></div><div class="dot"></div><div class="dot"></div>
        </div>
        <button class="btn-primary" onclick="navTo('intro-2')">Continue</button>
    </div>
</section>

<section id="intro-2" class="screen" data-state="next">
    <div class="intro-bg" style="filter: hue-rotate(45deg);"></div>
    <div class="welcome-content">
        <div class="illustration">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
                <path d="M9 3v18"></path><path d="M3 9h18"></path>
            </svg>
        </div>
        <div class="text-group">
            <h1>Lightning Fast Scanning</h1>
            <p>Just point your camera at the lecturer's QR code and you're marked present in milliseconds.</p>
        </div>
    </div>
    <div class="welcome-footer">
        <div class="pagination">
            <div class="dot"></div><div class="dot active"></div><div class="dot"></div>
        </div>
        <button class="btn-primary" onclick="navTo('intro-3')">Next</button>
    </div>
</section>

<section id="intro-3" class="screen" data-state="next">
    <div class="intro-bg" style="filter: hue-rotate(90deg);"></div>
    <div class="welcome-content">
        <div class="illustration">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 3v18h18"></path>
                <path d="M18 17V9"></path>
                <path d="M13 17V5"></path>
                <path d="M8 17v-3"></path>
            </svg>
        </div>
        <div class="text-group">
            <h1>Track Your Progress</h1>
            <p>Monitor your attendance scores in real-time and never fall below the required threshold.</p>
        </div>
    </div>
    <div class="welcome-footer">
            <div class="pagination">
            <div class="dot"></div><div class="dot"></div><div class="dot active"></div>
        </div>
        <div style="width: 100%; display: flex; flex-direction: column; gap: 12px;">
            <a href="student/login.php" class="btn-primary" style="text-align: center; text-decoration: none; background: linear-gradient(135deg, var(--primary), var(--secondary)); display: flex; align-items: center; justify-content: center; gap: 10px;">
                <i data-lucide="graduation-cap" style="width: 20px;"></i> Continue as Student
            </a>
            <a href="lecturer/login.php" class="btn-role-secondary" style="text-align: center; text-decoration: none; background: white; color: var(--text-dark); border: 2px solid var(--border); padding: 18px; border-radius: 20px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
                <i data-lucide="briefcase" style="width: 20px;"></i> Continue as Lecturer
            </a>
        </div>
    </div>
</section>

<style>
.btn-role-secondary:hover {
    background: #f8fafc !important;
    border-color: var(--primary) !important;
    color: var(--primary) !important;
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(0, 102, 255, 0.05);
}
.btn-role-secondary:active {
    transform: translateY(0);
}
</style>

<script>
function navTo(targetId) {
    document.querySelectorAll('.screen').forEach(screen => {
        if (screen.id === targetId) {
            screen.setAttribute('data-state', 'active');
        } else if (screen.getAttribute('data-state') === 'active') {
            screen.setAttribute('data-state', 'prev');
        } else {
            screen.setAttribute('data-state', 'next');
        }
    });
}
// Double check icons are created
if (window.lucide) {
    lucide.createIcons();
}
</script>

<?php include 'includes/footer.php'; ?>
