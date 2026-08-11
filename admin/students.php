<?php
require_once '../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../lecturer/login");
    exit;
}

$page_title = "Student Management";
include '../includes/header.php';

$db = get_db_connection();

// Logic for search
$search = $_GET['q'] ?? '';
$where = "WHERE role = 'student'";
$params = [];
if ($search) {
    $where .= " AND (fullname LIKE ? OR student_id LIKE ? OR username LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// Fetch students
$stmt = $db->prepare("SELECT * FROM users $where ORDER BY fullname ASC");
$stmt->execute($params);
$students = $stmt->fetchAll();
?>

<div class="desktop-only-layout" style="background: #f1f5f9; min-height: 100vh;">
    <header style="padding: 60px 80px 40px; display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
                <div style="background: #10b981; width: 12px; height: 12px; border-radius: 4px;"></div>
                <span style="color: #64748b; font-size: 13px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase;">Student Registry</span>
            </div>
            <h1 style="font-size: 56px; font-weight: 950; color: #0f172a; letter-spacing: -2.5px;">Student <span style="color: #10b981;">Management</span></h1>
            <p style="color: #94a3b8; font-size: 18px; font-weight: 500; margin-top: 10px;">Oversee and register new student accounts.</p>
        </div>
        <div style="display: flex; gap: 20px;">
             <form action="" method="GET" style="position: relative;">
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search students..." style="padding: 15px 25px 15px 50px; border-radius: 20px; border: 1.5px solid #e2e8f0; width: 300px; font-weight: 600;">
                <i data-lucide="search" style="position: absolute; left: 20px; top: 15px; width: 20px; color: #94a3b8;"></i>
             </form>
        </div>
    </header>

    <div style="padding: 0 80px 80px; display: grid; grid-template-columns: 8fr 4fr; gap: 40px; align-items: start;">
        
        <!-- Students List -->
        <div style="background: white; border-radius: 50px; padding: 50px; border: 1.5px solid #f1f5f9; box-shadow: 0 25px 60px rgba(0,0,0,0.03);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
                <h3 style="font-size: 26px; font-weight: 950; color: #0f172a; letter-spacing: -1px;">Active Students</h3>
                <div style="background: #ecfdf5; padding: 10px 20px; border-radius: 12px; font-size: 14px; font-weight: 800; color: #10b981;">
                    <?php echo count($students); ?> Enrolled
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php if (empty($students)): ?>
                    <div style="text-align: center; padding: 60px; background: #f8fafc; border-radius: 35px; border: 2px dashed #e2e8f0;">
                         <i data-lucide="users" style="width: 48px; height: 48px; color: #cbd5e1; margin-bottom: 20px;"></i>
                         <p style="color: #64748b; font-weight: 700;">No students found in the registry.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($students as $stu): 
                        $avatar = $stu['avatar_url'] ? (strpos($stu['avatar_url'], 'http') === 0 ? $stu['avatar_url'] : BASE_URL . $stu['avatar_url']) : "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($stu['username']);
                    ?>
                        <div style="background: #fff; padding: 25px 35px; border-radius: 30px; border: 1.5px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; transition: all 0.2s ease;">
                            <div style="display: flex; align-items: center; gap: 20px;">
                                <div style="width: 50px; height: 50px; border-radius: 15px; overflow: hidden; background: #f1f5f9;">
                                    <img src="<?php echo $avatar; ?>" width="100%" height="100%" style="object-fit: cover;">
                                </div>
                                <div>
                                    <p style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;"><?php echo htmlspecialchars($stu['fullname']); ?> <span style="font-size: 12px; color: #64748b;">(<?php echo htmlspecialchars($stu['student_id']); ?>)</span></p>
                                    <p style="font-size: 12px; font-weight: 600; color: #64748b; margin: 4px 0 0;">@<?php echo htmlspecialchars($stu['username']); ?> &bull; <?php echo htmlspecialchars($stu['email'] ?? 'No Email'); ?></p>
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <button onclick="editStudent(<?php echo htmlspecialchars(json_encode([
                                    'id' => $stu['id'],
                                    'student_id' => $stu['student_id'],
                                    'fullname' => $stu['fullname'],
                                    'username' => $stu['username']
                                ])); ?>)" style="background: #eff6ff; color: #3b82f6; border: none; width: 44px; height: 44px; border-radius: 14px; cursor: pointer; display: flex; justify-content: center; align-items: center;">
                                    <i data-lucide="edit-2" style="width: 20px;"></i>
                                </button>
                                <button onclick="deleteStudent(<?php echo $stu['id']; ?>)" style="background: #fee2e2; color: #ef4444; border: none; width: 44px; height: 44px; border-radius: 14px; cursor: pointer; display: flex; justify-content: center; align-items: center;">
                                    <i data-lucide="trash-2" style="width: 20px;"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Add Student Form -->
        <div style="background: white; border-radius: 50px; padding: 50px; border: 1.5px solid #f1f5f9; box-shadow: 0 25px 60px rgba(0,0,0,0.03); position: sticky; top: 40px;">
            <div style="margin-bottom: 30px;">
                <h3 style="font-size: 22px; font-weight: 900; color: #0f172a; letter-spacing: -1px; margin: 0;">Register Student</h3>
                <p style="color: #64748b; font-size: 13px; font-weight: 500; margin: 5px 0 0;">Create a new student profile.</p>
            </div>
            
            <form id="addStudentForm" action="process_student.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo AttendEaseSecurity::getCsrfToken(); ?>">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Registration Number (Student ID)</label>
                    <input type="text" name="student_id" required style="width: 100%; box-sizing: border-box; padding: 15px 20px; border-radius: 15px; border: 2px solid #e2e8f0; background: #f8fafc; font-weight: 600; color: #0f172a;" placeholder="e.g. STU001">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Full Name</label>
                    <input type="text" name="fullname" required style="width: 100%; box-sizing: border-box; padding: 15px 20px; border-radius: 15px; border: 2px solid #e2e8f0; background: #f8fafc; font-weight: 600; color: #0f172a;" placeholder="e.g. John Doe">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Username (Login ID)</label>
                    <input type="text" name="username" required style="width: 100%; box-sizing: border-box; padding: 15px 20px; border-radius: 15px; border: 2px solid #e2e8f0; background: #f8fafc; font-weight: 600; color: #0f172a;" placeholder="e.g. jdoe">
                </div>

                <div class="form-group" style="margin-bottom: 30px; position: relative;">
                    <label style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Initial Password</label>
                    <input type="password" name="password" required style="width: 100%; box-sizing: border-box; padding: 15px 45px 15px 20px; border-radius: 15px; border: 2px solid #e2e8f0; background: #f8fafc; font-weight: 600; color: #0f172a;" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                    <button type="button" onclick="togglePassword(this)" style="position: absolute; right: 15px; top: 38px; background: none; border: none; cursor: pointer; color: #94a3b8;"><i data-lucide="eye" style="width: 18px;"></i></button>
                </div>

                <button type="submit" style="width: 100%; background: #10b981; color: white; border: none; padding: 18px; border-radius: 20px; font-weight: 800; font-size: 15px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);">
                    <i data-lucide="user-plus" style="width: 18px;"></i>
                    Register Student
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Mobile Interface -->
<div class="mobile-only-layout">
    <div class="dash-header" style="margin-bottom: 20px;">
        <div>
            <h2 style="font-weight: 800; font-size: 22px;">Student <span style="color: #10b981;">Management</span></h2>
            <p style="font-size: 13px; color: var(--text-muted); font-weight: 600;">Student Registry</p>
        </div>
    </div>

    <div style="padding: 0 20px 30px;">
        <button onclick="document.getElementById('mobileStudentForm').style.display='block'" style="width: 100%; background: #10b981; color: white; border: none; padding: 15px; border-radius: 15px; font-weight: 800; font-size: 15px; margin-bottom: 25px; display: flex; align-items: center; justify-content: center; gap: 8px;">
            <i data-lucide="user-plus" style="width: 18px;"></i>
            Register Student
        </button>

        <div id="mobileStudentForm" style="display: none; background: var(--surface); padding: 20px; border-radius: 20px; border: 1.5px solid var(--border); margin-bottom: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h4 style="font-weight: 800; font-size: 15px; margin: 0;">New Student</h4>
                <button onclick="document.getElementById('mobileStudentForm').style.display='none'" style="background: none; border: none; color: var(--text-muted);"><i data-lucide="x"></i></button>
            </div>
            
            <form id="addStudentFormMobile" action="process_student.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                <input type="hidden" name="csrf_token" value="<?php echo AttendEaseSecurity::getCsrfToken(); ?>">
                <input type="hidden" name="action" value="create">
                
                <div>
                    <label style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Reg Number</label>
                    <input type="text" name="student_id" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1.5px solid var(--border); font-weight: 600;" placeholder="STU001">
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Full Name</label>
                    <input type="text" name="fullname" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1.5px solid var(--border); font-weight: 600;" placeholder="John Doe">
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Username</label>
                    <input type="text" name="username" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1.5px solid var(--border); font-weight: 600;" placeholder="jdoe">
                </div>
                <div style="position: relative;">
                    <label style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Initial Password</label>
                    <input type="password" name="password" required style="width: 100%; padding: 12px 40px 12px 12px; border-radius: 12px; border: 1.5px solid var(--border); font-weight: 600;" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                    <button type="button" onclick="togglePassword(this)" style="position: absolute; right: 12px; top: 30px; background: none; border: none; cursor: pointer; color: #94a3b8;"><i data-lucide="eye" style="width: 16px;"></i></button>
                </div>

                <button type="submit" style="width: 100%; background: #10b981; color: white; border: none; padding: 14px; border-radius: 12px; font-weight: 800;">Register</button>
            </form>
        </div>

        <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 15px;">Active Students (<?php echo count($students); ?>)</h3>
        
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php if (empty($students)): ?>
                <div style="text-align: center; padding: 30px; background: var(--surface); border-radius: 20px;">
                    <p style="color: var(--text-muted); font-weight: 600; font-size: 13px;">No students found.</p>
                </div>
            <?php else: ?>
                <?php foreach($students as $stu): 
                    $avatar = $stu['avatar_url'] ? (strpos($stu['avatar_url'], 'http') === 0 ? $stu['avatar_url'] : BASE_URL . $stu['avatar_url']) : "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($stu['username']);
                ?>
                    <div style="background: var(--surface); padding: 15px; border-radius: 20px; border: 1.5px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="width: 40px; height: 40px; border-radius: 12px; overflow: hidden; background: #f1f5f9;">
                                <img src="<?php echo $avatar; ?>" width="100%" height="100%" style="object-fit: cover;">
                            </div>
                            <div>
                                <p style="font-size: 14px; font-weight: 800; color: var(--text-dark); margin: 0;"><?php echo htmlspecialchars($stu['fullname']); ?></p>
                                <p style="font-size: 11px; font-weight: 600; color: var(--text-muted); margin: 4px 0 0;"><?php echo htmlspecialchars($stu['student_id']); ?></p>
                            </div>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button onclick="editStudent(<?php echo htmlspecialchars(json_encode([
                                'id' => $stu['id'],
                                'student_id' => $stu['student_id'],
                                'fullname' => $stu['fullname'],
                                'username' => $stu['username']
                            ])); ?>)" style="background: #eff6ff; color: #3b82f6; border: none; width: 36px; height: 36px; border-radius: 10px; display: flex; justify-content: center; align-items: center;">
                                <i data-lucide="edit-2" style="width: 16px;"></i>
                            </button>
                            <button onclick="deleteStudent(<?php echo $stu['id']; ?>)" style="background: #fee2e2; color: #ef4444; border: none; width: 36px; height: 36px; border-radius: 10px; display: flex; justify-content: center; align-items: center;">
                                <i data-lucide="trash-2" style="width: 16px;"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function togglePassword(btn) {
    const input = btn.previousElementSibling;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.setAttribute('data-lucide', 'eye-off');
    } else {
        input.type = 'password';
        icon.setAttribute('data-lucide', 'eye');
    }
    lucide.createIcons();
}

