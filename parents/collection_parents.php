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

$allocationRows = [];
$stmt = $conn->prepare(
    "SELECT 
        r.record_id, 
        r.project_title_id, 
        r.classification_id, 
        r.approved_funds, 
        r.actual_expenditure, 
        r.project_status 
    FROM records r 
    WHERE r.college_id = ? 
    ORDER BY r.record_id DESC LIMIT 5"
);
if ($stmt) {
    $stmt->bind_param("i", $collegeId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $allocationRows[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Collection Report</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/collection_user.css">
</head>
<body>
  <?php include('sidebar_parents.php'); ?>
  <?php include('topbar_parents.php'); ?>

<div class="report-container">
  <div class="report-header">
    <div class="school-year-selector">
      <label for="schoolYear">Select School Year:</label>
      <select id="schoolYear" name="schoolYear" onchange="filterSchoolYear()">
        <option value="2024-2025">2024-2025</option>
        <option value="2025-2026" selected>2025-2026</option>
      </select>
    </div>
    <button class="download-btn">Download Excel Copy</button>
  </div>

  <div class="kpi-cards-grid">
    <div class="kpi-card approved-card">
      <div class="card-label">TOTAL APPROVED FUNDS</div>
      <div class="card-value">₱640,000</div>
    </div>
    <div class="kpi-card actual-card">
      <div class="card-label">TOTAL EXPENDITURE</div>
      <div class="card-value">₱578,500</div>
    </div>
    <div class="kpi-card remaining-card">
      <div class="card-label">TOTAL REMAINING BALANCE</div>
      <div class="card-value">₱61,500</div>
    </div>
  </div>

  <div class="project-table-container">
    <table class="project-table">
      <thead>
        <tr>
          <th>Project Title</th>
          <th>Activity Classification</th>
          <th>College</th>
          <th>Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr data-year="2024-2025">
          <td class="project-title">Week of Welcoming</td>
          <td>AV & Technical Services</td>
          <td>COT</td>
          <td>03-06- 2025</td>
          <td><span class="status-badge status-completed">Completed</span></td>
        </tr>
        <tr data-year="2025-2026">
          <td class="project-title">Intramurals Budget Allocation</td>
          <td>Wardrobe & Styling</td>
          <td>CON</td>
          <td>01-24-2026</td>
          <td><span class="status-badge status-completed">Completed</span></td>
        </tr>
        <tr data-year="2024-2025">
          <td class="project-title">Freshman Night</td>
          <td>Events / Socials</td>
          <td>ALL</td>
          <td>09-12-2024</td>
          <td><span class="status-badge status-completed">Completed</span></td>
        </tr>
        <tr data-year="2024-2025">
          <td class="project-title">Year-End Gala</td>
          <td>Catering</td>
          <td>COB</td>
          <td>05-20-2025</td>
          <td><span class="status-badge status-completed">Completed</span></td>
        </tr>
      </tbody>
      <tfoot>
        <tr>
          <td></td>
        </tr>
      </tfoot>
    </table>
  </div>

  <div class="report-footer">
    <p class="footer-note" id="footer-note">Note: This report is for viewing purposes only. Currently viewing School Year 2025-2026.</p>
  </div>
</div>

<script>
  function filterSchoolYear() {
    const selectedYear = document.getElementById('schoolYear').value;
    document.querySelectorAll('table.project-table tbody tr').forEach(row => {
      row.style.display = row.dataset.year === selectedYear ? '' : 'none';
    });
    document.getElementById('footer-note').textContent = 'Note: This report is for viewing purposes only. Currently viewing School Year ' + selectedYear + '.';
  }

  window.addEventListener('DOMContentLoaded', filterSchoolYear);
</script>

</body>
</html>