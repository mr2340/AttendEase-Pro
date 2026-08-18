<?php
require_once '../includes/config.php';
require_once '../includes/stat_engine.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth Check - Ensure user is a parent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../student/login"); // Or a dedicated parent login if exists
    exit;
}

$db = get_db_connection();
$parent_id = $_SESSION['user_id'];

// Fetch Linked Students
$stmt = $db->prepare("
    SELECT u.* FROM users u 
    JOIN parent_student_map m ON u.id = m.student_id 
    WHERE m.parent_id = ?
");
$stmt->execute([$parent_id]);
$students = $stmt->fetchAll();

$page_title = "Guardian Hub";
include '../includes/header.php';
?>

<section id="parent-portal" class="screen" data-state="active" style="background: #f1f5f9; min-height: 100vh;">
    <div class="scrollable-content">
        <!-- Premium Header -->
        <div style="padding: 40px 24px 20px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                <span style="background: #0ea5e9; color: white; padding: 4px 12px; border-radius: 100px; font-size: 10px; font-weight: 800; letter-spacing: 1px;">GUARDIAN ACCESS</span>
            </div>
            <h1 style="font-size: 36px; font-weight: 900; color: #0f172a; letter-spacing: -1.5px;">Guardian <span style="color: #0ea5e9;">Intelligence</span></h1>
            <p style="color: #64748b; font-size: 15px; font-weight: 500; margin-top: 5px;">Secure oversight for your children's academic vitality.</p>
        </div>

        <div style="padding: 0 24px;">
            <?php if (empty($students)): ?>
                <!-- Empty State: No Students Linked -->
                <div style="background: white; padding: 50px 30px; border-radius: 40px; text-align: center; border: 2px dashed #cbd5e1; margin-top: 20px;">
                    <div style="width: 80px; height: 80px; background: #f8fafc; color: #cbd5e1; border-radius: 30px; display: flex; justify-content: center; align-items: center; margin: 0 auto 20px;">
                        <i data-lucide="user-plus" style="width: 40px; height: 40px;"></i>
                    </div>
                    <h3 style="font-size: 20px; font-weight: 850; color: #1e293b; margin-bottom: 10px;">No Students Linked</h3>
                    <p style="color: #64748b; font-size: 14px; line-height: 1.6; margin-bottom: 25px;">Enter your child's unique Matric Number to begin monitoring their attendance and academic performance.</p>
                    <button onclick="openLinkModal()" class="btn-primary" style="background: #0f172a; border-radius: 20px; height: 60px; font-size: 15px; font-weight: 800;">
                        Link Matric Number
                    </button>
                </div>
            <?php else: ?>
                <!-- List of Linked Students -->
                <div style="display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 30px;">
                    <?php foreach($students as $student): 
                        $score = StatEngine::getStudentAttendanceScore($student['id']);
                        $status_color = ($score >= 75) ? '#10b981' : '#ef4444';
                        $status_label = ($score >= 75) ? 'OPTIMAL' : 'AT RISK';
                    ?>
                        <div style="background: white; border-radius: 35px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <div style="width: 55px; height: 55px; border-radius: 20px; overflow: hidden; border: 2px solid #f1f5f9;">
                                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo htmlspecialchars($student['username']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <div>
                                        <h3 style="font-size: 18px; font-weight: 900; color: #0f172a;"><?php echo htmlspecialchars($student['fullname']); ?></h3>
                                        <p style="font-size: 12px; color: #94a3b8; font-weight: 700;"><?php echo htmlspecialchars($student['student_id']); ?></p>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <span style="background: <?php echo $status_color; ?>15; color: <?php echo $status_color; ?>; padding: 6px 14px; border-radius: 100px; font-size: 10px; font-weight: 900;"><?php echo $status_label; ?></span>
                                </div>
                            </div>

                            <!-- Attendance Metric -->
                            <div style="background: #f8fafc; padding: 20px; border-radius: 25px; border: 1px solid #f1f5f9; margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 10px;">
                                    <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Semester Turnout</span>
                                    <span style="font-size: 24px; font-weight: 900; color: #0f172a;"><?php echo $score; ?>%</span>
                                </div>
                                <div style="height: 8px; background: #e2e8f0; border-radius: 10px; overflow: hidden;">
                                    <div style="width: <?php echo $score; ?>%; height: 100%; background: <?php echo $status_color; ?>; border-radius: 10px;"></div>
                                </div>
                            </div>

                            <button onclick="viewStudentDetail(<?php echo $student['id']; ?>, '<?php echo addslashes($student['fullname']); ?>')" class="btn-primary" style="background: #f1f5f9; color: #0f172a; border-radius: 20px; font-size: 13px; font-weight: 800; border: 1px solid #e2e8f0;">
                                Detailed Intel <i data-lucide="arrow-right" style="width: 16px; margin-left: 5px;"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>

                    <button onclick="openLinkModal()" style="background: none; border: 2px dashed #cbd5e1; padding: 20px; border-radius: 30px; color: #64748b; font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 10px; cursor: pointer;">
                        <i data-lucide="plus" style="width: 18px;"></i> Link another student
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <div style="height: 100px;"></div>
    </div>

    <!-- Navigation is handled by footer.php -->
</section>

<script>
function openLinkModal() {
    Swal.fire({
        title: 'Link Student',
        input: 'text',
        inputLabel: "Child's Registration ID",
        inputPlaceholder: 'e.g. STU-2024-001',
        showCancelButton: true,
        confirmButtonText: 'Verify & Link',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'btn-primary swal2-confirm',
            cancelButton: 'swal2-cancel'
        },
        showLoaderOnConfirm: true,
        preConfirm: (studentId) => {
            return fetch('link_student.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ student_id: studentId })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message)
                }
                return data;
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error}`)
            })
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Linked!',
                text: 'Student has been successfully linked to your profile.',
                icon: 'success'
            }).then(() => location.reload());
        }
    })
}

function viewStudentDetail(id, name) {
    // Redirect to a detailed view or show a modal with StatEngine breakdown
    Swal.fire({
        title: name + "'s Intel",
        html: '<div id="detail-loading" class="loader"></div>',
        width: '90%',
        padding: '20px',
        showConfirmButton: false,
        didOpen: () => {
            fetch(`get_student_intel.php?id=${id}`)
                .then(r => r.text())
                .then(html => {
                    document.getElementById('swal2-html-container').innerHTML = html;
                    if (window.lucide) lucide.createIcons();
                });
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>

<?php include '../includes/footer.php'; ?>
