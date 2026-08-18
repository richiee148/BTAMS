<?php
session_start();
require '../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname   = trim($_POST["firstname"]);
    $lastname    = trim($_POST["lastname"]);
    $username    = trim($_POST["username"]);
    $email       = trim($_POST["email"]);
    $password    = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $role        = 'student';
    $college_id  = (int)$_POST["college_id"];
    $student_id_number = trim($_POST["student_id_number"]);

    // ✅ Ensure email is a valid BukSU student email
    if (!preg_match("/^[A-Za-z0-9._%+-]+@student\.buksu\.edu\.ph$/", $email)) {
        $_SESSION['error'] = "You must use your official BukSU student email (@student.buksu.edu.ph).";
        header("Location: signup_students.php");
        exit();
    }

    // ✅ Ensure passwords match
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: signup_students.php");
        exit();
    }

    // ✅ Check if student already exists by email
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result && $result->num_rows > 0) {
        // Email already registered → block signup
        $_SESSION['error'] = "This email is already registered.";
        header("Location: signup_students.php");
        exit();
    }

    // ✅ No existing student → INSERT new record
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $insert = $conn->prepare("INSERT INTO users 
        (firstname, lastname, username, email, password, role, college_id, student_id_number) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $insert->bind_param("ssssssis", $firstname, $lastname, $username, $email, $hashed_password, $role, $college_id, $student_id_number);

    if ($insert->execute()) {
        $new_user_id = $conn->insert_id;

        $_SESSION['loggedin'] = true;
        $_SESSION['id'] = $new_user_id;
        $_SESSION['name'] = $firstname;
        $_SESSION['role'] = $role;
        $_SESSION['college_id'] = $college_id;

        header("Location: dashboard_students.php");
        exit();
    } else {
        $_SESSION['error'] = "Registration failed: " . $insert->error;
        header("Location: signup_students.php");
        exit();
    }
}
?>
