<?php
session_start();
include('../config.php');

if (!isset($_SESSION['loggedin']) || $_SESSION["loggedin"] !== true) {
    header("Location: login_admins.php"); 
    exit();
}

if (empty($_SESSION["college_id"])) {
    header("Location: select_college.php?user_id=" . ($_SESSION["id"] ?? ""));
    exit();
}

$pageTitle = "Budget Overview"; 
$collegeId = intval($_SESSION['college_id'] ?? 0);

$name = $_SESSION["name"];

$approvedBudget = 0;
$actualExpenditure = 0;
$remainingBalance = 0;
$overviewRecords = [];
$collegeAllocations = [];
$collegeTotal = 0;

$stmt = $conn->prepare("SELECT approved_funds, actual_expenditure, remaining_budget, record_id, project_title_id, classification_id, official_id, transaction_type, project_status, school_year, date_proposed FROM records WHERE college_id = ? ORDER BY date_proposed DESC");
if ($stmt) {
    $stmt->bind_param("i", $collegeId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $overviewRecords[] = $row;
        $approvedBudget += (float)$row['approved_funds'];
        $actualExpenditure += (float)$row['actual_expenditure'];
        $remainingBalance += (float)$row['remaining_budget'];
    }
    $stmt->close();
}

$stmt = $conn->prepare("SELECT classification_id, SUM(approved_funds) AS total FROM records WHERE college_id = ? GROUP BY classification_id ORDER BY total DESC LIMIT 6");
if ($stmt) {
    $stmt->bind_param("i", $collegeId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $category = trim($row['classification_id'] ?: 'Uncategorized');
        if ($category === '') {
            $category = 'Uncategorized';
        }
        $collegeAllocations[$category] = $row['total'];
        $collegeTotal += (float) $row['total'];
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Budget Overview – SBO Financial System</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
         <link rel="stylesheet" href="../css/report_adding.css"/>
  <link rel="stylesheet" href="../css/budget_overview_admin.css"/>
</head>
<body>

  <!-- Topbar include -->
  <?php include('topbar_admins.php'); ?>
        <?php include('sidebar_admins.php'); ?>
   

      <!-- Main content -->
      <main class="col-9 main-wrapper">
       
          <section class="kpi-grid">
      <div class="card kpi-card approved-card">
        <div class="kpi-icon-wrapper">&#x20B1;</div>
        <div class="kpi-content">
          <p class="kpi-label">TOTAL APPROVED FUNDS</p>
          <h2 class="kpi-value">&#x20B1;<?php echo number_format($approvedBudget, 0); ?></h2>
          <span class="kpi-subtext neutral"><?php echo number_format(count($overviewRecords)); ?> record<?php echo count($overviewRecords) === 1 ? '' : 's'; ?></span>
        </div>
      </div>

      <div class="card kpi-card expenditure-card">
        <div class="kpi-icon-wrapper">&#x2714;</div>
        <div class="kpi-content">
          <p class="kpi-label">ACTUAL EXPENDITURE</p>
          <h2 class="kpi-value">&#x20B1;<?php echo number_format($actualExpenditure, 0); ?></h2>
          <span class="kpi-subtext positive"><?php echo $approvedBudget > 0 ? round(($actualExpenditure / $approvedBudget) * 100, 1) . '% burn rate' : 'No approved budget'; ?></span>
        </div>
      </div>

      <div class="card kpi-card remaining-card">
        <div class="kpi-icon-wrapper">&#x2139;</div>
        <div class="kpi-content">
          <p class="kpi-label">REMAINING BALANCE</p>
          <h2 class="kpi-value">&#x20B1;<?php echo number_format($remainingBalance, 0); ?></h2>
          <span class="kpi-subtext warning"><?php echo $approvedBudget > 0 ? round(($remainingBalance / $approvedBudget) * 100, 1) . '% remaining' : 'N/A'; ?></span>
        </div>
      </div>
    </section>
   <main class="main-layout-grid">
      <div class="top-controls">
        <label class="form-label" for="schoolYearSelect">School Year</label>
        <select id="schoolYearSelect" class="form-select year-select">
          <option value="all">All Years</option>
          <option value="2024-2025">2024-2025</option>
          <option value="2025-2026" selected>2025-2026</option>
        </select>
      </div>



    <div class="tab-navigation-bar">
      <button class="tab-btn active">Schema View</button>
     
    </div>

    <div class="dashboard-main-layout">
      
      <section class="card content-section-left">
        <div class="section-header">
        </div>

        <div class="filter-controls">
          <div class="select-group">
            <div class="select-wrapper">

            </div>
            <div class="select-wrapper">
              <select>
                <option>All Years</option>
                  <option>2024-2025</option>

                <option>2025-2026</option>
              </select>
            </div>
          </div>
          <span class="schema-hint">Showing <?php echo number_format(count($overviewRecords)); ?> record<?php echo count($overviewRecords) === 1 ? '' : 's'; ?></span>
        </div>

        <div class="table-container">
          <table class="schema-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>PROJECT TITLE</th>
                <th>COLLEGE</th>
                <th>CLASSIFICATION</th>
                <th>ASSIGNED OFFICIAL</th>
                <th>TYPE</th>
                <th>APPROVED</th>
                <th>EXPENDITURE</th>
                <th>STATUS</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($overviewRecords)): ?>
                <?php foreach ($overviewRecords as $record): ?>
                  <?php $statusSlug = strtolower(str_replace(' ', '-', trim($record['project_status']))); ?>
                  <tr>
                    <td class="pk-col"><?php echo intval($record['record_id']); ?></td>
                    <td class="bold-text"><?php echo htmlspecialchars($record['project_title_id']); ?></td>
                    <td><span class="college-badge"><?php echo htmlspecialchars($record['classification_id'] ?? 'N/A'); ?></span></td>
                    <td><?php echo htmlspecialchars($record['classification_id'] ?? 'Uncategorized'); ?></td>
                    <td><?php echo htmlspecialchars($record['official_id'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($record['transaction_type'] ?? 'N/A'); ?></td>
                    <td>&#x20B1;<?php echo number_format((float)$record['approved_funds'], 0); ?></td>
                    <td>&#x20B1;<?php echo number_format((float)$record['actual_expenditure'], 0); ?></td>
                    <td><span class="status-badge status-<?php echo htmlspecialchars($statusSlug); ?>"><?php echo htmlspecialchars($record['project_status']); ?></span></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="9" style="text-align:center; padding: 20px 0; color: #64748b;">No budget records found for your college.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

      <section class="card content-section-right">
        <h3 class="section-title">College Allocation breakdown</h3>
        <p class="section-subtitle">Aggregate allocation distribution of SBO funds by college division.</p>

        <div class="allocation-bars-container">
          <?php if (!empty($collegeAllocations)): ?>
            <?php $maxCollegeAllocation = max($collegeAllocations); ?>
            <?php foreach ($collegeAllocations as $category => $amount): ?>
              <?php $percentage = $maxCollegeAllocation > 0 ? round(($amount / $maxCollegeAllocation) * 100) : 0; ?>
              <div class="alloc-item">
                <div class="alloc-meta">
                  <span class="college-badge"><?php echo htmlspecialchars($category); ?></span>
                  <span class="alloc-values"><strong>&#x20B1;<?php echo number_format($amount, 0); ?></strong> / &#x20B1;<?php echo number_format($amount, 0); ?> (<?php echo $percentage; ?>%)</span>
                </div>
                <div class="alloc-progress-track">
                  <div class="alloc-progress-fill" style="width: <?php echo $percentage; ?>%;"></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="alloc-item">
              <div class="alloc-meta">
                <span class="college-badge">N/A</span>
                <span class="alloc-values"><strong>&#x20B1;0</strong> / &#x20B1;0 (0%)</span>
              </div>
              <div class="alloc-progress-track">
                <div class="alloc-progress-fill" style="width: 0%;"></div>
              </div>
            </div>
          <?php endif; ?>
              <span class="college-badge color-cas">CAS</span>
              <span class="alloc-values"><strong>&#x20B1;10,000</strong> / &#x20B1;10,000 (100%)</span>
            </div>
            <div class="alloc-progress-track">
              <div class="alloc-progress-fill color-cas-bg" style="width: 15%;"></div>
            </div>
          </div>
        </div>

      
      </section>

    </div>
      </main>
    </div>
  </div>

  <script>
    const current = location.pathname.split('/').pop() || 'budget_overview_admins.php';
    document.addEventListener("DOMContentLoaded", () => {
      document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
        if (item.getAttribute('href') === current) {
          item.classList.add('active');
        }
      });
    });
  </script>
  <script>
  // Get modal and buttons
  const modal = document.getElementById("addRecordModal");
  const openBtn = document.getElementById("openModalBtn");
  const closeBtn = document.querySelector(".modal .close");
  const cancelBtn = document.getElementById("cancelBtn");

  // Open modal when Add Report button is clicked
  openBtn.onclick = () => {
    modal.style.display = "flex"; // show modal
  };

  // Close modal when X is clicked
  closeBtn.onclick = () => {
    modal.style.display = "none";
  };

  // Close modal when Cancel button is clicked
  cancelBtn.onclick = () => {
    modal.style.display = "none";
  };

  // Close modal when clicking outside of modal content
  window.onclick = (e) => {
    if (e.target === modal) {
      modal.style.display = "none";
    }
  };
</script>


</body>
</html>
