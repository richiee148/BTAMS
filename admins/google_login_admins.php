<?php
session_start();
$_SESSION['role'] = 'admin';
header("Location: ../googleAuth/google_login.php");
exit();
?>