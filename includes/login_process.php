<?php
/**
 * AttendEase Pro - Login Processor
 */
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $_POST['identifier'] ?? ''; // Can be student_id or username
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all fields.']);
        exit;
    }

    $db = get_db_connection();
    $stmt = $db->prepare("SELECT * FROM users WHERE student_id = ? OR username = ? LIMIT 1");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Login success
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['student_id'] = $user['student_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['role'] = $user['role'];
        
        // Determine if it's an AJAX/Fetch request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $isFetch = strpos($accept, 'application/json') !== false;
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isJsonPost = strpos($contentType, 'application/json') !== false;

        if ($isAjax || $isFetch || $isJsonPost || isset($_POST['ajax'])) {
            echo json_encode([
                'success' => true, 
                'message' => 'Login successful!',
                'role' => $user['role']
            ]);
        } else {
            // Standard form fallback - bulletproof redirection
            $dashboard = ($user['role'] === 'lecturer' || $user['role'] === 'admin') ? 'lecturer/dashboard' : 'student/dashboard';
            header("Location: " . BASE_URL . $dashboard);
        }
        exit;
    } else {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $isFetch = strpos($accept, 'application/json') !== false;

        if ($isAjax || $isFetch || isset($_POST['ajax'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials. Please verify your ID and password.']);
        } else {
            $referrer = $_SERVER['HTTP_REFERER'] ?? BASE_URL;
            header("Location: " . $referrer . "?error=invalid");
        }
        exit;
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method: ' . $_SERVER['REQUEST_METHOD'] . '. Please use POST.'
    ]);
}
?>
