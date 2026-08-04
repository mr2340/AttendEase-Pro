<?php
require_once '../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../lecturer/login");
    exit;
}

$page_title = "Faculty Management";
include '../includes/header.php';

$db = get_db_connection();

// Fetch lecturers
$stmt = $db->query("SELECT * FROM users WHERE role = 'lecturer' ORDER BY fullname ASC");
$lecturers = $stmt->fetchAll();
?>

<div class="desktop-only-layout" style="background: #f1f5f9; min-height: 100vh;">
    <header style="padding: 60px 80px 40px; display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
                <div style="background: #3b82f6; width: 12px; height: 12px; border-radius: 4px;"></div>
                <span style="color: #64748b; font-size: 13px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase;">Faculty Roster</span>
            </div>
            <h1 style="font-size: 56px; font-weight: 950; color: #0f172a; letter-spacing: -2.5px;">Faculty <span style="color: #3b82f6;">Management</span></h1>
            <p style="color: #94a3b8; font-size: 18px; font-weight: 500; margin-top: 10px;">Oversee and provision new lecturer accounts.</p>
        </div>
    </header>

    <div style="padding: 0 80px 80px; display: grid; grid-template-columns: 8fr 4fr; gap: 40px; align-items: start;">
        
        <!-- Lecturers List -->
        <div style="background: white; border-radius: 50px; padding: 50px; border: 1.5px solid #f1f5f9; box-shadow: 0 25px 60px rgba(0,0,0,0.03);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
                <h3 style="font-size: 26px; font-weight: 950; color: #0f172a; letter-spacing: -1px;">Active Lecturers</h3>
                <div style="background: #eff6ff; padding: 10px 20px; border-radius: 12px; font-size: 14px; font-weight: 800; color: #3b82f6;">
                    <?php echo count($lecturers); ?> Lecturers Enrolled
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php if (empty($lecturers)): ?>
                    <div style="text-align: center; padding: 60px; background: #f8fafc; border-radius: 35px; border: 2px dashed #e2e8f0;">
                         <i data-lucide="users-2" style="width: 48px; height: 48px; color: #cbd5e1; margin-bottom: 20px;"></i>
                         <p style="color: #64748b; font-weight: 700;">No faculty members found in the system.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($lecturers as $lec): 
                        $avatar = $lec['avatar_url'] ? (strpos($lec['avatar_url'], 'http') === 0 ? $lec['avatar_url'] : BASE_URL . $lec['avatar_url']) : "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($lec['username']);
                    ?>
                        <div style="background: #fff; padding: 25px 35px; border-radius: 30px; border: 1.5px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; transition: all 0.2s ease;">
                            <div style="display: flex; align-items: center; gap: 20px;">
                                <div style="width: 50px; height: 50px; border-radius: 15px; overflow: hidden; background: #f1f5f9;">
                                    <img src="<?php echo $avatar; ?>" width="100%" height="100%" style="object-fit: cover;">
                                </div>
                                <div>
                                    <p style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;"><?php echo htmlspecialchars($lec['fullname']); ?></p>
                                    <p style="font-size: 12px; font-weight: 600; color: #64748b; margin: 4px 0 0;">@<?php echo htmlspecialchars($lec['username']); ?> &bull; <?php echo htmlspecialchars($lec['email'] ?? 'No Email'); ?></p>
                                </div>
                            </div>
                            <div style="background: #eff6ff; color: #3b82f6; width: 40px; height: 40px; border-radius: 12px; display: flex; justify-content: center; align-items: center;">
                                <i data-lucide="chevron-right" style="width: 20px;"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Add Lecturer Form -->
        <div style="background: white; border-radius: 50px; padding: 50px; border: 1.5px solid #f1f5f9; box-shadow: 0 25px 60px rgba(0,0,0,0.03);">
            <div style="margin-bottom: 30px;">
                <h3 style="font-size: 22px; font-weight: 900; color: #0f172a; letter-spacing: -1px; margin: 0;">Provision Account</h3>
                <p style="color: #64748b; font-size: 13px; font-weight: 500; margin: 5px 0 0;">Create a new lecturer profile.</p>
            </div>
            
            <form id="addLecturerForm" action="process_lecturer.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo AttendEaseSecurity::getCsrfToken(); ?>">
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Full Name</label>
                    <input type="text" name="fullname" required style="width: 100%; box-sizing: border-box; padding: 15px 20px; border-radius: 15px; border: 2px solid #e2e8f0; background: #f8fafc; font-weight: 600; color: #0f172a;" placeholder="e.g. Dr. John Doe">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Username (Login ID)</label>
                    <input type="text" name="username" required style="width: 100%; box-sizing: border-box; padding: 15px 20px; border-radius: 15px; border: 2px solid #e2e8f0; background: #f8fafc; font-weight: 600; color: #0f172a;" placeholder="e.g. jdoe">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Email Address (Optional)</label>
                    <input type="email" name="email" style="width: 100%; box-sizing: border-box; padding: 15px 20px; border-radius: 15px; border: 2px solid #e2e8f0; background: #f8fafc; font-weight: 600; color: #0f172a;" placeholder="jdoe@university.edu">
                </div>

                <div class="form-group" style="margin-bottom: 30px;">
                    <label style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Initial Password</label>
                    <input type="password" name="password" required style="width: 100%; box-sizing: border-box; padding: 15px 20px; border-radius: 15px; border: 2px solid #e2e8f0; background: #f8fafc; font-weight: 600; color: #0f172a;" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                </div>

                <button type="submit" style="width: 100%; background: #3b82f6; color: white; border: none; padding: 18px; border-radius: 20px; font-weight: 800; font-size: 15px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);">
                    <i data-lucide="user-plus" style="width: 18px;"></i>
                    Register Lecturer
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Mobile Interface -->
<div class="mobile-only-layout">
    <div class="dash-header" style="margin-bottom: 20px;">
        <div>
            <h2 style="font-weight: 800; font-size: 22px;">Faculty <span style="color: #3b82f6;">Management</span></h2>
            <p style="font-size: 13px; color: var(--text-muted); font-weight: 600;">Faculty Roster</p>
        </div>
    </div>

    <div style="padding: 0 20px 30px;">
        <button onclick="document.getElementById('mobileLecturerForm').style.display='block'" style="width: 100%; background: #3b82f6; color: white; border: none; padding: 15px; border-radius: 15px; font-weight: 800; font-size: 15px; margin-bottom: 25px; display: flex; align-items: center; justify-content: center; gap: 8px;">
            <i data-lucide="user-plus" style="width: 18px;"></i>
            Provision Account
        </button>

        <div id="mobileLecturerForm" style="display: none; background: var(--surface); padding: 20px; border-radius: 20px; border: 1.5px solid var(--border); margin-bottom: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h4 style="font-weight: 800; font-size: 15px; margin: 0;">New Lecturer</h4>
                <button onclick="document.getElementById('mobileLecturerForm').style.display='none'" style="background: none; border: none; color: var(--text-muted);"><i data-lucide="x"></i></button>
            </div>
            
            <form id="addLecturerFormMobile" action="process_lecturer.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                <input type="hidden" name="csrf_token" value="<?php echo AttendEaseSecurity::getCsrfToken(); ?>">
                
                <div>
                    <label style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Full Name</label>
                    <input type="text" name="fullname" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1.5px solid var(--border); font-weight: 600;" placeholder="e.g. Dr. John Doe">
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Username (Login ID)</label>
                    <input type="text" name="username" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1.5px solid var(--border); font-weight: 600;" placeholder="e.g. jdoe">
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Email Address (Optional)</label>
                    <input type="email" name="email" style="width: 100%; padding: 12px; border-radius: 12px; border: 1.5px solid var(--border); font-weight: 600;" placeholder="jdoe@university.edu">
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Initial Password</label>
                    <input type="password" name="password" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1.5px solid var(--border); font-weight: 600;" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                </div>

                <button type="submit" style="width: 100%; background: #3b82f6; color: white; border: none; padding: 14px; border-radius: 12px; font-weight: 800;">Register</button>
            </form>
        </div>

        <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 15px;">Active Lecturers (<?php echo count($lecturers); ?>)</h3>
        
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php if (empty($lecturers)): ?>
                <div style="text-align: center; padding: 30px; background: var(--surface); border-radius: 20px;">
                    <p style="color: var(--text-muted); font-weight: 600; font-size: 13px;">No faculty members found.</p>
                </div>
            <?php else: ?>
                <?php foreach($lecturers as $lec): 
                    $avatar = $lec['avatar_url'] ? (strpos($lec['avatar_url'], 'http') === 0 ? $lec['avatar_url'] : BASE_URL . $lec['avatar_url']) : "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($lec['username']);
                ?>
                    <div style="background: var(--surface); padding: 15px; border-radius: 20px; border: 1.5px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="width: 40px; height: 40px; border-radius: 12px; overflow: hidden; background: #f1f5f9;">
                                <img src="<?php echo $avatar; ?>" width="100%" height="100%" style="object-fit: cover;">
                            </div>
                            <div>
                                <p style="font-size: 14px; font-weight: 800; color: var(--text-dark); margin: 0;"><?php echo htmlspecialchars($lec['fullname']); ?></p>
                                <p style="font-size: 11px; font-weight: 600; color: var(--text-muted); margin: 4px 0 0;">@<?php echo htmlspecialchars($lec['username']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
['addLecturerForm', 'addLecturerFormMobile'].forEach(id => {
    const form = document.getElementById(id);
    if (!form) return;
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i data-lucide="loader-2" class="spin"></i> Provisioning...';
        lucide.createIcons();

        try {
            const formData = new FormData(form);
            const res = await fetch(form.action, { method: 'POST', body: formData });
            const data = await res.json();

            if (data.status === 'success') {
                Swal.fire({
                    title: 'Provisioned!',
                    text: 'Lecturer account created successfully.',
                    icon: 'success',
                    confirmButtonColor: '#3b82f6',
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
