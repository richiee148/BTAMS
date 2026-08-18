<?php
session_start();
$_SESSION['role'] = 'parent';
header("Location: ../googleAuth/google_login.php");
exit();
?>