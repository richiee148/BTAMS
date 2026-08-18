<?php
require 'config.php';
$res = $conn->query('DESCRIBE records');
if (!$res) {
    echo 'ERROR: ' . $conn->error . PHP_EOL;
    exit(1);
}
while ($row = $res->fetch_assoc()) {
    echo implode(' | ', $row) . PHP_EOL;
}
$conn->close();
?>