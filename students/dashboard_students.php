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

$name      = $_SESSION["name"];
$collegeId = intval($_SESSION["college_id"]);

// ── School year filter (default to current) ──────────────────────────────────
$selectedYear = isset($_GET['school_year']) ? trim($_GET['school_year']) : '2025-2026';
$allowedYears = ['2023-2024', '2024-2025', '2025-2026', '2026-2027'];
if (!in_array($selectedYear, $allowedYears)) $selectedYear = '2025-2026';

// ── Fetch all records for this college + school year ─────────────────────────
$records = [];
$stmt = $conn->prepare("
    SELECT
        r.record_id,
        pt.project_title,
        c.college_name,
        ac.classification_name,
        r.date_proposed,
        r.approved_funds,
        r.actual_expenditure,
        r.remaining_budget,
        r.project_status,
        r.transaction_type,
        r.school_year
    FROM records r
    LEFT JOIN project_titles          pt ON r.project_title_id   = pt.project_title_id
    LEFT JOIN colleges                c  ON r.college_id          = c.college_id
    LEFT JOIN activity_classifications ac ON r.classification_id  = ac.classification_id
    WHERE r.college_id  = ?
      AND r.school_year = ?
    ORDER BY r.record_id DESC
");
if ($stmt) {
    $stmt->bind_param("is", $collegeId, $selectedYear);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $records[] = $row;
    $stmt->close();
}

// ── KPI totals ───────────────────────────────────────────────────────────────
$totalApproved     = array_sum(array_column($records, 'approved_funds'));
$totalActual       = array_sum(array_column($records, 'actual_expenditure'));
$totalRemaining    = array_sum(array_column($records, 'remaining_budget'));
$utilizationPct    = $totalApproved > 0 ? round(($totalActual / $totalApproved) * 100) : 0;

// ── Gauge SVG math (half-circle: arc length = π × r = π × 40 ≈ 125.66) ──────
$arcTotal   = 125.66;
$arcFilled  = round(($utilizationPct / 100) * $arcTotal, 2);
$arcRemain  = round($arcTotal - $arcFilled, 2);

// ── Monthly bar chart data (group by month for this year) ────────────────────
$monthlyData = [];
foreach ($records as $r) {
    if (empty($r['date_proposed'])) continue;
    $mon = date('M', strtotime($r['date_proposed']));
    if (!isset($monthlyData[$mon])) $monthlyData[$mon] = ['approved' => 0, 'actual' => 0];
    $monthlyData[$mon]['approved'] += (float)$r['approved_funds'];
    $monthlyData[$mon]['actual']   += (float)$r['actual_expenditure'];
}
$maxMonthly = 1;
foreach ($monthlyData as $m) {
    $maxMonthly = max($maxMonthly, $m['approved'], $m['actual']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Project Financial Transparency Report</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/dashboard_user.css">
  <style>
    /* ── Overrides / additions that ensure the CSS fully applies ── */
    .report-container { margin-left: 220px; margin-top: 64px; /* offset for sidebar + topbar */ box-sizing: border-box; }
    @media (max-width: 768px) { .report-container { margin-left: 0; } }

    .no-data-row td {
      text-align: center;
      padding: 30px;
      color: #a0aec0;
      font-style: italic;
    }
    .status-pending {
      background-color: #ebf4ff;
      color: #3182ce;
    }
    /* Highlighted search match */
    mark { background: #fff3cd; padding: 0 2px; border-radius: 2px; }
  </style>
</head>
<body>

<?php include('sidebar_students.php'); ?>
<?php include('topbar_students.php'); ?>

<div class="report-container">

  <!-- School Year Row -->
  <div class="school-year-row">
    <div class="school-year-selector">
      <label for="schoolYear">Select School Year:</label>
      <form method="GET" id="yearForm" style="display:inline">
        <select id="schoolYear" name="school_year" onchange="document.getElementById('yearForm').submit()">
          <?php foreach ($allowedYears as $yr): ?>
            <option value="<?= $yr ?>" <?= $yr === $selectedYear ? 'selected' : '' ?>><?= $yr ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
    <button class="download-btn" onclick="exportToExcel()">Download Excel Copy</button>
  </div>

  <!-- KPI Cards -->
  <div class="kpi-cards-grid">
    <div class="kpi-card approved-card">
      <div class="card-label">Approved Funds</div>
      <div class="card-value">₱<?= number_format($totalApproved, 2) ?></div>
    </div>
    <div class="kpi-card actual-card">
      <div class="card-label">Actual Expenditure</div>
      <div class="card-value">₱<?= number_format($totalActual, 2) ?></div>
    </div>
    <div class="kpi-card remaining-card">
      <div class="card-label">Remaining Balance</div>
      <div class="card-value">₱<?= number_format($totalRemaining, 2) ?></div>
    </div>
  </div>

  <!-- Analytics Row -->
  <div class="analytics-row-grid">

    <!-- Budget Utilization Gauge -->
    <article class="analytics-card">
      <h2 class="card-title">Budget Utilization</h2>
      <div class="gauge-wrapper">
        <svg viewBox="0 0 100 50" class="gauge-svg">
          <path d="M 10 50 A 40 40 0 0 1 90 50" class="gauge-background"/>
          <path d="M 10 50 A 40 40 0 0 1 90 50" class="gauge-fill"
                stroke-dasharray="<?= $arcFilled ?> <?= $arcRemain + 0.01 ?>"/>
        </svg>
        <div class="gauge-center-text">
          <div class="gauge-percentage"><?= $utilizationPct ?>%</div>
          <div class="gauge-sub">of budget utilized</div>
        </div>
      </div>
      <div class="utilization-description">
        ₱<?= number_format($totalActual, 2) ?> has been spent out of the total
        ₱<?= number_format($totalApproved, 2) ?> approved budget.
        ₱<?= number_format($totalRemaining, 2) ?> remains available.
      </div>
    </article>

    <!-- Monthly Bar Chart -->
    <article class="analytics-card">
      <h2 class="card-title">Approved vs Actual Summary</h2>
      <?php if (empty($monthlyData)): ?>
        <p style="color:#a0aec0;text-align:center;margin-top:40px;">No data for this school year.</p>
      <?php else: ?>
      <div class="chart-content">
        <div class="monthly-bar-chart">
          <div class="y-axis-labels">
            <?php
              $steps = 5;
              for ($i = $steps; $i >= 0; $i--):
                $val = ($maxMonthly / $steps) * $i;
                echo '<span>₱' . (($val >= 1000000) ? round($val/1000000,1).'M' : (($val >= 1000) ? round($val/1000,0).'K' : $val)) . '</span>';
              endfor;
            ?>
          </div>
          <div class="chart-bars-container" style="grid-template-columns: repeat(<?= count($monthlyData) ?>, 1fr)">
            <?php foreach ($monthlyData as $mon => $vals):
              $approvedPct = $maxMonthly > 0 ? round(($vals['approved'] / $maxMonthly) * 100) : 0;
              $actualPct   = $maxMonthly > 0 ? round(($vals['actual']   / $maxMonthly) * 100) : 0;
            ?>
            <div class="month-group">
              <div class="bar-pair">
                <div class="bar bar-green" style="height:<?= $approvedPct ?>%"
                     title="Approved: ₱<?= number_format($vals['approved'],2) ?>"></div>
                <div class="bar bar-navy"  style="height:<?= $actualPct ?>%"
                     title="Actual: ₱<?= number_format($vals['actual'],2) ?>"></div>
              </div>
              <span class="month-label"><?= htmlspecialchars($mon) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <!-- Legend -->
      <div style="display:flex;gap:16px;justify-content:center;margin-top:12px;font-size:12px;color:#718096;">
        <span><span style="display:inline-block;width:12px;height:12px;background:#48c774;border-radius:2px;margin-right:4px;vertical-align:middle"></span>Approved</span>
        <span><span style="display:inline-block;width:12px;height:12px;background:#1e3a5f;border-radius:2px;margin-right:4px;vertical-align:middle"></span>Actual</span>
      </div>
      <?php endif; ?>
    </article>

  </div><!-- /.analytics-row-grid -->

  <!-- Search -->
  <div class="search-filter-section">
    <input type="text" class="search-box" id="projectSearch"
           placeholder="Search by project title, classification, or status..."
           oninput="filterTable()">
  </div>

  <!-- Records Table -->
  <div class="project-table-container">
    <table class="project-table" id="recordsTable">
      <thead>
        <tr>
          <th>Project Title</th>
          <th>College</th>
          <th>Activity Classification</th>
          <th>Date Proposed</th>
          <th>Transaction Type</th>
          <th class="numeric-col">Approved Funds</th>
          <th class="numeric-col">Actual Expenditure</th>
          <th class="numeric-col">Remaining Budget</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <?php if (empty($records)): ?>
          <tr class="no-data-row">
            <td colspan="9">No records found for school year <?= htmlspecialchars($selectedYear) ?>.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($records as $r):
            $statusClass = match($r['project_status']) {
              'Completed'   => 'status-completed',
              'In Progress' => 'status-progress',
              'Pending'     => 'status-pending',
              default       => ''
            };
            $dateFormatted = !empty($r['date_proposed'])
              ? date('F d, Y', strtotime($r['date_proposed']))
              : '—';
          ?>
          <tr class="project-row">
            <td class="project-title"><?= htmlspecialchars($r['project_title'] ?? '—') ?></td>
            <td><?= htmlspecialchars($r['college_name'] ?? '—') ?></td>
            <td><?= htmlspecialchars($r['classification_name'] ?? '—') ?></td>
            <td><?= $dateFormatted ?></td>
            <td><?= htmlspecialchars($r['transaction_type'] ?? '—') ?></td>
            <td class="numeric-col">₱<?= number_format((float)$r['approved_funds'], 2) ?></td>
            <td class="numeric-col">₱<?= number_format((float)$r['actual_expenditure'], 2) ?></td>
            <td class="numeric-col">₱<?= number_format((float)$r['remaining_budget'], 2) ?></td>
            <td>
              <span class="status-badge <?= $statusClass ?>">
                <?= htmlspecialchars($r['project_status']) ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr class="grand-total-row">
          <td colspan="5" class="total-label">GRAND TOTAL:</td>
          <td class="numeric-col total-value">₱<?= number_format($totalApproved, 2) ?></td>
          <td class="numeric-col total-value">₱<?= number_format($totalActual, 2) ?></td>
          <td class="numeric-col total-value">₱<?= number_format($totalRemaining, 2) ?></td>
          <td></td>
        </tr>
      </tfoot>
    </table>
  </div>

  <div class="report-footer">
    <p class="footer-note">
      Note: This report is for viewing and transparency purposes only.
      Data is updated dynamically by the student treasury.
      For questions or corrections, please contact the Student Finance Office.
    </p>
  </div>

</div><!-- /.report-container -->

<script>
// ── Search / filter ──────────────────────────────────────────────────────────
function filterTable() {
  const q    = document.getElementById('projectSearch').value.toLowerCase().trim();
  const rows = document.querySelectorAll('#tableBody .project-row');
  let visibleApproved = 0, visibleActual = 0, visibleRemaining = 0;

  rows.forEach(function (row) {
    const text = row.textContent.toLowerCase();
    const show = !q || text.includes(q);
    row.style.display = show ? '' : 'none';

    if (show) {
      // Sum visible rows for the grand total footer
      const cells = row.querySelectorAll('.numeric-col');
      visibleApproved  += parsePHP(cells[0]);
      visibleActual    += parsePHP(cells[1]);
      visibleRemaining += parsePHP(cells[2]);
    }
  });

  // Update footer totals to reflect only visible rows
  const footerCells = document.querySelectorAll('.grand-total-row .total-value');
  if (footerCells.length >= 3) {
    footerCells[0].textContent = '₱' + formatNum(visibleApproved);
    footerCells[1].textContent = '₱' + formatNum(visibleActual);
    footerCells[2].textContent = '₱' + formatNum(visibleRemaining);
  }
}

function parsePHP(cell) {
  if (!cell) return 0;
  return parseFloat(cell.textContent.replace(/[₱,]/g, '')) || 0;
}

function formatNum(n) {
  return n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// ── Excel export (simple CSV download) ───────────────────────────────────────
function exportToExcel() {
  const rows  = document.querySelectorAll('#recordsTable tr');
  const lines = [];

  rows.forEach(function (row) {
    if (row.style.display === 'none') return;
    const cells = row.querySelectorAll('th, td');
    const cols  = [];
    cells.forEach(function (c) {
      let val = c.textContent.trim().replace(/\s+/g, ' ');
      // Wrap in quotes if contains comma
      if (val.includes(',')) val = '"' + val + '"';
      cols.push(val);
    });
    lines.push(cols.join(','));
  });

  const csv  = lines.join('\n');
  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href     = url;
  a.download = 'financial_report_<?= $selectedYear ?>.csv';
  a.click();
  URL.revokeObjectURL(url);
}
</script>

</body>
</html>