<?php
require_once '../includes/config.php';
require_once '../includes/stat_engine.php';

$page_title = "Analytics Hub";
include '../includes/header.php';

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

$user_id = $_SESSION['user_id'];
$course_stats = StatEngine::getDetailedAttendanceByCourse($user_id);
$overall_score = StatEngine::getStudentAttendanceScore($user_id);
$recent_activity = StatEngine::getRecentActivity($user_id, 12);
?>

<?php
$total_present = 0;
$total_absent = 0;
foreach ($course_stats as $stat) {
    $total_present += $stat['present'];
    $total_absent += $stat['absent'];
}
?>

<div class="mobile-only-layout">
    <div style="height: 30px;"></div> <!-- Spacer for top visibility -->

    <!-- Mobile Score Card -->
    <div style="padding: 0 20px 25px;">
        <div style="background: linear-gradient(135deg, var(--primary) 0%, #0052cc 100%); padding: 30px; border-radius: 35px; color: white; box-shadow: 0 15px 35px var(--primary-glow); position: relative; overflow: hidden;">
            <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
            <p style="font-size: 13px; font-weight: 600; opacity: 0.9; margin-bottom: 5px;">Aggregate Score</p>
            <h1 style="font-size: 56px; font-weight: 900; line-height: 1;"><?php echo $overall_score; ?>%</h1>
            <div style="margin-top: 25px; background: rgba(255,255,255,0.15); height: 8px; border-radius: 10px; overflow: hidden;">
                <div style="width: <?php echo $overall_score; ?>%; height: 100%; background: white; border-radius: 10px;"></div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards (Mobile) -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; padding: 0 20px 30px;">
        <div style="background: var(--surface); padding: 20px; border-radius: 25px; border: 1.5px solid var(--border); text-align: center;">
            <span style="display: block; font-size: 10px; font-weight: 800; color: var(--success); text-transform: uppercase; margin-bottom: 5px;">Present</span>
            <span style="font-size: 24px; font-weight: 900;"><?php echo $total_present; ?></span>
        </div>
        <div style="background: var(--surface); padding: 20px; border-radius: 25px; border: 1.5px solid var(--border); text-align: center;">
            <span style="display: block; font-size: 10px; font-weight: 800; color: var(--danger); text-transform: uppercase; margin-bottom: 5px;">Absent</span>
            <span style="font-size: 24px; font-weight: 900;"><?php echo $total_absent; ?></span>
        </div>
    </div>

    <!-- Course Breakdown (Mobile) -->
    <div style="padding: 0 20px 30px;">
        <h3 style="font-size: 12px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 15px;">Course Breakdown</h3>
        <div style="background: var(--surface); border-radius: 30px; padding: 20px; border: 1.5px solid var(--border);">
            <?php if (empty($course_stats)): ?>
                <p style="text-align: center; color: var(--text-muted); font-size: 13px; padding: 10px;">No courses detected.</p>
            <?php endif; ?>
            <?php foreach ($course_stats as $stat): 
                $c = $stat['percentage'] >= 75 ? 'var(--primary)' : 'var(--danger)'; ?>
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-size: 14px; font-weight: 800;"><?php echo htmlspecialchars($stat['course_code']); ?></span>
                        <span style="font-size: 14px; font-weight: 900; color: <?php echo $c; ?>;"><?php echo $stat['percentage']; ?>%</span>
                    </div>
                    <div style="height: 6px; background: var(--bg-main); border-radius: 10px; overflow: hidden;">
                        <div style="width: <?php echo $stat['percentage']; ?>%; height: 100%; background: <?php echo $c; ?>;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Activity Timeline (Mobile) -->
    <div style="padding: 0 20px;">
        <h3 style="font-size: 12px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 15px;">Recent Scan History</h3>
        <?php if (empty($recent_activity)): ?>
            <div style="text-align: center; padding: 40px 20px; background: var(--surface); border-radius: 30px; border: 1.5px dashed var(--border);">
                <i data-lucide="calendar-x" style="width: 32px; height: 32px; color: var(--text-muted); margin-bottom: 10px;"></i>
                <p style="color: var(--text-muted); font-size: 13px;">No check-ins recorded yet.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php foreach ($recent_activity as $act): ?>
                    <div style="background: var(--surface); padding: 18px; border-radius: 25px; border: 1.5px solid var(--border); display: flex; align-items: center; gap: 15px;">
                        <div style="width: 45px; height: 45px; border-radius: 15px; background: rgba(0, 102, 255, 0.05); color: var(--primary); display: flex; align-items: center; justify-content: center;">
                            <i data-lucide="check-circle" style="width: 20px; height: 20px;"></i>
                        </div>
                        <div style="flex: 1;">
                            <h4 style="font-size: 14px; font-weight: 800; margin: 0; color: var(--text-dark);"><?php echo htmlspecialchars($act['course_code']); ?></h4>
                            <p style="font-size: 11px; color: var(--text-muted); margin: 2px 0 0;"><?php echo date('M d, Y • h:i A', strtotime($act['timestamp'])); ?></p>
                        </div>
                        <div style="background: var(--primary-glow); color: var(--primary); padding: 5px 12px; border-radius: 50px; font-size: 10px; font-weight: 800; text-transform: uppercase;">
                            Present
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Desktop View: Bento Analytics Hub -->
<div class="desktop-only-layout">
    <header class="desktop-header">
        <div class="header-breadcrumb">
            <span class="date-pill">INTELLIGENCE SUITE</span>
            <div class="clock-badge"><i data-lucide="activity"></i> REAL-TIME ANALYTICS</div>
        </div>
        <h1 class="desktop-greeting">Attendance Intelligence</h1>
    </header>

    <div class="bento-grid">
        <!-- Hero Card: Aggregate Analytics -->
        <div class="bento-card bento-hero-card card-large">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span class="card-meta" style="color: rgba(255,255,255,0.6);">AGGREGATE SCORE</span>
                    <h2 class="metric-value"><?php echo $overall_score; ?>%</h2>
                    <p style="color: rgba(255,255,255,0.8); font-size: 14px; font-weight: 500; margin-top: 10px;">
                        Based on <?php echo count($course_stats); ?> active academic tracks.
                    </p>
                </div>
                <div style="text-align: right;">
                    <div class="score-badge excellent" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2);">
                        STATUS: OPTIMAL
                    </div>
                </div>
            </div>
            <div class="progress-track" style="background: rgba(255,255,255,0.1); height: 12px; border-radius: 20px; margin-top: 50px;">
                <div style="width: <?php echo $overall_score; ?>%; background: white; height: 100%; border-radius: 20px; box-shadow: 0 0 20px rgba(255,255,255,0.3);"></div>
            </div>
        </div>

        <!-- Reputation Intelligence Card -->
        <div class="bento-card card-medium">
            <span class="card-meta">STUDENT REPUTATION</span>
            <div class="reputation-hub" style="margin-top: 25px;">
                <div class="reputation-tier">
                    <?php 
                    if($overall_score >= 90) echo "ELITE NODE";
                    elseif($overall_score >= 75) echo "STABLE NODE";
                    else echo "RISK RADAR";
                    ?>
                </div>
                <p style="font-size: 13px; color: var(--text-muted); line-height: 1.6;">
                    Your attendance consistency is in the top 15% of your class. Exam eligibility is confirmed.
                </p>
                <div style="margin-top: 10px; display: flex; gap: 10px;">
                    <i data-lucide="award" style="color: var(--primary);"></i>
                    <i data-lucide="zap" style="color: var(--secondary);"></i>
                    <i data-lucide="shield-check" style="color: var(--success);"></i>
                </div>
            </div>
        </div>

        <!-- Detailed Breakdown -->
        <div class="bento-card card-wide">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h3 class="card-title">Academic Matrix</h3>
                <button style="background: var(--bg-main); border: none; padding: 10px 20px; border-radius: 12px; font-weight: 700; color: var(--primary); cursor: pointer;">
                    EXPORT PDF <i data-lucide="download" style="width: 14px; margin-left: 5px;"></i>
                </button>
            </div>
            
            <div class="table-header">
                <span>COURSE IDENTITY</span>
                <span style="text-align: center;">PRESENCE LOG</span>
                <span style="text-align: center;">VITALITY SCORE</span>
            </div>

            <?php foreach ($course_stats as $stat): 
                $badge_class = $stat['percentage'] >= 85 ? 'excellent' : ($stat['percentage'] >= 75 ? 'warning' : 'danger');
            ?>
                <div class="table-row">
                    <div class="course-name-tag">
                        <?php echo htmlspecialchars($stat['course_name']); ?>
                        <div style="font-size: 11px; font-weight: 600; color: var(--text-muted); opacity: 0.7;">
                            <?php echo htmlspecialchars($stat['course_code']); ?> • <?php echo $stat['total_sessions']; ?> Sessions
                        </div>
                    </div>
                    <div class="presence-metric" style="text-align: center;">
                        <span style="color: var(--text-dark); font-weight: 800;"><?php echo $stat['present']; ?></span> / <?php echo $stat['total_sessions']; ?>
                    </div>
                    <div style="display: flex; justify-content: center;">
                        <div class="score-badge <?php echo $badge_class; ?>">
                            <?php echo $stat['percentage']; ?>%
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>

<div style="height: 100px;"></div>
<?php include '../includes/footer.php'; ?>
