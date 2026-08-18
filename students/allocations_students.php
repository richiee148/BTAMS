<?php
session_start();
include('../config.php');

if (!isset($_SESSION['loggedin']) || $_SESSION["loggedin"] !== true) {
    header("Location: login_students.php"); 
    exit();
}

if (empty($_SESSION['college_id']) || empty($_SESSION['code_verified'])) {
    header('Location: /SBO-BTAMS/select_college.php?user_id=' . $_SESSION['id']);
    exit();
}

$name = $_SESSION["name"];
$collegeId = intval($_SESSION["college_id"]);

$allowedYears = ['2023-2024', '2024-2025', '2025-2026', '2026-2027'];
$selectedYear = isset($_GET['school_year']) ? trim($_GET['school_year']) : '2025-2026';
if (!in_array($selectedYear, $allowedYears, true)) {
    $selectedYear = '2025-2026';
}

$allocationRows = [];
$totalApproved = 0;
$totalActual = 0;
$totalRemaining = 0;
$stmt = $conn->prepare(
    "SELECT
        r.record_id,
        COALESCE(pt.project_title, '—') AS project_title,
        COALESCE(c.college_name, '—') AS college_name,
        COALESCE(ac.classification_name, '—') AS classification_name,
        r.date_proposed,
        r.approved_funds,
        r.actual_expenditure,
        COALESCE(r.remaining_budget, r.approved_funds - r.actual_expenditure, 0) AS remaining_budget,
        r.project_status,
        r.school_year
    FROM records r
    LEFT JOIN project_titles pt ON r.project_title_id = pt.project_title_id
    LEFT JOIN colleges c ON r.college_id = c.college_id
    LEFT JOIN activity_classifications ac ON r.classification_id = ac.classification_id
    WHERE r.college_id = ?
    ORDER BY r.record_id DESC"
);
if ($stmt) {
    $stmt->bind_param("i", $collegeId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $allocationRows[] = $row;
        if ($row['school_year'] === $selectedYear) {
            $totalApproved += (float) $row['approved_funds'];
            $totalActual += (float) $row['actual_expenditure'];
            $totalRemaining += (float) $row['remaining_budget'];
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Project Financial Transparency Report</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/allocation_user.css">
</head>
<body>
   <?php include('sidebar_students.php'); ?>
   <?php include('topbar_students.php'); ?>


<div class="report-container">
  <div class="report-header">
    <div class="school-year-selector">
      <label for="schoolYear">Select School Year:</label>
      <select id="schoolYear" name="schoolYear" onchange="filterSchoolYear()">
        <?php foreach ($allowedYears as $yr): ?>
          <option value="<?= $yr ?>" <?= $yr === $selectedYear ? 'selected' : '' ?>><?= $yr ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="download-btn">Download Excel Copy</button>
  </div>

  <div class="kpi-cards-grid">
    <div class="kpi-card approved-card">
      <div class="card-label">APPROVED FUNDS</div>
      <div class="card-value" id="kpi-approved">₱<?= number_format($totalApproved, 2) ?></div>
    </div>
    <div class="kpi-card actual-card">
      <div class="card-label">ACTUAL EXPENDITURE</div>
      <div class="card-value" id="kpi-actual">₱<?= number_format($totalActual, 2) ?></div>
    </div>
    <div class="kpi-card utilization-card">
      <div class="card-label">UTILIZATION RATE</div>
      <div class="card-value" id="kpi-utilization"><?= $totalApproved > 0 ? number_format(($totalActual / $totalApproved) * 100, 1) : 0 ?>%</div>
    </div>
  </div>

  <div class="project-table-container">
    <table class="project-table" id="projectTable">
      <thead>
        <tr>
          <th>Project Title</th>
          <th>College</th>
          <th>Activity Classification</th>
          <th>Date</th>
          <th class="numeric-col">Approved Funds</th>
          <th class="numeric-col">Actual Expenditure</th>
          <th class="numeric-col">Remaining Budget</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($allocationRows)): ?>
          <tr class="no-data-row">
            <td colspan="8">No records found for <?= htmlspecialchars($selectedYear) ?>.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($allocationRows as $row): ?>
            <?php
              $status = trim((string) $row['project_status']);
              $lower = strtolower($status);
              $statusClass = 'status-pending';
              if ($lower === 'completed') {
                $statusClass = 'status-completed';
              } elseif ($lower === 'in progress' || $lower === 'in-progress') {
                $statusClass = 'status-progress';
              } elseif ($lower === 'pending') {
                $statusClass = 'status-pending';
              }
              $formattedDate = !empty($row['date_proposed']) ? date('m-d-Y', strtotime($row['date_proposed'])) : '—';
            ?>
            <tr data-year="<?= htmlspecialchars($row['school_year']) ?>">
              <td class="project-title"><?= htmlspecialchars($row['project_title']) ?></td>
              <td><?= htmlspecialchars($row['college_name']) ?></td>
              <td><?= htmlspecialchars($row['classification_name']) ?></td>
              <td><?= htmlspecialchars($formattedDate) ?></td>
              <td class="numeric-col font-mono" data-value="<?= htmlspecialchars((float) $row['approved_funds']) ?>"><?= number_format((float) $row['approved_funds']) ?></td>
              <td class="numeric-col font-mono" data-value="<?= htmlspecialchars((float) $row['actual_expenditure']) ?>"><?= number_format((float) $row['actual_expenditure']) ?></td>
              <td class="numeric-col font-mono" data-value="<?= htmlspecialchars((float) $row['remaining_budget']) ?>"><?= number_format((float) $row['remaining_budget']) ?></td>
              <td><span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($status ?: 'Unknown') ?></span></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr class="grand-total-row">
          <td colspan="5" class="total-label">GRAND TOTAL:</td>
          <td class="numeric-col total-value font-mono" id="total-approved">₱<?= number_format($totalApproved, 2) ?></td>
          <td class="numeric-col total-value font-mono" id="total-actual">₱<?= number_format($totalActual, 2) ?></td>
          <td class="numeric-col total-value font-mono" id="total-remaining">₱<?= number_format($totalRemaining, 2) ?></td>
          <td></td>
        </tr>
      </tfoot>
    </table>
  </div>

  <div class="report-footer">
    <p class="footer-note" id="footer-note">Note: This report is for viewing purposes only. Currently viewing School Year <?= htmlspecialchars($selectedYear) ?>.</p>
  </div>
</div>

<script>
  function filterSchoolYear() {
    const selector = document.getElementById('schoolYear');
    const selectedYear = selector.value;
    const rows = document.querySelectorAll('#projectTable tbody tr');
    
    let totalApproved = 0;
    let totalActual = 0;
    let totalRemaining = 0;

    rows.forEach(row => {
      if (row.getAttribute('data-year') === selectedYear) {
        row.style.display = '';

        const approvedCell = row.cells[4];
        const actualCell = row.cells[5];
        const remainingCell = row.cells[6];

        const approvedVal = parseFloat(approvedCell?.getAttribute('data-value'));
        const actualVal = parseFloat(actualCell?.getAttribute('data-value'));
        const remainingVal = parseFloat(remainingCell?.getAttribute('data-value'));

        if (!isNaN(approvedVal)) {
          totalApproved += approvedVal;
        }
        if (!isNaN(actualVal)) {
          totalActual += actualVal;
        }
        if (!isNaN(remainingVal)) {
          totalRemaining += remainingVal;
        }
      } else {
        row.style.display = 'none';
      }
    });

    const finalRemaining = totalApproved - totalActual;
    const utilizationRate = totalApproved > 0 ? ((totalActual / totalApproved) * 100).toFixed(1) : 0;

    const fmt = (val) => '₱' + val.toLocaleString('en-US');

    // Update UI Elements
    document.getElementById('kpi-approved').innerText = fmt(totalApproved);
    document.getElementById('kpi-actual').innerText = fmt(totalActual);
    document.getElementById('kpi-utilization').innerText = utilizationRate + '%';

    document.getElementById('total-approved').innerText = fmt(totalApproved);
    document.getElementById('total-actual').innerText = fmt(totalActual);
    document.getElementById('total-remaining').innerText = fmt(finalRemaining);

    document.getElementById('footer-note').innerText = 'Note: This report is for viewing purposes only. Currently viewing School Year ' + selectedYear + '.';
  }

  // Run initialization
  window.addEventListener('DOMContentLoaded', filterSchoolYear);
</script>

</body>
</html>