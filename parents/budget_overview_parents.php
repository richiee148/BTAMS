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
  <title>Student Budget Overview</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/budget_overview_user.css">
</head>
<body>
     <?php include('sidebar_parents.php'); ?>
     <?php include('topbar_parents.php'); ?>


<div class="report-container">
  
  <div class="school-year-row">
    <div class="school-year-selector">
      <label for="schoolYear">Select School Year:</label>
      <select id="schoolYear" name="schoolYear" onchange="filterBySchoolYear(this.value)">
        <?php foreach ($allowedYears as $yr): ?>
          <option value="<?= $yr ?>" <?= $yr === $selectedYear ? 'selected' : '' ?>><?= $yr ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="download-btn">Download Excel Copy</button>
  </div>

  <!-- KPI Cards -->
  <div class="kpi-cards-grid">
    <div class="kpi-card approved-card">
      <div class="card-label">APPROVED BUDGET</div>
      <div class="card-value" id="approvedBudget">₱<?= number_format($totalApproved, 2) ?></div>
    </div>
    <div class="kpi-card actual-card">
      <div class="card-label">TOTAL COLLECTED</div>
      <div class="card-value" id="totalCollected">₱3,500<?= number_format($totalActual, 2) ?></div>
    </div>
    <div class="kpi-card remaining-card">
      <div class="card-label">REMAINING BALANCE</div>
      <div class="card-value" id="remainingBalance">₱<?= number_format($totalRemaining, 2) ?></div>
    </div>
  </div>

  <!-- Visual Analytics Section -->
  <div class="analytics-row-grid">
    <!-- Budget Utilization Gauge -->
    <article class="analytics-card">
      <h2 class="card-title">Budget Utilization</h2>
      <div class="gauge-wrapper">
        <svg viewBox="0 0 100 50" class="gauge-svg">
          <path d="M 10 50 A 40 40 0 0 1 90 50" class="gauge-background" />
          <path d="M 10 50 A 40 40 0 0 1 90 50" class="gauge-fill" stroke-dasharray="113.1 125.6" />
        </svg>
        <div class="gauge-center-text">
          <div class="gauge-percentage">90%</div>
          <div class="gauge-sub">of budget utilized</div>
        </div>
      </div>
      <div class="utilization-description">
        ₱578,500 has been collected out of the total ₱640,000 approved budget. ₱61,500 remains available for future student initiatives.
      </div>
    </article>

    <!-- Income vs Expense Chart -->
    <article class="analytics-card">
      <h2 class="card-title">Collected vs Allocated Summary</h2>
      <div class="chart-content">
        <div class="monthly-bar-chart">
          <div class="y-axis-labels">
            <span>₱2.0M</span>
            <span>₱1.6M</span>
            <span>₱1.2M</span>
            <span>₱0.8M</span>
            <span>₱0.4M</span>
            <span>₱0.0M</span>
          </div>

          <div class="chart-bars-container">
            <div class="month-group">
              <div class="bar-pair">
                <div class="bar bar-green" style="height: 55%;" title="Collected: ₱1.1M"></div>
                <div class="bar bar-navy" style="height: 40%;" title="Allocated: ₱0.8M"></div>
              </div>
              <span class="month-label">Jan</span>
            </div>

            <div class="month-group">
              <div class="bar-pair">
                <div class="bar bar-green" style="height: 90%;" title="Collected: ₱1.8M"></div>
                <div class="bar bar-navy" style="height: 60%;" title="Allocated: ₱1.2M"></div>
              </div>
              <span class="month-label">Feb</span>
            </div>

            <div class="month-group">
              <div class="bar-pair">
                <div class="bar bar-green" style="height: 80%;" title="Collected: ₱1.6M"></div>
                <div class="bar bar-navy" style="height: 70%;" title="Allocated: ₱1.4M"></div>
              </div>
              <span class="month-label">Mar</span>
            </div>

            <div class="month-group">
              <div class="bar-pair">
                <div class="bar bar-green" style="height: 95%;" title="Collected: ₱1.9M"></div>
                <div class="bar bar-navy" style="height: 80%;" title="Allocated: ₱1.6M"></div>
              </div>
              <span class="month-label">Apr</span>
            </div>
          </div>
        </div>
      </div>
    </article>
  </div>

  <!-- Search & Filter Controls -->
  <div class="search-filter-section">
    <input 
      type="text" 
      class="search-box" 
      id="projectSearch" 
      placeholder="Search by Project Title..."
      onkeyup="filterTable()"
    >

  </div>

  <!-- Budget Line Items Table -->
  <div class="project-table-container">
    <table class="line-items-table" id="budgetTable">
      <thead>
        <tr>
          <th>Project Title</th>
          <th class="numeric-col">Approved Funds</th>
          <th class="numeric-col">Actual Expenditure</th>
          <th class="numeric-col">Remaining Budget</th>
          <th>Project Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($allocationRows)): ?>
          <tr class="no-data-row">
            <td colspan="5">No records found for <?= htmlspecialchars($selectedYear) ?>.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($allocationRows as $row): ?>
            <?php
              $approved = (float) $row['approved_funds'];
              $actual = (float) $row['actual_expenditure'];
              $remaining = (float) $row['remaining_budget'];
              $utilization = $approved > 0 ? round(($actual / $approved) * 100) : 0;
              if ($utilization >= 90) {
                $pillClass = 'pill-high';
                $pillText = 'High Utilization';
              } elseif ($utilization >= 60) {
                $pillClass = 'pill-medium';
                $pillText = 'Medium Utilization';
              } else {
                $pillClass = 'pill-low';
                $pillText = 'Under Budget';
              }
            ?>
            <tr class="sector-row" data-college="<?= htmlspecialchars($row['college_name']) ?>" data-year="<?= htmlspecialchars($row['school_year']) ?>" data-approved="<?= htmlspecialchars($approved) ?>" data-actual="<?= htmlspecialchars($actual) ?>" data-remaining="<?= htmlspecialchars($remaining) ?>">
              <td class="bold-cell"><?= htmlspecialchars($row['project_title']) ?></td>
              <td class="numeric-cell" data-value="<?= htmlspecialchars($approved) ?>">₱<?= number_format($approved, 2) ?></td>
              <td class="numeric-cell" data-value="<?= htmlspecialchars($actual) ?>">₱<?= number_format($actual, 2) ?></td>
              <td class="numeric-cell" data-value="<?= htmlspecialchars($remaining) ?>">₱<?= number_format($remaining, 2) ?></td>
              <td><span class="utilization-pill <?= $pillClass ?>"><?= $pillText ?></span></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Report Footer -->
  <div class="report-footer">
    <p class="footer-note" id="footer-note">Note: This dashboard is for viewing and transparency purposes only. Data is updated dynamically by the student treasury. For questions or corrections, please contact the Student Finance Office.</p>
  </div>

