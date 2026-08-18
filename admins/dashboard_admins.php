<?php
session_start();
include('../config.php');

if (!isset($_SESSION['loggedin']) || $_SESSION["loggedin"] !== true) {
    header("Location: login_admins.php"); 
    exit();
}

if (!isset($_SESSION['college_id'])) {
    header("Location: select_college.php?user_id=" . ($_SESSION["id"] ?? ""));
    exit();
}

$pageTitle = "Dashboard"; 
$name = $_SESSION["name"];
$collegeId = intval($_SESSION["college_id"]);

// Fetch statistics
$totalBudget = 0;
$totalCollections = 0;
$totalAllocated = 0;
$monthLabels = [];
$monthlyCollections = [];
$monthlyExpenses = [];
$budgetDistribution = [];
$budgetTotal = 0;

$stmt = $conn->prepare("SELECT SUM(IF(transaction_type='Appropriation', approved_funds, 0)) as collections, SUM(IF(transaction_type='Disbursement', approved_funds, 0)) as expenses FROM records WHERE college_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $collegeId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $totalCollections = $row['collections'] ?? 0;
        $totalAllocated = $row['expenses'] ?? 0;
    }
    $stmt->close();
}
$totalBudget = $totalCollections;
$remainingBudget = $totalBudget - $totalAllocated;

for ($i = 3; $i >= 0; $i--) {
    $monthKey = date('Y-m', strtotime("-{$i} months"));
    $monthLabels[] = date('M', strtotime($monthKey . '-01'));
    $monthlyCollections[$monthKey] = 0;
    $monthlyExpenses[$monthKey] = 0;
}

$stmt = $conn->prepare("SELECT DATE_FORMAT(date_proposed, '%Y-%m') AS month, transaction_type, SUM(approved_funds) AS total FROM records WHERE college_id = ? AND date_proposed >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 3 MONTH), '%Y-%m-01') GROUP BY month, transaction_type ORDER BY month ASC");
if ($stmt) {
    $stmt->bind_param("i", $collegeId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $month = $row['month'];
        if (isset($monthlyCollections[$month]) && strtolower($row['transaction_type']) === 'appropriation') {
            $monthlyCollections[$month] = $row['total'];
        }
        if (isset($monthlyExpenses[$month]) && strtolower($row['transaction_type']) === 'disbursement') {
            $monthlyExpenses[$month] = $row['total'];
        }
    }
    $stmt->close();
}

$stmt = $conn->prepare("SELECT classification_id, SUM(approved_funds) AS total FROM records WHERE college_id = ? AND transaction_type = 'Disbursement' GROUP BY classification_id ORDER BY total DESC LIMIT 4");
if ($stmt) {
    $stmt->bind_param("i", $collegeId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $budgetDistribution[] = $row;
    }
    $stmt->close();
}
$budgetTotal = array_sum(array_column($budgetDistribution, 'total'));

// Fetch recent transactions
$recentTransactions = [];
$stmt = $conn->prepare("SELECT project_title_id, classification_id, transaction_type, approved_funds, date_proposed FROM records WHERE college_id = ? ORDER BY date_proposed DESC LIMIT 5");
if ($stmt) {
    $stmt->bind_param("i", $collegeId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recentTransactions[] = $row;
    }
    $stmt->close();
}

