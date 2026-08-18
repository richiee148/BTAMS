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

$pageTitle = "Budget Allocations"; 
$name = $_SESSION["name"];
$collegeId = intval($_SESSION["college_id"]);

$allocationRows = [];
$totalApproved = 0;
$totalDisbursed = 0;
$totalRemaining = 0;
$allocationCategories = [];
$allocationStatusCounts = [];
$stmt = $conn->prepare(
    "SELECT 
        r.record_id, 
        r.project_title_id, 
        r.classification_id, 
        r.official_id, 
        r.school_year, 
        r.transaction_type, 
        r.approved_funds, 
        r.actual_expenditure, 
        r.remaining_budget, 
        r.project_status 
    FROM records r 
    WHERE r.college_id = ? 
    ORDER BY r.record_id DESC"
);
if ($stmt) {
    $stmt->bind_param("i", $collegeId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $allocationRows[] = $row;
        $totalApproved += (float) $row['approved_funds'];
        $totalDisbursed += (float) $row['actual_expenditure'];
        $totalRemaining += (float) $row['remaining_budget'];

        $category = trim($row['classification_id'] ?: 'Uncategorized');
        if ($category === '') {
            $category = 'Uncategorized';
        }
        $allocationCategories[$category] = ($allocationCategories[$category] ?? 0) + (float) $row['approved_funds'];

        $status = trim($row['project_status'] ?: 'Unknown');
        $allocationStatusCounts[$status] = ($allocationStatusCounts[$status] ?? 0) + 1;
    }
    $stmt->close();
}
arsort($allocationCategories);
$topAllocationCategories = array_slice($allocationCategories, 0, 4, true);
$maxAllocationCategory = !empty($topAllocationCategories) ? max($topAllocationCategories) : 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Budget Allocations – SBO Financial System</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../css/allocation_admin.css"/>
      <link rel="stylesheet" href="../css/report_adding.css"/>
    <link rel="stylesheet" href="../css/dashboard_admin.css"/>
    <style>
      .action-cell { min-width: 140px; }
      .table-icon-btn {
        border: 1px solid transparent;
        border-radius: 8px;
        background: rgba(148, 163, 184, 0.08);
        color: #334155;
        cursor: pointer;
        font-size: 1rem;
        padding: 8px 10px;
        margin-right: 6px;
        transition: background 150ms ease, color 150ms ease, transform 150ms ease;
      }
      .table-icon-btn.edit-btn {
        background: rgba(59, 130, 246, 0.12);
        color: #1d4ed8;
      }
      .table-icon-btn.delete-btn {
        background: rgba(220, 38, 38, 0.12);
        color: #dc2626;
      }
      .table-icon-btn:hover {
        transform: translateY(-1px);
      }
      .table-icon-btn.edit-btn:hover {
        background: rgba(59, 130, 246, 0.18);
      }
      .table-icon-btn.delete-btn:hover {
        background: rgba(220, 38, 38, 0.18);
      }
      .table-icon-btn:focus {
        outline: 2px solid rgba(59,130,246,0.4);
        outline-offset: 2px;
      }

      .delete-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.6);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 24px;
      }
      .delete-overlay.active {
        display: flex;
      }
      .delete-dialog {
        width: min(420px, 100%);
        background: #ffffff;
        border-radius: 18px;
        box-shadow: 0 28px 80px rgba(15, 23, 42, 0.16);
        padding: 28px;
        text-align: left;
      }
      .delete-dialog h2 {
        margin: 0 0 10px;
        font-size: 1.2rem;
        color: #0f172a;
      }
      .delete-dialog p {
        margin: 0 0 20px;
        color: #475569;
        line-height: 1.6;
      }
      .delete-dialog .delete-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
      }
      .delete-dialog .delete-actions button {
        min-width: 96px;
        border-radius: 10px;
        font-weight: 600;
        border: none;
        padding: 10px 14px;
        cursor: pointer;
      }
      .delete-dialog .cancel-delete {
        background: #f1f5f9;
        color: #475569;
      }
      .delete-dialog .confirm-delete {
        background: #ef4444;
        color: #ffffff;
      }
      .delete-dialog .cancel-delete:hover {
        background: #e2e8f0;
      }
      .delete-dialog .confirm-delete:hover {
        background: #dc2626;
      }
    </style>
