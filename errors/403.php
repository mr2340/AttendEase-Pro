<?php
require_once '../includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - AttendEase Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { --primary: #ef4444; --bg: #fef2f2; --surface: #ffffff; --text: #7f1d1d; --muted: #b91c1c; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg); display: flex; align-items: center; justify-content: center; height: 100vh; overflow: hidden; color: var(--text); padding: 20px; }
        .error-container { text-align: center; max-width: 400px; width: 100%; background: var(--surface); padding: 40px 30px; border-radius: 40px; box-shadow: 0 25px 50px -12px rgba(239,68,68,0.1); }
        .icon-box { width: 80px; height: 80px; background: rgba(239, 68, 68, 0.1); color: var(--primary); border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px; }
        h1 { font-size: 80px; font-weight: 800; line-height: 1; margin-bottom: 10px; color: var(--primary); }
        h2 { font-size: 20px; font-weight: 700; margin-bottom: 15px; }
        p { color: var(--muted); font-size: 15px; line-height: 1.6; margin-bottom: 30px; }
        .btn { display: inline-block; background: var(--primary); color: white; text-decoration: none; padding: 16px 30px; border-radius: 20px; font-weight: 700; transition: 0.3s; box-shadow: 0 10px 20px rgba(239,68,68,0.2); }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="icon-box"><i data-lucide="shield-off" size="40"></i></div>
        <h1>403</h1>
        <h2>Access Denied</h2>
        <p>You don't have the required permissions to view this secure resource.</p>
        <a href="<?php echo BASE_URL; ?>" class="btn">Back to Dashboard</a>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
