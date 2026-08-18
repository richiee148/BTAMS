<?php
session_start();
require '../config.php'; // Ensure $conn is defined here as a MySQLi connection

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; 

if ($_SERVER["REQUEST_METHOD"] === 'POST') {
    $email = trim($_POST["email"]);
    

    // Check if user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Generate secure random code
        $reset_code = random_int(100000, 999999);

        // Update reset code in DB
        $update = $conn->prepare("UPDATE users SET reset_code = ? WHERE email = ?");
        $update->bind_param("is", $reset_code, $email);
        $update->execute();

        if ($update->affected_rows > 0) {
            $_SESSION['email'] = $email;
        

            $mail = new PHPMailer(true);
            try {
                // SMTP configuration
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'richellemaedealdo@gmail.com';
                $mail->Password   = 'lffj tozs lyff jhne';   
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Email setup
                $mail->setFrom('support@yourdomain.com', 'SBO-BTAMS');
                $mail->addAddress($email, $user['username']);
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Code';
                $mail->Body    = "<p>Hello <b>{$user['username']}</b>,</p>
                                  <p>Your password reset code is: 
                                  <b style='font-size:20px;'>{$reset_code}</b></p>
                                  <p>Please enter this code on the verification page.</p>";
                $mail->AltBody = "Your password reset code is: {$reset_code}";

                if ($mail->send()) {
                    $_SESSION['forgotpass_success'] = "Verification code sent to your email.";
                    header("Location: verify_code_students.php");
                    exit();
                } else {
                    $_SESSION['forgotpass_error'] = "Mailer Error: " . $mail->ErrorInfo;
                    header("Location: forgot_password_students.php");
                    exit();
                }
            } catch (Exception $e) {
                $_SESSION['forgotpass_error'] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                header("Location: forgot_password_students.php");
                exit();
            }
        } else {
            $_SESSION['forgotpass_error'] = "Failed to update reset code in database.";
            header("Location: forgot_password_students.php");
            exit();
        }
    } else {
        $_SESSION['forgotpass_error'] = "No user found with that email.";
        header("Location: forgot_password_students.php");
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password | BukSU Budget System</title>
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
    <h1>Secure <br>Password Reset</h1>
    <p>Recover your SBO Budget Allocation & Management account</p>
  </div>

  <!-- Forgot Password Card -->
  <div class="container-fluid vh-100 d-flex justify-content-center align-items-center">
    <div class="card p-4 shadow forgot-card">
      <h3 class="fw-bold text-center mb-3">Forgot Password</h3>

      <!-- Success/Error Messages -->
      <div class="mt-2">
        <?php
        if (isset($_SESSION['forgotpass_success'])){
          echo '<div class="alert alert-success text-center py-2">' . $_SESSION['forgotpass_success'] . '</div>';
          unset($_SESSION['forgotpass_success']);
        }
        if (isset($_SESSION['forgotpass_error'])){
          echo '<div class="alert alert-danger text-center py-2">' . $_SESSION['forgotpass_error'] . '</div>';
          unset($_SESSION['forgotpass_error']);
        }
        ?>
      </div>

      <!-- Forgot Password Form -->
      <form action="forgot_password_students.php" method="POST">
        <div class="mb-3 text-start mt-2">
          <label class="form-label mb-1">Email Address</label>
          <input type="email" name="email" class="form-control rounded-pill" placeholder="Enter your email" required>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mb-3 custom-btn mt-2 rounded-pill">
          Send Verification Code
        </button>
      </form>

      <!-- Back to Login -->
      <div class="text-center">
        <p class="small mb-0 mt-2">Remembered your password? <a href="login_students.php" class="text-decoration-none fw-bold">Login</a></p>
      </div>
    </div>
  </div>

</body>
</html>
