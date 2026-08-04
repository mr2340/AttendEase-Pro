<?php
require_once __DIR__ . '/includes/config.php';

$db = get_db_connection();

// Generate new hash for 'password123'
$new_hash = password_hash('password123', PASSWORD_DEFAULT);

// Update all demo users with the valid hash
$stmt = $db->prepare("UPDATE users SET password = ?");
$stmt->execute([$new_hash]);

echo "All users updated with valid hash for 'password123'.<br>";
echo "The new valid hash is: " . $new_hash . "<br>";