</div>

<script>
  function filterTable() {
    const searchValue = document.getElementById('projectSearch').value.toLowerCase();
    const collegeValue = document.getElementById('collegeFilter')?.value || '';
    const rows = document.querySelectorAll('.sector-row');

    rows.forEach(row => {
      const projectName = row.cells[0].textContent.toLowerCase();
      const college = row.dataset.college || '';

      const matchesSearch = projectName.includes(searchValue);
      const matchesCollege = collegeValue === '' || college === collegeValue;

      row.style.display = (matchesSearch && matchesCollege) ? '' : 'none';
    });
  }

  function filterBySchoolYear(year) {
    const rows = document.querySelectorAll('.sector-row');
    let approved = 0;
    let actual = 0;
    let remaining = 0;

    rows.forEach(row => {
      const visible = row.dataset.year === year;
      row.style.display = visible ? '' : 'none';
      if (visible) {
        approved += parseFloat(row.dataset.approved || 0);
        actual += parseFloat(row.dataset.actual || 0);
        remaining += parseFloat(row.dataset.remaining || 0);
      }
    });

    document.getElementById('approvedBudget').innerText = '₱' + approved.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('totalCollected').innerText = '₱' + actual.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('remainingBalance').innerText = '₱' + remaining.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('footer-note').textContent = 'Note: This dashboard is for viewing purposes only. Currently viewing School Year ' + year + '.';
  }

  window.addEventListener('DOMContentLoaded', () => {
    const select = document.getElementById('schoolYear');
    if (select) filterBySchoolYear(select.value);
  });
</script>

</body>
</html>