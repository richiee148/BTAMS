<?php
session_start();
require '../config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Combine all digits into one code
    $code = $_POST['digit1'] . $_POST['digit2'] . $_POST['digit3'] . 
            $_POST['digit4'] . $_POST['digit5'] . $_POST['digit6'];

    $email = $_SESSION['email'] ?? null;

    if ($email) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND reset_code = ?");
        $stmt->bind_param("ss", $email, $code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Code verified, allow reset
            $_SESSION['verified'] = true;
            header("Location: reset_password_admins.php");
            exit();
        } else {
            $_SESSION['verify_error'] = "Invalid verification code.";
            header("Location: verify_code_admins.php");
            exit();
        }
    } else {
        $_SESSION['verify_error'] = "Session expired. Please request a new code.";
        header("Location: forgot_password_admins.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify Code | BukSU Budget System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
     <h1>Secure <br>Verify Code</h1>
     <p>Recover your SBO Budget <br>Allocation & Management account</p>
   </div>

   <div class="container-fluid vh-100 d-flex justify-content-center align-items-center">
     <div class="card p-5 shadow rounded-4 text-center login-card" style="max-width:500px; width:100%;">
       <h2 class="text-black fw-semibold mb-4">Enter Verification Code</h2>

       <!-- Success/Error Messages -->
       <div class="mt-2">
         <?php
         if (isset($_SESSION['verify_success'])){
           echo '<div class="alert alert-success text-center py-2">' . $_SESSION['verify_success'] . '</div>';
           unset($_SESSION['verify_success']);
         }
         if (isset($_SESSION['verify_error'])){
           echo '<div class="alert alert-danger text-center py-2">' . $_SESSION['verify_error'] . '</div>';
           unset($_SESSION['verify_error']);
         }
         ?>
       </div>

       <form action="verify_code_admins.php" method="POST">
         <div class="d-flex justify-content-center gap-1 mb-3 mt-1" style="height: 100px; align-items:center;">
           <input type="text" name="digit1" maxlength="1" class="code-input" required>
           <input type="text" name="digit2" maxlength="1" class="code-input" required>
           <input type="text" name="digit3" maxlength="1" class="code-input" required>
           <input type="text" name="digit4" maxlength="1" class="code-input" required>
           <input type="text" name="digit5" maxlength="1" class="code-input" required>
           <input type="text" name="digit6" maxlength="1" class="code-input" required>
         </div>

         <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mb-3 custom-btn mt-2 rounded-pill">
           Verify
         </button>
       </form>

       <div class="text-center">
         <p class="small mb-0 mt-2">Go back to <a href="login_admins.php" class="text-decoration-none fw-bold">Login</a></p>
       </div>
     </div>
   </div>

   <script>
   const inputs = document.querySelectorAll('.code-input');
   inputs.forEach((input, index) => {
     input.addEventListener('input', () => {
       if (input.value.length === 1 && index < inputs.length - 1) {
         inputs[index + 1].focus();
       }
     });
     input.addEventListener('keydown', (e) => {
       if (e.key === "Backspace" && index > 0 && input.value === "") {
         inputs[index - 1].focus();
       }
     });
   });
   </script>

</body>
</html>
