<?php
session_start();
require '../config.php'; // Ensure this file correctly initializes $conn

// Load .env so we can access reCAPTCHA keys
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $recaptchaSecret = $_ENV['RECAPTCHA_SECRET_KEY'] ?? '';
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

    // Verify reCAPTCHA
    $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecret}&response={$recaptchaResponse}");
    $captchaSuccess = json_decode($verify);

    if (!$captchaSuccess || !$captchaSuccess->success) {
        $_SESSION['login_error'] = "Captcha verification failed. Please try again.";
        header('Location: login_students.php');
        exit();
    }

    // Check if inputs are provided
    if (!empty(trim($_POST['email'])) && !empty(trim($_POST['password']))) {
        
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $college_id = $_POST['college_id'] ?? null;

        // Prepare SQL to fetch student details by email
        $sql = "SELECT id, firstname, email, password, college_id FROM users WHERE email = ? AND role = 'student'";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            
            if ($stmt->execute()) {
                $stmt->store_result();

                // Check if email exists
                if ($stmt->num_rows >= 1) {
                    $stmt->bind_result($id, $name, $db_email, $hashed_password, $college_id);

                    if ($stmt->fetch()) {
                        // Verify hashed password
                        if (password_verify($password, $hashed_password)) {
                            
                            // Password is correct! Create Session
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["name"] = $name;
                            $_SESSION["email"] = $db_email;
                            $_SESSION["college_id"] = $college_id;

                            header("Location: dashboard_students.php");
                            exit();
                            
                        } else {
                            $_SESSION['login_error'] = "Invalid email or password!";
                        }
                    }
                } else {
                    $_SESSION['login_error'] = "Email doesn't exist.";
                }
            } else {
                $_SESSION['login_error'] = "Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    } else {
        $_SESSION['login_error'] = "Please fill in all fields.";
    }
    
    // If we reach here, login failed. Redirect back to login_students.php
    header("Location: login_students.php");
    exit();
}

$conn->close();
?>
