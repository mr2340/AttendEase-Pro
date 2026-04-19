<?php
$page_title = "Reports";
include '../includes/header.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
?>

<section class="screen" data-state="active">
    <div class="dashboard-wrapper" style="height: 100%; display: flex; flex-direction: column;">
        <div class="tabs-container" style="flex: 1; overflow-y: auto;">
            <div class="dash-header">
                <div class="greeting">
                    <p>Performance</p>
                    <h2>Attendance Reports</h2>
                </div>
            </div>
            
            <div style="padding: 0 24px;">
                <div style="background: var(--surface); border-radius: 24px; padding: 24px; border: 1px solid var(--border); margin-bottom: 24px;">
                    <h4 style="font-size: 16px; margin-bottom: 20px;">By Course</h4>
                    
                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-size: 14px; font-weight: 600;">Data Structures</span>
                            <span style="font-size: 14px; font-weight: 800; color: var(--primary);">92%</span>
                        </div>
                        <div style="height: 6px; background: var(--bg-main); border-radius: 10px;">
                            <div style="width: 92%; height: 100%; background: var(--primary); border-radius: 10px;"></div>
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-size: 14px; font-weight: 600;">Machine Learning</span>
                            <span style="font-size: 14px; font-weight: 800; color: var(--success);">85%</span>
                        </div>
                        <div style="height: 6px; background: var(--bg-main); border-radius: 10px;">
                            <div style="width: 85%; height: 100%; background: var(--success); border-radius: 10px;"></div>
                        </div>
                    </div>

                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-size: 14px; font-weight: 600;">Cloud Computing</span>
                            <span style="font-size: 14px; font-weight: 800; color: var(--warning);">70%</span>
                        </div>
                        <div style="height: 6px; background: var(--bg-main); border-radius: 10px;">
                            <div style="width: 70%; height: 100%; background: var(--warning); border-radius: 10px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include '../includes/navbar.php'; ?>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
