<?php
require 'config.php';
$tables = ['project_titles', 'activity_classifications'];
foreach ($tables as $table) {
    echo "TABLE $table:\n";
    $res = $conn->query('DESCRIBE ' . $table);
    if (!$res) {
        echo 'ERROR: ' . $conn->error . PHP_EOL;
        continue;
    }
    while ($row = $res->fetch_assoc()) {
        echo implode(' | ', $row) . PHP_EOL;
    }
    echo PHP_EOL;
}
$conn->close();
?>