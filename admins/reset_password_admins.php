<?php
session_start();
require '../config.php'; // Ensure $conn is defined here as a MySQLi connection

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_SESSION['email']; // email stored during forgot_password
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password === $confirm_password) {
        // Hash the password before saving
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_code = NULL WHERE email = ?");
        $stmt->bind_param("ss", $hashed, $email);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Password reset successful. Please log in.";
            header("Location: login_admins.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to reset password.";
        }
    } else {
        $_SESSION['error'] = "Passwords do not match.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password | BukSU Budget System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

   <div class="static-bg"></div>
   <div class="video-diagonal">
      <video autoplay muted loop playsinline>
          <source src="../images/buksuvid.mp4" type="video/mp4">
      </video>
      <div class="video-overlay"></div>
   </div>

   <!-- Left-side description -->
   <div class="description-container d-none d-lg-block">
     <h1>Secure <br>Reset Password</h1>
     <p>Recover your SBO Budget Allocation & Management account</p>
   </div>

   <!-- Reset Password Card -->
   <div class="container-fluid vh-100 d-flex justify-content-center align-items-center">
     <div class="card p-4 shadow forgot-card">
       <h3 class="fw-bold text-center mb-3">Reset Password</h3>

       <!-- Success/Error Messages -->
       <div class="mt-2">
         <?php
         if (isset($_SESSION['success'])){
           echo '<div class="alert alert-success text-center py-2">' . $_SESSION['success'] . '</div>';
           unset($_SESSION['success']);
         }
         if (isset($_SESSION['error'])){
           echo '<div class="alert alert-danger text-center py-2">' . $_SESSION['error'] . '</div>';
           unset($_SESSION['error']);
         }
         ?>
       </div>

       <!-- Reset Password Form -->
       <form action="reset_password_admins.php" method="POST">
         <div class="mb-3 text-start mt-2">
           <label class="form-label mb-1">New Password</label>
           <input type="password" name="password" class="form-control rounded-pill" placeholder="Enter new password" required>
         </div>
         <div class="mb-3 text-start mt-2">
           <label class="form-label mb-1">Confirm Password</label>
           <input type="password" name="confirm_password" class="form-control rounded-pill" placeholder="Confirm password" required>
         </div>

         <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mb-3 custom-btn mt-2 rounded-pill">
           Reset Password
         </button>
       </form>

       <!-- Back to Login -->
       <div class="text-center">
         <p class="small mb-0 mt-2">Go back to <a href="login_admins.php" class="text-decoration-none fw-bold">Login</a></p>
       </div>
     </div>
   </div>

</body>
</html>
