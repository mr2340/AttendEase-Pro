<?php
$page_title = "Analytics";
include '../includes/header.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'lecturer' && $_SESSION['role'] !== 'admin')) {
    header("Location: login");
    exit;
}

$db = get_db_connection();
$user_id = $_SESSION['user_id'];

// Fetch Lecturer's Courses
$stmt = $db->prepare("SELECT * FROM courses WHERE lecturer_id = ?");
$stmt->execute([$user_id]);
$courses = $stmt->fetchAll();

// Fetch Recent Sessions with Attendance Counts
$stmt = $db->prepare("
    SELECT s.*, c.course_name, c.course_code,
    (SELECT COUNT(*) FROM attendance WHERE session_id = s.id) as student_count
    FROM sessions s 
    JOIN courses c ON s.course_id = c.id 
    WHERE s.lecturer_id = ?
    ORDER BY s.created_at DESC
    LIMIT 20
");
$stmt->execute([$user_id]);
$recent_sessions = $stmt->fetchAll();

// Calculate Global Stats
$total_sessions = count($recent_sessions);
$total_attendance = 0;
foreach($recent_sessions as $rs) $total_attendance += $rs['student_count'];
$avg_attendance = ($total_sessions > 0) ? round($total_attendance / $total_sessions, 1) : 0;

// Fetch Critical Insights: Students at Risk (< 75%)
$stmt = $db->prepare("
    SELECT u.username, u.email, c.course_code,
    (COUNT(a.id) * 100 / (SELECT COUNT(*) FROM sessions WHERE course_id = c.id)) as attendance_rate
    FROM attendance a
    JOIN users u ON a.student_id = u.id
    JOIN sessions s ON a.session_id = s.id
    JOIN courses c ON s.course_id = c.id
    WHERE c.lecturer_id = ?
    GROUP BY u.id, c.id
    HAVING attendance_rate < 75
    LIMIT 5
");
$stmt->execute([$user_id]);
$at_risk = $stmt->fetchAll();

// Fetch Top Performers
$stmt = $db->prepare("
    SELECT u.username, COUNT(a.id) as scan_count
    FROM attendance a
    JOIN users u ON a.student_id = u.id
    JOIN sessions s ON a.session_id = s.id
    WHERE s.lecturer_id = ?
    GROUP BY u.id
    ORDER BY scan_count DESC
    LIMIT 3
");
$stmt->execute([$user_id]);
$top_performers = $stmt->fetchAll();
?>

<div class="mobile-only-layout">
<section id="reports-portal" class="screen" data-state="active" style="background: #f8fafc;">
    <div class="scrollable-content">
        <!-- Header -->
        <div style="padding: 30px 24px 20px;">
            <h1 style="font-size: 32px; font-weight: 900; color: #0f172a; letter-spacing: -1px;">Intelligence <span style="color: var(--primary);">Reports</span></h1>
            <p style="color: #64748b; font-size: 14px; margin-top: 5px;">Comprehensive attendance analytics</p>
        </div>

        <!-- Stats Overview -->
        <div style="padding: 0 24px; margin-bottom: 30px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div style="background: white; padding: 20px; border-radius: 28px; border: 1px solid #e2e8f0; box-shadow: 0 10px 20px rgba(0,0,0,0.02);">
                    <div style="width: 40px; height: 40px; background: #ecfdf5; color: #10b981; border-radius: 12px; display: flex; justify-content: center; align-items: center; margin-bottom: 12px;">
                        <i data-lucide="bar-chart-3" style="width: 20px;"></i>
                    </div>
                    <h3 style="font-size: 24px; font-weight: 850; color: #0f172a;"><?php echo $total_sessions; ?></h3>
                    <p style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Total Sessions</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 28px; border: 1px solid #e2e8f0; box-shadow: 0 10px 20px rgba(0,0,0,0.02);">
                    <div style="width: 40px; height: 40px; background: #eff6ff; color: #3b82f6; border-radius: 12px; display: flex; justify-content: center; align-items: center; margin-bottom: 12px;">
                        <i data-lucide="users" style="width: 20px;"></i>
                    </div>
                    <h3 style="font-size: 24px; font-weight: 850; color: #0f172a;"><?php echo $avg_attendance; ?></h3>
                    <p style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Avg Presence</p>
                </div>
            </div>
        </div>

        <!-- Course Filters (Horizontal Scroll) -->
        <div style="overflow-x: auto; padding: 0 24px; margin-bottom: 30px; display: flex; gap: 10px; scrollbar-width: none;">
            <div style="background: #0f172a; color: white; padding: 10px 20px; border-radius: 100px; font-size: 13px; font-weight: 700; white-space: nowrap;">All Courses</div>
            <?php foreach($courses as $c): ?>
                <div style="background: white; color: #64748b; padding: 10px 20px; border-radius: 100px; font-size: 13px; font-weight: 700; white-space: nowrap; border: 1px solid #e2e8f0;">
                    <?php echo htmlspecialchars($c['course_code']); ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Intelligence Section (Mobile) -->
        <div style="padding: 0 24px 30px;">
            <h3 style="font-size: 12px; font-weight: 850; color: #94a3b8; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 1px;">Risk Radar</h3>
            <div style="background: white; border-radius: 35px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                <?php if (empty($at_risk)): ?>
                    <div style="text-align: center; color: #10b981; font-weight: 700; font-size: 14px;">
                        <i data-lucide="shield-check" style="width: 20px; vertical-align: middle; margin-right: 5px;"></i> All nodes stable
                    </div>
                <?php else: ?>
                    <?php foreach($at_risk as $r): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9;">
                            <div>
                                <h4 style="font-size: 14px; font-weight: 800; color: #1e293b;"><?php echo htmlspecialchars($r['username']); ?></h4>
                                <p style="font-size: 11px; color: #94a3b8; font-weight: 600;"><?php echo $r['course_code']; ?></p>
                            </div>
                            <span style="background: #fef2f2; color: #ef4444; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 800;">
                                <?php echo round($r['attendance_rate']); ?>%
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Log -->
        <div class="section-title" style="padding: 0 24px; margin-bottom: 15px;">
            <span style="font-weight: 900; font-size: 13px; color: #0f172a; text-transform: uppercase; letter-spacing: 1px;">Live Log History</span>
        </div>

        <div style="padding: 0 24px;">
            <?php if (empty($recent_sessions)): ?>
                <div style="background: white; padding: 50px 20px; border-radius: 40px; text-align: center; border: 2px dashed #cbd5e1;">
                    <i data-lucide="database-zap" style="width: 40px; height: 40px; color: #cbd5e1; margin-bottom: 15px;"></i>
                    <p style="color: #64748b; font-weight: 600;">System synchronized. No logs yet.</p>
                </div>
            <?php else: ?>
                <?php foreach($recent_sessions as $sess): ?>
                    <div style="background: white; padding: 20px; border-radius: 28px; border: 1px solid #e2e8f0; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 15px rgba(0,0,0,0.01);">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="width: 50px; height: 50px; background: #f8fafc; color: #475569; border-radius: 18px; display: flex; flex-direction: column; justify-content: center; align-items: center; line-height: 1;">
                                <span style="font-size: 14px; font-weight: 900;"><?php echo date('d', strtotime($sess['created_at'])); ?></span>
                                <span style="font-size: 9px; font-weight: 800; text-transform: uppercase; margin-top: 2px;"><?php echo date('M', strtotime($sess['created_at'])); ?></span>
                            </div>
                            <div>
                                <h4 style="font-weight: 800; font-size: 15px; color: #0f172a;"><?php echo htmlspecialchars($sess['course_name']); ?></h4>
                                <p style="font-size: 11px; color: #64748b; font-weight: 700; display: flex; align-items: center; gap: 5px;">
                                    <i data-lucide="clock" style="width: 10px;"></i> <?php echo date('h:i A', strtotime($sess['created_at'])); ?> • <span style="color: var(--primary);"><?php echo $sess['student_count']; ?> Present</span>
                                </p>
                            </div>
                        </div>
                        <a href="session_details?id=<?php echo $sess['id']; ?>" style="width: 40px; height: 40px; background: #f1f5f9; color: #0f172a; border-radius: 14px; display: flex; justify-content: center; align-items: center; text-decoration: none; transition: transform 0.2s ease;">
                            <i data-lucide="arrow-right" style="width: 18px;"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div style="height: 120px;"></div>
    </div>
</div>

        <div style="height: 120px;"></div>
    </div>
</section>
</div>

<!-- Desktop: Intelligence Matrix (Bento Refactor) -->
<div class="desktop-only-layout" style="background: #fbfcfd; min-height: 100vh;">
    <!-- Enriched Desktop Header -->
    <header style="padding: 50px 60px 30px; display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <div style="background: var(--primary); width: 12px; height: 12px; border-radius: 4px;"></div>
                <span style="color: #64748b; font-size: 13px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;">Command Reports</span>
            </div>
            <h1 style="font-size: 48px; font-weight: 950; color: #0f172a; letter-spacing: -2.5px;">Analytics <span style="color: var(--primary);">Intelligence</span></h1>
            <p style="color: #94a3b8; font-size: 16px; font-weight: 500; margin-top: 8px;">Real-time synoptic overview of faculty academic engagement.</p>
        </div>
        <div style="display: flex; gap: 20px; align-items: center;">
            <div style="text-align: right;">
                <p style="font-size: 12px; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">System Health</p>
                <p style="font-size: 18px; font-weight: 900; color: #10b981;">OPTIMAL</p>
            </div>
            <button class="btn-primary" style="height: 55px; padding: 0 30px; border-radius: 18px; font-weight: 800; box-shadow: 0 10px 25px var(--primary-glow);">
                <i data-lucide="download-cloud" style="width: 18px; margin-right: 10px;"></i> Master Export
            </button>
        </div>
    </header>

    <!-- Top Statistical Tier -->
    <div style="padding: 0 60px 40px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px;">
        <div style="background: white; padding: 30px; border-radius: 35px; border: 1.5px solid #f1f5f9; box-shadow: 0 15px 35px rgba(0,0,0,0.02);">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                <div style="width: 50px; height: 50px; background: #eff6ff; color: var(--primary); border-radius: 16px; display: flex; justify-content: center; align-items: center;">
                    <i data-lucide="calendar" style="width: 24px;"></i>
                </div>
                <h4 style="font-size: 13px; font-weight: 800; color: #64748b; text-transform: uppercase;">Managed Sessions</h4>
            </div>
            <h2 style="font-size: 40px; font-weight: 950; color: #0f172a;"><?php echo $total_sessions; ?></h2>
            <p style="font-size: 13px; color: #10b981; font-weight: 700; margin-top: 5px;">+12% from last cycle</p>
        </div>
        
        <div style="background: white; padding: 30px; border-radius: 35px; border: 1.5px solid #f1f5f9; box-shadow: 0 15px 35px rgba(0,0,0,0.02);">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                <div style="width: 50px; height: 50px; background: #ecfdf5; color: #10b981; border-radius: 16px; display: flex; justify-content: center; align-items: center;">
                    <i data-lucide="trending-up" style="width: 24px;"></i>
                </div>
                <h4 style="font-size: 13px; font-weight: 800; color: #64748b; text-transform: uppercase;">Average Turnout</h4>
            </div>
            <h2 style="font-size: 40px; font-weight: 950; color: #0f172a;"><?php echo $avg_attendance; ?></h2>
            <p style="font-size: 13px; color: #94a3b8; font-weight: 700; margin-top: 5px;">Students per session</p>
        </div>

        <div style="background: white; padding: 30px; border-radius: 35px; border: 1.5px solid #f1f5f9; box-shadow: 0 15px 35px rgba(0,0,0,0.02);">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                <div style="width: 50px; height: 50px; background: #fff7ed; color: #f97316; border-radius: 16px; display: flex; justify-content: center; align-items: center;">
                    <i data-lucide="shield-alert" style="width: 24px;"></i>
                </div>
                <h4 style="font-size: 13px; font-weight: 800; color: #64748b; text-transform: uppercase;">Students At Risk</h4>
            </div>
            <h2 style="font-size: 40px; font-weight: 950; color: #ef4444;"><?php echo count($at_risk); ?></h2>
            <p style="font-size: 13px; color: #ef4444; font-weight: 700; margin-top: 5px;">Requires intervention</p>
        </div>
    </div>

    <!-- Main Content Hub -->
    <div style="padding: 0 60px 60px; display: grid; grid-template-columns: 7fr 3fr; gap: 35px; align-items: start;">
        
        <!-- Left: Course Matrix -->
        <div style="background: white; border-radius: 45px; padding: 45px; border: 1.5px solid #f1f5f9; box-shadow: 0 20px 60px rgba(0,0,0,0.03);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
                <h3 style="font-size: 22px; font-weight: 900; color: #0f172a; letter-spacing: -0.5px;">Live Academic Matrix</h3>
                <div style="display: flex; gap: 12px;">
                    <span style="background: #f8fafc; color: #64748b; padding: 8px 16px; border-radius: 12px; font-size: 12px; font-weight: 700; border: 1px solid #e2e8f0;">Sorted by: Latest</span>
                </div>
            </div>

            <table style="width: 100%; border-collapse: separate; border-spacing: 0 15px;">
                <thead>
                    <tr style="text-align: left;">
                        <th style="padding: 0 15px 15px; font-size: 11px; color: #94a3b8; font-weight: 850; text-transform: uppercase; letter-spacing: 1px;">Course Identity</th>
                        <th style="padding: 0 15px 15px; font-size: 11px; color: #94a3b8; font-weight: 850; text-transform: uppercase; letter-spacing: 1px; text-align: center;">Pulse Events</th>
                        <th style="padding: 0 15px 15px; font-size: 11px; color: #94a3b8; font-weight: 850; text-transform: uppercase; letter-spacing: 1px; text-align: center;">Avg Presence</th>
                        <th style="padding: 0 15px 15px; font-size: 11px; color: #94a3b8; font-weight: 850; text-transform: uppercase; letter-spacing: 1px; text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($courses)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 50px; color: #94a3b8;">No courses mapped to your profile.</td></tr>
                    <?php endif; ?>
                    <?php foreach($courses as $c): 
                        $cs_stmt = $db->prepare("SELECT COUNT(*) as s_count, (SELECT COUNT(*) FROM attendance WHERE session_id IN (SELECT id FROM sessions WHERE course_id = ?)) as a_count FROM sessions WHERE course_id = ?");
                        $cs_stmt->execute([$c['id'], $c['id']]);
                        $cs_data = $cs_stmt->fetch();
                        $c_avg = ($cs_data['s_count'] > 0) ? round($cs_data['a_count'] / $cs_data['s_count'], 1) : 0;
                    ?>
                        <tr style="background: #fcfdfe; transition: transform 0.2s; cursor: pointer;">
                            <td style="padding: 22px 20px; border-radius: 20px 0 0 20px; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; border-left: 1px solid #f1f5f9;">
                                <div style="font-weight: 900; color: #0f172a; font-size: 16px;"><?php echo htmlspecialchars($c['course_name']); ?></div>
                                <div style="font-size: 12px; color: var(--primary); font-weight: 800; margin-top: 2px;"># <?php echo $c['course_code']; ?></div>
                            </td>
                            <td style="text-align: center; font-weight: 850; color: #475569; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9;"><?php echo $cs_data['s_count']; ?></td>
                            <td style="text-align: center; font-weight: 900; color: var(--text-dark); border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9;">
                                <span style="font-size: 18px;"><?php echo $c_avg; ?></span>
                                <span style="font-size: 11px; color: #94a3b8; font-weight: 700;">/ session</span>
                            </td>
                            <td style="text-align: center; border-radius: 0 20px 20px 0; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; border-right: 1px solid #f1f5f9;">
                                <button style="background: #f1f5f9; border: none; width: 40px; height: 40px; border-radius: 12px; color: #0f172a; cursor: pointer;">
                                    <i data-lucide="chevron-right" style="width: 18px;"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Right: Intelligence Sidebar -->
        <div style="display: flex; flex-direction: column; gap: 35px;">
            <!-- Top Performers (Compact) -->
            <div style="background: #0f172a; border-radius: 40px; padding: 40px; color: white;">
                <h3 style="font-size: 18px; font-weight: 900; margin-bottom: 25px; letter-spacing: -0.5px;">Elite Nodes</h3>
                <?php foreach($top_performers as $tp): ?>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="width: 40px; height: 40px; border-radius: 12px; overflow: hidden; border: 1.5px solid rgba(255,255,255,0.1);">
                                <img src="https://api.dicebear.com/7.x/bottts/svg?seed=<?php echo $tp['username']; ?>" style="width: 100%;">
                            </div>
                            <div>
                                <h4 style="font-size: 14px; font-weight: 800;"><?php echo htmlspecialchars($tp['username']); ?></h4>
                                <p style="font-size: 10px; color: rgba(255,255,255,0.5); font-weight: 700; text-transform: uppercase;">Active Scan</p>
                            </div>
                        </div>
                        <span style="color: #10b981; font-weight: 900; font-size: 15px;"><?php echo $tp['scan_count']; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Risk Radar (Actionable) -->
            <div style="background: white; border-radius: 40px; padding: 40px; border: 1.5px solid #f1f5f9; box-shadow: 0 15px 40px rgba(0,0,0,0.02);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                    <h3 style="font-size: 18px; font-weight: 950; color: #ef4444; letter-spacing: -0.5px;">Risk Radar</h3>
                    <div style="width: 10px; height: 10px; background: #ef4444; border-radius: 50%; animation: pulseShield 2s infinite;"></div>
                </div>
                
                <?php if (empty($at_risk)): ?>
                    <div style="text-align: center; padding: 20px;">
                        <i data-lucide="shield-check" style="width: 32px; height: 32px; color: #10b981; margin-bottom: 12px;"></i>
                        <p style="color: #64748b; font-size: 14px; font-weight: 600;">System coverage optimal</p>
                    </div>
                <?php else: ?>
                    <?php foreach($at_risk as $r): ?>
                        <div style="margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #f8fafc;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; align-items: center;">
                                <span style="font-size: 14px; font-weight: 850; color: #0f172a;"><?php echo htmlspecialchars($r['username']); ?></span>
                                <span style="font-size: 14px; font-weight: 900; color: #ef4444;"><?php echo round($r['attendance_rate']); ?>%</span>
                            </div>
                            <div style="height: 6px; background: #fef2f2; border-radius: 10px; overflow: hidden;">
                                <div style="width: <?php echo $r['attendance_rate']; ?>%; height: 100%; background: #ef4444; border-radius: 10px;"></div>
                            </div>
                            <p style="font-size: 11px; color: #94a3b8; font-weight: 700; margin-top: 8px;">Target: <?php echo $r['course_code']; ?></p>
                        </div>
                    <?php endforeach; ?>
                    <button style="width: 100%; height: 50px; background: #fef2f2; color: #ef4444; border: none; border-radius: 16px; font-size: 13px; font-weight: 850; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i data-lucide="megaphone" style="width: 16px;"></i> Dispatch Alerts
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<?php include '../includes/footer.php'; ?>
