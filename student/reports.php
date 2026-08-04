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
    <div style="height: 30px;"></div>

    <!-- Mobile Hero Card -->
    <div style="padding: 0 20px 25px;">
        <div style="background: linear-gradient(135deg, #0062ff 0%, #00d2ff 100%); border-radius: 35px; padding: 30px; position: relative; overflow: hidden; box-shadow: 0 20px 40px rgba(0, 102, 255, 0.3);">
            <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%); filter: blur(20px);"></div>
            
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: 11px; font-weight: 800; color: rgba(255,255,255,0.8); text-transform: uppercase; letter-spacing: 1.5px;">Aggregate Score</span>
                    <div style="font-size: 56px; font-weight: 900; color: white; line-height: 1; margin: 10px 0; letter-spacing: -2px;"><?php echo $overall_score; ?>%</div>
                </div>
            </div>
            
            <!-- Dynamic Ring Progress -->
            <div style="margin-top: 20px; display: flex; align-items: center; gap: 15px;">
                <svg width="40" height="40" viewBox="0 0 36 36" style="transform: rotate(-90deg);">
                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="4" />
                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="white" stroke-width="4" stroke-dasharray="<?php echo $overall_score; ?>, 100" />
                </svg>
                <div style="flex: 1;">
                    <div style="font-size: 12px; color: #cbd5e1; font-weight: 600;">Status</div>
                    <div style="font-size: 14px; font-weight: 800; color: white;">
                        <?php echo $overall_score >= 75 ? 'Safe / Eligible' : 'At Risk'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards (Mobile) -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; padding: 0 20px 30px;">
        <div style="background: white; padding: 20px; border-radius: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); text-align: center; border: 1px solid rgba(0,0,0,0.05);">
            <span style="display: block; font-size: 11px; font-weight: 800; color: #10b981; text-transform: uppercase; margin-bottom: 5px; letter-spacing: 1px;">Present</span>
            <span style="font-size: 28px; font-weight: 900; color: #0f172a;"><?php echo $total_present; ?></span>
        </div>
        <div style="background: white; padding: 20px; border-radius: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); text-align: center; border: 1px solid rgba(0,0,0,0.05);">
            <span style="display: block; font-size: 11px; font-weight: 800; color: #ef4444; text-transform: uppercase; margin-bottom: 5px; letter-spacing: 1px;">Absent</span>
            <span style="font-size: 28px; font-weight: 900; color: #0f172a;"><?php echo $total_absent; ?></span>
        </div>
    </div>

    <!-- Course Breakdown (Mobile) -->
    <div style="padding: 0 20px 30px;">
        <h3 style="font-size: 13px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 1px;">Course Breakdown</h3>
        <div style="background: white; border-radius: 30px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02);">
            <?php if (empty($course_stats)): ?>
                <p style="text-align: center; color: #94a3b8; font-size: 14px; padding: 20px; font-weight: 500;">No courses detected.</p>
            <?php endif; ?>
            <?php foreach ($course_stats as $stat): 
                $c = $stat['percentage'] >= 75 ? '#0062ff' : '#ef4444'; 
            ?>
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-size: 15px; font-weight: 800; color: #1e293b;"><?php echo htmlspecialchars($stat['course_code']); ?></span>
                        <span style="font-size: 15px; font-weight: 900; color: <?php echo $c; ?>;"><?php echo $stat['percentage']; ?>%</span>
                    </div>
                    <div style="height: 8px; background: #f1f5f9; border-radius: 10px; overflow: hidden;">
                        <div style="width: <?php echo $stat['percentage']; ?>%; height: 100%; background: <?php echo $c; ?>; border-radius: 10px;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Activity Timeline (Mobile) -->
    <div style="padding: 0 20px;">
        <h3 style="font-size: 13px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 1px;">Recent Check-Ins</h3>
        <?php if (empty($recent_activity)): ?>
            <div style="text-align: center; padding: 40px 20px; background: white; border-radius: 30px; border: 2px dashed #e2e8f0;">
                <i data-lucide="history" style="width: 32px; height: 32px; color: #cbd5e1; margin-bottom: 10px;"></i>
                <p style="color: #94a3b8; font-size: 14px; font-weight: 500;">No history found.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php foreach ($recent_activity as $act): ?>
                    <div style="background: white; padding: 20px; border-radius: 25px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02); display: flex; align-items: center; gap: 15px;">
                        <div style="width: 50px; height: 50px; border-radius: 15px; background: rgba(0, 98, 255, 0.08); color: #0062ff; display: flex; align-items: center; justify-content: center;">
                            <i data-lucide="check-circle-2" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div style="flex: 1;">
                            <h4 style="font-size: 15px; font-weight: 800; margin: 0; color: #0f172a;"><?php echo htmlspecialchars($act['course_code']); ?></h4>
                            <p style="font-size: 12px; font-weight: 500; color: #64748b; margin: 4px 0 0;"><?php echo date('M d • h:i A', strtotime($act['timestamp'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Desktop View: Bento Analytics Hub -->
<div class="desktop-only-layout">
    <header class="desktop-header" style="margin-bottom: 40px;">
        <div class="header-breadcrumb" style="display: flex; gap: 10px; margin-bottom: 15px;">
            <span class="date-pill" style="background: rgba(0,98,255,0.1); color: #0062ff; padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase;">Intelligence Suite</span>
            <div class="clock-badge" style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; display: flex; align-items: center; gap: 6px;"><i data-lucide="activity" style="width:12px; height:12px;"></i> Real-Time</div>
        </div>
        <h1 class="desktop-greeting" style="font-size: 32px; font-weight: 900; color: #0f172a; letter-spacing: -1px; margin: 0;">Attendance Intelligence</h1>
    </header>

    <div class="bento-grid">
        
        <!-- Hero Card: Aggregate Analytics (Span 8) -->
        <div class="bento-card bento-hero-card card-large" style="display: flex; flex-direction: column; justify-content: center; position: relative; overflow: hidden; padding: 40px;">
            <div style="position: absolute; right: -80px; top: -80px; opacity: 0.05; pointer-events: none;">
                <i data-lucide="pie-chart" style="width: 400px; height: 400px; color: white;"></i>
            </div>
            
            <div style="display: flex; gap: 50px; align-items: center; z-index: 1;">
                <div style="position: relative; width: 180px; height: 180px; flex-shrink: 0;">
                    <svg viewBox="0 0 36 36" style="transform: rotate(-90deg); width: 100%; height: 100%;">
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="2.5" />
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="white" stroke-width="2.5" stroke-dasharray="<?php echo $overall_score; ?>, 100" style="filter: drop-shadow(0 0 12px rgba(255, 255, 255, 0.4)); transition: stroke-dasharray 1s ease-out;" />
                    </svg>
                    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                        <span style="font-size: 42px; font-weight: 900; color: white; line-height: 1; letter-spacing: -2px;"><?php echo $overall_score; ?><span style="font-size: 20px; color: rgba(255,255,255,0.8);">%</span></span>
                    </div>
                </div>

                <div style="flex: 1;">
                    <div style="display: inline-block; padding: 6px 14px; background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 100px; margin-bottom: 20px;">
                        <span style="font-size: 11px; font-weight: 800; color: white; text-transform: uppercase; letter-spacing: 1.5px;">Aggregate Analytics</span>
                    </div>
                    
                    <div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); padding: 25px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.3); box-shadow: inset 0 0 20px rgba(255,255,255,0.1);">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                            <i data-lucide="shield-check" style="color: white; width: 20px; height: 20px;"></i>
                            <h4 style="color: white; font-size: 17px; font-weight: 800; margin: 0; letter-spacing: -0.5px;">Status: Optimal</h4>
                        </div>
                        <p style="color: rgba(255,255,255,0.9); font-size: 15px; margin: 0; font-weight: 500; line-height: 1.6;">
                            Based on <?php echo count($course_stats); ?> active academic tracks. Your overall presence is solid.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reputation Hub (Span 4) -->
        <div class="bento-card card-medium" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1.5px;">Student Reputation</span>
                <div style="font-size: 28px; font-weight: 900; color: #0f172a; margin: 15px 0 10px; letter-spacing: -1px;">
                    <?php 
                    if($overall_score >= 90) echo "Elite Node";
                    elseif($overall_score >= 75) echo "Stable Node";
                    else echo "Risk Radar";
                    ?>
                </div>
                <p style="font-size: 14px; font-weight: 500; color: #64748b; line-height: 1.6; margin: 0;">
                    Your attendance consistency is robust. Exam eligibility is confirmed across active courses.
                </p>
            </div>
            
            <div style="margin-top: 25px; display: flex; gap: 15px;">
                <div style="width: 50px; height: 50px; border-radius: 15px; background: rgba(0, 98, 255, 0.08); display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="award" style="color: #0062ff; width: 24px;"></i>
                </div>
                <div style="width: 50px; height: 50px; border-radius: 15px; background: rgba(16, 185, 129, 0.08); display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="shield-check" style="color: #10b981; width: 24px;"></i>
                </div>
            </div>
        </div>

        <!-- Academic Matrix / Detailed Breakdown (Span 12) -->
        <div class="bento-card card-wide" style="padding: 35px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h3 style="font-size: 18px; font-weight: 900; color: #0f172a; margin: 0; letter-spacing: -0.5px;">Academic Matrix</h3>
                <button style="background: rgba(0, 98, 255, 0.1); border: none; padding: 10px 20px; border-radius: 12px; font-weight: 800; font-size: 12px; color: #0062ff; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s;">
                    EXPORT PDF <i data-lucide="download" style="width: 14px;"></i>
                </button>
            </div>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; padding-bottom: 15px; border-bottom: 2px solid #f1f5f9; margin-bottom: 15px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">Course Identity</span>
                <span style="text-align: center; font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">Presence Log</span>
                <span style="text-align: right; font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">Vitality Score</span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 10px;">
                <?php foreach ($course_stats as $stat): 
                    $badge_bg = $stat['percentage'] >= 75 ? 'rgba(0, 98, 255, 0.1)' : 'rgba(239, 68, 68, 0.1)';
                    $badge_color = $stat['percentage'] >= 75 ? '#0062ff' : '#ef4444';
                ?>
                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; align-items: center; padding: 15px; border-radius: 16px; background: white; border: 1px solid #f1f5f9; transition: transform 0.2s, box-shadow 0.2s;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="width: 45px; height: 45px; border-radius: 12px; background: #f8fafc; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #0f172a; font-size: 12px;">
                                <?php echo htmlspecialchars($stat['course_code']); ?>
                            </div>
                            <div>
                                <div style="font-weight: 800; color: #0f172a; font-size: 15px;"><?php echo htmlspecialchars($stat['course_name']); ?></div>
                                <div style="font-size: 12px; font-weight: 600; color: #94a3b8; margin-top: 4px;"><?php echo $stat['total_sessions']; ?> Total Sessions</div>
                            </div>
                        </div>
                        
                        <div style="text-align: center; font-weight: 800; color: #0f172a; font-size: 15px;">
                            <?php echo $stat['present']; ?> <span style="color: #94a3b8; font-weight: 600; font-size: 13px;">/ <?php echo $stat['total_sessions']; ?></span>
                        </div>
                        
                        <div style="display: flex; justify-content: flex-end;">
                            <div style="background: <?php echo $badge_bg; ?>; color: <?php echo $badge_color; ?>; padding: 8px 16px; border-radius: 20px; font-weight: 800; font-size: 13px;">
                                <?php echo $stat['percentage']; ?>%
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
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
