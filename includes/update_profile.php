<?php
/**
 * AttendEase Pro - Profile Update Processor (v2 with File Upload)
 */
require_once __DIR__ . '/config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

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
        
        // Handle Avatar Upload
        $avatar_path = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['avatar']['tmp_name'];
            $file_name = $_FILES['avatar']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($file_ext, $allowed_ext)) {
                $new_file_name = "avatar_" . $user_id . "_" . time() . "." . $file_ext;
                $upload_dir = __DIR__ . "/../assets/uploads/avatars/";
                
                if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                    $avatar_path = "assets/uploads/avatars/" . $new_file_name;
                }
            }
        }

        // Build Query
        if ($avatar_path) {
            $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, bio_enabled = ?, dark_mode = ?, avatar_url = ? WHERE id = ?");
            $stmt->execute([$username, $email, $bio_enabled, $dark_mode, $avatar_path, $user_id]);
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
