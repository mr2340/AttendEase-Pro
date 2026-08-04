<?php
require_once '../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth Check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'lecturer' && $_SESSION['role'] !== 'admin')) {
    header("Location: login");
    exit;
}

$db = get_db_connection();
$user_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'] ?? null;

if (!$course_id) {
    header("Location: courses");
    exit;
}

// Verify Ownership
$stmt = $db->prepare("SELECT * FROM courses WHERE id = ? AND lecturer_id = ?");
$stmt->execute([$course_id, $user_id]);
$course = $stmt->fetch();

if (!$course) {
    header("Location: courses");
    exit;
}

// Handle Form Submission (Add Schedule)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_schedule'])) {
    $day = (int)$_POST['day_of_week'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $location = $_POST['location'] ?? 'Classroom';

    if ($start && $end) {
        $stmt = $db->prepare("INSERT INTO schedules (course_id, day_of_week, start_time, end_time, location) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$course_id, $day, $start, $end, $location]);
        header("Location: manage_schedule.php?course_id=$course_id&success=1");
        exit;
    }
}

// Handle Deletion
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM schedules WHERE id = ? AND course_id = ?");
    $stmt->execute([(int)$_GET['delete'], $course_id]);
    header("Location: manage_schedule.php?course_id=$course_id&deleted=1");
    exit;
}

// Fetch Existing Schedules
$stmt = $db->prepare("SELECT * FROM schedules WHERE course_id = ? ORDER BY day_of_week, start_time");
$stmt->execute([$course_id]);
$schedules = $stmt->fetchAll();

$days = [
    1 => 'Monday', 
    2 => 'Tuesday', 
    3 => 'Wednesday', 
    4 => 'Thursday', 
    5 => 'Friday', 
    6 => 'Saturday', 
    0 => 'Sunday'
];

$page_title = "Manage Schedule";
include '../includes/header.php';
?>

