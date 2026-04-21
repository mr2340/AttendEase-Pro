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

<<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Mobile: Portal -->
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

        <!-- Risk Radar (Mobile) -->
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

        <!-- Recent Log (Mobile) -->
        <div class="section-title" style="padding: 0 24px; margin-bottom: 15px;">
            <span style="font-weight: 900; font-size: 13px; color: #0f172a; text-transform: uppercase; letter-spacing: 1px;">Live Log History</span>
        </div>

        <div style="padding: 0 24px;">
            <?php foreach(array_slice($recent_sessions, 0, 5) as $sess): ?>
                <div style="background: white; padding: 20px; border-radius: 28px; border: 1px solid #e2e8f0; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="width: 50px; height: 50px; background: #f8fafc; color: #475569; border-radius: 18px; display: flex; flex-direction: column; justify-content: center; align-items: center; line-height: 1;">
                            <span style="font-size: 14px; font-weight: 900;"><?php echo date('d', strtotime($sess['created_at'])); ?></span>
                            <span style="font-size: 9px; font-weight: 800; text-transform: uppercase; margin-top: 2px;"><?php echo date('M', strtotime($sess['created_at'])); ?></span>
                        </div>
                        <div>
                            <h4 style="font-weight: 800; font-size: 14px; color: #0f172a;"><?php echo htmlspecialchars($sess['course_name']); ?></h4>
                            <p style="font-size: 11px; color: #64748b; font-weight: 700;"><?php echo $sess['student_count']; ?> Present</p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="height: 120px;"></div>
    </div>
</section>
</div>

