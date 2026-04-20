<?php
/**
 * AttendEase Pro - Profile Update Processor (v2 with File Upload)
 */
require_once __DIR__ . '/config.php';
// AttendEaseSecurity handles session_start safely

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// CSRF Validation for Production
$csrf_token = $_POST['csrf_token'] ?? '';
AttendEaseSecurity::validateCsrf($csrf_token);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $bio_enabled = isset($_POST['bio_enabled']) ? 1 : 0;
    $dark_mode = isset($_POST['dark_mode']) ? 1 : 0;

    if (empty($username) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Username and Email are required.']);
        exit;
    }

    try {
        $db = get_db_connection();
        
        // Handle Cloudinary Avatar URL
        $avatar_url = $_POST['avatar_url'] ?? null;

        // Build Query
        if ($avatar_url) {
            $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, bio_enabled = ?, dark_mode = ?, avatar_url = ? WHERE id = ?");
            $stmt->execute([$username, $email, $bio_enabled, $dark_mode, $avatar_url, $user_id]);
        } else {
            $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, bio_enabled = ?, dark_mode = ? WHERE id = ?");
            $stmt->execute([$username, $email, $bio_enabled, $dark_mode, $user_id]);
        }

        // Update session
        $_SESSION['username'] = $username;

        echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