<div class="desktop-only-layout" style="background: #f8fafc; min-height: 100vh;">
    <header style="padding: 60px 5% 40px; max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
                <a href="course_details?id=<?php echo $course_id; ?>" style="color: #64748b; margin-right: 10px;"><i data-lucide="arrow-left-circle" style="width: 24px;"></i></a>
                <span style="color: #64748b; font-size: 13px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase;">Temporal Configuration</span>
            </div>
            <h1 style="font-size: 56px; font-weight: 950; color: #0f172a; letter-spacing: -2.5px;">Schedule <span style="color: var(--primary);">Manager</span></h1>
            <p style="color: #94a3b8; font-size: 18px; font-weight: 500; margin-top: 10px;">Set recurring class times for <b><?php echo htmlspecialchars($course['course_name']); ?></b>.</p>
        </div>
    </header>

    <div style="padding: 0 5%; padding-bottom: 80px; max-width: 1400px; margin: 0 auto; display: grid; grid-template-columns: minmax(500px, 1.5fr) 1fr; gap: 40px; align-items: start;">
        
        <!-- Left: Current Timeline -->
        <div style="background: white; border-radius: 50px; padding: 45px; border: 1.5px solid #f1f5f9; box-shadow: 0 20px 60px rgba(0,0,0,0.03);">
            <h3 style="font-size: 24px; font-weight: 950; color: #0f172a; margin-bottom: 35px; letter-spacing: -0.5px;">Weekly Timeline</h3>
            
            <?php if (empty($schedules)): ?>
                <div style="text-align: center; padding: 60px; background: #f8fafc; border-radius: 35px; border: 2px dashed #e2e8f0;">
                    <i data-lucide="calendar-x" style="width: 48px; height: 48px; color: #cbd5e1; margin-bottom: 20px;"></i>
                    <p style="color: #64748b; font-weight: 700;">No recurring schedules detected for this course.</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <?php foreach ($schedules as $item): ?>
                        <div style="background: #fff; padding: 25px; border-radius: 30px; border: 1.5px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; transition: all 0.2s ease;">
                            <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap; flex: 1;">
                                <div style="min-width: 100px;">
                                    <span style="background: #eff6ff; color: var(--primary); padding: 4px 12px; border-radius: 8px; font-size: 11px; font-weight: 850; text-transform: uppercase;">
                                        <?php 
                                            $d = $item['day_of_week'];
                                            echo isset($days[$d]) ? $days[$d] : htmlspecialchars($d); 
                                        ?>
                                    </span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <div style="text-align: right;">
                                        <p style="font-size: 18px; font-weight: 900; color: #0f172a;"><?php echo date('h:i A', strtotime($item['start_time'])); ?></p>
                                        <p style="font-size: 10px; font-weight: 700; color: #94a3b8;">START</p>
                                    </div>
                                    <div style="width: 20px; height: 1.5px; background: #e2e8f0;"></div>
                                    <div>
                                        <p style="font-size: 18px; font-weight: 900; color: #0f172a;"><?php echo date('h:i A', strtotime($item['end_time'])); ?></p>
                                        <p style="font-size: 10px; font-weight: 700; color: #94a3b8;">END</p>
                                    </div>
                                </div>
                                <div style="margin-left: auto; padding-right: 15px;">
                                    <p style="font-size: 14px; font-weight: 700; color: #64748b; white-space: nowrap;">
                                        <i data-lucide="map-pin" style="width: 14px; margin-right: 5px; vertical-align: middle;"></i>
                                        <?php echo htmlspecialchars($item['location']); ?>
                                    </p>
                                </div>
                            </div>
                            <a href="?course_id=<?php echo $course_id; ?>&delete=<?php echo $item['id']; ?>" onclick="return confirm('Archive this temporal node?')" style="background: #fee2e2; color: #ef4444; width: 44px; height: 44px; border-radius: 14px; display: flex; justify-content: center; align-items: center; text-decoration: none;">
                                <i data-lucide="trash-2" style="width: 18px;"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right: Deployment Form -->
        <div style="background: #0f172a; border-radius: 50px; padding: 45px; color: white; box-shadow: 0 25px 60px rgba(0,0,0,0.1); position: sticky; top: 40px;">
            <h3 style="font-size: 22px; font-weight: 950; margin-bottom: 35px; letter-spacing: -0.5px;">Inject Temporal Node</h3>
            
            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 25px;">
                <input type="hidden" name="add_schedule" value="1">
                
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 850; color: rgba(255,255,255,0.4); text-transform: uppercase; margin-bottom: 12px; letter-spacing: 1px;">Target Day</label>
                    <select name="day_of_week" style="width: 100%; height: 55px; background: rgba(255,255,255,0.05); border: 1.5px solid rgba(255,255,255,0.1); border-radius: 18px; padding: 0 20px; font-weight: 700; font-family: inherit; font-size: 15px; color: white; outline: none;">
                        <?php foreach ($days as $val => $name): ?>
                            <option value="<?php echo $val; ?>"><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 850; color: rgba(255,255,255,0.4); text-transform: uppercase; margin-bottom: 12px; letter-spacing: 1px;">Start Time</label>
                        <input type="time" name="start_time" required style="width: 100%; height: 55px; background: rgba(255,255,255,0.05); border: 1.5px solid rgba(255,255,255,0.1); border-radius: 18px; padding: 0 20px; font-weight: 700; font-family: inherit; font-size: 15px; color: white; outline: none;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 850; color: rgba(255,255,255,0.4); text-transform: uppercase; margin-bottom: 12px; letter-spacing: 1px;">End Time</label>
                        <input type="time" name="end_time" required style="width: 100%; height: 55px; background: rgba(255,255,255,0.05); border: 1.5px solid rgba(255,255,255,0.1); border-radius: 18px; padding: 0 20px; font-weight: 700; font-family: inherit; font-size: 15px; color: white; outline: none;">
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 11px; font-weight: 850; color: rgba(255,255,255,0.4); text-transform: uppercase; margin-bottom: 12px; letter-spacing: 1px;">Location / Venue</label>
                    <input type="text" name="location" placeholder="e.g. Hall 4, Room 202" style="width: 100%; height: 55px; background: rgba(255,255,255,0.05); border: 1.5px solid rgba(255,255,255,0.1); border-radius: 18px; padding: 0 20px; font-weight: 700; font-family: inherit; font-size: 15px; color: white; outline: none;">
                </div>

                <div style="background: rgba(255,255,255,0.03); padding: 25px; border-radius: 28px; border: 1.5px solid rgba(255,255,255,0.05);">
                    <p style="font-size: 13px; font-weight: 700; color: rgba(255,255,255,0.6); line-height: 1.6;">
                        <i data-lucide="info" style="width: 16px; margin-right: 5px; vertical-align: middle;"></i>
                        This will create a recurring weekly entry in the student schedule matrix.
                    </p>
                </div>

                <button type="submit" class="btn-primary" style="height: 65px; border-radius: 20px; font-size: 16px; font-weight: 900; box-shadow: 0 15px 35px var(--primary-glow);">
                    Commit to Timeline
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Mobile Interface (Minimalist) -->
<div class="mobile-only-layout" style="background: #f8fafc;">
    <div style="padding: 30px 24px;">
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px;">
            <a href="course_details?id=<?php echo $course_id; ?>" style="color: #0f172a;"><i data-lucide="chevron-left" style="width: 28px;"></i></a>
            <h2 style="font-size: 24px; font-weight: 900; letter-spacing: -1px;">Schedule</h2>
        </div>

        <form action="" method="POST" style="background: white; padding: 25px; border-radius: 35px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
            <input type="hidden" name="add_schedule" value="1">
            <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 20px;">Add New Class</h3>
            
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <select name="day_of_week" style="width: 100%; height: 50px; border-radius: 14px; padding: 0 15px; border: 1.5px solid #f1f5f9; font-weight: 700;">
                    <?php foreach ($days as $val => $name): ?>
                        <option value="<?php echo $val; ?>"><?php echo $name; ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="display: flex; gap: 10px;">
                    <input type="time" name="start_time" required style="flex: 1; height: 50px; border-radius: 14px; padding: 0 15px; border: 1.5px solid #f1f5f9; font-weight: 700;">
                    <input type="time" name="end_time" required style="flex: 1; height: 50px; border-radius: 14px; padding: 0 15px; border: 1.5px solid #f1f5f9; font-weight: 700;">
                </div>
                <input type="text" name="location" placeholder="Location" style="width: 100%; height: 50px; border-radius: 14px; padding: 0 15px; border: 1.5px solid #f1f5f9; font-weight: 700;">
                <button type="submit" class="btn-primary" style="height: 50px; border-radius: 14px; font-weight: 800;">Add Class</button>
            </div>
        </form>

        <h3 style="font-size: 13px; font-weight: 850; color: #94a3b8; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 1px;">Active Schedule</h3>
        <?php foreach ($schedules as $item): ?>
            <div style="background: white; padding: 20px; border-radius: 28px; border: 1px solid #e2e8f0; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <span style="font-size: 10px; font-weight: 800; color: var(--primary); text-transform: uppercase;"><?php 
                        $d = $item['day_of_week'];
                        echo isset($days[$d]) ? $days[$d] : htmlspecialchars($d); 
                    ?></span>
                    <h4 style="font-size: 15px; font-weight: 800; margin-top: 2px;"><?php echo date('h:i A', strtotime($item['start_time'])); ?> - <?php echo date('h:i A', strtotime($item['end_time'])); ?></h4>
                    <p style="font-size: 12px; color: #64748b; margin-top: 2px;"><?php echo htmlspecialchars($item['location']); ?></p>
                </div>
                <a href="?course_id=<?php echo $course_id; ?>&delete=<?php echo $item['id']; ?>" onclick="return confirm('Delete?')" style="width: 36px; height: 36px; background: #fef2f2; color: #ef4444; border-radius: 10px; display: flex; align-items: center; justify-content: center; text-decoration: none;">
                    <i data-lucide="trash-2" style="width: 16px;"></i>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <div style="height: 100px;"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
</script>

<?php include '../includes/footer.php'; ?>
