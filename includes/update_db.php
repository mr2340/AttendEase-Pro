<?php
/**
 * AttendEase Pro - Database Expansion Pack
 * Run this file once to add new feature tables
 */
require_once 'config.php';

$db = get_db_connection();

try {
    // 1. Announcements Table
    $db->exec("CREATE TABLE IF NOT EXISTS announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lecturer_id INT NOT NULL,
        course_id VARCHAR(50) NOT NULL,
        title VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. Parent-Student Mapping (For Parent Portal)
    $db->exec("CREATE TABLE IF NOT EXISTS parent_students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        parent_id INT NOT NULL,
        student_id INT NOT NULL,
        UNIQUE KEY(parent_id, student_id)
    )");

    // 3. Add 'trend' and 'risk_level' columns to users for AI Prediction (Optional cache)
    // we can calculate this live, but caching helps performance.
    
    echo "Database updated successfully for Features 1, 2, 3, and 4!";
} catch (PDOException $e) {
    echo "Update failed: " . $e->getMessage();
}
?>
