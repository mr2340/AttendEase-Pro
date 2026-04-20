<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$theme_class = "";
if (isset($_SESSION['user_id'])) {
    $db = get_db_connection();
    $theme_stmt = $db->prepare("SELECT dark_mode FROM users WHERE id = ?");
    $theme_stmt->execute([$_SESSION['user_id']]);
    $user_pref = $theme_stmt->fetch();
    if ($user_pref && $user_pref['dark_mode']) {
        $theme_class = "dark-mode";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="manifest" href="<?php echo BASE_URL; ?>manifest.json">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?php echo isset($page_title) ? $page_title . " | " . APP_NAME : APP_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>favicon.ico">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
    
    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js"></script>
    
    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/main.css">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#0066ff">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Cloudinary Widget SDK -->
    <script src="https://upload-widget.cloudinary.com/global/all.js" type="text/javascript"></script>

    <script>
        window.AttendEaseConfig = {
            baseUrl: '<?php echo BASE_URL; ?>',
            fcm: {
                apiKey: '<?php echo FCM_API_KEY; ?>',
                authDomain: '<?php echo FCM_AUTH_DOMAIN; ?>',
                projectId: '<?php echo FCM_PROJECT_ID; ?>',
                storageBucket: '<?php echo FCM_STORAGE_BUCKET; ?>',
                messagingSenderId: '<?php echo FCM_MESSAGING_SENDER_ID; ?>',
                appId: '<?php echo FCM_APP_ID; ?>',
                measurementId: '<?php echo FCM_MEASUREMENT_ID; ?>',
                vapidKey: '<?php echo FCM_VAPID_KEY; ?>'
            },
            cloudinary: {
                cloudName: '<?php echo CLOUDINARY_CLOUD_NAME; ?>',
                uploadPreset: '<?php echo CLOUDINARY_UPLOAD_PRESET; ?>',
                apiKey: '<?php echo CLOUDINARY_API_KEY; ?>'
            }
        };
    </script>
</head>
<body class="<?php echo $theme_class; ?>">
    <div id="app-container">
