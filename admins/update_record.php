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

// Debug: uncomment to check
// error_log("Update attempt: recordId=$recordId, collegeId=$collegeId, projectTitle=$projectTitle");

if ($recordId <= 0 || $collegeId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid record ID or college ID']);
    exit();
}

$projectTitle = trim($_POST['project_title_id'] ?? '');
$approvedFunds = floatval($_POST['approved_funds'] ?? 0);
$actualExpenditure = floatval($_POST['actual_expenditure'] ?? 0);
$projectStatus = trim($_POST['project_status'] ?? '');

if (empty($projectTitle) || $approvedFunds < 0 || $actualExpenditure < 0 || empty($projectStatus)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

$remainingBudget = $approvedFunds - $actualExpenditure;

$stmt = $conn->prepare('UPDATE records SET project_title_id = ?, approved_funds = ?, actual_expenditure = ?, remaining_budget = ?, project_status = ? WHERE record_id = ? AND college_id = ?');
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

$stmt->bind_param('sdddisi', $projectTitle, $approvedFunds, $actualExpenditure, $remainingBudget, $projectStatus, $recordId, $collegeId);
$stmt->execute();
$affectedRows = $stmt->affected_rows;
$stmt->close();

if ($affectedRows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Record not found or no changes made']);
}
exit();
