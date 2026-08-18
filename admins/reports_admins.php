<?php
session_start();
include('../config.php');

if (!isset($_SESSION['loggedin']) || $_SESSION["loggedin"] !== true) {
    header("Location: login_admins.php"); 
    exit();
}

$pageTitle = "Reports"; 

$name = $_SESSION["name"];
$collegeId = intval($_SESSION["college_id"] ?? 0);

$totalIncome = 0;
$totalExpense = 0;
$recordCount = 0;
$expenseCount = 0;
$incomeCount = 0;
$pendingReportCount = 0;

$stmt = $conn->prepare("SELECT transaction_type, SUM(approved_funds) AS total, COUNT(*) AS count FROM records WHERE college_id = ? GROUP BY transaction_type");
if ($stmt) {
    $stmt->bind_param("i", $collegeId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (strtolower($row['transaction_type']) === 'appropriation') {
            $totalIncome = $row['total'] ?? 0;
            $incomeCount = $row['count'] ?? 0;
        } elseif (strtolower($row['transaction_type']) === 'disbursement') {
            $totalExpense = $row['total'] ?? 0;
            $expenseCount = $row['count'] ?? 0;
        }
    }
    $stmt->close();
}

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM records WHERE college_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $collegeId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $recordCount = $row['total'] ?? 0;
    }
    $stmt->close();
}

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM records WHERE college_id = ? AND project_status = 'Pending'");
if ($stmt) {
    $stmt->bind_param("i", $collegeId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $pendingReportCount = $row['total'] ?? 0;
    }
    $stmt->close();
}

