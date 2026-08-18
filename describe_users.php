<?php
require 'config.php';
$res = $conn->query('DESCRIBE users');
if (!$res) {
    echo 'ERROR: ' . $conn->error . PHP_EOL;
    exit(1);
}
while ($row = $res->fetch_assoc()) {
    echo implode(' | ', $row) . PHP_EOL;
}

echo "\nSAMPLE project_titles:\n";
$res = $conn->query('SELECT project_title_id, record_title FROM project_titles LIMIT 20');
while ($row = $res->fetch_assoc()) {
    echo $row['project_title_id'] . ' | ' . $row['record_title'] . PHP_EOL;
}

echo "\nSAMPLE activity_classifications:\n";
$res = $conn->query('SELECT classification_id, classification_name FROM activity_classifications LIMIT 20');
while ($row = $res->fetch_assoc()) {
    echo $row['classification_id'] . ' | ' . $row['classification_name'] . PHP_EOL;
}
$conn->close();
?>