<?php
$page_title = "Lecturer Dashboard";
include '../includes/header.php';

// Auth Check - Allow Lecturers and Admins
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'lecturer' && $_SESSION['role'] !== 'admin')) {
    header("Location: login");
    exit;
}

$db = get_db_connection();
$user_id = $_SESSION['user_id'];

// Fetch Courses managed by this lecturer
$stmt = $db->prepare("SELECT * FROM courses WHERE lecturer_id = ?");
$stmt->execute([$user_id]);
$courses = $stmt->fetchAll();

// Fetch Active Sessions
$stmt = $db->prepare("
    SELECT s.*, c.course_name 
    FROM sessions s 
    JOIN courses c ON s.course_id = c.id 
    WHERE s.lecturer_id = ? AND s.status = 'active'
");
$stmt->execute([$user_id]);
$active_sessions = $stmt->fetchAll();
?>

<section id="lecturer-dashboard" class="screen" data-state="active">
    <div class="scrollable-content">
        <div class="dash-header" style="margin-bottom: 30px;">
            <div class="greeting">
                <p style="font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 1.5px; font-size: 11px; margin-bottom: 5px;">Faculty Portal</p>
                <h1 style="font-size: 32px; font-weight: 900; letter-spacing: -1px; color: var(--text-dark);">
                    Welcome, <span style="color: var(--primary);">Dr. <?php echo explode('_', $_SESSION['username'])[1] ?? 'Lecturer'; ?></span>
                </h1>
            </div>
            <div class="profile-avatar" style="width: 60px; height: 60px; border-radius: 20px; border: 2px solid var(--surface); box-shadow: 0 10px 30px var(--primary-glow);">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Lecturer" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        </div>

        <!-- Quick Stats -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; padding: 0 24px; margin-bottom: 30px;">
            <div style="background: var(--surface); padding: 20px; border-radius: 24px; border: 1px solid var(--border);">
                <i data-lucide="book-open" style="color: var(--primary); margin-bottom: 10px;"></i>
                <h3 style="font-size: 24px; font-weight: 800;"><?php echo count($courses); ?></h3>
                <p style="font-size: 12px; color: var(--text-muted);">Active Courses</p>
            </div>
            <div style="background: var(--surface); padding: 20px; border-radius: 24px; border: 1px solid var(--border);">
                <i data-lucide="users" style="color: var(--success); margin-bottom: 10px;"></i>
                <h3 style="font-size: 24px; font-weight: 800;">124</h3>
                <p style="font-size: 12px; color: var(--text-muted);">Total Students</p>
            </div>
        </div>

        <!-- Active QR Sessions -->
        <div class="section-title">
            <span>Live Attendance</span>
        </div>
        
        <div style="padding: 0 24px; margin-bottom: 30px;">
            <?php if (empty($active_sessions)): ?>
                <div style="background: var(--primary-glow); padding: 25px; border-radius: 28px; text-align: center; border: 1px dashed var(--primary);">
                    <p style="color: var(--primary); font-weight: 600; margin-bottom: 15px;">No active attendance sessions.</p>
                    <button onclick="window.location.href='generate_qr'" class="btn-primary" style="padding: 12px 24px; font-size: 14px; width: auto;">
                        Start New Session
                    </button>
                </div>
            <?php else: ?>
                <?php foreach($active_sessions as $sess): ?>
                    <div style="background: var(--surface); padding: 20px; border-radius: 24px; border: 2px solid var(--primary); margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <h4 style="font-weight: 800;"><?php echo htmlspecialchars($sess['course_name']); ?></h4>
                            <p style="font-size: 12px; color: var(--text-muted);">Session ID: <?php echo $sess['id']; ?></p>
                        </div>
                        <a href="view_qr?id=<?php echo $sess['id']; ?>" style="background: var(--primary); color: white; padding: 10px; border-radius: 12px;">
                            <i data-lucide="qr-code"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Course List -->
        <div class="section-title">
            <span>Your Courses</span>
        </div>
        
        <div style="padding: 0 24px;">
            <?php foreach($courses as $course): ?>
                <div style="background: var(--surface); padding: 18px; border-radius: 24px; border: 1px solid var(--border); margin-bottom: 12px; display: flex; align-items: center; gap: 15px;">
                    <div style="width: 45px; height: 45px; background: var(--bg-main); border-radius: 12px; display: flex; justify-content: center; align-items: center; color: var(--primary);">
                        <i data-lucide="graduation-cap"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="font-size: 15px; font-weight: 700;"><?php echo htmlspecialchars($course['course_name']); ?></h4>
                        <p style="font-size: 12px; color: var(--text-muted);"><?php echo htmlspecialchars($course['course_code']); ?></p>
                    </div>
                    <a href="course_details.php?id=<?php echo $course['id']; ?>" style="color: var(--text-muted);">
                        <i data-lucide="chevron-right"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="height: 100px;"></div>
    </div>

    <!-- Specialized Lecturer Nav -->
    <nav class="bottom-nav">
        <a href="dashboard" class="nav-item active">
            <i data-lucide="layout-dashboard"></i>
            <span>Dashboard</span>
        </a>
        <a href="generate_qr" class="nav-item">
            <i data-lucide="plus-circle"></i>
            <span>New Class</span>
        </a>
        <a href="reports" class="nav-item">
            <i data-lucide="file-text"></i>
            <span>Reports</span>
        </a>
        <a href="profile" class="nav-item">
            <i data-lucide="user-cog"></i>
            <span>Settings</span>
        </a>
    </nav>
</section>

<?php include '../includes/footer.php'; ?>