</head>
<body>

  <!-- Topbar include -->
  <?php include('topbar_admins.php'); ?>
<?php include('sidebar_admins.php'); ?>


    <div class="main-wrapper">
    
    <div class="kpi-grid">
      <div class="card kpi-card budget-card">
        <div class="kpi-icon-wrapper"></div>
        <div class="kpi-content">
          <p class="kpi-label">APPROVED BUDGET</p>
          <h2 class="kpi-value">₱<?php echo number_format($totalApproved, 0); ?></h2>
          <span class="kpi-subtext neutral"><?php echo number_format(count($allocationRows)); ?> record<?php echo count($allocationRows) === 1 ? '' : 's'; ?></span>
        </div>
      </div>

      <div class="card kpi-card allocated-card">
        <div class="kpi-icon-wrapper"></div>
        <div class="kpi-content">
          <p class="kpi-label">TOTAL ALLOCATED</p>
          <h2 class="kpi-value">₱<?php echo number_format($totalApproved, 0); ?></h2>
          <span class="kpi-subtext positive"><?php echo $totalApproved > 0 ? round(($totalApproved / ($totalApproved ?: 1)) * 100) . '% of approved' : 'No allocation yet'; ?></span>
        </div>
      </div>

      <div class="card kpi-card disbursed-card">
        <div class="kpi-icon-wrapper"></div>
        <div class="kpi-content">
          <p class="kpi-label">DISBURSED</p>
          <h2 class="kpi-value">₱<?php echo number_format($totalDisbursed, 0); ?></h2>
          <span class="kpi-subtext positive"><?php echo $totalApproved > 0 ? round(($totalDisbursed / ($totalApproved ?: 1)) * 100) . '% disbursed' : 'N/A'; ?></span>
        </div>
      </div>

      <div class="card kpi-card unallocated-card">
        <div class="kpi-icon-wrapper"></div>
        <div class="kpi-content">
          <p class="kpi-label">UNALLOCATED</p>
          <h2 class="kpi-value">₱<?php echo number_format($totalRemaining, 0); ?></h2>
          <span class="kpi-subtext negative"><?php echo $totalApproved > 0 ? round(($totalRemaining / ($totalApproved ?: 1)) * 100) . '% remaining' : 'N/A'; ?></span>
        </div>
      </div>
      
