<?php
$page_title = "Session Intel";
include '../includes/header.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'lecturer' && $_SESSION['role'] !== 'admin')) {
    header("Location: login");
    exit;
}

$db = get_db_connection();
$session_id = $_GET['id'] ?? null;

if (!$session_id) {
    header("Location: reports");
    exit;
}

// Fetch Session & Course Details
$stmt = $db->prepare("
    SELECT s.*, c.course_name, c.course_code 
    FROM sessions s 
    JOIN courses c ON s.course_id = c.id 
    WHERE s.id = ?
");
$stmt->execute([$session_id]);
$session = $stmt->fetch();

if (!$session) {
    header("Location: reports");
    exit;
}

// Fetch Attended Students
$stmt = $db->prepare("
    SELECT u.full_name, u.username, a.created_at as marked_at 
    FROM attendance a 
    JOIN users u ON a.student_id = u.id 
    WHERE a.session_id = ?
    ORDER BY a.created_at ASC
");
$stmt->execute([$session_id]);
$attendees = $stmt->fetchAll();
?>

<section id="session-intel" class="screen" data-state="active" style="background: #f8fafc;">
    <div class="scrollable-content">
        <!-- Header -->
        <div style="padding: 20px 24px; display: flex; align-items: center; justify-content: space-between; background: white; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 100;">
            <a href="reports" style="width: 40px; height: 40px; background: #f1f5f9; border-radius: 12px; display: flex; justify-content: center; align-items: center; color: #0f172a;">
                <i data-lucide="chevron-left"></i>
            </a>
            <h2 style="font-weight: 850; font-size: 17px; letter-spacing: -0.5px; color: #0f172a;">Session Details</h2>
            <div style="width: 40px;"></div>
        </div>

        <!-- Session Overview Card -->
        <div style="padding: 24px;">
            <div style="background: white; padding: 25px; border-radius: 30px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                    <div style="width: 50px; height: 50px; background: #0066ff; color: white; border-radius: 16px; display: flex; justify-content: center; align-items: center;">
                        <i data-lucide="book-open"></i>
                    </div>
                    <div>
                        <h3 style="font-weight: 900; font-size: 18px; color: #0f172a;"><?php echo htmlspecialchars($session['course_name']); ?></h3>
                        <p style="font-size: 12px; font-weight: 700; color: #94a3b8;"><?php echo htmlspecialchars($session['course_code']); ?></p>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                    <div>
                        <p style="font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Date & Time</p>
                        <p style="font-size: 13px; font-weight: 700; color: #1e293b; margin-top: 3px;"><?php echo date('M d, Y • h:i A', strtotime($session['created_at'])); ?></p>
                    </div>
                    <div>
                        <p style="font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Total Present</p>
                        <p style="font-size: 13px; font-weight: 700; color: #10b981; margin-top: 3px;"><?php echo count($attendees); ?> Students</p>
                    </div>
                </div>
            </div>

            <!-- Attendance List -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; padding: 0 5px;">
                <h4 style="font-weight: 850; color: #0f172a; font-size: 16px;">Attendance List</h4>
                <button onclick="window.print()" style="background: none; border: none; color: #0066ff; font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 5px;">
                    <i data-lucide="download" style="width: 14px;"></i> Export PDF
                </button>
            </div>

            <?php if (empty($attendees)): ?>
                <div style="background: white; padding: 40px 20px; border-radius: 30px; text-align: center; border: 1px dashed #cbd5e1;">
                    <p style="color: #64748b; font-weight: 600;">No students recorded for this session.</p>
                </div>
            <?php else: ?>
                <div style="background: white; border-radius: 30px; border: 1px solid #e2e8f0; overflow: hidden;">
                    <?php foreach($attendees as $index => $stu): ?>
                        <div style="padding: 15px 20px; border-bottom: <?php echo ($index == count($attendees) - 1) ? 'none' : '1px solid #f1f5f9'; ?>; display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 35px; height: 35px; background: #f8fafc; border-radius: 10px; display: flex; justify-content: center; align-items: center; font-size: 12px; font-weight: 800; color: #64748b;">
                                    <?php echo $index + 1; ?>
                                </div>
                                <div>
                                    <h5 style="font-weight: 700; font-size: 14px; color: #1e293b;"><?php echo htmlspecialchars($stu['full_name']); ?></h5>
                                    <p style="font-size: 11px; color: #94a3b8; font-weight: 600;"><?php echo htmlspecialchars($stu['username']); ?></p>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <p style="font-size: 11px; font-weight: 700; color: #10b981;">Present</p>
                                <p style="font-size: 9px; color: #cbd5e1;"><?php echo date('h:i:s A', strtotime($stu['marked_at'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div style="height: 100px;"></div>
    </div>
</section>

<style>
@media print {
    body * { visibility: hidden; }
    #session-intel, #session-intel * { visibility: visible; }
    nav, button, a { display: none !important; }
    #session-intel { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>

<?php include '../includes/footer.php'; ?>