['addStudentForm', 'addStudentFormMobile'].forEach(id => {
    const form = document.getElementById(id);
    if (!form) return;
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i data-lucide="loader-2" class="spin"></i> Registering...';
        lucide.createIcons();

        try {
            const formData = new FormData(form);
            const res = await fetch(form.action, { method: 'POST', body: formData });
            const data = await res.json();

            if (data.status === 'success') {
                Swal.fire({
                    title: 'Registered!',
                    text: 'Student account created successfully.',
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

async function editStudent(studentData) {
    // Generate the Edit Form Modal using SweetAlert2
    const { value: formValues } = await Swal.fire({
        title: 'Edit Student',
        html: `
            <form id="editStudentSwalForm" style="text-align: left; margin-top: 15px;">
                <input type="hidden" name="csrf_token" value="<?php echo AttendEaseSecurity::getCsrfToken(); ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="${studentData.id}">
                
                <div style="margin-bottom: 15px;">
                    <label style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Reg Number</label>
                    <input type="text" name="student_id" id="swal-student_id" class="swal2-input" style="width: 100%; box-sizing: border-box; margin-top: 5px;" value="${studentData.student_id}">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Full Name</label>
                    <input type="text" name="fullname" id="swal-fullname" class="swal2-input" style="width: 100%; box-sizing: border-box; margin-top: 5px;" value="${studentData.fullname}">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Username</label>
                    <input type="text" name="username" id="swal-username" class="swal2-input" style="width: 100%; box-sizing: border-box; margin-top: 5px;" value="${studentData.username}">
                </div>
                <div style="margin-bottom: 15px; position: relative;">
                    <label style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">New Password (Optional)</label>
                    <input type="password" name="password" id="swal-password" class="swal2-input" style="width: 100%; box-sizing: border-box; margin-top: 5px;" placeholder="Leave blank to keep current">
                    <button type="button" onclick="togglePassword(this)" style="position: absolute; right: 15px; top: 38px; background: none; border: none; cursor: pointer; color: #94a3b8;"><i data-lucide="eye" style="width: 18px;"></i></button>
                </div>
            </form>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Save Changes',
        confirmButtonColor: '#10b981',
        didOpen: () => {
            lucide.createIcons();
        },
        preConfirm: () => {
            const form = document.getElementById('editStudentSwalForm');
            if(!form.student_id.value || !form.fullname.value || !form.username.value) {
                Swal.showValidationMessage('All fields (except password) are required.');
                return false;
            }
            return new FormData(form);
        }
    });

    if (formValues) {
        try {
            const res = await fetch('process_student.php', { method: 'POST', body: formValues });
            const data = await res.json();
            
            if (data.status === 'success') {
                Swal.fire({
                    title: 'Updated!',
                    text: 'Student updated successfully.',
                    icon: 'success',
                    confirmButtonColor: '#10b981'
                }).then(() => location.reload());
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch(e) {
            Swal.fire('Error', 'Network Error', 'error');
        }
    }
}

async function deleteStudent(id) {
    const res = await Swal.fire({
        title: 'Delete Student?',
        text: 'This will remove the student and their attendance records.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete',
        confirmButtonColor: '#ef4444'
    });
    
    if (res.isConfirmed) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        formData.append('csrf_token', '<?php echo AttendEaseSecurity::getCsrfToken(); ?>');
        
        try {
            const response = await fetch('process_student.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.status === 'success') location.reload();
            else Swal.fire('Error', data.message, 'error');
        } catch (e) {
            Swal.fire('Error', 'Network Error', 'error');
        }
    }
}
</script>

<?php include '../includes/footer.php'; ?>
