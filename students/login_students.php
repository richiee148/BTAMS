<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

// Load .env from the project root
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$siteKey = $_ENV['RECAPTCHA_SITE_KEY'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BukSU | Budget Management System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

  <!-- Background layers -->
  <div class="static-bg"></div>
  <div class="video-diagonal">
    <video autoplay muted loop playsinline>
      <source src="../images/buksuvid.mp4" type="video/mp4">
    </video>
    <div class="video-overlay"></div>
  </div>

  <!-- Left-side description -->
  <div class="description-container d-none d-lg-block">
    <h1>SBO BUDGET <br>ALLOCATION <br>& MANAGEMENT</h1>
    <p><i class="bi bi-check-circle-fill me-2"></i> Real-time Fund Tracking</p>
    <p><i class="bi bi-check-circle-fill me-2"></i> Budget Allocation Overview</p>
    <p><i class="bi bi-check-circle-fill me-2"></i> Transparent Financial Reporting</p>
  </div>

  <!-- Login card -->
  <div class="container-fluid vh-100 d-flex justify-content-center align-items-center">
    <div class="card p-5 shadow login-card">
      <h3 class="fw-bold mb-4 text-center">Login Account</h3>

      <!-- Success/Error Messages -->
      <?php if (isset($_SESSION['login_error'])): ?>
        <div class="alert alert-danger text-center py-2">
          <?= $_SESSION['login_error']; ?>
        </div>
        <?php unset($_SESSION['login_error']); ?>
      <?php endif; ?>

      <?php if (isset($_SESSION['login_success'])): ?>
        <div class="alert alert-success text-center py-2">
          <?= $_SESSION['login_success']; ?>
        </div>
        <?php unset($_SESSION['login_success']); ?>
      <?php endif; ?>

      <!-- Login form -->
      <form action="validate_students.php" method="POST">
        <div class="mb-3 text-start">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control w-100" placeholder="Enter your email" required>
        </div>

        <div class="mb-3 text-start">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control w-100" placeholder="Enter your password" required>
        </div>

        <!-- reCAPTCHA -->
        <div class="g-recaptcha mb-3" data-sitekey="<?= htmlspecialchars($siteKey) ?>"></div>

        <div class="text-start mb-3">
          <a href="forgot_password_students.php" class="text-decoration-none">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-primary w-100 rounded-pill mb-3">Login</button>
      </form>

      <!-- Social login + signup links -->
      <p class="mb-2 text-center text-muted">Sign up with</p>
      <div class="d-flex justify-content-center gap-2 mb-4">
        <a href="google_login_students.php" class="btn btn-google rounded-pill px-3"><i class="bi bi-google"></i></a>
      </div>

      <p class="text-center mb-0">Don’t have an account? <a href="signup_students.php" class="fw-bold">Sign up</a></p>
    </div>
  </div>

  <!-- reCAPTCHA script -->
  <script src="https://www.google.com/recaptcha/api.js"></script>
</body>
</html>