$recentReportRows = [];
$stmt = $conn->prepare("SELECT record_id, project_title_id, classification_id, date_proposed, school_year, transaction_type, official_id FROM records WHERE college_id = ? ORDER BY date_proposed DESC LIMIT 5");
if ($stmt) {
    $stmt->bind_param("i", $collegeId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recentReportRows[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reports – SBO Financial System</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../css/reports_admin.css"/>
</head>
<body>

  <!-- Topbar include -->
  <?php include('topbar_admins.php'); ?>
        <?php include('sidebar_admins.php'); ?>
   

      <!-- Main content -->
      <main class=" main-wrapper">
      
         <section class="cards-grid">

      <!-- Annual Budget Report -->
      <div class="card" data-year="2024-2025">
        <div class="card__icon card__icon--navy">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="1.8">
            <rect x="4" y="4" width="16" height="16" rx="2"/><line x1="8" y1="9" x2="16" y2="9"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="12" y2="17"/>
          </svg>
        </div>
        <h2 class="card__title">Annual Budget Report</h2>
        <p class="card__desc">Comprehensive summary of all income, expenses, and allocations for the full fiscal year.</p>
        <span class="card__date">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          Last generated: <?php echo date('M j, Y'); ?>
        </span>
      </div>

      <!-- Collections Summary -->
      <div class="card" data-year="2024-2025">
        <div class="card__icon card__icon--green">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>
          </svg>
        </div>
        <h2 class="card__title">Collections Summary</h2>
        <p class="card__desc">Collected funds: &#x20B1;<?php echo number_format($totalIncome, 0); ?> across <?php echo number_format($incomeCount, 0); ?> income records.</p>
        <span class="card__date">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          Last generated: <?php echo date('M j, Y'); ?>
        </span>
      </div>

      <!-- Allocation Report -->
      <div class="card" data-year="2024-2025">
        <div class="card__icon card__icon--orange">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
            <rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="8" rx="1"/><rect x="3" y="13" width="8" height="8" rx="1"/><rect x="13" y="13" width="8" height="8" rx="1"/>
          </svg>
        </div>
        <h2 class="card__title">Allocation Report</h2>
        <p class="card__desc">Allocated funds: &#x20B1;<?php echo number_format($totalExpense, 0); ?> across <?php echo number_format($expenseCount, 0); ?> expense records.</p>
        <span class="card__date">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          Last generated: <?php echo date('M j, Y'); ?>
        </span>
      </div>

      <!-- Budget vs Actual -->
      <div class="card" data-year="2025-2026">
        <div class="card__icon card__icon--red">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
            <rect x="2" y="14" width="4" height="7"/><rect x="9" y="9" width="4" height="12"/><rect x="16" y="4" width="4" height="17"/>
          </svg>
        </div>
        <h2 class="card__title">Budget vs Actual</h2>
        <p class="card__desc">Budget analysis: total allocations vs total collections for your college.</p>
        <span class="card__date">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          Last generated: <?php echo date('M j, Y'); ?>
        </span>
      </div>

      <!-- Transparency Report -->
      <div class="card" data-year="2025-2026">
        <div class="card__icon card__icon--slate">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
            <rect x="2" y="6" width="20" height="12" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/><line x1="6" y1="14" x2="8" y2="14"/><line x1="11" y1="14" x2="13" y2="14"/>
          </svg>
        </div>
        <h2 class="card__title">Transparency Report</h2>
        <p class="card__desc">Public-facing summary for student access showing fund utilization and program outcomes. <?php echo number_format($pendingReportCount); ?> pending records are still under review.</p>
        <span class="card__date">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          Last generated: <?php echo date('M j, Y'); ?>
        </span>
      </div>

      <!-- Custom Report -->
      <div class="card card--custom" data-year="2025-2026">
        <div class="card__icon card__icon--teal">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/>
          </svg>
        </div>
        <h2 class="card__title">Custom Report</h2>
        <p class="card__desc">Build a custom report by selecting date range, categories, and data fields to include.</p>
        <span class="card__generate">+ Generate new report</span>
      </div>

    </section>
  
        <div class="top-controls">
          <label class="form-label" for="schoolYearSelect">School Year</label>
          <select id="schoolYearSelect" class="form-select year-select">
            <option value="all">All Years</option>
            <option value="2024-2025">2024-2025</option>
            <option value="2025-2026" selected>2025-2026</option>
          </select>
        </div>
    <!-- Recent Report Activity Table -->
    <section class="activity">
      <div class="activity__header">
        <h2 class="activity__title">Recent report activity</h2>
<a class="activity__export" href="download_report.php?export=all">Export all</a>
      </div>

      <table class="table" data-year="2025-2026">
        <thead>
          <tr>
            <th class="table__th">Report Name</th>
            <th class="table__th">Generated By</th>
            <th class="table__th">Date</th>
            <th class="table__th">Period</th>
            <th class="table__th">Format</th>
            <th class="table__th">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($recentReportRows)): ?>
            <?php foreach ($recentReportRows as $row): ?>
              <?php
                $format = strtolower($row['transaction_type']) === 'appropriation' ? 'pdf' : 'excel';
                $label = strtolower($row['transaction_type']) === 'appropriation' ? 'PDF' : 'Excel';
                $reportName = $row['project_title_id'] ?: $row['classification_id'] ?: 'Report';
              ?>
              <tr class="table__row">
                <td class="table__td table__td--name"><?php echo htmlspecialchars($reportName); ?></td>
                <td class="table__td"><?php echo htmlspecialchars($row['official_id']); ?></td>
                <td class="table__td"><?php echo date('M d, Y', strtotime($row['date_proposed'])); ?></td>
                <td class="table__td"><?php echo htmlspecialchars($row['school_year']); ?></td>
                <td class="table__td"><span class="badge badge--<?php echo $format; ?>"><?php echo $label; ?></span></td>
                <td class="table__td"><a class="table__action" href="download_report.php?record_id=<?php echo $row['record_id']; ?>">Download</a></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align:center; padding: 20px 0; color: #64748b;">No recent report activity found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>
      </main>
    </div>
  </div>

  <script>
    const current = location.pathname.split('/').pop() || 'dashboard_admins.php';
    document.addEventListener("DOMContentLoaded", () => {
      document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
        if (item.getAttribute('href') === current) {
          item.classList.add('active');
        }
      });
    });
  </script>

</body>
</html>
