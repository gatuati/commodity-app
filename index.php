<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
// Redirect to login page if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
   
    exit();
}
$host = 'sql206.infinityfree.com';
// If the user is logged in, redirect to the dashboard
header("Location: dashboard.php");
exit();
?>