<?php
require_once '../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth Check - Allow Lecturers and Admins
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'lecturer' && $_SESSION['role'] !== 'admin')) {
    header("Location: login");
    exit;
}

$page_title = "Home";
include '../includes/header.php';

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
// Intelligence Engine - Dynamic Stats
require_once '../includes/stat_engine.php';
$stats = StatEngine::getLecturerStats($user_id);
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
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 10px;"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="M12 6v8"/><path d="M9 22h6"/></svg>
                <h3 style="font-size: 24px; font-weight: 800;"><?php echo $stats['total_sessions']; ?></h3>
                <p style="font-size: 12px; color: var(--text-muted);">Sessions Held</p>
            </div>
            <div style="background: var(--surface); padding: 20px; border-radius: 24px; border: 1px solid var(--border);">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 10px;"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <h3 style="font-size: 24px; font-weight: 800;"><?php echo $stats['total_students']; ?></h3>
                <p style="font-size: 12px; color: var(--text-muted);">Total Reach</p>
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
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/><path d="M21 21v.01"/><path d="M12 7v3a2 2 0 0 1-2 2H7"/><path d="M3 12h.01"/><path d="M12 3h.01"/><path d="M12 16h.01"/><path d="M16 12h1"/><path d="M21 12v.01"/><path d="M12 21v-1"/></svg>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Broadcast Alert System -->
        <div class="section-title">
            <span>Broadcast Intelligence</span>
        </div>
        <div style="padding: 0 24px; margin-bottom: 30px;">
            <div style="background: var(--surface); padding: 24px; border-radius: 32px; border: 1.5px solid var(--border); box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                    <div style="width: 40px; height: 40px; background: var(--primary-glow); color: var(--primary); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>
                    </div>
                    <h3 style="font-size: 16px; font-weight: 800;">Send Instant Announcement</h3>
                </div>
                
                <form id="announcementForm" style="display: flex; flex-direction: column; gap: 15px;">
                    <select name="course_id" class="form-control" style="padding: 12px; border-radius: 14px; font-size: 13px;" required>
                        <option value="">Select Target Course...</option>
                        <?php foreach($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['course_code']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="title" placeholder="Announcement Title (e.g. Class Moved)" class="form-control" style="padding: 12px; border-radius: 14px; font-size: 13px;" required>
                    <textarea name="message" placeholder="Message content..." style="width: 100%; height: 100px; padding: 15px; border-radius: 16px; border: 1.5px solid var(--border); font-size: 13px; outline: none;"></textarea>
                    
                    <button type="submit" class="btn-primary" style="padding: 14px; border-radius: 16px; font-size: 14px; font-weight: 800; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                        Send to All Students
                    </button>
                </form>
            </div>
        </div>

        <!-- Course List -->
        <div class="section-title">
            <span>Course Catalog</span>
        </div>
        
        <div style="padding: 0 24px;">
            <?php foreach($courses as $course): ?>
                <div style="background: var(--surface); padding: 18px; border-radius: 24px; border: 1px solid var(--border); margin-bottom: 12px; display: flex; align-items: center; gap: 15px;">
                    <div style="width: 45px; height: 45px; background: var(--bg-main); border-radius: 12px; display: flex; justify-content: center; align-items: center; color: var(--primary);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="font-size: 14px; font-weight: 700;"><?php echo htmlspecialchars($course['course_name']); ?></h4>
                        <p style="font-size: 11px; color: var(--text-muted);"><?php echo htmlspecialchars($course['course_code']); ?></p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <a href="export_attendance?course_id=<?php echo $course['id']; ?>" style="width: 36px; height: 36px; background: #22c55e; color: white; border-radius: 10px; display: flex; align-items: center; justify-content: center;" title="Export CSV">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                        </a>
                        <a href="course_details?id=<?php echo $course['id']; ?>" style="width: 36px; height: 36px; background: var(--bg-main); color: var(--text-muted); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="height: 100px;"></div>
    </div>

    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Broadcast Intelligence - Pulse Engine
    const announceForm = document.getElementById('announcementForm');
    if (announceForm) {
        announceForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const btn = form.querySelector('button');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            // Show Loading
            const originalBtnText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<div class="loader" style="width:16px; height:16px; border-width:2px;"></div> &nbsp; Deploying Pulse...';

            try {
                const response = await fetch('../includes/process_broadcast.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '<?php echo AttendEaseSecurity::getCsrfToken(); ?>'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        title: 'Broadcast Deployed!',
                        text: result.message + " (Sent to " + result.stats.sent + " students)",
                        icon: 'success',
                        confirmButtonText: 'Acknowledged',
                        buttonsStyling: false,
                        customClass: { confirmButton: 'btn-primary swal2-confirm' }
                    });
                    form.reset();
                } else {
                    Swal.fire({
                        title: 'Deployment Failed',
                        text: result.message,
                        icon: 'error',
                        confirmButtonText: 'Try Again',
                        buttonsStyling: false,
                        customClass: { confirmButton: 'btn-primary swal2-confirm' }
                    });
                }
            } catch (err) {
                console.error("Pulse error:", err);
                Swal.fire('Error', 'Connection to broadcast node failed.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalBtnText;
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
