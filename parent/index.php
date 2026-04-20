<?php
$page_title = "Parent Portal";
include '../includes/header.php';
?>
<section id="parent-portal" class="screen" data-state="active">
    <div class="scrollable-content">
        <div class="dash-header" style="margin-bottom: 30px;">
            <div class="greeting">
                <p style="font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 1.5px; font-size: 11px; margin-bottom: 5px;">Family Portal</p>
                <h1 style="font-size: 32px; font-weight: 900; letter-spacing: -1px; color: var(--text-dark);">Guardian Hub</h1>
            </div>
        </div>

        <div style="padding: 0 24px;">
            <div style="background: var(--surface); padding: 30px; border-radius: 32px; text-align: center; border: 1px solid var(--border);">
                <div style="width: 80px; height: 80px; background: var(--primary-glow); color: var(--primary); border-radius: 24px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                    <i data-lucide="shield-check" size="40"></i>
                </div>
                <h2 style="font-size: 22px; font-weight: 800; margin-bottom: 10px;">Monitor Academic Progress</h2>
                <p style="color: var(--text-muted); line-height: 1.6; margin-bottom: 25px;">Stay connected with your child's attendance and academic trends in real-time. Secure, encrypted access for authorized guardians only.</p>
                
                <form action="parent_process.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                    <input type="text" name="student_id" placeholder="Enter Student ID" style="padding: 15px; border-radius: 18px; border: 1.5px solid var(--border); outline: none;">
                    <input type="password" name="guardian_key" placeholder="Enter Guardian Security Key" style="padding: 15px; border-radius: 18px; border: 1.5px solid var(--border); outline: none;">
                    <button class="btn-primary" style="padding: 16px; border-radius: 20px; font-weight: 800;">Authenticate Access</button>
                </form>
            </div>
        </div>
    </div>
</nav>
</section>
<?php include '../includes/footer.php'; ?>
