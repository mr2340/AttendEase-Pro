<?php
session_start();
$_SESSION['user_id'] = 2; // Lecturer ID
$_SESSION['role'] = 'lecturer';
$_SESSION['username'] = 'lecturer1';
ob_start();
require 'lecturer/dashboard.php';
$output = ob_get_clean();
echo "SUCCESS";
?>
