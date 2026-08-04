<?php
require_once '../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../lecturer/login");
    exit;
}

$page_title = "Course Matrix";
include '../includes/header.php';

$db = get_db_connection();

// Fetch Courses with Lecturer Info
$stmt = $db->query("
    SELECT c.id, c.course_code, c.course_name, u.fullname as lecturer_name 
    FROM courses c 
    LEFT JOIN users u ON c.lecturer_id = u.id 
    ORDER BY c.course_code ASC
");
$courses = $stmt->fetchAll();

// Fetch Lecturers for the dropdown
$lecturer_stmt = $db->query("SELECT id, fullname FROM users WHERE role = 'lecturer' ORDER BY fullname ASC");
$lecturers = $lecturer_stmt->fetchAll();
?>

<div class="desktop-only-layout" style="background: #f1f5f9; min-height: 100vh;">
    <header style="padding: 60px 80px 40px; display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
                <div style="background: #10b981; width: 12px; height: 12px; border-radius: 4px;"></div>
                <span style="color: #64748b; font-size: 13px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase;">Curriculum Mapping</span>
            </div>
            <h1 style="font-size: 56px; font-weight: 950; color: #0f172a; letter-spacing: -2.5px;">Course <span style="color: #10b981;">Matrix</span></h1>
            <p style="color: #94a3b8; font-size: 18px; font-weight: 500; margin-top: 10px;">Define curriculum and assign faculty coordinators.</p>
        </div>
    </header>

    <div style="padding: 0 80px 80px; display: grid; grid-template-columns: 8fr 4fr; gap: 40px; align-items: start;">
        
        <!-- Courses List -->
        <div style="background: white; border-radius: 50px; padding: 50px; border: 1.5px solid #f1f5f9; box-shadow: 0 25px 60px rgba(0,0,0,0.03);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
                <h3 style="font-size: 26px; font-weight: 950; color: #0f172a; letter-spacing: -1px;">Active Courses</h3>
                <div style="background: #ecfdf5; padding: 10px 20px; border-radius: 12px; font-size: 14px; font-weight: 800; color: #10b981;">
                    <?php echo count($courses); ?> Courses Mapped
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php if (empty($courses)): ?>
                    <div style="text-align: center; padding: 60px; background: #f8fafc; border-radius: 35px; border: 2px dashed #e2e8f0;">
                         <i data-lucide="book-x" style="width: 48px; height: 48px; color: #cbd5e1; margin-bottom: 20px;"></i>
                         <p style="color: #64748b; font-weight: 700;">No courses have been added to the system yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($courses as $course): ?>
                        <div style="background: #fff; padding: 25px 35px; border-radius: 30px; border: 1.5px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; transition: all 0.2s ease;">
                            <div style="display: flex; align-items: center; gap: 20px;">
                                <div style="width: 50px; height: 50px; border-radius: 15px; background: #ecfdf5; color: #10b981; display: flex; justify-content: center; align-items: center;">
                                    <i data-lucide="book-open" style="width: 24px;"></i>
                                </div>
                                <div>
                                    <p style="font-size: 18px; font-weight: 900; color: #0f172a; margin: 0;"><?php echo htmlspecialchars($course['course_code']); ?></p>
                                    <p style="font-size: 14px; font-weight: 600; color: #64748b; margin: 4px 0 0;"><?php echo htmlspecialchars($course['course_name']); ?></p>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <p style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin: 0 0 4px 0;">Assigned To</p>
                                <p style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="user" style="width: 14px; color: #3b82f6;"></i>
                                    <?php echo htmlspecialchars($course['lecturer_name'] ?? 'Unassigned'); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Add Course Form -->
        <div style="background: white; border-radius: 50px; padding: 50px; border: 1.5px solid #f1f5f9; box-shadow: 0 25px 60px rgba(0,0,0,0.03);">
            <div style="margin-bottom: 30px;">
                <h3 style="font-size: 22px; font-weight: 900; color: #0f172a; letter-spacing: -1px; margin: 0;">Add New Course</h3>
                <p style="color: #64748b; font-size: 13px; font-weight: 500; margin: 5px 0 0;">Create a course and assign faculty.</p>
            </div>
            
            <form id="addCourseForm" action="process_course.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo AttendEaseSecurity::getCsrfToken(); ?>">
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Course Code</label>
                    <input type="text" name="course_code" required style="width: 100%; box-sizing: border-box; padding: 15px 20px; border-radius: 15px; border: 2px solid #e2e8f0; background: #f8fafc; font-weight: 600; color: #0f172a; text-transform: uppercase;" placeholder="e.g. CSC401">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Course Name</label>
                    <input type="text" name="course_name" required style="width: 100%; box-sizing: border-box; padding: 15px 20px; border-radius: 15px; border: 2px solid #e2e8f0; background: #f8fafc; font-weight: 600; color: #0f172a;" placeholder="e.g. Artificial Intelligence">
                </div>

                <div class="form-group" style="margin-bottom: 30px;">
                    <label style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Assign Lecturer</label>
                    <select name="lecturer_id" required style="width: 100%; box-sizing: border-box; padding: 15px 20px; border-radius: 15px; border: 2px solid #e2e8f0; background: #f8fafc; font-weight: 600; color: #0f172a; appearance: none; cursor: pointer;">
                        <option value="" disabled selected>-- Select a Lecturer --</option>
                        <?php foreach($lecturers as $lec): ?>
                            <option value="<?php echo $lec['id']; ?>"><?php echo htmlspecialchars($lec['fullname']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" style="width: 100%; background: #10b981; color: white; border: none; padding: 18px; border-radius: 20px; font-weight: 800; font-size: 15px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);">
                    <i data-lucide="plus-square" style="width: 18px;"></i>
                    Create Course
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Mobile Interface -->
<div class="mobile-only-layout">
    <div class="dash-header" style="margin-bottom: 20px;">
        <div>
            <h2 style="font-weight: 800; font-size: 22px;">Course <span style="color: #10b981;">Matrix</span></h2>
            <p style="font-size: 13px; color: var(--text-muted); font-weight: 600;">Curriculum Mapping</p>
        </div>
    </div>

    <div style="padding: 0 20px 30px;">
        <button onclick="document.getElementById('mobileCourseForm').style.display='block'" style="width: 100%; background: #10b981; color: white; border: none; padding: 15px; border-radius: 15px; font-weight: 800; font-size: 15px; margin-bottom: 25px; display: flex; align-items: center; justify-content: center; gap: 8px;">
            <i data-lucide="plus-square" style="width: 18px;"></i>
            Create Course
        </button>

        <div id="mobileCourseForm" style="display: none; background: var(--surface); padding: 20px; border-radius: 20px; border: 1.5px solid var(--border); margin-bottom: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h4 style="font-weight: 800; font-size: 15px; margin: 0;">New Course</h4>
                <button onclick="document.getElementById('mobileCourseForm').style.display='none'" style="background: none; border: none; color: var(--text-muted);"><i data-lucide="x"></i></button>
            </div>
            
            <form id="addCourseFormMobile" action="process_course.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                <input type="hidden" name="csrf_token" value="<?php echo AttendEaseSecurity::getCsrfToken(); ?>">
                
                <div>
                    <label style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Course Code</label>
                    <input type="text" name="course_code" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1.5px solid var(--border); font-weight: 600; text-transform: uppercase;" placeholder="e.g. CSC401">
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Course Name</label>
                    <input type="text" name="course_name" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1.5px solid var(--border); font-weight: 600;" placeholder="e.g. Artificial Intelligence">
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Assign Lecturer</label>
                    <select name="lecturer_id" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1.5px solid var(--border); font-weight: 600;">
                        <option value="" disabled selected>-- Select a Lecturer --</option>
                        <?php foreach($lecturers as $lec): ?>
                            <option value="<?php echo $lec['id']; ?>"><?php echo htmlspecialchars($lec['fullname']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" style="width: 100%; background: #10b981; color: white; border: none; padding: 14px; border-radius: 12px; font-weight: 800;">Create</button>
            </form>
        </div>

        <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 15px;">Active Courses (<?php echo count($courses); ?>)</h3>
        
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php if (empty($courses)): ?>
                <div style="text-align: center; padding: 30px; background: var(--surface); border-radius: 20px;">
                    <p style="color: var(--text-muted); font-weight: 600; font-size: 13px;">No courses mapped.</p>
                </div>
            <?php else: ?>
                <?php foreach($courses as $course): ?>
                    <div style="background: var(--surface); padding: 15px; border-radius: 20px; border: 1.5px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <p style="font-size: 14px; font-weight: 900; color: var(--text-dark); margin: 0;"><?php echo htmlspecialchars($course['course_code']); ?></p>
                            <p style="font-size: 11px; font-weight: 600; color: var(--text-muted); margin: 2px 0 6px;"><?php echo htmlspecialchars($course['course_name']); ?></p>
                            <div style="display: inline-flex; align-items: center; gap: 4px; background: rgba(59, 130, 246, 0.1); padding: 4px 8px; border-radius: 8px;">
                                <i data-lucide="user" style="width: 12px; color: #3b82f6;"></i>
                                <span style="font-size: 10px; font-weight: 700; color: #3b82f6;"><?php echo htmlspecialchars($course['lecturer_name'] ?? 'Unassigned'); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
['addCourseForm', 'addCourseFormMobile'].forEach(id => {
    const form = document.getElementById(id);
    if (!form) return;
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i data-lucide="loader-2" class="spin"></i> Creating...';
        lucide.createIcons();

        try {
            const formData = new FormData(form);
            const res = await fetch(form.action, { method: 'POST', body: formData });
            const data = await res.json();

            if (data.status === 'success') {
                Swal.fire({
                    title: 'Created!',
                    text: 'Course added and assigned successfully.',
                    icon: 'success',
                    confirmButtonColor: '#10b981',
                    customClass: { popup: 'aura-popup' }
                }).then(() => window.location.reload());
            } else {
                Swal.fire('Error', data.message, 'error');
                btn.innerHTML = originalText;
            }
        } catch (err) {
            Swal.fire('Error', 'Network Error', 'error');
            btn.innerHTML = originalText;
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
