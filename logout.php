<?php
session_start(); 

$host = 'sql206.infinityfree.com';
$_SESSION = array();
session_destroy();


header("Location: login.php");
exit(); 
?>