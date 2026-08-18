<?php
session_start();
include('../config.php');

if (!isset($_SESSION['loggedin']) || $_SESSION["loggedin"] !== true) {
    header("Location: login_admins.php"); 
    exit();
}

$pageTitle = "Collections"; 

$name = $_SESSION["name"];
$collegeId = intval($_SESSION["college_id"] ?? 0);

// Fetch collection statistics
$totalCollected = 0;
$collectionRecords = [];
$thisMonth = 0;
$pendingAmount = 0;
$pendingCount = 0;
$stmt = $conn->prepare("SELECT SUM(approved_funds) as total FROM records WHERE college_id = ? AND transaction_type = 'Appropriation'");
if ($stmt) {
    $stmt->bind_param("i", $collegeId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $totalCollected = $row['total'] ?? 0;
    }
    $stmt->close();
}

// Fetch collection records
$stmt = $conn->prepare("SELECT record_id, project_title_id, classification_id, approved_funds, date_proposed, official_id, project_status FROM records WHERE college_id = ? AND transaction_type = 'Appropriation' ORDER BY date_proposed DESC LIMIT 20");
if ($stmt) {
    $stmt->bind_param("i", $collegeId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $collectionRecords[] = $row;
        if (date('Y-m', strtotime($row['date_proposed'])) === date('Y-m')) {
            $thisMonth += $row['approved_funds'];
        }
        if (strtolower($row['project_status']) === 'pending') {
            $pendingAmount += $row['approved_funds'];
            $pendingCount += 1;
        }
    }
    $stmt->close();
}

$collectionCategoryTotals = [];
foreach ($collectionRecords as $record) {
    $category = trim($record['classification_id'] ?: 'Other');
    if ($category === '') {
        $category = 'Other';
    }
    if (!isset($collectionCategoryTotals[$category])) {
        $collectionCategoryTotals[$category] = 0;
    }
    $collectionCategoryTotals[$category] += $record['approved_funds'];
}
arsort($collectionCategoryTotals);
$topCollectionCategories = array_slice($collectionCategoryTotals, 0, 4, true);
$collectionMaxCategoryAmount = !empty($topCollectionCategories) ? max($topCollectionCategories) : 1;
$collectionMaxCategoryAmount = max(1, $collectionMaxCategoryAmount);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Collections – SBO Financial System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="../css/report_adding.css"/>
  <link rel="stylesheet" href="../css/collection_admin.css"/>
