<?php
include 'config.php';
foreach (['project_titles', 'activity_classifications', 'records'] as $table) {
    echo "--- $table ---\n";
    $result = $conn->query("DESCRIBE $table");
    if (!$result) {
        echo "Error describing $table: " . $conn->error . "\n";
        continue;
    }
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' | ' . $row['Type'] . ' | ' . $row['Null'] . ' | ' . $row['Key'] . ' | ' . $row['Default'] . ' | ' . $row['Extra'] . "\n";
    }
}
?>