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
    ORDER BY r.record_id DESC"
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reports</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
   <link rel="stylesheet" href="../css/report_user.css" />
</head>
<body>
 <?php include('sidebar_parents.php'); ?>
 <?php include('topbar_parents.php'); ?>

  <main class="main">

    <!-- Report Cards Grid -->
    <section class="cards-grid">

      <!-- Annual Budget Report -->
      <div class="card">
        <div class="card__icon card__icon--navy">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="1.8">
            <rect x="4" y="4" width="16" height="16" rx="2"/><line x1="8" y1="9" x2="16" y2="9"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="12" y2="17"/>
          </svg>
        </div>
        <h2 class="card__title">Annual Budget Report</h2>
        <p class="card__desc">Comprehensive summary of all income, expenses, and allocations for the full fiscal year.</p>
        <span class="card__date">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          Last generated: Apr 1, 2024
        </span>
      </div>

      <!-- Collections Summary -->
      <div class="card">
        <div class="card__icon card__icon--green">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>
          </svg>
        </div>
        <h2 class="card__title">Collections Summary</h2>
        <p class="card__desc">Monthly and quarterly breakdown of all collected funds by category and source.</p>
        <span class="card__date">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          Last generated: Apr 22, 2024
        </span>
      </div>

      <!-- Allocation Report -->
      <div class="card">
        <div class="card__icon card__icon--orange">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
            <rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="8" rx="1"/><rect x="3" y="13" width="8" height="8" rx="1"/><rect x="13" y="13" width="8" height="8" rx="1"/>
          </svg>
        </div>
        <h2 class="card__title">Allocation Report</h2>
        <p class="card__desc">Detailed view of how funds were distributed across all programs and sectors.</p>
        <span class="card__date">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          Last generated: Apr 15, 2024
        </span>
      </div>

      <!-- Budget vs Actual -->
      <div class="card">
        <div class="card__icon card__icon--red">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
            <rect x="2" y="14" width="4" height="7"/><rect x="9" y="9" width="4" height="12"/><rect x="16" y="4" width="4" height="17"/>
          </svg>
        </div>
        <h2 class="card__title">Budget vs Actual</h2>
        <p class="card__desc">Variance analysis comparing planned budget against actual spending per category.</p>
        <span class="card__date">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          Last generated: Apr 20, 2024
        </span>
      </div>

      <!-- Transparency Report -->
      <div class="card">
        <div class="card__icon card__icon--slate">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
            <rect x="2" y="6" width="20" height="12" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/><line x1="6" y1="14" x2="8" y2="14"/><line x1="11" y1="14" x2="13" y2="14"/>
          </svg>
        </div>
        <h2 class="card__title">Transparency Report</h2>
        <p class="card__desc">Public-facing summary for student access showing fund utilization and program outcomes.</p>
        <span class="card__date">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          Last generated: Apr 22, 2024
        </span>
      </div>

      <!-- Custom Report -->
      <div class="card card--custom">
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

    <!-- Recent Report Activity Table -->
    <section class="activity">
      <div class="activity__header">
        <h2 class="activity__title">Recent report activity</h2>
        <a class="activity__export" href="#">Export all</a>
      </div>

      <table class="table">
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
          <tr class="table__row">
            <td class="table__td table__td--name">Q1 Budget Report</td>
            <td class="table__td">K. Cachuela</td>
            <td class="table__td">Apr 22, 2024</td>
            <td class="table__td">Jan–Mar 2024</td>
            <td class="table__td"><span class="badge badge--pdf">PDF</span></td>
            <td class="table__td"><a class="table__action" href="#">Download</a></td>
          </tr>
          <tr class="table__row">
            <td class="table__td table__td--name">Collections Summary Mar</td>
            <td class="table__td">M. Reyes</td>
            <td class="table__td">Apr 15, 2024</td>
            <td class="table__td">March 2024</td>
            <td class="table__td"><span class="badge badge--excel">Excel</span></td>
            <td class="table__td"><a class="table__action" href="#">Download</a></td>
          </tr>
          <tr class="table__row">
            <td class="table__td table__td--name">Allocation Report Q1</td>
            <td class="table__td">J. Santos</td>
            <td class="table__td">Apr 10, 2024</td>
            <td class="table__td">Jan–Mar 2024</td>
            <td class="table__td"><span class="badge badge--pdf">PDF</span></td>
            <td class="table__td"><a class="table__action" href="#">Download</a></td>
          </tr>
          <tr class="table__row">
            <td class="table__td table__td--name">Transparency Report Apr</td>
            <td class="table__td">K. Cachuela</td>
            <td class="table__td">Apr 5, 2024</td>
            <td class="table__td">April 2024</td>
            <td class="table__td"><span class="badge badge--html">HTML</span></td>
            <td class="table__td"><a class="table__action" href="#">Download</a></td>
          </tr>
        </tbody>
      </table>
    </section>
  </main>

</body>
</html>