</div>
          <!-- Main content -->
    <main class="main-layout-grid">
      <div class="top-controls">
        <label class="form-label" for="schoolYearSelect">School Year</label>
        <select id="schoolYearSelect" class="form-select year-select">
          <option value="all">All Years</option>
          <option value="2024-2025">2024-2025</option>
          <option value="2025-2026" selected>2025-2026</option>
        </select>
      </div>


    <div class="main-layout-grid">
      
      <section class="card table-section" data-year="2025-2026">
        <div class="table-header">
          <h3 class="section-title">Allocation records</h3>
          <button class="btn-primary">+ New Allocation</button>
        </div>

        <div class="table-filters">
          <div class="select-wrapper">
            <select>
              <option>All Sectors</option>
            </select>
          </div>
          <div class="select-wrapper">
            <select>
              <option>All Status</option>
            </select>
          </div>
        </div>

        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>PROJECT / PROGRAM</th>
                <th>SECTOR</th>
                <th>ALLOCATED</th>
                <th>USED</th>
                <th>STATUS</th>
                <th>ACTION</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($allocationRows)): ?>
                <?php foreach ($allocationRows as $allocation): ?>
                  <?php $statusSlug = strtolower(str_replace(' ', '-', trim($allocation['project_status']))); ?>
                  <tr data-record-id="<?php echo intval($allocation['record_id']); ?>">
                    <td class="project-name"><?php echo htmlspecialchars($allocation['project_title_id']); ?></td>
                    <td><span class="badge sector-education"><?php echo htmlspecialchars($allocation['classification_id'] ?? 'Uncategorized'); ?></span></td>
                    <td><?php echo '₱' . number_format((float)($allocation['approved_funds'] ?? 0), 0); ?></td>
                    <td><?php echo '₱' . number_format((float)($allocation['actual_expenditure'] ?? 0), 0); ?></td>
                    <td><span class="badge status-<?php echo htmlspecialchars($statusSlug); ?>"><?php echo htmlspecialchars($allocation['project_status']); ?></span></td>
                    <td class="action-cell">
                      <button class="table-icon-btn edit-btn" type="button" aria-label="Edit allocation">✎</button>
                      <button class="table-icon-btn delete-btn" type="button" aria-label="Delete allocation">🗑</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" style="text-align:center; padding: 20px 0; color: #64748b;">No allocations found for your college yet.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

      <div class="delete-overlay" id="deleteOverlay" aria-hidden="true">
        <div class="delete-dialog" role="dialog" aria-modal="true" aria-labelledby="deleteDialogTitle">
          <h2 id="deleteDialogTitle">Delete allocation?</h2>
          <p>Do you want to delete this allocation record?</p>
          <div class="delete-actions">
            <button type="button" class="cancel-delete" id="cancelDeleteBtn">Cancel</button>
            <button type="button" class="confirm-delete" id="confirmDeleteBtn">Delete</button>
          </div>
        </div>
      </div>

      <section class="card chart-section">
        <h3 class="section-title">Sector allocation</h3>
        
        <div class="chart-container">
          <div class="chart-y-axis">
            <span>&#x20B1;4.0M</span>
            <span>&#x20B1;3.5M</span>
            <span>&#x20B1;3.0M</span>
            <span>&#x20B1;2.5M</span>
            <span>&#x20B1;2.0M</span>
            <span>&#x20B1;1.5M</span>
            <span>&#x20B1;1.0M</span>
            <span>&#x20B1;0.5M</span>
            <span>&#x20B1;0.0M</span>
          </div>
          
          <div class="chart-bars-wrapper">
            <div class="gridlines">
              <div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div>
            </div>
            
            <div class="bars-data">
              <?php if (!empty($topAllocationCategories)): ?>
                <?php foreach ($topAllocationCategories as $category => $amount): ?>
                  <?php $height = round(($amount / $maxAllocationCategory) * 100); ?>
                  <div class="bar-group">
                    <div class="bar-pair">
                      <div class="bar primary" style="height: <?php echo $height; ?>%;"></div>
                    </div>
                    <span class="bar-label"><?php echo htmlspecialchars($category); ?></span>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="bar-group">
                  <div class="bar-pair">
                    <div class="bar primary" style="height: 20%;"></div>
                  </div>
                  <span class="bar-label">No data</span>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="progress-breakdown">
          <?php if (!empty($topAllocationCategories)): ?>
            <?php foreach ($topAllocationCategories as $category => $amount): ?>
              <?php $percentage = $totalApproved > 0 ? round(($amount / $totalApproved) * 100) : 0; ?>
              <div class="progress-item">
                <div class="progress-info">
                  <span class="progress-name"><?php echo htmlspecialchars($category); ?></span>
                  <span class="progress-amount">₱<?php echo number_format($amount, 0); ?></span>
                </div>
                <div class="progress-track">
                  <div class="progress-fill" style="width: <?php echo $percentage; ?>%;"></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="progress-item">
              <div class="progress-info">
                <span class="progress-name">No allocation category found</span>
                <span class="progress-amount">₱0</span>
              </div>
              <div class="progress-track">
                <div class="progress-fill" style="width: 0%;"></div>
              </div>
            </div>
          <?php endif; ?>
        </div>

      </section>

    </div>
