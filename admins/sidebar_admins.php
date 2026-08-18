<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If the user is not logged in, redirect to login
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    $_SESSION['username'] = 'Guest';
    $_SESSION['role'] = 'User';
}
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
  <a class="nav-item active" href="dashboard_admins.php">Dashboard</a>

  <div class="sidebar-section">Finance</div>
  <a class="nav-item" href="collections_admins.php">Collections</a>
  <a class="nav-item" href="allocations_admins.php">Allocations</a>
  <a class="nav-item" href="budget_overview_admins.php">Budget Overview</a>

  <div class="sidebar-section">Insights</div>
  <a class="nav-item" href="reports_admins.php">Reports</a>

  <div class="sidebar-footer">
    
    <div class="logout.php">
    <a class="nav-item" href="../LandingPage.php">Logout</a>
    
  </div>
</div>
</aside>