<!-- Desktop: Intelligence Matrix -->
<div class="desktop-only-layout" style="background: #fbfcfd; min-height: 100vh;">
    <header style="padding: 50px 60px 30px; display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <div style="background: var(--primary); width: 12px; height: 12px; border-radius: 4px;"></div>
                <span style="color: #64748b; font-size: 13px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;">Command Reports</span>
            </div>
            <h1 style="font-size: 48px; font-weight: 950; color: #0f172a; letter-spacing: -2.5px;">Analytics <span style="color: var(--primary);">Intelligence</span></h1>
            <p style="color: #94a3b8; font-size: 16px; font-weight: 500; margin-top: 8px;">Real-time synoptic overview of faculty engagement patterns.</p>
        </div>
        <div style="display: flex; gap: 15px;">
            <button class="btn-primary" style="height: 55px; padding: 0 30px; border-radius: 18px; font-weight: 800;">
                <i data-lucide="download-cloud" style="width: 18px; margin-right: 10px;"></i> Export Data
            </button>
        </div>
    </header>

    <div style="padding: 0 60px 40px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px;">
        <div style="background: white; padding: 30px; border-radius: 35px; border: 1.5px solid #f1f5f9; box-shadow: 0 15px 35px rgba(0,0,0,0.02);">
            <p style="font-size: 12px; font-weight: 850; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">Session Integrity</p>
            <h2 style="font-size: 40px; font-weight: 950; color: #0f172a;"><?php echo $total_sessions; ?></h2>
            <div style="width: 100%; height: 6px; background: #eff6ff; border-radius: 10px; margin-top: 20px; overflow: hidden;">
                <div style="width: 85%; height: 100%; background: var(--primary); border-radius: 10px;"></div>
            </div>
        </div>
        <div style="background: white; padding: 30px; border-radius: 35px; border: 1.5px solid #f1f5f9; box-shadow: 0 15px 35px rgba(0,0,0,0.02);">
            <p style="font-size: 12px; font-weight: 850; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">Presence Core</p>
            <h2 style="font-size: 40px; font-weight: 950; color: #10b981;"><?php echo $avg_attendance; ?></h2>
            <p style="font-size: 13px; color: #64748b; font-weight: 600; margin-top: 5px;">Avg participants / session</p>
        </div>
        <div style="background: white; padding: 30px; border-radius: 35px; border: 1.5px solid #f1f5f9; box-shadow: 0 15px 35px rgba(0,0,0,0.02);">
            <p style="font-size: 12px; font-weight: 850; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">Risk Anomalies</p>
            <h2 style="font-size: 40px; font-weight: 950; color: #ef4444;"><?php echo count($at_risk); ?></h2>
            <p style="font-size: 13px; color: #ef4444; font-weight: 700; margin-top: 5px;">Below 75% threshold</p>
        </div>
    </div>

    <!-- Analytics Matrix -->
    <div style="padding: 0 60px 60px; display: grid; grid-template-columns: 7.5fr 4.5fr; gap: 35px; min-height: 500px;">
        <!-- Distribution Area -->
        <div style="background: white; border-radius: 45px; padding: 45px; border: 1.5px solid #f1f5f9; box-shadow: 0 20px 60px rgba(0,0,0,0.03); display: flex; flex-direction: column;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
                <h3 style="font-size: 22px; font-weight: 950; color: #0f172a; letter-spacing: -1px;">Attendance Trendline</h3>
                <span style="font-size: 12px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Last 15 Sessions</span>
            </div>
            <div style="flex: 1; position: relative;">
                <canvas id="attendanceTrendChart"></canvas>
            </div>
        </div>

        <!-- Radar & Side Intel -->
        <div style="display: flex; flex-direction: column; gap: 35px;">
             <!-- Distribution Pie -->
             <div style="background: white; border-radius: 40px; padding: 40px; border: 1.5px solid #f1f5f9; box-shadow: 0 15px 40px rgba(0,0,0,0.02);">
                <h3 style="font-size: 18px; font-weight: 900; color: #0f172a; margin-bottom: 30px;">Course Distribution</h3>
                <div style="height: 220px; position: relative;">
                    <canvas id="courseDistributionChart"></canvas>
                </div>
            </div>

            <!-- Risk List -->
            <div style="background: #0f172a; border-radius: 40px; padding: 40px; color: white;">
                <h3 style="font-size: 18px; font-weight: 900; margin-bottom: 25px;">Risk Analytics</h3>
                <?php if (empty($at_risk)): ?>
                    <p style="color: #10b981; font-weight: 700;">Coverage stable. No anomalies detected.</p>
                <?php else: ?>
                    <?php foreach($at_risk as $r): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <div>
                                <h4 style="font-size: 14px; font-weight: 800;"><?php echo htmlspecialchars($r['username']); ?></h4>
                                <p style="font-size: 10px; color: rgba(255,255,255,0.4);"><?php echo $r['course_code']; ?></p>
                            </div>
                            <span style="color: #ef4444; font-weight: 900;"><?php echo round($r['attendance_rate']); ?>%</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Prepare chart data
$trend_labels = [];
$trend_values = [];
foreach(array_reverse(array_slice($recent_sessions, 0, 15)) as $s) {
    $trend_labels[] = date('M d', strtotime($s['created_at']));
    $trend_values[] = $s['student_count'];
}

$dist_labels = [];
$dist_values = [];
foreach($courses as $c) {
    $dist_labels[] = $c['course_code'];
    // Count sessions for this course
    $cs_stmt = $db->prepare("SELECT COUNT(*) FROM sessions WHERE course_id = ?");
    $cs_stmt->execute([$c['id']]);
    $dist_values[] = $cs_stmt->fetchColumn();
}
?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // Line Chart: Attendance Trend
    const trendCtx = document.getElementById('attendanceTrendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($trend_labels); ?>,
                datasets: [{
                    label: 'Attendee Count',
                    data: <?php echo json_encode($trend_values); ?>,
                    borderColor: '#0066ff',
                    backgroundColor: 'rgba(0, 102, 255, 0.05)',
                    borderWidth: 4,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#0066ff',
                    pointBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { display: false } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Doughnut Chart: Distribution
    const distCtx = document.getElementById('courseDistributionChart');
    if (distCtx) {
        new Chart(distCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($dist_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($dist_values); ?>,
                    backgroundColor: ['#0066ff', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                    borderWidth: 0,
                    hoverOffset: 20
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
                }
            }
        });
    }
});
</script>
>

<?php include '../includes/footer.php'; ?>
