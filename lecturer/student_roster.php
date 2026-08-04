<?php
$page_title = "Student Roster";
include '../includes/header.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'lecturer' && $_SESSION['role'] !== 'admin')) {
    header("Location: login");
    exit;
}

$db = get_db_connection();
$user_id = $_SESSION['user_id'];

$stmt = $db->prepare("
    SELECT 
        u.id, u.username, u.student_id, u.fullname,
        (SELECT COUNT(*) FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE e.student_id = u.id AND c.lecturer_id = ?) as course_count,
        (SELECT COUNT(*) FROM attendance a JOIN sessions s ON a.session_id = s.id JOIN courses c ON s.course_id = c.id WHERE a.student_id = u.id AND c.lecturer_id = ? AND a.status IN ('present', 'late')) as attended_sessions,
        (SELECT COUNT(*) FROM sessions s JOIN courses c ON s.course_id = c.id JOIN enrollments e ON e.course_id = c.id WHERE e.student_id = u.id AND c.lecturer_id = ? AND s.status = 'closed') as total_sessions
    FROM users u
    WHERE u.role = 'student' AND u.id IN (
        SELECT student_id FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE c.lecturer_id = ?
    )
    ORDER BY u.username ASC
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id]);
$students = $stmt->fetchAll();
?>

        <!-- Header (Screen Only) -->
        <div class="screen-only-header" style="padding: 0 0 20px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                <div>
                    <h1 style="font-size: 36px; font-weight: 950; color: var(--text-dark); letter-spacing: -1px;">Student <span style="color: var(--primary);">Roster</span></h1>
                    <p style="color: var(--text-muted); font-size: 15px; margin-top: 8px;">View all enrolled students and their global attendance health.</p>
                </div>
                <div>
                    <button class="btn-primary" style="padding: 14px 24px; border-radius: 16px; font-size: 14px;" onclick="window.print()">
                        <i data-lucide="printer" style="width: 16px; margin-right: 8px; vertical-align: middle;"></i> Print Roster
                    </button>
                </div>
            </div>
        </div>

        <!-- Print Only Header -->
        <div class="print-only-header" style="display: none; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #1e293b; padding-bottom: 15px;">
                <div>
                    <h1 style="font-size: 20pt; font-weight: 900; color: #0f172a; margin: 0 0 5px 0; letter-spacing: -0.5px; text-transform: uppercase;">Student Roster Report</h1>
                    <p style="margin: 0; font-size: 11pt; color: #475569; font-weight: 600;">AttendEase Pro Academic System</p>
                </div>
                <div style="text-align: right;">
                    <p style="margin: 0; font-size: 10pt; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Date Generated</p>
                    <p style="margin: 4px 0 0 0; font-size: 11pt; color: #0f172a; font-weight: bold;"><?php echo date('F j, Y'); ?></p>
                </div>
            </div>
        </div>

        <div style="padding: 0 0 40px;">
            <div style="background: white; border-radius: 30px; border: 1px solid var(--border); box-shadow: 0 10px 30px rgba(0,0,0,0.02); overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 20px; text-align: left; font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">Student</th>
                            <th style="padding: 20px; text-align: left; font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">Enrolled Courses</th>
                            <th style="padding: 20px; text-align: center; font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">Attendance</th>
                            <th style="padding: 20px; text-align: right; font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">Health</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($students as $stu): 
                            $total = $stu['total_sessions'];
                            $attended = $stu['attended_sessions'];
                            $percentage = ($total > 0) ? round(($attended / $total) * 100) : 0;
                            
                            $health_color = '#10b981'; // Green
                            $health_bg = 'rgba(16, 185, 129, 0.1)';
                            if ($percentage < 75) {
                                $health_color = '#ef4444'; // Red
                                $health_bg = 'rgba(239, 68, 68, 0.1)';
                            } elseif ($percentage < 85) {
                                $health_color = '#f59e0b'; // Yellow
                                $health_bg = 'rgba(245, 158, 11, 0.1)';
                            }
                        ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: all 0.2s;" onmouseover="this.style.backgroundColor='#f8fafc'" onmouseout="this.style.backgroundColor='transparent'">
                            <td style="padding: 20px;">
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <div class="avatar-wrapper" style="width: 45px; height: 45px; border-radius: 12px; background: #e2e8f0; overflow: hidden;">
                                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo htmlspecialchars($stu['username']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <div>
                                        <h4 style="font-weight: 800; font-size: 15px; color: var(--text-dark); margin: 0;"><?php echo htmlspecialchars($stu['fullname']); ?></h4>
                                        <p style="font-size: 12px; color: var(--text-muted); font-weight: 600; margin: 2px 0 0;"><?php echo htmlspecialchars($stu['student_id'] ?: $stu['username']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 20px;">
                                <span style="font-size: 14px; font-weight: 700; color: var(--text-dark);"><?php echo $stu['course_count']; ?> Courses</span>
                            </td>
                            <td style="padding: 20px; text-align: center;">
                                <span style="font-size: 14px; font-weight: 800; color: var(--text-dark);"><?php echo $attended; ?></span>
                                <span style="font-size: 12px; font-weight: 600; color: var(--text-muted);"> / <?php echo $total; ?> sessions</span>
                            </td>
                            <td style="padding: 20px; text-align: right;">
                                <span style="display: inline-block; padding: 6px 12px; border-radius: 10px; font-weight: 800; font-size: 13px; color: <?php echo $health_color; ?>; background: <?php echo $health_bg; ?>;">
                                    <?php echo $percentage; ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($students)): ?>
                        <tr>
                            <td colspan="4" style="padding: 40px; text-align: center; color: var(--text-muted); font-weight: 600;">No students enrolled yet.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div style="height: 100px;"></div>

<style>
@media print {
    /* Reset layout flows */
    @page { margin: 15mm; size: A4; }
    body, html, #app-container, .main-wrapper { 
        display: block !important; 
        width: 100% !important; 
        margin: 0 !important; 
        padding: 0 !important; 
        background: white !important; 
        color: #0f172a !important;
        font-family: 'Inter', 'Helvetica Neue', Arial, sans-serif !important;
    }
    
    /* Hide UI Elements */
    .sidebar, .bottom-nav, button, .screen-only-header, .avatar-wrapper { 
        display: none !important; 
    }
    
    /* Show Print Header */
    .print-only-header { 
        display: block !important; 
    }
    
    /* Elegant Table Design */
    table { width: 100% !important; border: none !important; border-collapse: collapse !important; margin-top: 20px !important; }
    
    /* Header Row */
    thead tr { border-bottom: 2px solid #0f172a !important; }
    th { 
        background: transparent !important; 
        color: #0f172a !important; 
        padding: 12px 8px !important; 
        font-size: 9pt !important; 
        font-weight: 800 !important; 
        text-transform: uppercase !important; 
        letter-spacing: 1px !important;
        border: none !important;
    }
    
    /* Body Rows */
    tbody tr { border-bottom: 1px solid #e2e8f0 !important; page-break-inside: avoid; }
    td { 
        border: none !important; 
        padding: 15px 8px !important; 
        color: #1e293b !important; 
        font-size: 10pt !important;
        vertical-align: middle !important;
    }
    
    /* Typography inside table */
    td h4 { margin: 0 !important; font-size: 11pt !important; font-weight: bold !important; color: #0f172a !important; }
    td p { margin: 3px 0 0 0 !important; font-size: 9pt !important; color: #64748b !important; }
    
    /* Hide decorative containers */
    div { box-shadow: none !important; }
    .main-wrapper > div { border: none !important; }
    
    /* Alignment */
    th:first-child, td:first-child { padding-left: 0 !important; }
    th:last-child, td:last-child { padding-right: 0 !important; text-align: right !important; }
    
    /* Simplify the health pill */
    td span { background: none !important; padding: 0 !important; color: black !important; }
}
</style>

<?php include '../includes/footer.php'; ?>
