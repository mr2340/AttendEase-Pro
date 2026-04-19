<nav class="bottom-nav">
    <a href="dashboard.php" class="nav-item <?php echo ($page_title == 'Dashboard') ? 'active' : ''; ?>">
        <i data-lucide="home"></i>
        <span>Home</span>
    </a>
    <a href="schedule.php" class="nav-item <?php echo ($page_title == 'Schedule') ? 'active' : ''; ?>">
        <i data-lucide="calendar"></i>
        <span>Schedule</span>
    </a>
    
    <div class="nav-fab-container">
        <button class="nav-fab" onclick="App.startScanner()">
            <i data-lucide="qr-code" style="width: 28px; height: 28px;"></i>
        </button>
    </div>

    <a href="reports.php" class="nav-item <?php echo ($page_title == 'Reports') ? 'active' : ''; ?>">
        <i data-lucide="bar-chart-3"></i>
        <span>Reports</span>
    </a>
    <a href="profile.php" class="nav-item <?php echo ($page_title == 'Profile') ? 'active' : ''; ?>">
        <i data-lucide="user"></i>
        <span>Profile</span>
    </a>
</nav>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
