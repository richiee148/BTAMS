<?php
session_start();
$_SESSION['role'] = 'student';
header("Location: ../googleAuth/google_login.php");
exit();
?>