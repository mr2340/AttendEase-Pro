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

$page_title = "Admin Matrix";
include '../includes/header.php';

$db = get_db_connection();

// Logic for search
$search = $_GET['q'] ?? '';
$where = '';
$params = [];
if ($search) {
    $where = " AND (fullname LIKE ? OR student_id LIKE ? OR username LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// Fetch all students
$stmt = $db->prepare("SELECT * FROM users WHERE role = 'student' $where ORDER BY fullname ASC");
$stmt->execute($params);
$students = $stmt->fetchAll();

// Fetch all parents
$stmt = $db->prepare("SELECT * FROM users WHERE role = 'parent' $where ORDER BY fullname ASC");
$stmt->execute($params);
$parents = $parents ?? $stmt->fetchAll();

// Fetch all existing mappings
$stmt = $db->query("
    SELECT m.id, p.fullname as parent_name, s.fullname as student_name, s.student_id as student_reg
    FROM parent_student_map m
    JOIN users p ON m.parent_id = p.id
    JOIN users s ON m.student_id = s.id
    ORDER BY m.id DESC
");
$mappings = $stmt->fetchAll();
?>

<div class="desktop-only-layout" style="background: #f1f5f9; min-height: 100vh;">
    <header style="padding: 60px 80px 40px; display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
                <div style="background: #ef4444; width: 12px; height: 12px; border-radius: 4px;"></div>
                <span style="color: #64748b; font-size: 13px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase;">Infrastructure Command</span>
            </div>
            <h1 style="font-size: 56px; font-weight: 950; color: #0f172a; letter-spacing: -2.5px;">Guardian <span style="color: #ef4444;">Infrastructure</span></h1>
            <p style="color: #94a3b8; font-size: 18px; font-weight: 500; margin-top: 10px;">Manage parent-student associations and security oversight.</p>
        </div>
        <div style="display: flex; gap: 20px;">
             <form action="" method="GET" style="position: relative;">
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search users..." style="padding: 15px 25px 15px 50px; border-radius: 20px; border: 1.5px solid #e2e8f0; width: 300px; font-weight: 600;">
                <i data-lucide="search" style="position: absolute; left: 20px; top: 15px; width: 20px; color: #94a3b8;"></i>
             </form>
        </div>
    </header>

    <div style="padding: 0 80px 80px; display: grid; grid-template-columns: 8fr 4fr; gap: 40px; align-items: start;">
        
        <!-- Existing Mappings Matrix -->
        <div style="background: white; border-radius: 50px; padding: 50px; border: 1.5px solid #f1f5f9; box-shadow: 0 25px 60px rgba(0,0,0,0.03);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
                <h3 style="font-size: 26px; font-weight: 950; color: #0f172a; letter-spacing: -1px;">Active Associations</h3>
                <div style="background: #f8fafc; padding: 10px 20px; border-radius: 12px; font-size: 14px; font-weight: 800; color: #64748b;">
                    <?php echo count($mappings); ?> Mappings Discovered
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php if (empty($mappings)): ?>
                    <div style="text-align: center; padding: 60px; background: #f8fafc; border-radius: 35px; border: 2px dashed #e2e8f0;">
                         <i data-lucide="link-2-off" style="width: 48px; height: 48px; color: #cbd5e1; margin-bottom: 20px;"></i>
                         <p style="color: #64748b; font-weight: 700;">No active associations found in infrastructure.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($mappings as $map): ?>
                        <div style="background: #fff; padding: 25px 35px; border-radius: 30px; border: 1.5px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; transition: all 0.2s ease;">
                            <div style="display: flex; align-items: center; gap: 30px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 10px; height: 10px; background: #10b981; border-radius: 50%;"></div>
                                    <div>
                                        <p style="font-size: 10px; font-weight: 850; color: #94a3b8; text-transform: uppercase;">Guardian</p>
                                        <p style="font-size: 16px; font-weight: 800; color: #0f172a;"><?php echo htmlspecialchars($map['parent_name']); ?></p>
                                    </div>
                                </div>
                                <div style="color: #cbd5e1;">
                                    <i data-lucide="arrow-right-left"></i>
                                </div>
                                <div>
                                    <p style="font-size: 10px; font-weight: 850; color: #94a3b8; text-transform: uppercase;">Student</p>
                                    <p style="font-size: 16px; font-weight: 800; color: #0f172a;"><?php echo htmlspecialchars($map['student_name']); ?> <span style="color: #64748b; font-size: 12px; font-weight: 600;">(<?php echo $map['student_reg']; ?>)</span></p>
                                </div>
                            </div>
                            <button onclick="deleteMapping(<?php echo $map['id']; ?>)" style="background: #fee2e2; color: #ef4444; border: none; width: 44px; height: 44px; border-radius: 14px; cursor: pointer; display: flex; justify-content: center; align-items: center;">
                                <i data-lucide="trash-2" style="width: 20px;"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Deployment Console: Create Mapping -->
        <div style="background: white; border-radius: 50px; padding: 45px; border: 1.5px solid #f1f5f9; box-shadow: 0 25px 60px rgba(0,0,0,0.03); position: sticky; top: 40px;">
            <h3 style="font-size: 24px; font-weight: 950; color: #0f172a; letter-spacing: -0.5px; margin-bottom: 35px;">Deploy New Association</h3>
            
            <form id="associationForm" style="display: flex; flex-direction: column; gap: 25px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 850; color: #94a3b8; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 1px;">Target Guardian</label>
                    <select name="parent_id" style="width: 100%; height: 55px; background: #f8fafc; border: 1.5px solid #f1f5f9; border-radius: 18px; padding: 0 20px; font-weight: 700; font-family: inherit; font-size: 15px; color: #0f172a;">
                        <option value="">Select Parent...</option>
                        <?php foreach($parents as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['fullname']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 850; color: #94a3b8; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 1px;">Target Student</label>
                    <select name="student_id" style="width: 100%; height: 55px; background: #f8fafc; border: 1.5px solid #f1f5f9; border-radius: 18px; padding: 0 20px; font-weight: 700; font-family: inherit; font-size: 15px; color: #0f172a;">
                        <option value="">Select Student...</option>
                        <?php foreach($students as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['fullname']); ?> (<?php echo $s['student_id']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="background: #fef2f2; padding: 25px; border-radius: 28px; border: 1.5px solid #fee2e2;">
                    <p style="font-size: 13px; font-weight: 700; color: #991b1b; line-height: 1.6;">
                        <i data-lucide="alert-triangle" style="width: 16px; margin-right: 5px; vertical-align: middle;"></i>
                        Linking will grant the guardian real-time access to the student's attendance vital signs.
                    </p>
                </div>

                <button type="submit" class="btn-primary" style="background: #0f172a; height: 65px; border-radius: 20px; font-size: 16px; font-weight: 900; box-shadow: 0 15px 35px rgba(0,0,0,0.1);">
                    Seal Association
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Mobile Interface -->
<div class="mobile-only-layout">
    <div class="dash-header" style="margin-bottom: 20px;">
        <div>
            <h2 style="font-weight: 800; font-size: 22px;">Guardian <span style="color: #ef4444;">Matrix</span></h2>
            <p style="font-size: 13px; color: var(--text-muted); font-weight: 600;">Infrastructure Command</p>
        </div>
    </div>

    <div style="padding: 0 20px 30px;">
        <!-- Add Association Button -->
        <button onclick="document.getElementById('mobileAssociationForm').style.display='block'" style="width: 100%; background: #ef4444; color: white; border: none; padding: 15px; border-radius: 15px; font-weight: 800; font-size: 15px; margin-bottom: 25px; display: flex; align-items: center; justify-content: center; gap: 8px;">
            <i data-lucide="link" style="width: 18px;"></i>
            Deploy Association
        </button>

        <!-- Association Form (Hidden by default) -->
        <div id="mobileAssociationForm" style="display: none; background: var(--surface); padding: 20px; border-radius: 20px; border: 1.5px solid var(--border); margin-bottom: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h4 style="font-weight: 800; font-size: 15px; margin: 0;">New Link</h4>
                <button onclick="document.getElementById('mobileAssociationForm').style.display='none'" style="background: none; border: none; color: var(--text-muted);"><i data-lucide="x"></i></button>
            </div>
            
            <form id="associationFormMobile" style="display: flex; flex-direction: column; gap: 15px;">
                <div>
                    <label style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Guardian</label>
                    <select name="parent_id" style="width: 100%; padding: 12px; border-radius: 12px; border: 1.5px solid var(--border); font-weight: 600;">
                        <option value="">Select Parent...</option>
                        <?php foreach($parents as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['fullname']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Student</label>
                    <select name="student_id" style="width: 100%; padding: 12px; border-radius: 12px; border: 1.5px solid var(--border); font-weight: 600;">
                        <option value="">Select Student...</option>
                        <?php foreach($students as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['fullname']); ?> (<?php echo $s['student_id']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" style="width: 100%; background: #0f172a; color: white; border: none; padding: 14px; border-radius: 12px; font-weight: 800;">Seal Link</button>
            </form>
        </div>

        <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 15px;">Active Associations</h3>
        
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php if (empty($mappings)): ?>
                <div style="text-align: center; padding: 30px; background: var(--surface); border-radius: 20px;">
                    <p style="color: var(--text-muted); font-weight: 600; font-size: 13px;">No active links.</p>
                </div>
            <?php else: ?>
                <?php foreach($mappings as $map): ?>
                    <div style="background: var(--surface); padding: 15px; border-radius: 20px; border: 1.5px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <p style="font-size: 10px; font-weight: 800; color: #ef4444; margin: 0; text-transform: uppercase;">Guardian: <?php echo htmlspecialchars($map['parent_name']); ?></p>
                            <p style="font-size: 14px; font-weight: 800; color: var(--text-dark); margin: 4px 0 0;"><?php echo htmlspecialchars($map['student_name']); ?></p>
                            <p style="font-size: 11px; font-weight: 600; color: var(--text-muted); margin: 0;"><?php echo $map['student_reg']; ?></p>
                        </div>
                        <button onclick="deleteMapping(<?php echo $map['id']; ?>)" style="background: #fee2e2; color: #ef4444; border: none; width: 36px; height: 36px; border-radius: 10px; display: flex; justify-content: center; align-items: center;">
                            <i data-lucide="trash-2" style="width: 16px;"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();

    const forms = [document.getElementById('associationForm'), document.getElementById('associationFormMobile')];
    forms.forEach(form => {
        if (!form) return;
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = Object.fromEntries(new FormData(form).entries());
            
            if (!data.parent_id || !data.student_id) {
                Swal.fire('Payload Incomplete', 'Both nodes (Guardian & Student) must be selected.', 'warning');
                return;
            }

            try {
                const res = await fetch('process_mapping.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (result.success) {
                    Swal.fire('Association Sealed', 'The secure link is now active.', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Process Error', result.message, 'error');
                }
            } catch (err) {
                Swal.fire('Network Error', 'Connection to infrastructure failed.', 'error');
            }
        });
    });
});

async function deleteMapping(id) {
    const confirm = await Swal.fire({
        title: 'Sever Link?',
        text: 'This will instantly revoke guardian access to student data.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Sever Link',
        buttonsStyling: false,
        customClass: { confirmButton: 'btn-primary swal2-confirm', cancelButton: 'swal2-cancel' }
    });

    if (confirm.isConfirmed) {
        const res = await fetch('process_mapping.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const result = await res.json();
        if (result.success) location.reload();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
