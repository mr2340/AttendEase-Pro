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

$page_title = "Academic Catalog";
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

<div class="mobile-only-layout">
<section id="courses-catalog" class="screen" data-state="active" style="background: #f8fafc;">
    <div class="scrollable-content">
        <!-- Header -->
        <div style="padding: 40px 24px 20px;">
            <p style="font-weight: 850; color: var(--primary); text-transform: uppercase; letter-spacing: 2px; font-size: 11px; margin-bottom: 8px;">Faculty Resource</p>
            <h1 style="font-size: 36px; font-weight: 950; color: #0f172a; letter-spacing: -1.5px; line-height: 1;">Academic <span style="color: var(--primary);">Matrix</span></h1>
            <p style="color: #64748b; font-size: 14px; margin-top: 10px; font-weight: 500;">Manage your pedagogical nodes and student engagement.</p>
        </div>

        <!-- Global Stats Overlay -->
        <div style="padding: 0 24px; margin-bottom: 35px;">
            <div style="background: #0f172a; padding: 25px; border-radius: 35px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);">
                <div>
                    <h3 style="font-size: 24px; font-weight: 900; color: white;"><?php echo count($courses); ?></h3>
                    <p style="font-size: 11px; font-weight: 800; color: rgba(255,255,255,0.5); text-transform: uppercase;">Active Nodes</p>
                </div>
                <div>
                    <h3 style="font-size: 24px; font-weight: 900; color: var(--primary);">
                        <?php 
                            $total_students = 0;
                            foreach($courses as $c) $total_students += $c['student_count'];
                            echo $total_students;
                        ?>
                    </h3>
                    <p style="font-size: 11px; font-weight: 800; color: rgba(255,255,255,0.5); text-transform: uppercase;">Total Students</p>
                </div>
            </div>
        </div>

        <!-- Course List -->
        <div style="padding: 0 24px; display: flex; flex-direction: column; gap: 20px;">
            <?php foreach($courses as $c): ?>
                <div style="background: white; border-radius: 35px; border: 1.5px solid #e2e8f0; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                        <div style="flex: 1;">
                            <span style="background: #f1f5f9; color: #64748b; padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 900; text-transform: uppercase;"><?php echo htmlspecialchars($c['course_code']); ?></span>
                            <h3 style="font-size: 20px; font-weight: 900; color: #0f172a; margin-top: 10px; letter-spacing: -0.5px;"><?php echo htmlspecialchars($c['course_name']); ?></h3>
                        </div>
                        <button onclick="showCourseQR('<?php echo $c['permanent_token']; ?>', '<?php echo addslashes($c['course_code']); ?>', '<?php echo addslashes($c['course_name']); ?>')" style="width: 44px; height: 44px; background: var(--primary); color: white; border: none; border-radius: 14px; display: flex; justify-content: center; align-items: center; box-shadow: 0 8px 15px var(--primary-glow);">
                            <i data-lucide="qr-code" style="width: 20px;"></i>
                        </button>
                    </div>

                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px; padding: 15px; background: #f8fafc; border-radius: 20px;">
                        <div style="flex: 1;">
                            <p style="font-size: 10px; font-weight: 850; color: #94a3b8; text-transform: uppercase;">Engagement</p>
                            <p style="font-size: 15px; font-weight: 900; color: #0f172a;"><?php echo $c['student_count']; ?> Students</p>
                        </div>
                        <div style="width: 1.5px; height: 20px; background: #e2e8f0;"></div>
                        <div style="flex: 1;">
                            <p style="font-size: 10px; font-weight: 850; color: #94a3b8; text-transform: uppercase;">Status</p>
                            <p style="font-size: 15px; font-weight: 900; color: #10b981;">Online</p>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <a href="manage_schedule?course_id=<?php echo $c['id']; ?>" style="height: 50px; background: white; border: 1.5px solid #e2e8f0; border-radius: 16px; display: flex; align-items: center; justify-content: center; gap: 8px; color: #0f172a; font-weight: 800; font-size: 13px; text-decoration: none;">
                            <i data-lucide="calendar" style="width: 16px;"></i> Schedule
                        </a>
                        <a href="course_details?id=<?php echo $c['id']; ?>" style="height: 50px; background: #0f172a; color: white; border-radius: 16px; display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 800; font-size: 13px; text-decoration: none;">
                            <i data-lucide="bar-chart-3" style="width: 16px;"></i> Insights
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="height: 120px;"></div>
    </div>
</section>
</div>