</head>
<body>

  <!-- Topbar include -->
  <?php include('topbar_admins.php'); ?>
        <?php include('sidebar_admins.php'); ?>


      <!-- Main content -->
      <main class="col-9 main-wrapper">
    
        <section class="kpi-grid">
      <div class="card kpi-card total-collected-card">
        <div class="kpi-icon-wrapper">
          <span class="kpi-icon">&#x2714;</span>
        </div>
        <div class="kpi-content">
          <p class="kpi-label">TOTAL COLLECTED</p>
          <h2 class="kpi-value">&#x20B1;<?php echo number_format($totalCollected, 0); ?></h2>
          <span class="kpi-subtext positive"><?php echo number_format(count($collectionRecords)); ?> transactions</span>
        </div>
      </div>

      <div class="card kpi-card this-month-card">
        <div class="kpi-icon-wrapper">
          <span class="kpi-icon">&#x1F4C5;</span>
        </div>
        <div class="kpi-content">
          <p class="kpi-label">THIS MONTH</p>
          <h2 class="kpi-value">&#x20B1;<?php echo number_format($thisMonth ?? 0, 0); ?></h2>
          <span class="kpi-subtext positive">
            <span class="trend-arrow">&#x25B2;</span> Updated
          </span>
        </div>
      </div>

      <div class="card kpi-card pending-card">
        <div class="kpi-icon-wrapper">
          <span class="kpi-icon">&#x1F4C8;</span>
        </div>
        <div class="kpi-content">
          <p class="kpi-label">PENDING VERIFICATION</p>
          <h2 class="kpi-value">&#x20B1;<?php echo number_format($pendingAmount ?? 0, 0); ?></h2>
          <span class="kpi-subtext negative"><?php echo number_format($pendingCount ?? 0); ?> records pending</span>
        </div>
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
    <section class="card table-section" data-year="2025-2026">
      <div class="table-header">
        <h3 class="section-title">Collection records</h3>
      </div>

      <div class="table-controls">
        <div class="filters-group">
          <div class="select-wrapper">
            <select id="collectionCategoryFilter">
              <option value="all">All Categories</option>
              <?php foreach ($collectionCategoryTotals as $category => $total): ?>
                <option value="<?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $category))); ?>"><?php echo htmlspecialchars($category); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="select-wrapper">
            <select id="collectionStatusFilter">
              <option value="all">All Status</option>
              <option value="verified">Verified</option>
              <option value="pending">Pending</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <div class="select-wrapper">
            <select id="collectionMonthFilter">
              <option value="all">All Months</option>
              <option value="2024-04">April 2024</option>
              <option value="2024-05">May 2024</option>
              <option value="2024-06">June 2024</option>
            </select>
          </div>
        </div>
        <div class="search-wrapper">
          <div class="search-input-wrap">
            <input id="collectionSearch" type="text" placeholder="Search description...">
          </div>
        </div>
      </div>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>DATE</th>
              <th>DESCRIPTION</th>
              <th>CATEGORY</th>
              <th>COLLECTED BY</th>
              <th>STATUS</th>
              <th class="text-right">AMOUNT</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($collectionRecords)): ?>
              <?php foreach ($collectionRecords as $record): ?>
                <?php 
                  $statusClass = strtolower(str_replace(' ', '-', $record['project_status']));
                  $categoryClass = strtolower(str_replace(' ', '-', $record['classification_id']));
                  $monthValue = date('Y-m', strtotime($record['date_proposed']));
                ?>
                <tr data-category="<?php echo htmlspecialchars($categoryClass); ?>" data-status="<?php echo htmlspecialchars($statusClass); ?>" data-month="<?php echo htmlspecialchars($monthValue); ?>">
                  <td><?php echo date('M d, Y', strtotime($record['date_proposed'])); ?></td>
                  <td class="description-text"><?php echo htmlspecialchars($record['project_title_id']); ?></td>
                  <td><?php echo htmlspecialchars($record['classification_id']); ?></td>
                  <td><?php echo htmlspecialchars($record['official_id']); ?></td>
                  <td><span class="badge status-<?php echo htmlspecialchars($statusClass); ?>"><?php echo htmlspecialchars($record['project_status']); ?></span></td>
                  <td class="amount-value text-right">&#x20B1;<?php echo number_format((float)$record['approved_funds'], 0); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" style="text-align:center; padding: 20px 0; color: #64748b;">No collection records found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="card chart-section">
      <h3 class="section-title">Collections trend – April 2024</h3>
      
      <div class="chart-container">
        <div class="chart-y-axis">
          <span>&#x20B1;1.6M</span>
          <span>&#x20B1;1.2M</span>
          <span>&#x20B1;0.8M</span>
          <span>&#x20B1;0.4M</span>
          <span>&#x20B1;0.0M</span>
        </div>
        
        <div class="chart-bars-wrapper">
          <div class="gridlines">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
          </div>
          
          <div class="bars-data">
            <?php if (!empty($topCollectionCategories)): ?>
              <?php foreach ($topCollectionCategories as $category => $amount): ?>
                <?php $height = round(($amount / $collectionMaxCategoryAmount) * 100); ?>
                <div class="bar-group">
                  <div class="bar" style="height: <?php echo $height; ?>%;"></div>
                  <span class="bar-label"><?php echo htmlspecialchars($category); ?></span>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="bar-group">
                <div class="bar" style="height: 20%;"></div>
                <span class="bar-label">No data</span>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>

  </main>
    </div>
  </div>

  <script>
    const current = location.pathname.split('/').pop() || 'dashboard_students.php';
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
