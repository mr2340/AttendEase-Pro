<?php
/**
 * AttendEase Pro - Login Processor
 */
require_once __DIR__ . '/config.php';
session_start();

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
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // Determine if it's an AJAX request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $isFetch = strpos($accept, 'application/json') !== false;

        if ($isAjax || $isFetch || isset($_POST['ajax'])) {
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
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strpos(($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false) {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
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
