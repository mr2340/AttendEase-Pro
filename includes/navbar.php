<?php
$role = $_SESSION['role'] ?? 'student';
$base = BASE_URL;

// Define Navigation sets
if ($role === 'admin') {
    $nav_items = [
        ['label' => 'Home', 'icon' => 'home', 'url' => $base . 'admin/dashboard.php'],
        ['label' => 'Lecturers', 'icon' => 'users', 'url' => $base . 'admin/lecturers.php'],
        ['label' => 'Students', 'icon' => 'graduation-cap', 'url' => $base . 'admin/students.php'],
        ['label' => 'FAB', 'icon' => 'shield', 'url' => $base . 'admin/matrix.php'],
        ['label' => 'Courses', 'icon' => 'book-open', 'url' => $base . 'admin/courses.php'],
        ['label' => 'Profile', 'icon' => 'user', 'url' => $base . 'admin/profile.php']
    ];
} else if ($role === 'lecturer') {
    $nav_items = [
        ['label' => 'Home', 'icon' => 'home', 'url' => $base . 'lecturer/dashboard.php'],
        ['label' => 'Analytics', 'icon' => 'bar-chart-2', 'url' => $base . 'lecturer/reports.php'],
        ['label' => 'Logs', 'icon' => 'clipboard-list', 'url' => $base . 'lecturer/attendance_log.php'],
        ['label' => 'FAB', 'icon' => 'plus', 'url' => $base . 'lecturer/generate_qr.php'],
        ['label' => 'Courses', 'icon' => 'book-open', 'url' => $base . 'lecturer/courses.php'],
        ['label' => 'Profile', 'icon' => 'user', 'url' => $base . 'lecturer/profile.php']
    ];
} else if ($role === 'parent') {
    $nav_items = [
        ['label' => 'Home', 'icon' => 'home', 'url' => $base . 'parent/index.php'],
        ['label' => 'Alerts', 'icon' => 'bell', 'url' => '#'],
        ['label' => 'FAB', 'icon' => 'shield-check', 'url' => '#'],
        ['label' => 'History', 'icon' => 'clock', 'url' => '#'],
        ['label' => 'Logout', 'icon' => 'log-out', 'url' => $base . 'student/logout.php']
    ];
} else {
    $nav_items = [
        ['label' => 'Home', 'icon' => 'home', 'url' => $base . 'student/dashboard.php'],
        ['label' => 'Schedule', 'icon' => 'calendar', 'url' => $base . 'student/schedule.php'],
        ['label' => 'FAB', 'icon' => 'qr-code', 'url' => '#'],
        ['label' => 'History', 'icon' => 'bar-chart-3', 'url' => $base . 'student/reports.php'],
        ['label' => 'Profile', 'icon' => 'user', 'url' => $base . 'student/profile.php']
    ];
}
?>

<nav class="bottom-nav">
    <?php foreach($nav_items as $index => $item): ?>
        <?php if($item['label'] === 'FAB'): ?>
            <div class="nav-fab-container">
                <?php if($role === 'lecturer' || $role === 'admin'): ?>
                    <a href="<?php echo $item['url']; ?>" class="nav-fab" style="display: flex; text-decoration: none;">
                        <i data-lucide="<?php echo $item['icon']; ?>" style="color: #fff; width: 28px; height: 28px;"></i>
                    </a>
                <?php else: ?>
                    <button class="nav-fab" onclick="AttendEase.startScanner()">
                        <i data-lucide="<?php echo $item['icon']; ?>" style="color: #fff; width: 28px; height: 28px;"></i>
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <a href="<?php echo $item['url']; ?>" class="nav-item <?php echo ($page_title == $item['label']) ? 'active' : ''; ?>">
                <i data-lucide="<?php echo $item['icon']; ?>"></i>
                <span><?php echo $item['label']; ?></span>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
