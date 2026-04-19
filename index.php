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
        <a href="student/login.php" class="btn-primary" style="text-align: center; text-decoration: none;">Get Started</a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
