<?php
include 'config.php';
$records = $conn->query("SELECT * FROM records ORDER BY record_id DESC");
?>