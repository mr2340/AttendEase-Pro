<aside class="sidebar">
    <div class="sidebar-logo">
        <h2 style="font-weight: 900; letter-spacing: -1.5px; font-size: 26px; color: var(--text-dark); display: flex; align-items: center; gap: 8px;">
            <div style="width: 35px; height: 35px; background: var(--primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white;">
                <i data-lucide="zap" style="width: 20px; height: 20px;"></i>
            </div>
            Attend<span style="color: var(--primary);">Ease</span>
        </h2>
    </div>

    <nav class="sidebar-nav">
        <a href="dashboard" class="sidebar-item <?php echo ($page_title == 'Dashboard') ? 'active' : ''; ?>">
            <i data-lucide="home"></i>
            <span>Home</span>
        </a>
        <a href="schedule" class="sidebar-item <?php echo ($page_title == 'Schedule') ? 'active' : ''; ?>">
            <i data-lucide="calendar"></i>
            <span>Schedule</span>
        </a>
        <a href="reports" class="sidebar-item <?php echo ($page_title == 'Reports') ? 'active' : ''; ?>">
            <i data-lucide="bar-chart-3"></i>
            <span>Reports</span>
        </a>
        <a href="profile" class="sidebar-item <?php echo ($page_title == 'Profile') ? 'active' : ''; ?>">
            <i data-lucide="user"></i>
            <span>Profile</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <button onclick="AttendEase.startScanner()" class="btn-primary" style="padding: 18px; border-radius: 20px; width: 100%; display: flex; align-items: center; justify-content: center; gap: 12px; font-size: 15px; border: none; cursor: pointer;">
            <i data-lucide="qr-code"></i>
            <span>Scan Attendance</span>
        </button>
        
        <div style="margin-top: 20px; padding: 15px; background: var(--bg-main); border-radius: 20px; display: flex; align-items: center; gap: 12px;">
            <div style="width: 40px; height: 40px; border-radius: 12px; background: #e2e8f0; overflow: hidden;">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo $_SESSION['username']; ?>" alt="avatar" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <div style="flex: 1; overflow: hidden;">
                <p style="font-weight: 700; font-size: 14px; color: var(--text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo $_SESSION['username']; ?></p>
                <p style="font-size: 11px; color: var(--text-muted);">Student ID: #<?php echo $_SESSION['user_id']; ?></p>
            </div>
            <a href="logout.php" style="color: var(--text-muted);"><i data-lucide="log-out" style="width: 18px; height: 18px;"></i></a>
        </div>
    </div>
</aside>
