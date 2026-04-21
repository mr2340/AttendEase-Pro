<?php
require_once '../includes/config.php';
require_once '../includes/stat_engine.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    exit('Unauthorized');
}

$db = get_db_connection();
$parent_id = $_SESSION['user_id'];
$student_id = $_GET['id'] ?? null;

// Security: Verify the parent actually owns this link
$stmt = $db->prepare("SELECT id FROM parent_student_map WHERE parent_id = ? AND student_id = ?");
$stmt->execute([$parent_id, $student_id]);
if (!$stmt->fetch()) {
    exit('Unauthorized Access to Intel Node');
}

$course_stats = StatEngine::getDetailedAttendanceByCourse($student_id);
$recent_activity = StatEngine::getRecentActivity($student_id, 5);
?>

<div style="text-align: left; max-height: 70vh; overflow-y: auto; padding-right: 10px;">
    <h4 style="font-size: 14px; font-weight: 850; color: #94a3b8; text-transform: uppercase; margin-bottom: 20px; letter-spacing: 1px;">Academic Matrix Breakdown</h4>
    
    <?php foreach ($course_stats as $stat): 
        $c = $stat['percentage'] >= 75 ? '#10b981' : '#ef4444'; ?>
        <div style="background: #f8fafc; padding: 15px; border-radius: 20px; margin-bottom: 12px; border: 1px solid #f1f5f9;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <div>
                    <span style="font-size: 13px; font-weight: 800; color: #0f172a;"><?php echo htmlspecialchars($stat['course_code']); ?></span>
                    <span style="font-size: 10px; color: #94a3b8; margin-left: 5px;"><?php echo $stat['total_sessions']; ?> Sessions</span>
                </div>
                <span style="font-size: 14px; font-weight: 900; color: <?php echo $c; ?>;"><?php echo $stat['percentage']; ?>%</span>
            </div>
            <div style="height: 6px; background: #e2e8f0; border-radius: 10px; overflow: hidden;">
                <div style="width: <?php echo $stat['percentage']; ?>%; height: 100%; background: <?php echo $c; ?>;"></div>
            </div>
            <div style="display: flex; gap: 15px; margin-top: 10px;">
                <div style="font-size: 10px; font-weight: 700; color: #64748b;">
                    <span style="color: #10b981;">●</span> <?php echo $stat['present']; ?> Present
                </div>
                <div style="font-size: 10px; font-weight: 700; color: #64748b;">
                    <span style="color: #ef4444;">●</span> <?php echo $stat['absent']; ?> Absent
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <h4 style="font-size: 14px; font-weight: 850; color: #94a3b8; text-transform: uppercase; margin: 25px 0 15px; letter-spacing: 1px;">Recent Audit Log</h4>
    <?php if(empty($recent_activity)): ?>
        <p style="font-size: 13px; color: #94a3b8; text-align: center; padding: 20px;">No scan activity recorded.</p>
    <?php else: ?>
        <?php foreach($recent_activity as $act): ?>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9;">
                <div style="width: 32px; height: 32px; background: #ecfdf5; color: #10b981; border-radius: 10px; display: flex; justify-content: center; align-items: center;">
                    <i data-lucide="check-circle" style="width: 16px;"></i>
                </div>
                <div>
                    <p style="font-size: 13px; font-weight: 750; color: #0f172a; margin:0;">Check-in: <?php echo htmlspecialchars($act['course_code']); ?></p>
                    <p style="font-size: 10px; color: #94a3b8; font-weight: 600;"><?php echo date('M d, h:i A', strtotime($act['timestamp'])); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
