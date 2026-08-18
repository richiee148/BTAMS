<?php
session_start();
include('../config.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$recordId = intval($_POST['record_id'] ?? 0);
$collegeId = intval($_SESSION['college_id'] ?? 0);

if ($recordId <= 0 || $collegeId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid record ID']);
    exit();
}

$stmt = $conn->prepare('DELETE FROM records WHERE record_id = ? AND college_id = ?');
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

$stmt->bind_param('ii', $recordId, $collegeId);
$stmt->execute();
$affectedRows = $stmt->affected_rows;
$stmt->close();

if ($affectedRows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Record not found or already deleted']);
}
exit();
