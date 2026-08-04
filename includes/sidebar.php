<?php
$is_lecturer = (isset($_SESSION['role']) && $_SESSION['role'] === 'lecturer');
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
?>
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
        <a href="<?php echo BASE_URL . ($_SESSION['role'] ?? 'student'); ?>/dashboard" class="sidebar-item <?php echo ($page_title == 'Home' || $page_title == 'Dashboard') ? 'active' : ''; ?>">
            <i data-lucide="home"></i>
            <span>Home</span>
        </a>
        
        <?php if ($is_lecturer): ?>
            <a href="<?php echo BASE_URL; ?>lecturer/courses" class="sidebar-item <?php echo ($page_title == 'My Portfolio' || $page_title == 'Courses') ? 'active' : ''; ?>">
                <i data-lucide="book-open"></i>
                <span>My Portfolio</span>
            </a>
            <a href="<?php echo BASE_URL; ?>lecturer/reports" class="sidebar-item <?php echo ($page_title == 'Analytics' || $page_title == 'Intelligence' || $page_title == 'Reports') ? 'active' : ''; ?>">
                <i data-lucide="bar-chart-3"></i>
                <span>Intelligence</span>
            </a>
            <a href="<?php echo BASE_URL; ?>lecturer/student_roster" class="sidebar-item <?php echo ($page_title == 'Student Roster') ? 'active' : ''; ?>">
                <i data-lucide="users"></i>
                <span>Student Roster</span>
            </a>
        <?php endif; ?>

        <?php if ($is_admin): ?>
            <a href="<?php echo BASE_URL; ?>admin/matrix" class="sidebar-item <?php echo ($page_title == 'Admin Matrix' || $page_title == 'Infrastructure') ? 'active' : ''; ?>">
                <i data-lucide="shield-check" style="color: #ef4444;"></i>
                <span style="color: #ef4444; font-weight: 800;">Admin Matrix</span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/lecturers" class="sidebar-item <?php echo ($page_title == 'Lecturers' || $page_title == 'Faculty Management') ? 'active' : ''; ?>">
                <i data-lucide="users-2" style="color: #3b82f6;"></i>
                <span style="font-weight: 700;">Faculty Management</span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/courses" class="sidebar-item <?php echo ($page_title == 'Courses' || $page_title == 'Course Matrix') ? 'active' : ''; ?>">
                <i data-lucide="layout-grid" style="color: #10b981;"></i>
                <span style="font-weight: 700;">Course Matrix</span>
            </a>
        <?php endif; ?>

        <?php if (!$is_lecturer && !$is_admin): ?>
            <a href="<?php echo BASE_URL; ?>student/schedule" class="sidebar-item <?php echo ($page_title == 'Schedule') ? 'active' : ''; ?>">
                <i data-lucide="calendar"></i>
                <span>Schedule</span>
            </a>
            <a href="<?php echo BASE_URL; ?>student/reports" class="sidebar-item <?php echo ($page_title == 'Reports' || $page_title == 'History') ? 'active' : ''; ?>">
                <i data-lucide="bar-chart-3"></i>
                <span>History</span>
            </a>
        <?php endif; ?>

        <a href="<?php echo BASE_URL . ($_SESSION['role'] ?? 'student'); ?>/profile" class="sidebar-item <?php echo ($page_title == 'Profile' || $page_title == 'Settings') ? 'active' : ''; ?>">
            <i data-lucide="user"></i>
            <span>Profile</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <?php if ($is_lecturer): ?>
            <a href="<?php echo BASE_URL; ?>lecturer/generate_qr" class="btn-primary" style="padding: 18px; border-radius: 20px; width: 100%; display: flex; align-items: center; justify-content: center; gap: 12px; font-size: 15px; border: none; cursor: pointer; text-decoration: none; color: white;">
                <i data-lucide="qr-code"></i>
                <span>Deploy Pulse Node</span>
            </a>
        <?php elseif (!$is_admin): ?>
            <button onclick="AttendEase.startScanner()" class="btn-primary" style="padding: 18px; border-radius: 20px; width: 100%; display: flex; align-items: center; justify-content: center; gap: 12px; font-size: 15px; border: none; cursor: pointer;">
                <i data-lucide="scan"></i>
                <span>Scan Attendance</span>
            </button>
        <?php endif; ?>
        
        <div style="margin-top: 20px; padding: 15px; background: var(--bg-main); border-radius: 20px; display: flex; align-items: center; gap: 12px;">
            <div style="width: 40px; height: 40px; border-radius: 12px; background: #e2e8f0; overflow: hidden;">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo $_SESSION['username'] ?? 'user'; ?>" alt="avatar" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <div style="flex: 1; overflow: hidden;">
                <p style="font-weight: 700; font-size: 14px; color: var(--text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username'] ?? 'User'); ?></p>
                <p style="font-size: 11px; color: var(--text-muted);"><?php echo $is_lecturer ? 'Lecturer ID' : ($is_admin ? 'Admin ID' : 'Student ID'); ?>: #<?php echo $_SESSION['user_id'] ?? '??'; ?></p>
            </div>
            <a href="<?php echo BASE_URL; ?>logout.php" style="color: var(--text-muted);"><i data-lucide="log-out" style="width: 18px; height: 18px;"></i></a>
        </div>
    </div>
</aside>
