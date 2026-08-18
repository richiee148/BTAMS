<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();
include '../config.php';

header('Content-Type: application/json');

// ── Helper: send JSON and exit ──────────────────────────────────────────────
function respond(bool $success, string $messageOrError, int $id = 0): void {
    if ($success) {
        echo json_encode(['success' => true, 'message' => $messageOrError, 'id' => $id]);
    } else {
        error_log('[process_record.php] ' . $messageOrError);
        echo json_encode(['success' => false, 'error' => $messageOrError]);
    }
    exit;
}

// ── Only accept POST ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

// ── Collect & sanitise POST values ──────────────────────────────────────────
$project_title_id   = isset($_POST['project_title_id'])   ? (int)   $_POST['project_title_id']   : 0;
$classification_id  = isset($_POST['classification_id'])  ? (int)   $_POST['classification_id']  : 0;
$official_id        = isset($_POST['official_id'])         ? (int)   $_POST['official_id']         : 0;
$college_id         = isset($_POST['college_id'])          ? (int)   $_POST['college_id']          : 0;
$school_year        = trim($_POST['school_year']        ?? '');
$date_proposed      = trim($_POST['date_proposed']      ?? '');
$transaction_type   = trim($_POST['transaction_type']   ?? '');
$project_status     = trim($_POST['project_status']     ?? '');

// FIX 1: use !empty() so blank strings properly default to 0.0
$approved_funds     = !empty($_POST['approved_funds'])     ? (float) $_POST['approved_funds']     : 0.0;
$actual_expenditure = !empty($_POST['actual_expenditure']) ? (float) $_POST['actual_expenditure'] : 0.0;
$remaining_budget   = max(0.0, $approved_funds - $actual_expenditure);

// ── Whitelist constants ──────────────────────────────────────────────────────
$allowedTransactionTypes = ['Disbursement', 'Appropriation'];
$allowedProjectStatuses  = ['Pending', 'In Progress', 'Completed'];

// ── Required-field validation ────────────────────────────────────────────────
if (!$project_title_id)       respond(false, 'Project title is required.');
if (!$classification_id)      respond(false, 'Classification is required.');
if (!$official_id)            respond(false, 'Official is required.');
if (empty($school_year))      respond(false, 'School year is required.');
if (empty($date_proposed))    respond(false, 'Date proposed is required.');
if (empty($transaction_type)) respond(false, 'Transaction type is required.');
if (empty($project_status))   respond(false, 'Project status is required.');
if ($approved_funds < 0)      respond(false, 'Approved funds cannot be negative.');
if ($actual_expenditure < 0)  respond(false, 'Actual expenditure cannot be negative.');

if (!in_array($transaction_type, $allowedTransactionTypes, true))
    respond(false, 'Invalid transaction type: ' . $transaction_type);

if (!in_array($project_status, $allowedProjectStatuses, true))
    respond(false, 'Invalid project status: ' . $project_status);

// ── Validate date format ─────────────────────────────────────────────────────
$dateObj = DateTime::createFromFormat('Y-m-d', $date_proposed);
if (!$dateObj || $dateObj->format('Y-m-d') !== $date_proposed)
    respond(false, 'Invalid date format for date proposed.');

// ── Verify project_title_id exists ──────────────────────────────────────────
$stmt = $conn->prepare("SELECT project_title_id FROM project_titles WHERE project_title_id = ?");
if (!$stmt) respond(false, 'DB error (project title): ' . $conn->error);
$stmt->bind_param("i", $project_title_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0)
    respond(false, 'Selected project title does not exist.');
$stmt->close();

// ── Verify classification_id exists ─────────────────────────────────────────
$stmt = $conn->prepare("SELECT classification_id FROM activity_classifications WHERE classification_id = ?");
if (!$stmt) respond(false, 'DB error (classification): ' . $conn->error);
$stmt->bind_param("i", $classification_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0)
    respond(false, 'Selected classification does not exist.');
$stmt->close();

// ── FIX 2: Verify official exists in users table (NOT positions table) ───────
// Your DB had: FOREIGN KEY (official_id) REFERENCES positions(position_id)  <- WRONG
// It should be: FOREIGN KEY (official_id) REFERENCES users(id)              <- CORRECT
// Run this SQL in phpMyAdmin to fix the FK before deploying:
//   ALTER TABLE records DROP FOREIGN KEY records_ibfk_4;
//   ALTER TABLE records ADD CONSTRAINT records_ibfk_4
//     FOREIGN KEY (official_id) REFERENCES users(id);
if ($college_id > 0) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND college_id = ? AND role = 'admin'");
    if (!$stmt) respond(false, 'DB error (official): ' . $conn->error);
    $stmt->bind_param("ii", $official_id, $college_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0)
        respond(false, 'Selected official does not belong to this college or is not an admin. (official_id=' . $official_id . ', college_id=' . $college_id . ')');
    $stmt->close();
} else {
    // No college in session — verify user exists, is admin, and derive college_id from them
    $stmt = $conn->prepare("SELECT id, college_id FROM users WHERE id = ? AND role = 'admin'");
    if (!$stmt) respond(false, 'DB error (official fallback): ' . $conn->error);
    $stmt->bind_param("i", $official_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0)
        respond(false, 'Selected official does not exist or is not an admin.');
    $row = $res->fetch_assoc();
    $college_id = (int)($row['college_id'] ?? 0);
    $stmt->close();
}

// ── Check for duplicate record ──────────────────────────────────────────────
$stmt = $conn->prepare("SELECT record_id FROM records WHERE project_title_id = ? AND college_id = ? AND date_proposed = ?");
if (!$stmt) respond(false, 'DB error (duplicate check): ' . $conn->error);
$stmt->bind_param("iis", $project_title_id, $college_id, $date_proposed);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close();
    respond(true, 'Record already exists.', 0); // Return success with ID 0 or handle as needed
}
$stmt->close();

// ── Insert record ────────────────────────────────────────────────────────────
$sql = "INSERT INTO records
        (project_title_id, college_id, classification_id, official_id,
         school_year, date_proposed, transaction_type,
         approved_funds, actual_expenditure, remaining_budget, project_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) respond(false, 'Prepare failed: ' . $conn->error);

// 4 ints, 3 strings, 3 doubles, 1 string = "iiiisssddds" (11 params)
$bind = $stmt->bind_param(
    "iiiisssddds",
    $project_title_id,
    $college_id,
    $classification_id,
    $official_id,
    $school_year,
    $date_proposed,
    $transaction_type,
    $approved_funds,
    $actual_expenditure,
    $remaining_budget,
    $project_status
);
if (!$bind) respond(false, 'Bind failed: ' . $stmt->error);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    $stmt->close();
    $conn->close();
    respond(true, 'Record added successfully.', $newId);
} else {
    $err = $stmt->error;
    $stmt->close();
    $conn->close();
    respond(false, 'Insert failed: ' . $err);
}