</main>

  <script>
  // Preserve existing nav highlight logic
  const current = location.pathname.split('/').pop() || 'dashboard_admins.php';
  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll('.nav-item').forEach(item => {
      item.classList.remove('active');
      if (item.getAttribute('href') === current) {
        item.classList.add('active');
      }
    });
  });

  function formatMoney(value) {
    const numeric = parseFloat(String(value).replace(/[^0-9\.\-]/g, ''));
    if (!Number.isFinite(numeric)) {
      return String(value).trim();
    }
    return '₱' + numeric.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
  }

  function normalizeStatus(status) {
    return String(status).trim().toLowerCase().replace(/\s+/g, '-');
  }

  function updateStatusCell(statusCell, statusText) {
    const slug = normalizeStatus(statusText);
    const badgeClass = `status-${slug}`;
    statusCell.innerHTML = `<span class="badge ${badgeClass}">${statusText}</span>`;
  }

  const deleteOverlay = document.getElementById('deleteOverlay');
  const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
  const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
  let rowToDelete = null;

  document.addEventListener('click', event => {
    const deleteBtn = event.target.closest('.delete-btn');
    if (deleteBtn) {
      rowToDelete = deleteBtn.closest('tr');
      if (!rowToDelete) return;
      deleteOverlay.classList.add('active');
      deleteOverlay.setAttribute('aria-hidden', 'false');
      return;
    }

    const editBtn = event.target.closest('.edit-btn');
    if (editBtn) {
      const row = editBtn.closest('tr');
      if (!row) return;

      const projectCell = row.querySelector('.project-name');
      const allocatedCell = row.children[2];
      const usedCell = row.children[3];
      const statusCell = row.children[4];

      const currentProject = projectCell.textContent.trim();
      const currentAllocated = allocatedCell.textContent.trim();
      const currentUsed = usedCell.textContent.trim();
      const currentStatus = statusCell.textContent.trim();

      const newProject = prompt('Edit project / program', currentProject);
      if (newProject === null) return;
      const newAllocated = prompt('Edit allocated amount', currentAllocated.replace(/[^0-9\.\-]/g, ''));
      if (newAllocated === null) return;
      const newUsed = prompt('Edit used amount', currentUsed.replace(/[^0-9\.\-]/g, ''));
      if (newUsed === null) return;
      const newStatus = prompt('Edit status', currentStatus);
      if (newStatus === null) return;

      projectCell.textContent = newProject.trim() || currentProject;
      allocatedCell.textContent = formatMoney(newAllocated || currentAllocated);
      usedCell.textContent = formatMoney(newUsed || currentUsed);
      updateStatusCell(statusCell, newStatus.trim() || currentStatus);
    }
  });

  cancelDeleteBtn.addEventListener('click', () => {
    rowToDelete = null;
    deleteOverlay.classList.remove('active');
    deleteOverlay.setAttribute('aria-hidden', 'true');
  });

  confirmDeleteBtn.addEventListener('click', () => {
    if (!rowToDelete) {
      deleteOverlay.classList.remove('active');
      deleteOverlay.setAttribute('aria-hidden', 'true');
      return;
    }

    const recordId = rowToDelete.dataset.recordId;
    if (recordId) {
      fetch('delete_record.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'record_id=' + encodeURIComponent(recordId)
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          rowToDelete.remove();
          rowToDelete = null;
        } else {
          alert(data.message || 'Unable to delete allocation record.');
        }
      })
      .catch(() => {
        alert('An error occurred while deleting the record.');
      })
      .finally(() => {
        deleteOverlay.classList.remove('active');
        deleteOverlay.setAttribute('aria-hidden', 'true');
      });
    } else {
      rowToDelete.remove();
      rowToDelete = null;
      deleteOverlay.classList.remove('active');
      deleteOverlay.setAttribute('aria-hidden', 'true');
    }
  });

  deleteOverlay.addEventListener('click', (event) => {
    if (event.target === deleteOverlay) {
      rowToDelete = null;
      deleteOverlay.classList.remove('active');
      deleteOverlay.setAttribute('aria-hidden', 'true');
    }
  });
</script>


</body>
</html>
