<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If the user is not logged in, set defaults
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    $_SESSION['username'] = 'Guest';
    $_SESSION['role'] = 'User';
}

$currentPage = basename(parse_url($_SERVER['PHP_SELF'], PHP_URL_PATH));
?>
<link rel="stylesheet" href="../css/sidebar.css"/>
<aside class="sidebar">
  
  <div class="sidebar-logo">
    <div class="logo-mark">
      <img src="../images/logo.png" alt="System Logo" class="logo-img" />
    </div>
    <div>
      <div class="logo-title">SBO Financial</div>
      <div class="logo-sub">Budget Transparency System</div>
    </div>
  </div>

  <div class="sidebar-section">Main</div>
  <a class="nav-item <?= $currentPage === 'dashboard_students.php' ? 'active' : '' ?>" href="dashboard_students.php">Dashboard</a>

  <div class="sidebar-section">Finance</div>
  <a class="nav-item <?= $currentPage === 'collection_students.php' ? 'active' : '' ?>" href="collection_students.php">Collections</a>
  <a class="nav-item <?= $currentPage === 'allocations_students.php' ? 'active' : '' ?>" href="allocations_students.php">Allocations</a>
  <a class="nav-item <?= $currentPage === 'budget_overview_students.php' ? 'active' : '' ?>" href="budget_overview_students.php">Budget Overview</a>

  <div class="sidebar-section">Insights</div>
  <a class="nav-item <?= $currentPage === 'reports_students.php' ? 'active' : '' ?>" href="reports_students.php">Reports</a>

  <div class="sidebar-footer">
    <a class="nav-item logout-btn" href="../LandingPage.php">Logout</a>
  </div>
</aside>
</aside>