// Fetch allocations by classification
$allocationsByType = [];
$stmt = $conn->prepare("SELECT classification_id, SUM(approved_funds) as total FROM records WHERE college_id = ? AND transaction_type = 'Disbursement' GROUP BY classification_id");
if ($stmt) {
    $stmt->bind_param("i", $collegeId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $allocationsByType[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard – SBO Financial System</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <link rel="stylesheet" href="../css/record_adding.css"/>
        
  <link rel="stylesheet" href="../css/dashboard_admin.css"/>
 
</head>
<body>

  
  <?php include('topbar_admins.php'); ?>
        <?php include('sidebar_admins.php'); ?>


<main class="main-content">


    <!-- Stat cards row -->
    <div class="stat-grid">
      <div class="stat-card blue">
        <div class="stat-icon-wrap">
          <svg viewBox="0 0 24 24"><path d="M11.8 10.9..."/></svg>
        </div>
        <div class="stat-body">
          <div class="stat-label">Total Budget</div>
          <div class="stat-value">₱<?php echo number_format($totalBudget, 0); ?></div>
          <div class="stat-change">Total Available</div>
        </div>
      </div>
      <div class="stat-card green">
        <div class="stat-icon-wrap">
          <svg viewBox="0 0 24 24"><path d="M16 6..."/></svg>
        </div>
        <div class="stat-body">
          <div class="stat-label">Total Collections</div>
          <div class="stat-value">₱<?php echo number_format($totalCollections, 0); ?></div>
          <div class="stat-change"><?php echo $totalCollections > 0 ? round(($totalCollections / ($totalBudget ?: 1)) * 100) . '% collected' : 'No collections'; ?></div>
        </div>
      </div>
      <div class="stat-card gold">
        <div class="stat-icon-wrap">
          <svg viewBox="0 0 24 24"><path d="M3 13..."/></svg>
        </div>
        <div class="stat-body">
          <div class="stat-label">Total Allocated</div>
          <div class="stat-value">₱<?php echo number_format($totalAllocated, 0); ?></div>
          <div class="stat-change"><?php echo $totalBudget > 0 ? round(($totalAllocated / $totalBudget) * 100) . '% utilization' : '0% utilization'; ?></div>
        </div>
      </div>
      <div class="stat-card red">
        <div class="stat-icon-wrap">
          <svg viewBox="0 0 24 24"><path d="M12 2..."/></svg>
        </div>
        <div class="stat-body">
          <div class="stat-label">Remaining Budget</div>
          <div class="stat-value">₱<?php echo number_format(max(0, $remainingBudget), 0); ?></div>
          <div class="stat-change <?php echo $remainingBudget < 0 ? 'neg' : ''; ?>"><?php echo $totalBudget > 0 ? round(($remainingBudget / $totalBudget) * 100) . '% of total' : 'N/A'; ?></div>
        </div>
      </div>
    </div>
     <div class="top-controls">
          <label class="form-label" for="schoolYearSelect">School Year</label>
          <select id="schoolYearSelect" class="form-select year-select">
            <option value="all">All Years</option>
            <option value="2024-2025">2024-2025</option>
            <option value="2025-2026" selected>2025-2026</option>
          </select>
        </div>


    <!-- Charts row -->
    <div class="two-col">
      <div class="card">
        <div class="card-head">
          <span class="card-title">Monthly Collections vs Expenses</span>
        </div>
        <div class="chart-wrap"><canvas id="lineChart"></canvas></div>
      </div>
      <div class="card">
        <div class="card-head"><span class="card-title">Budget distribution</span></div>
        <div class="donut-container">
          <canvas id="donutChart"></canvas>
          <div class="donut-center">
            <div class="donut-main">₱<?php echo number_format($totalBudget, 0); ?></div>
            <div class="donut-sub">Total Budget</div>
          </div>
          
        </div>
         <div class="donut-legend">
    <?php if (!empty($budgetDistribution)): ?>
      <?php $legendColors = ['#0f2557', '#0d8c6d', '#e8a020', '#8a95b0']; ?>
      <?php foreach ($budgetDistribution as $index => $item): ?>
        <?php $category = $item['classification_id'] ?: 'Uncategorized'; ?>
        <?php $percent = $budgetTotal > 0 ? round(($item['total'] / $budgetTotal) * 100) : 0; ?>
        <span><span class="legend-box" style="background: <?php echo $legendColors[$index] ?? '#cbd5e1'; ?>;"></span> <?php echo htmlspecialchars($category); ?> <?php echo $percent; ?>%</span>
      <?php endforeach; ?>
    <?php else: ?>
      <span>No expense categories yet</span>
    <?php endif; ?>
  </div>
      </div>
      
 
  </div>
 

    

    <!-- Transactions -->
    <div class="card">
      <div class="card-head">
        <span class="card-title">Recent transactions</span>
      </div>
      <table class="data-table">
        <thead>
          <tr><th>Date</th><th>Description</th><th>Category</th><th>Amount</th></tr>
        </thead>
        <tbody>
          <?php if (!empty($recentTransactions)): ?>
            <?php foreach ($recentTransactions as $txn): ?>
              <tr>
                <td><?php echo date('M d', strtotime($txn['date_proposed'])); ?></td>
                <td><?php echo htmlspecialchars($txn['project_title_id'] ?? 'N/A'); ?></td>
                <td><span class="badge <?php echo strtolower($txn['transaction_type']) === 'appropriation' ? 'green' : 'blue'; ?>"><?php echo htmlspecialchars($txn['transaction_type']); ?></span></td>
                <td class="amount-<?php echo strtolower($txn['transaction_type']) === 'appropriation' ? 'pos' : 'neg'; ?>"><?php echo (strtolower($txn['transaction_type']) === 'appropriation' ? '+' : '−') . '₱' . number_format($txn['approved_funds'], 0); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="4" style="text-align:center; padding: 20px 0; color: #64748b;">No recent transactions</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Allocation progress -->
    <div class="card">
      <div class="card-head"><span class="card-title">Allocation progress</span></div>
      <?php if (!empty($allocationsByType)): ?>
        <?php $maxAllocation = max(array_column($allocationsByType, 'total')) ?: 1; ?>
        <?php foreach ($allocationsByType as $alloc): ?>
          <?php $fillPercent = round(($alloc['total'] / $maxAllocation) * 100); ?>
          <div class="prog-item">
            <div class="prog-row"><span class="prog-label"><?php echo htmlspecialchars($alloc['classification_id'] ?: 'Uncategorized'); ?></span><span class="prog-vals">₱<?php echo number_format($alloc['total'], 0); ?> / ₱<?php echo number_format($totalBudget, 0); ?></span></div>
            <div class="prog-track"><div class="prog-fill" style="width: <?php echo $fillPercent; ?>%"></div></div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="prog-item">
          <div class="prog-row"><span class="prog-label">No allocations</span><span class="prog-vals">₱0 / ₱0</span></div>
          <div class="prog-track"><div class="prog-fill" style="width: 0%"></div></div>
        </div>
      <?php endif; ?>
      <div class="prog-summary">
        <span>Remaining budget</span><strong>₱<?php echo number_format($remainingBudget, 0); ?></strong>
      </div>
    </div>

  </div>
</main>

<!-- Include Modal Component -->
<?php include('record_adding.php'); ?>

<script>
  new Chart(document.getElementById('lineChart'), {
    type:'line',
    data:{
      labels:[<?php echo implode(',', array_map(fn($label) => "'" . $label . "'", $monthLabels)); ?>],
      datasets:[
        {label:'Collections',data:[<?php echo implode(',', array_map(fn($val) => (int)$val, $monthlyCollections)); ?>],borderColor:'#0d8c6d',fill:false},
        {label:'Expenses',data:[<?php echo implode(',', array_map(fn($val) => (int)$val, $monthlyExpenses)); ?>],borderColor:'#0f2557',borderDash:[5,5],fill:false}
      ]
    },
    options:{
      responsive:true,
      scales:{
        y:{
          ticks:{callback:function(value){return '₱' + value.toLocaleString();}}
        }
      }
    }
  });

  const budgetData = [<?php foreach ($allocationsByType as $alloc) { echo $alloc['total'] . ', '; } ?>];
  const colors = ['#0f2557','#0d8c6d','#e8a020','#8a95b0'];
  const filteredData = budgetData.filter(x => x > 0);
  new Chart(document.getElementById('donutChart'), {
    type:'doughnut',
    data:{
      datasets:[{
        data: filteredData.length ? filteredData : [1],
        backgroundColor: filteredData.length ? colors.slice(0, filteredData.length) : ['#cbd5e1']
      }]
    },
    options: {
      cutout: '70%'
    }
  });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Modal Control
  const modal = document.getElementById("addRecordModal");
  const addRecordBtn = document.getElementById("addRecordBtn");
  const closeBtn = document.querySelector(".close-btn");
  const cancelBtn = document.getElementById("cancelRecordBtn");
  const addRecordForm = document.getElementById("addRecordForm");

  // Open modal when Add Record button is clicked
  addRecordBtn.addEventListener("click", () => {
    modal.classList.add("active");
    addRecordForm.reset();
  });

  // Close modal when X is clicked
  closeBtn.addEventListener("click", () => {
    modal.classList.remove("active");
  });

  // Close modal when Cancel button is clicked
  cancelBtn.addEventListener("click", () => {
    modal.classList.remove("active");
  });

  // Close modal when clicking outside of modal content
  window.addEventListener("click", (e) => {
    if (e.target === modal) {
      modal.classList.remove("active");
    }
  });

  // Handle form submission
  addRecordForm.addEventListener("submit", (e) => {
    e.preventDefault();
    
    // Calculate remaining budget
    const approvedFunds = parseFloat(document.querySelector('input[name="approved_funds"]').value) || 0;
    const actualExpenditure = parseFloat(document.querySelector('input[name="actual_expenditure"]').value) || 0;
    document.querySelector('input[name="remaining_budget"]').value = (approvedFunds - actualExpenditure).toFixed(2);

    // Send form data via AJAX
    const formData = new FormData(addRecordForm);
    
    fetch('process_record.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.text())
    .then(text => {
      let result;
      try {
        result = JSON.parse(text);
      } catch (e) {
        throw new Error('Invalid server response: ' + text);
      }

      if (result.success) {
        alert(result.message || 'Record saved successfully!');
        modal.classList.remove("active");
        addRecordForm.reset();
        location.reload();
      } else {
        alert(result.error || result.message || 'Error saving record.');
      }
    })
    .catch(error => {
      console.error('Error saving record:', error);
      alert('Error saving record. Please try again.');
    });
  });

  // Auto-calculate remaining budget as user types
  const approvedInput = document.querySelector('input[name="approved_funds"]');
  const expenditureInput = document.querySelector('input[name="actual_expenditure"]');
  const remainingInput = document.querySelector('input[name="remaining_budget"]');

  [approvedInput, expenditureInput].forEach(input => {
    input.addEventListener("input", () => {
      const approved = parseFloat(approvedInput.value) || 0;
      const expended = parseFloat(expenditureInput.value) || 0;
      remainingInput.value = (approved - expended).toFixed(2);
    });
  });
</script>

</body>
</html>