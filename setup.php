<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $host = $_POST['host'] ?? 'localhost';
    $user = $_POST['user'] ?? 'root';
    $pass = $_POST['pass'] ?? '';
    $dbname = $_POST['dbname'] ?? 'attendease_db';
    
    try {
        // Connect to MySQL server without database
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Read database.sql
        $sqlFile = __DIR__ . '/database.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("database.sql file not found!");
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Replace the hardcoded database name if a different one is provided
        if ($dbname !== 'attendease_db') {
            $sql = str_replace('attendease_db', $dbname, $sql);
        }
        
        // Execute the SQL queries
        $pdo->exec($sql);
        
        // --- EXTRA SEEDING AND SCHEMA UPDATES ---
        
        // 1. Create admin_notifications table
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // 2. Seed 3 more lecturers
        $pdo->exec("INSERT IGNORE INTO users (student_id, username, password, fullname, role) VALUES
        (NULL, 'dr_jones', '\$2y\$10\$Av3e3cUeQP36n/PMG/Ip7uxrkW29kWeeRPbHx4DnQhlsaTyuZv7KS', 'Dr. Indiana Jones', 'lecturer'),
        (NULL, 'prof_oak', '\$2y\$10\$Av3e3cUeQP36n/PMG/Ip7uxrkW29kWeeRPbHx4DnQhlsaTyuZv7KS', 'Prof. Samuel Oak', 'lecturer'),
        (NULL, 'mr_feeny', '\$2y\$10\$Av3e3cUeQP36n/PMG/Ip7uxrkW29kWeeRPbHx4DnQhlsaTyuZv7KS', 'Mr. George Feeny', 'lecturer')");
        
        // 3. Seed 80 students dynamically
        $students_sql = "INSERT IGNORE INTO users (student_id, username, password, fullname, role) VALUES ";
        $student_values = [];
        for ($i = 3; $i < 83; $i++) {
            $padded_i = str_pad($i, 3, '0', STR_PAD_LEFT);
            $student_values[] = "('STU{$padded_i}', 'student{$i}', '\$2y\$10\$Av3e3cUeQP36n/PMG/Ip7uxrkW29kWeeRPbHx4DnQhlsaTyuZv7KS', 'Student {$i}', 'student')";
        }
        $pdo->exec($students_sql . implode(", ", $student_values));
        
        // 4. Enroll the students in random courses
        $enroll_sql = "INSERT IGNORE INTO enrollments (student_id, course_id) VALUES ";
        $enroll_values = [];
        $course_ids = [1, 2, 3, 4];
        // Assuming students start from ID 7 or so, we will just use the STU ids to get the user IDs
        $stmt = $pdo->query("SELECT id FROM users WHERE role = 'student'");
        $student_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($student_ids as $sid) {
            $keys = array_rand($course_ids, 2);
            foreach ((array)$keys as $k) {
                $enroll_values[] = "({$sid}, {$course_ids[$k]})";
            }
        }
        if (!empty($enroll_values)) {
            $pdo->exec($enroll_sql . implode(", ", $enroll_values));
        }
        
        // 5. Seed 10 classes per day
        $sched_sql = "INSERT IGNORE INTO schedules (course_id, day_of_week, start_time, end_time, location) VALUES ";
        $sched_values = [];
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        foreach ($days as $day) {
            for ($i = 0; $i < 10; $i++) {
                $c_id = $course_ids[array_rand($course_ids)];
                $start_hour = 8 + ($i % 8);
                $start_time = sprintf("%02d:00:00", $start_hour);
                $end_time = sprintf("%02d:00:00", $start_hour + 1);
                $loc = "Room " . rand(100, 200);
                $sched_values[] = "({$c_id}, '{$day}', '{$start_time}', '{$end_time}', '{$loc}')";
            }
        }
        $pdo->exec($sched_sql . implode(", ", $sched_values));
        
        // ----------------------------------------
        
        // Write to .env
        $envFile = __DIR__ . '/.env';
        $envContent = "";
        
        if (file_exists($envFile)) {
            $envContent = file_get_contents($envFile);
            
            // Replace existing DB config
            $envContent = preg_replace('/^DB_HOST=.*$/m', "DB_HOST=\"$host\"", $envContent);
            $envContent = preg_replace('/^DB_USER=.*$/m', "DB_USER=\"$user\"", $envContent);
            $envContent = preg_replace('/^DB_PASS=.*$/m', "DB_PASS=\"$pass\"", $envContent);
            $envContent = preg_replace('/^DB_NAME=.*$/m', "DB_NAME=\"$dbname\"", $envContent);
            
            // If they didn't exist, append them
            if (!strpos($envContent, 'DB_HOST=')) $envContent .= "\nDB_HOST=\"$host\"";
            if (!strpos($envContent, 'DB_USER=')) $envContent .= "\nDB_USER=\"$user\"";
            if (!strpos($envContent, 'DB_PASS=')) $envContent .= "\nDB_PASS=\"$pass\"";
            if (!strpos($envContent, 'DB_NAME=')) $envContent .= "\nDB_NAME=\"$dbname\"";
            
        } else {
            $envContent = "DB_HOST=\"$host\"\nDB_USER=\"$user\"\nDB_PASS=\"$pass\"\nDB_NAME=\"$dbname\"\nAPP_NAME=\"AttendEase Pro\"\nSECURE_KEY=\"" . bin2hex(random_bytes(32)) . "\"";
        }
        
        file_put_contents($envFile, trim($envContent));
        
        echo json_encode(['success' => true, 'message' => 'Database created successfully!']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

$defaultHost = 'localhost';
$defaultUser = 'root';
$defaultPass = '';
$defaultDb = 'attendease_db';

if (file_exists(__DIR__ . '/.env')) {
    $envLines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\n\r\0\x0B\"'");
            if ($key === 'DB_HOST') $defaultHost = $val;
            if ($key === 'DB_USER') $defaultUser = $val;
            if ($key === 'DB_PASS') $defaultPass = $val;
            if ($key === 'DB_NAME') $defaultDb = $val;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AttendEase Pro - Setup Workspace</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0d1117;
            --container-bg: #161b22;
            --border-color: #30363d;
            --text-main: #c9d1d9;
            --accent: #58a6ff;
            --success: #2ea043;
            --error: #f85149;
            --muted: #8b949e;
            --font-mono: 'JetBrains Mono', monospace;
        }

        body {
            font-family: var(--font-mono);
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .terminal {
            background: var(--container-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            overflow: hidden;
        }

        .terminal-header {
            background: #010409;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }

        .window-controls {
            display: flex;
            gap: 8px;
        }

        .control {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        .close { background: #ff5f56; }
        .minimize { background: #ffbd2e; }
        .maximize { background: #27c93f; }

        .terminal-title {
            margin: 0 auto;
            color: var(--muted);
            font-size: 12px;
            transform: translateX(-20px);
        }

        .terminal-body {
            padding: 30px;
        }

        .line {
            margin-bottom: 20px;
            font-size: 14px;
        }

        .line span.prompt {
            color: var(--success);
            margin-right: 10px;
            font-weight: bold;
        }

        .line span.path {
            color: var(--accent);
            margin-right: 10px;
        }

        h1 {
            font-size: 18px;
            margin: 0 0 15px 0;
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .form-group label {
            width: 150px;
            color: var(--muted);
            font-size: 14px;
        }

        .form-group label::after {
            content: ':';
        }

        .input-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            background: transparent;
        }

        .input-wrapper::before {
            content: '>';
            color: var(--accent);
            margin-right: 8px;
        }

        input {
            background: transparent;
            border: none;
            color: var(--text-main);
            font-family: var(--font-mono);
            font-size: 14px;
            width: 100%;
            outline: none;
            caret-color: var(--accent);
        }
        
        input::placeholder {
            color: #484f58;
        }

        input:focus {
            border-bottom: 1px solid var(--accent);
        }

        button {
            background: transparent;
            border: 1px solid var(--accent);
            color: var(--accent);
            font-family: var(--font-mono);
            padding: 8px 16px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 15px;
            border-radius: 4px;
        }

        button:hover {
            background: var(--accent);
            color: var(--bg-color);
        }

        .log-output {
            display: none;
            margin-top: 20px;
            border-top: 1px dashed var(--border-color);
            padding-top: 20px;
            font-size: 13px;
        }

        .log-line {
            margin-bottom: 6px;
            opacity: 0;
            animation: fadeIn 0.1s forwards;
        }

        .log-line.error { color: var(--error); }
        .log-line.success { color: var(--success); }
        .log-line.info { color: var(--muted); }

        @keyframes fadeIn {
            to { opacity: 1; }
        }

        .blinking-cursor {
            display: inline-block;
            width: 8px;
            height: 15px;
            background: var(--text-main);
            animation: blink 1s step-end infinite;
            vertical-align: middle;
            margin-left: 2px;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }

        #setup-form-wrapper {
            transition: opacity 0.3s;
        }

        .action-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--bg-color);
            background: var(--success);
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            opacity: 0;
        }
        
        .action-link:hover {
            opacity: 0.9 !important;
        }
    </style>
</head>
<body>

    <div class="terminal">
        <div class="terminal-header">
            <div class="window-controls">
                <div class="control close"></div>
                <div class="control minimize"></div>
                <div class="control maximize"></div>
            </div>
            <div class="terminal-title">admin@attendease:~</div>
        </div>
        <div class="terminal-body">
            
            <div id="setup-form-wrapper">
                <div class="line">
                    <span class="prompt">admin@attendease</span>:<span class="path">~/setup</span>$ ./install.sh
                </div>
                
                <h1>[AttendEase Setup Initializer]</h1>
                <p style="color: var(--muted); font-size: 13px; margin-bottom: 25px;">Enter database connection parameters to build the environment.</p>

                <form id="setupForm">
                    <div class="form-group">
                        <label for="host">DB_HOST</label>
                        <div class="input-wrapper">
                            <input type="text" id="host" name="host" value="<?php echo htmlspecialchars($defaultHost); ?>" required autocomplete="off">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="user">DB_USER</label>
                        <div class="input-wrapper">
                            <input type="text" id="user" name="user" value="<?php echo htmlspecialchars($defaultUser); ?>" required autocomplete="off">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="pass">DB_PASS</label>
                        <div class="input-wrapper">
                            <input type="password" id="pass" name="pass" value="<?php echo htmlspecialchars($defaultPass); ?>" placeholder="<empty>" autocomplete="new-password">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="dbname">DB_NAME</label>
                        <div class="input-wrapper">
                            <input type="text" id="dbname" name="dbname" value="<?php echo htmlspecialchars($defaultDb); ?>" required autocomplete="off">
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 10px;">
                        <button type="submit" id="submitBtn">Execute</button>
                        <span id="active-cursor" class="blinking-cursor"></span>
                    </div>
                </form>
            </div>

            <div class="log-output" id="logOutput">
                <!-- Logs will appear here -->
            </div>
            <a href="index.php" class="action-link" id="continueLink">Launch Application -></a>
        </div>
    </div>

    <script>
        const form = document.getElementById('setupForm');
        const submitBtn = document.getElementById('submitBtn');
        const logOutput = document.getElementById('logOutput');
        const activeCursor = document.getElementById('active-cursor');
        const continueLink = document.getElementById('continueLink');
        const formWrapper = document.getElementById('setup-form-wrapper');
        
        function addLog(message, type = 'info', delay = 0) {
            return new Promise(resolve => {
                setTimeout(() => {
                    const line = document.createElement('div');
                    line.className = `log-line ${type}`;
                    
                    const timestamp = new Date().toISOString().split('T')[1].substring(0, 8);
                    line.innerHTML = `<span style="color: #484f58;">[${timestamp}]</span> ${message}`;
                    
                    logOutput.appendChild(line);
                    logOutput.scrollTop = logOutput.scrollHeight;
                    resolve();
                }, delay);
            });
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(form);
            
            // UI updates
            activeCursor.style.display = 'none';
            const inputs = form.querySelectorAll('input');
            inputs.forEach(input => input.disabled = true);
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.5';
            
            logOutput.style.display = 'block';
            logOutput.innerHTML = '';
            
            await addLog('Initializing database deployment sequence...', 'info', 100);
            await addLog('Connecting to MySQL host...', 'info', 400);
            
            try {
                const response = await fetch('setup.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    await addLog('Connection established successfully.', 'success', 300);
                    await addLog(`Reading schema from database.sql...`, 'info', 500);
                    await addLog(`Executing SQL statements (Creating DB & Tables)...`, 'info', 600);
                    await addLog('Populating demo data...', 'info', 400);
                    await addLog('Updating local .env configuration...', 'info', 300);
                    await addLog('Deployment completed with status: 0 (SUCCESS)', 'success', 500);
                    
                    await addLog('<br>--- credentials ---', 'info', 300);
                    await addLog('Admin User: <strong>admin</strong>', 'success', 100);
                    await addLog('Admin Pass: <strong>password123</strong>', 'success', 100);
                    
                    setTimeout(() => {
                        formWrapper.style.display = 'none';
                        continueLink.style.animation = 'fadeIn 1s forwards';
                    }, 1000);

                } else {
                    await addLog(`FATAL ERROR: ${result.message}`, 'error', 200);
                    await addLog('Deployment aborted.', 'error', 100);
                    
                    // Reset UI
                    inputs.forEach(input => input.disabled = false);
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                    activeCursor.style.display = 'inline-block';
                }
            } catch (error) {
                await addLog('FATAL ERROR: Network or server failure.', 'error', 200);
                
                // Reset UI
                inputs.forEach(input => input.disabled = false);
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                activeCursor.style.display = 'inline-block';
            }
        });
    </script>
</body>
</html>
