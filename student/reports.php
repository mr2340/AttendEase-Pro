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
$recent_activity = StatEngine::getRecentActivity($user_id, 8);
?>

<section class="screen" data-state="active">
    <div class="scrollable-content">
        <!-- Dashboard Header -->
        <div class="dash-header" style="margin-bottom: 30px;">
            <div class="greeting">
                <p style="font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 2px; font-size: 10px; margin-bottom: 8px;">Intelligence Suite</p>
                <h2 style="font-size: 28px; font-weight: 900; color: var(--text-dark); letter-spacing: -1px;">Attendance Analytics</h2>
            </div>
        </div>

        <div style="padding: 0 24px;">
            <!-- Score Card -->
            <div style="background: linear-gradient(135deg, var(--primary) 0%, #0052cc 100%); padding: 30px; border-radius: 35px; color: white; margin-bottom: 24px; box-shadow: 0 15px 35px var(--primary-glow); position: relative; overflow: hidden;">
                <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 1;">
                    <div>
                        <p style="font-size: 13px; font-weight: 600; opacity: 0.9; margin-bottom: 5px;">Aggregate Score</p>
                        <h1 style="font-size: 56px; font-weight: 900; line-height: 1;"><?php echo $overall_score; ?>%</h1>
                    </div>
                    <div style="width: 70px; height: 70px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); border-radius: 22px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.3);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    </div>
                </div>
                
                <div style="margin-top: 25px; background: rgba(255,255,255,0.15); height: 8px; border-radius: 10px; overflow: hidden;">
                    <div style="width: <?php echo $overall_score; ?>%; height: 100%; background: white; border-radius: 10px; transition: 1.5s cubic-bezier(0.34, 1.56, 0.64, 1);"></div>
                </div>
                <p style="font-size: 11px; margin-top: 15px; font-weight: 600; opacity: 0.8;">Smart Advice: <?php echo $overall_score >= 80 ? 'Keep maintaining this elite streak!' : 'Focus on upcoming sessions to avoid risk.'; ?></p>
            </div>

            <!-- Course Breakdown -->
            <div class="section-title" style="margin-bottom: 15px;">
                <span>Detailed Course Breakdown</span>
            </div>

            <?php if (empty($course_stats)): ?>
                <div style="background: var(--surface); border-radius: 28px; padding: 40px 20px; text-align: center; border: 1.5px dashed var(--border);">
                    <div style="width: 60px; height: 60px; background: var(--bg-main); border-radius: 50%; display: flex; justify-content: center; align-items: center; margin: 0 auto 20px; color: var(--text-muted);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h9z"/></svg>
                    </div>
                    <h4 style="font-weight: 800; color: var(--text-dark);">No enrollment data</h4>
                    <p style="font-size: 13px; color: var(--text-muted); margin-top: 5px;">Your course analytics will appear here once you are enrolled in sessions.</p>
                </div>
            <?php else: ?>
                <div style="background: var(--surface); border-radius: 32px; padding: 20px; border: 1.5px solid var(--border); box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                    <?php foreach ($course_stats as $index => $stat): 
                        $color = 'var(--primary)';
                        if ($stat['percentage'] < 75) $color = 'var(--danger)';
                        elseif ($stat['percentage'] < 85) $color = 'var(--warning)';
                    ?>
                        <div style="margin-bottom: <?php echo $index === count($course_stats) - 1 ? '0' : '25px'; ?>;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                <div>
                                    <h4 style="font-size: 15px; font-weight: 800; color: var(--text-dark); margin: 0;"><?php echo htmlspecialchars($stat['course_name']); ?></h4>
                                    <span style="font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;"><?php echo htmlspecialchars($stat['course_code']); ?></span>
                                </div>
                                <div style="text-align: right;">
                                    <span style="font-size: 16px; font-weight: 900; color: <?php echo $color; ?>;"><?php echo $stat['percentage']; ?>%</span>
                                    <p style="font-size: 10px; color: var(--text-muted); font-weight: 700;"><?php echo $stat['present']; ?>/<?php echo $stat['total_sessions']; ?> Sessions</p>
                                </div>
                            </div>
                            <div style="height: 10px; background: var(--bg-main); border-radius: 10px; overflow: hidden; display: flex;">
                                <div style="width: <?php echo $stat['percentage']; ?>%; height: 100%; background: <?php echo $color; ?>; border-radius: 10px;"></div>
                            </div>
                            <div style="display: flex; gap: 15px; margin-top: 8px;">
                                <span style="font-size: 10px; font-weight: 700; color: var(--success); display: flex; align-items: center; gap: 3px;"><span style="width: 6px; height: 6px; background: var(--success); border-radius: 50%;"></span> <?php echo $stat['present']; ?> Present</span>
                                <span style="font-size: 10px; font-weight: 700; color: var(--warning); display: flex; align-items: center; gap: 3px;"><span style="width: 6px; height: 6px; background: var(--warning); border-radius: 50%;"></span> <?php echo $stat['late']; ?> Late</span>
                                <span style="font-size: 10px; font-weight: 700; color: var(--danger); display: flex; align-items: center; gap: 3px;"><span style="width: 6px; height: 6px; background: var(--danger); border-radius: 50%;"></span> <?php echo $stat['absent']; ?> Absent</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Recent Activity -->
            <div class="section-title" style="margin: 30px 0 15px;">
                <span>Attendance Timeline</span>
            </div>

            <div style="margin-bottom: 40px;">
                <?php if (empty($recent_activity)): ?>
                    <div style="padding: 20px; text-align: center; color: var(--text-muted); font-size: 14px; font-weight: 600;">No recent activity logs.</div>
                <?php else: ?>
                    <?php foreach ($recent_activity as $act): 
                        $status_color = 'var(--primary)';
                        $bg_glow = 'var(--primary-glow)';
                        if ($act['status'] == 'late') { $status_color = 'var(--warning)'; $bg_glow = 'rgba(245, 158, 11, 0.1)'; }
                        if ($act['status'] == 'absent') { $status_color = 'var(--danger)'; $bg_glow = 'rgba(239, 68, 68, 0.1)'; }
                        if ($act['status'] == 'present') { $status_color = 'var(--success)'; $bg_glow = 'rgba(16, 185, 129, 0.1)'; }
                    ?>
                        <div style="background: var(--surface); padding: 16px; border-radius: 24px; border: 1.5px solid var(--border); margin-bottom: 12px; display: flex; align-items: center; gap: 15px;">
                            <div style="width: 44px; height: 44px; background: <?php echo $bg_glow; ?>; color: <?php echo $status_color; ?>; border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <?php if($act['status'] == 'present'): ?>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                <?php elseif($act['status'] == 'late'): ?>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <?php else: ?>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                <?php endif; ?>
                            </div>
                            <div style="flex: 1; overflow: hidden;">
                                <h4 style="font-size: 14px; font-weight: 800; color: var(--text-dark); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($act['topic'] ?: $act['course_name']); ?></h4>
                                <p style="font-size: 11px; color: var(--text-muted); font-weight: 600;"><?php echo htmlspecialchars($act['course_name']); ?> • <?php echo date('d M, H:i', strtotime($act['timestamp'])); ?></p>
                            </div>
                            <div style="text-align: right;">
                                <span style="background: <?php echo $bg_glow; ?>; color: <?php echo $status_color; ?>; font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 4px 10px; border-radius: 50px;">
                                    <?php echo $act['status']; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div style="height: 100px;"></div>
        <?php include '../includes/navbar.php'; ?>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
