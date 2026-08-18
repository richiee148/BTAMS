<?php
session_start();
include('../config.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login_admins.php');
    exit();
}

$collegeId = intval($_SESSION['college_id'] ?? 0);
$recordId = isset($_GET['record_id']) ? intval($_GET['record_id']) : 0;
$exportAll = isset($_GET['export']) && $_GET['export'] === 'all';

$rows = [];
if ($exportAll) {
    $stmt = $conn->prepare("SELECT record_id, project_title_id, classification_id, transaction_type, approved_funds, actual_expenditure, remaining_budget, project_status, official_id, school_year, DATE_FORMAT(date_proposed, '%Y-%m-%d') AS date_proposed FROM records WHERE college_id = ? ORDER BY date_proposed DESC");
    if ($stmt) {
        $stmt->bind_param('i', $collegeId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
    }
    $filename = 'sbo_reports_' . date('Ymd_His') . '.csv';
} else {
    if ($recordId <= 0) {
        header('Location: reports_admins.php');
        exit();
    }
    $stmt = $conn->prepare("SELECT record_id, project_title_id, classification_id, transaction_type, approved_funds, actual_expenditure, remaining_budget, project_status, official_id, school_year, DATE_FORMAT(date_proposed, '%Y-%m-%d') AS date_proposed FROM records WHERE record_id = ? AND college_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('ii', $recordId, $collegeId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
    }
    $filename = 'sbo_report_' . $recordId . '_' . date('Ymd_His') . '.csv';
}

if (empty($rows)) {
    header('Location: reports_admins.php?error=no_data');
    exit();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
if ($output === false) {
    exit();
}

fputcsv($output, [
    'Record ID',
    'Project Title',
    'Classification',
    'Transaction Type',
    'Approved Funds',
    'Actual Expenditure',
    'Remaining Budget',
    'Project Status',
    'Official',
    'School Year',
    'Date Proposed'
]);

foreach ($rows as $row) {
    fputcsv($output, [
        $row['record_id'],
        $row['project_title_id'],
        $row['classification_id'],
        $row['transaction_type'],
        $row['approved_funds'],
        $row['actual_expenditure'],
        $row['remaining_budget'],
        $row['project_status'],
        $row['official_id'],
        $row['school_year'],
        $row['date_proposed'],
    ]);
}

fclose($output);
exit();
