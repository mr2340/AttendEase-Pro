<?php
require_once '../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth Check - Admins only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../lecturer/login");
    exit;
}

$page_title = "Home";
include '../includes/header.php';

$db = get_db_connection();

// Fetch System Analytics
$stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
$total_students = $stmt->fetch()['count'];

$stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'parent'");
$total_parents = $stmt->fetch()['count'];

$stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'lecturer'");
$total_lecturers = $stmt->fetch()['count'];

$stmt = $db->query("SELECT COUNT(*) as count FROM courses");
$total_courses = $stmt->fetch()['count'];

$stmt = $db->query("SELECT COUNT(*) as count FROM parent_student_map");
$total_mappings = $stmt->fetch()['count'];
?>

<div class="desktop-only-layout" style="background: #f1f5f9; min-height: 100vh;">
    <header style="padding: 60px 80px 40px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
            <div style="background: #0f172a; width: 12px; height: 12px; border-radius: 4px;"></div>
            <span style="color: #64748b; font-size: 13px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase;">Command Center</span>
        </div>
        <h1 style="font-size: 56px; font-weight: 950; color: #0f172a; letter-spacing: -2.5px;">System <span style="color: #3b82f6;">Overview</span></h1>
        <p style="color: #94a3b8; font-size: 18px; font-weight: 500; margin-top: 10px;">High-level analytics of the Guardian Infrastructure.</p>
    </header>

    <div style="padding: 0 80px 80px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 30px;">
        
        <!-- Metric Card 1: Students -->
        <div style="background: white; border-radius: 35px; padding: 40px; border: 1.5px solid #f1f5f9; box-shadow: 0 20px 40px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 20px;">
            <div style="width: 60px; height: 60px; background: #e0e7ff; border-radius: 20px; display: flex; justify-content: center; align-items: center; color: #4f46e5;">
                <i data-lucide="users" style="width: 28px; height: 28px;"></i>
            </div>
            <div>
                <h3 style="font-size: 42px; font-weight: 900; color: #0f172a; margin: 0; letter-spacing: -1px;"><?php echo number_format($total_students); ?></h3>
                <p style="font-size: 14px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 5px;">Total Students</p>
            </div>
        </div>

        <!-- Metric Card 2: Faculty -->
        <div style="background: white; border-radius: 35px; padding: 40px; border: 1.5px solid #f1f5f9; box-shadow: 0 20px 40px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 20px;">
            <div style="width: 60px; height: 60px; background: #dbeafe; border-radius: 20px; display: flex; justify-content: center; align-items: center; color: #3b82f6;">
                <i data-lucide="graduation-cap" style="width: 28px; height: 28px;"></i>
            </div>
            <div>
                <h3 style="font-size: 42px; font-weight: 900; color: #0f172a; margin: 0; letter-spacing: -1px;"><?php echo number_format($total_lecturers); ?></h3>
                <p style="font-size: 14px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 5px;">Total Faculty</p>
            </div>
        </div>

        <!-- Metric Card 3: Courses -->
        <div style="background: white; border-radius: 35px; padding: 40px; border: 1.5px solid #f1f5f9; box-shadow: 0 20px 40px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 20px;">
            <div style="width: 60px; height: 60px; background: #d1fae5; border-radius: 20px; display: flex; justify-content: center; align-items: center; color: #10b981;">
                <i data-lucide="book-open" style="width: 28px; height: 28px;"></i>
            </div>
            <div>
                <h3 style="font-size: 42px; font-weight: 900; color: #0f172a; margin: 0; letter-spacing: -1px;"><?php echo number_format($total_courses); ?></h3>
                <p style="font-size: 14px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 5px;">Active Courses</p>
            </div>
        </div>

        <!-- Metric Card 4: Guardian Mappings -->
        <div style="background: white; border-radius: 35px; padding: 40px; border: 1.5px solid #f1f5f9; box-shadow: 0 20px 40px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 20px;">
            <div style="width: 60px; height: 60px; background: #fee2e2; border-radius: 20px; display: flex; justify-content: center; align-items: center; color: #ef4444;">
                <i data-lucide="shield-check" style="width: 28px; height: 28px;"></i>
            </div>
            <div>
                <h3 style="font-size: 42px; font-weight: 900; color: #0f172a; margin: 0; letter-spacing: -1px;"><?php echo number_format($total_mappings); ?></h3>
                <p style="font-size: 14px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 5px;">Active Linkages</p>
            </div>
        </div>

    </div>
</div>

<!-- Mobile Interface -->
<div class="mobile-only-layout">
    <div class="dash-header" style="margin-bottom: 30px;">
        <div>
            <h2 style="font-weight: 800; font-size: 24px;">System <span style="color: #3b82f6;">Overview</span></h2>
            <p style="font-size: 13px; color: var(--text-muted); font-weight: 600;">Command Center</p>
        </div>
    </div>

    <div style="padding: 0 20px 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
        
        <div style="background: var(--surface); padding: 20px; border-radius: 25px; border: 1.5px solid var(--border);">
            <div style="width: 40px; height: 40px; background: rgba(79, 70, 229, 0.1); border-radius: 12px; display: flex; justify-content: center; align-items: center; color: #4f46e5; margin-bottom: 15px;">
                <i data-lucide="users" style="width: 20px; height: 20px;"></i>
            </div>
            <h3 style="font-size: 28px; font-weight: 900; margin: 0 0 4px;"><?php echo number_format($total_students); ?></h3>
            <p style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin: 0;">Students</p>
        </div>

        <div style="background: var(--surface); padding: 20px; border-radius: 25px; border: 1.5px solid var(--border);">
            <div style="width: 40px; height: 40px; background: rgba(59, 130, 246, 0.1); border-radius: 12px; display: flex; justify-content: center; align-items: center; color: #3b82f6; margin-bottom: 15px;">
                <i data-lucide="graduation-cap" style="width: 20px; height: 20px;"></i>
            </div>
            <h3 style="font-size: 28px; font-weight: 900; margin: 0 0 4px;"><?php echo number_format($total_lecturers); ?></h3>
            <p style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin: 0;">Faculty</p>
        </div>

        <div style="background: var(--surface); padding: 20px; border-radius: 25px; border: 1.5px solid var(--border);">
            <div style="width: 40px; height: 40px; background: rgba(16, 185, 129, 0.1); border-radius: 12px; display: flex; justify-content: center; align-items: center; color: #10b981; margin-bottom: 15px;">
                <i data-lucide="book-open" style="width: 20px; height: 20px;"></i>
            </div>
            <h3 style="font-size: 28px; font-weight: 900; margin: 0 0 4px;"><?php echo number_format($total_courses); ?></h3>
            <p style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin: 0;">Courses</p>
        </div>

        <div style="background: var(--surface); padding: 20px; border-radius: 25px; border: 1.5px solid var(--border);">
            <div style="width: 40px; height: 40px; background: rgba(239, 68, 68, 0.1); border-radius: 12px; display: flex; justify-content: center; align-items: center; color: #ef4444; margin-bottom: 15px;">
                <i data-lucide="shield-check" style="width: 20px; height: 20px;"></i>
            </div>
            <h3 style="font-size: 28px; font-weight: 900; margin: 0 0 4px;"><?php echo number_format($total_mappings); ?></h3>
            <p style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin: 0;">Linkages</p>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>

<?php include '../includes/footer.php'; ?>
