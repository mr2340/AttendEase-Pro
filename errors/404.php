<?php
require_once '../includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - AttendEase Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --primary: #0066ff;
            --bg: #f8fafc;
            --surface: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg); display: flex; align-items: center; justify-content: center; height: 100vh; overflow: hidden; color: var(--text); padding: 20px; }
        .error-container { text-align: center; max-width: 400px; width: 100%; background: var(--surface); padding: 40px 30px; border-radius: 40px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.1); }
        .icon-box { width: 80px; height: 80px; background: rgba(0, 102, 255, 0.1); color: var(--primary); border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px; }
        h1 { font-size: 80px; font-weight: 800; line-height: 1; margin-bottom: 10px; background: linear-gradient(135deg, var(--primary), #00d2ff); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        h2 { font-size: 20px; font-weight: 700; margin-bottom: 15px; }
        p { color: var(--muted); font-size: 15px; line-height: 1.6; margin-bottom: 30px; }
        .btn { display: inline-block; background: var(--primary); color: white; text-decoration: none; padding: 16px 30px; border-radius: 20px; font-weight: 700; transition: 0.3s; box-shadow: 0 10px 20px rgba(0,66,255,0.2); }
        .btn:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(0,66,255,0.3); }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="icon-box"><i data-lucide="search-x" size="40"></i></div>
        <h1>404</h1>
        <h2>Oops! Page Lost.</h2>
        <p>The screen you are looking for doesn't exist or has been moved to a new location.</p>
        <a href="<?php echo BASE_URL; ?>" class="btn">Back to Safety</a>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
