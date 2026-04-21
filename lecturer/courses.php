<?php
require_once '../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth Check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'lecturer' && $_SESSION['role'] !== 'admin')) {
    header("Location: login");
    exit;
}

$page_title = "Courses";
include '../includes/header.php';

$db = get_db_connection();
$user_id = $_SESSION['user_id'];

// Fetch Courses with student counts and last session info
$stmt = $db->prepare("
    SELECT c.*, 
    (SELECT COUNT(DISTINCT student_id) FROM enrollments WHERE course_id = c.id) as student_count,
    (SELECT MAX(created_at) FROM sessions WHERE course_id = c.id) as last_session
    FROM courses c 
    WHERE c.lecturer_id = ?
");
$stmt->execute([$user_id]);
$courses = $stmt->fetchAll();
?>

<section id="courses-catalog" class="screen" data-state="active" style="background: #f8fafc;">
    <div class="scrollable-content">
        <!-- Header -->
        <div style="padding: 30px 24px 20px;">
            <h1 style="font-size: 32px; font-weight: 900; color: #0f172a; letter-spacing: -1px;">Academic <span style="color: var(--primary);">Catalog</span></h1>
            <p style="color: #64748b; font-size: 14px; margin-top: 5px;">Manage your assigned courses and student reach.</p>
        </div>

        <!-- Global Stats -->
        <div style="padding: 0 24px; margin-bottom: 30px;">
            <div style="background: white; padding: 24px; border-radius: 35px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; background: var(--primary-glow); color: var(--primary); border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="graduation-cap" style="width: 24px;"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 20px; font-weight: 850; color: #0f172a;"><?php echo count($courses); ?></h3>
                        <p style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Active Courses</p>
                    </div>
                </div>
                <div style="width: 1px; height: 40px; background: #e2e8f0;"></div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; background: #ecfdf5; color: #10b981; border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="users" style="width: 24px;"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 20px; font-weight: 850; color: #0f172a;">
                            <?php 
                                $total_students = 0;
                                foreach($courses as $c) $total_students += $c['student_count'];
                                echo $total_students;
                            ?>
                        </h3>
                        <p style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Total Reach</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Grid -->
        <div style="padding: 0 24px; display: grid; grid-template-columns: 1fr; gap: 20px;">
            <?php if (empty($courses)): ?>
                <div style="background: white; padding: 50px 20px; border-radius: 40px; text-align: center; border: 2px dashed #cbd5e1;">
                    <i data-lucide="book-x" style="width: 48px; height: 48px; color: #cbd5e1; margin-bottom: 20px;"></i>
                    <h3 style="font-weight: 800; color: #1e293b;">No courses assigned yet.</h3>
                    <p style="color: #64748b; font-size: 14px;">Contact your administrator if this is an error.</p>
                </div>
            <?php else: ?>
                <?php foreach($courses as $c): ?>
                    <div style="background: white; border-radius: 35px; border: 1.5px solid #e2e8f0; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.02); transition: transform 0.3s ease;">
                        <div style="padding: 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <span style="background: #f1f5f9; color: #64748b; padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase;"><?php echo htmlspecialchars($c['course_code']); ?></span>
                                <h3 style="font-size: 19px; font-weight: 900; color: #0f172a; margin-top: 10px; letter-spacing: -0.5px; line-height: 1.2;">
                                    <?php echo htmlspecialchars($c['course_name']); ?>
                                </h3>
                            </div>
                            <div style="width: 44px; height: 44px; background: #f8fafc; border-radius: 12px; display: flex; justify-content: center; align-items: center; color: var(--primary);">
                                <i data-lucide="book-open" style="width: 20px;"></i>
                            </div>
                        </div>
                        
                        <div style="padding: 20px 24px; display: flex; align-items: center; justify-content: space-between; background: #fdfdfd;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div>
                                    <p style="font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Students</p>
                                    <p style="font-size: 15px; font-weight: 800; color: #1e293b;"><?php echo $c['student_count']; ?></p>
                                </div>
                                <div style="width: 1px; height: 20px; background: #e2e8f0;"></div>
                                <div>
                                    <p style="font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Last Session</p>
                                    <p style="font-size: 15px; font-weight: 800; color: #1e293b;">
                                        <?php echo $c['last_session'] ? date('M d', strtotime($c['last_session'])) : 'Never'; ?>
                                    </p>
                                </div>
                            </div>
                            <a href="generate_qr" style="width: 40px; height: 40px; background: #0f172a; color: white; border-radius: 100px; display: flex; justify-content: center; align-items: center; transition: all 0.2s ease;">
                                <i data-lucide="plus" style="width: 18px;"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div style="height: 120px;"></div>
    </div>

    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<?php include '../includes/footer.php'; ?>
