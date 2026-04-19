<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="bottom-nav">
    <a href="dashboard.php" class="nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
        <span>Home</span>
    </a>
    <a href="schedule.php" class="nav-item <?php echo $current_page == 'schedule.php' ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
        <span>Schedule</span>
    </a>
    
    <div class="nav-fab-container">
        <div class="nav-fab" onclick="openScanner()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 7V5a2 2 0 0 1 2-2h2"></path><path d="M17 3h2a2 2 0 0 1 2 2v2"></path>
                <path d="M21 17v2a2 2 0 0 1-2 2h-2"></path><path d="M7 21H5a2 2 0 0 1-2-2v-2"></path>
            </svg>
        </div>
    </div>

    <a href="reports.php" class="nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
        <span>Reports</span>
    </a>
    <a href="profile.php" class="nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        <span>Profile</span>
    </a>
</nav>

<!-- SCANNER OVERLAY (Shared across all authenticated pages) -->
<div id="scanner-overlay">
    <div style="padding: 50px 20px; display: flex; justify-content: space-between; color: white;">
        <h3>Scan QR Code</h3>
        <span onclick="closeScanner()" style="cursor: pointer; font-size: 24px;">&times;</span>
    </div>
    <div style="flex: 1; display: flex; align-items: center; justify-content: center;">
        <div class="scanner-frame"><div class="scanner-laser"></div></div>
    </div>
</div>

<style>
/* Ensure links in nav don't have default styles */
.bottom-nav a { text-decoration: none; }
</style>