<!-- Desktop Content: Master Matrix -->
<div class="desktop-only-layout" style="background: #f8fafc; min-height: 100vh;">
    <header style="padding: 70px 80px 40px; display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                <div style="background: var(--primary); width: 15px; height: 15px; border-radius: 4px; box-shadow: 0 0 15px var(--primary-glow);"></div>
                <span style="color: #64748b; font-size: 14px; font-weight: 900; letter-spacing: 2px; text-transform: uppercase;">Faculty Intelligence Unit</span>
            </div>
            <h1 style="font-size: 64px; font-weight: 950; color: #0f172a; letter-spacing: -3px; line-height: 1;">Academic <span style="color: var(--primary);">Portfolio</span></h1>
            <p style="color: #94a3b8; font-size: 20px; font-weight: 500; margin-top: 15px;">A unified control plane for your instructional nodes and student datasets.</p>
        </div>
        <div style="display: flex; gap: 30px;">
             <div style="background: white; padding: 25px 40px; border-radius: 30px; border: 1.5px solid #f1f5f9; display: flex; align-items: center; gap: 25px;">
                <div style="width: 60px; height: 60px; background: #ecfdf5; color: #10b981; border-radius: 20px; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="users" style="width: 32px;"></i>
                </div>
                <div>
                    <p style="font-size: 12px; color: #94a3b8; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;">Managed Fleet</p>
                    <p style="font-size: 28px; font-weight: 950; color: #0f172a;"><?php echo $total_students; ?> <span style="font-size: 14px; color: #94a3b8;">Students</span></p>
                </div>
            </div>
        </div>
    </header>

    <div style="padding: 0 80px 80px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(450px, 1fr)); gap: 35px;">
            <?php foreach($courses as $c): ?>
                <div style="background: white; border-radius: 50px; border: 1.5px solid #f1f5f9; overflow: hidden; box-shadow: 0 30px 80px rgba(0,0,0,0.03); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); cursor: pointer; position: relative;" 
                     onmouseover="this.style.transform='translateY(-10px)'; this.style.borderColor='var(--primary)';" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='#f1f5f9';">
                    
                    <div style="padding: 45px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 35px;">
                            <div style="width: 70px; height: 70px; background: #f8fafc; color: var(--primary); border-radius: 24px; display: flex; align-items: center; justify-content: center;">
                                <i data-lucide="layers" style="width: 32px;"></i>
                            </div>
                            <div style="text-align: right;">
                                <span style="background: #eff6ff; color: var(--primary); padding: 8px 18px; border-radius: 12px; font-size: 13px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;">
                                    <?php echo htmlspecialchars($c['course_code']); ?>
                                </span>
                                <p style="font-size: 12px; color: #94a3b8; font-weight: 800; margin-top: 8px;">Master Node: Active</p>
                            </div>
                        </div>
                        
                        <h2 style="font-size: 32px; font-weight: 950; color: #0f172a; margin-bottom: 20px; letter-spacing: -1.5px; line-height: 1.1;">
                            <?php echo htmlspecialchars($c['course_name']); ?>
                        </h2>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-top: 40px; padding-top: 40px; border-top: 2px dashed #f1f5f9;">
                            <div>
                                <p style="font-size: 12px; color: #94a3b8; font-weight: 900; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px;">Student Volume</p>
                                <p style="font-size: 24px; font-weight: 950; color: #0f172a;"><?php echo $c['student_count']; ?></p>
                            </div>
                            <div>
                                <p style="font-size: 12px; color: #94a3b8; font-weight: 900; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px;">Last Broadcast</p>
                                <p style="font-size: 24px; font-weight: 950; color: #10b981;">
                                    <?php echo $c['last_session'] ? date('M d', strtotime($c['last_session'])) : 'NEVER'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: #0f172a; padding: 30px 45px; display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; gap: 20px;">
                            <a href="course_details?id=<?php echo $c['id']; ?>" style="color: rgba(255,255,255,0.7); text-decoration: none; font-weight: 800; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                                <i data-lucide="line-chart" style="width: 18px;"></i> Analytics
                            </a>
                            <a href="manage_schedule?course_id=<?php echo $c['id']; ?>" style="color: rgba(255,255,255,0.7); text-decoration: none; font-weight: 800; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                                <i data-lucide="clock" style="width: 18px;"></i> Schedule
                            </a>
                        </div>
                        <button onclick="showCourseQR('<?php echo $c['permanent_token']; ?>', '<?php echo addslashes($c['course_code']); ?>', '<?php echo addslashes($c['course_name']); ?>')" class="btn-primary" style="padding: 12px 30px; border-radius: 14px; font-size: 14px; font-weight: 900; border: none; cursor: pointer;">
                            <i data-lucide="qr-code" style="width: 18px; margin-right: 8px; vertical-align: middle;"></i> Master Node
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function showCourseQR(token, code, name) {
    Swal.fire({
        title: `<div style="font-weight:950; letter-spacing:-1.5px; font-size:28px;">Master Gateway: ${code}</div>`,
        html: `
            <div style="text-align: center; padding: 20px;">
                <p style="color: #64748b; font-size: 15px; margin-bottom: 30px; font-weight: 600; line-height:1.6;">Provide this permanent gateway for students to scan when any attendance node is live.</p>
                <div id="cat-qrcode" style="display: flex; justify-content: center; margin-bottom: 30px; background: #f8fafc; padding: 30px; border-radius: 40px; border: 3px solid #e2e8f0; box-shadow: inset 0 2px 10px rgba(0,0,0,0.05);"></div>
                <div style="background: #0f172a; padding: 20px; border-radius: 20px; color: white; font-size: 14px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 10px;">
                    <div style="width: 8px; height: 8px; background: #22c55e; border-radius: 50%; box-shadow: 0 0 10px #22c55e;"></div>
                    SECURE BROADCAST ACTIVE
                </div>
            </div>
        `,
        width: 550,
        showConfirmButton: true,
        confirmButtonText: 'Exit Terminal',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'btn-primary swal2-confirm'
        },
        didOpen: () => {
            if (typeof QRCode !== 'undefined') {
                new QRCode(document.getElementById("cat-qrcode"), {
                    text: token,
                    width: 280,
                    height: 280,
                    colorDark: "#0f172a",
                    colorLight: "#f8fafc",
                    correctLevel: QRCode.CorrectLevel.H
                });
            }
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>
