<?php
require_once '../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login");
    exit;
}

$page_title = "My Courses";
include '../includes/header.php';

$db = get_db_connection();
$user_id = $_SESSION['user_id'];

// Fetch Enrolled Courses
$stmt = $db->prepare("
    SELECT c.course_code, c.course_name, l.fullname as lecturer_name
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN users l ON c.lecturer_id = l.id
    WHERE e.student_id = ?
    ORDER BY c.course_code ASC
");
$stmt->execute([$user_id]);
$courses = $stmt->fetchAll();

?>

<div class="desktop-only-layout" style="background: #fbfcfd; min-height: 100vh;">
    <header style="padding: 50px 60px 30px; display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <div style="background: #10b981; width: 12px; height: 12px; border-radius: 4px;"></div>
                <span style="color: #64748b; font-size: 13px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;">Curriculum</span>
            </div>
            <h1 style="font-size: 48px; font-weight: 950; color: #0f172a; letter-spacing: -2.5px;">My <span style="color: #10b981;">Courses</span></h1>
            <p style="color: #94a3b8; font-size: 16px; font-weight: 500; margin-top: 8px;">Manage your enrolled classes.</p>
        </div>
    </header>

    <div style="padding: 0 60px 60px; display: grid; grid-template-columns: 2fr 1fr; gap: 40px;">
        
        <!-- Enrolled Courses List -->
        <div>
            <div style="background: white; border-radius: 35px; padding: 40px; border: 1.5px solid #f1f5f9; box-shadow: 0 15px 35px rgba(0,0,0,0.02);">
                <h3 style="font-size: 20px; font-weight: 900; color: #0f172a; margin: 0 0 25px 0;">Active Enrollments (<?php echo count($courses); ?>)</h3>
                
                <?php if (empty($courses)): ?>
                    <div style="text-align: center; padding: 40px; background: #f8fafc; border-radius: 20px; border: 1.5px dashed #cbd5e1;">
                        <i data-lucide="book-x" style="color: #94a3b8; width: 32px; height: 32px; margin-bottom: 15px;"></i>
                        <h4 style="font-size: 16px; font-weight: 800; color: #475569; margin: 0;">No Courses Found</h4>
                        <p style="color: #94a3b8; font-size: 13px; font-weight: 500; margin: 5px 0 0;">Use the form to enroll using a Course Code.</p>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <?php foreach($courses as $c): ?>
                            <div style="background: #f8fafc; border: 1px solid #f1f5f9; padding: 25px; border-radius: 20px; display: flex; align-items: center; justify-content: space-between;">
                                <div style="display: flex; align-items: center; gap: 20px;">
                                    <div style="width: 50px; height: 50px; background: #ecfdf5; color: #10b981; border-radius: 15px; display: flex; justify-content: center; align-items: center;">
                                        <i data-lucide="book-open" style="width: 24px;"></i>
                                    </div>
                                    <div>
                                        <h4 style="font-size: 16px; font-weight: 900; color: #0f172a; margin: 0;"><?php echo htmlspecialchars($c['course_code']); ?></h4>
                                        <p style="font-size: 13px; color: #64748b; font-weight: 600; margin: 4px 0 0;"><?php echo htmlspecialchars($c['course_name']); ?></p>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <p style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin: 0 0 5px 0;">Lecturer</p>
                                    <p style="font-size: 14px; font-weight: 700; color: #334155; margin: 0;"><?php echo htmlspecialchars($c['lecturer_name']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Enroll Form Sidebar -->
        <div>
            <div style="background: white; border-radius: 35px; padding: 40px; border: 1.5px solid #f1f5f9; box-shadow: 0 15px 35px rgba(0,0,0,0.02); position: sticky; top: 40px;">
                <div style="margin-bottom: 25px;">
                    <h3 style="font-size: 20px; font-weight: 900; color: #0f172a; margin: 0;">Enroll in Course</h3>
                    <p style="color: #64748b; font-size: 13px; font-weight: 500; margin: 5px 0 0;">Enter the course code provided by your lecturer.</p>
                </div>
                
                <form id="enrollmentForm" action="process_enrollment.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo AttendEaseSecurity::getCsrfToken(); ?>">
                    
                    <div style="margin-bottom: 25px;">
                        <label style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Course Code</label>
                        <input type="text" name="course_code" required style="width: 100%; box-sizing: border-box; padding: 18px 25px; border-radius: 20px; border: 2px solid #e2e8f0; background: #f8fafc; font-weight: 800; color: #0f172a; text-transform: uppercase; margin-top: 10px; font-size: 16px;" placeholder="e.g. CSC401">
                    </div>

                    <button type="submit" style="width: 100%; background: #10b981; color: white; border: none; padding: 18px; border-radius: 20px; font-weight: 800; font-size: 15px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3); transition: all 0.3s ease;">
                        <i data-lucide="log-in" style="width: 18px;"></i>
                        Join Course
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<!-- Mobile Interface -->
<div class="mobile-only-layout">
    <div class="dash-header" style="margin-bottom: 20px;">
        <div>
            <h2 style="font-weight: 800; font-size: 22px;">My <span style="color: #10b981;">Courses</span></h2>
            <p style="font-size: 13px; color: var(--text-muted); font-weight: 600;">Manage Enrollments</p>
        </div>
    </div>

    <div style="padding: 0 20px 30px;">
        <button onclick="document.getElementById('mobileEnrollForm').style.display='block'" style="width: 100%; background: #10b981; color: white; border: none; padding: 15px; border-radius: 15px; font-weight: 800; font-size: 15px; margin-bottom: 25px; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);">
            <i data-lucide="plus" style="width: 18px;"></i>
            Enroll via Code
        </button>

        <div id="mobileEnrollForm" style="display: none; background: var(--surface); padding: 25px; border-radius: 25px; border: 1.5px solid var(--border); margin-bottom: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h4 style="font-weight: 800; font-size: 16px; margin: 0;">Enroll in Course</h4>
                <button onclick="document.getElementById('mobileEnrollForm').style.display='none'" style="background: none; border: none; color: var(--text-muted); cursor: pointer;"><i data-lucide="x"></i></button>
            </div>
            
            <form id="enrollmentFormMobile" action="process_enrollment.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                <input type="hidden" name="csrf_token" value="<?php echo AttendEaseSecurity::getCsrfToken(); ?>">
                
                <div>
                    <label style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Course Code</label>
                    <input type="text" name="course_code" required style="width: 100%; padding: 15px; border-radius: 15px; border: 1.5px solid var(--border); font-weight: 800; text-transform: uppercase; margin-top: 8px; font-size: 15px;" placeholder="e.g. CSC401">
                </div>

                <button type="submit" style="width: 100%; background: #10b981; color: white; border: none; padding: 15px; border-radius: 15px; font-weight: 800; font-size: 15px;">Join Course</button>
            </form>
        </div>

        <h3 style="font-size: 16px; font-weight: 900; margin-bottom: 15px;">Active Enrollments (<?php echo count($courses); ?>)</h3>
        
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php if (empty($courses)): ?>
                <div style="text-align: center; padding: 30px; background: var(--surface); border-radius: 20px; border: 1.5px dashed var(--border);">
                    <p style="color: var(--text-muted); font-weight: 600; font-size: 13px;">You are not enrolled in any courses.</p>
                </div>
            <?php else: ?>
                <?php foreach($courses as $c): ?>
                    <div style="background: var(--surface); padding: 20px; border-radius: 20px; border: 1.5px solid var(--border); display: flex; align-items: center; gap: 15px;">
                        <div style="width: 45px; height: 45px; background: #ecfdf5; color: #10b981; border-radius: 12px; display: flex; justify-content: center; align-items: center; flex-shrink: 0;">
                            <i data-lucide="book-open" style="width: 20px;"></i>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <h4 style="font-size: 15px; font-weight: 900; color: var(--text-dark); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($c['course_code']); ?></h4>
                            <p style="font-size: 12px; font-weight: 600; color: var(--text-muted); margin: 4px 0 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($c['course_name']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    ['enrollmentForm', 'enrollmentFormMobile'].forEach(id => {
        const form = document.getElementById(id);
        if (!form) return;
        
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i data-lucide="loader-2" class="spin"></i> Enrolling...';
            lucide.createIcons();

            try {
                const formData = new FormData(form);
                const res = await fetch(form.action, { method: 'POST', body: formData });
                const data = await res.json();

                if (data.status === 'success') {
                    Swal.fire({
                        title: 'Enrolled Successfully!',
                        text: `You have joined ${data.course_code}.`,
                        icon: 'success',
                        confirmButtonColor: '#10b981',
                        customClass: { popup: 'aura-popup' }
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire('Enrollment Failed', data.message, 'error');
                    btn.innerHTML = originalText;
                }
            } catch (err) {
                Swal.fire('Error', 'Network Error', 'error');
                btn.innerHTML = originalText;
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
