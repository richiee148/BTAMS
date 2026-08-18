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
    $role        = 'admin';
    $college_id  = (int)$_POST["college_id"];
    $position_id = (int)$_POST["position_id"];
    $admin_code  = trim($_POST["admin_code"]);

    // ✅ Institutional email restriction
    if (!preg_match("/^[A-Za-z0-9._%+-]+@student\.buksu\.edu\.ph$/", $email)) {
        $_SESSION['signup_error'] = "Admins must use their official BukSU email (@student.buksu.edu.ph).";
        header("Location: signup_admins.php");
        exit();
    }

    // ✅ Passwords must match
    if ($password !== $confirm_password) {
        $_SESSION['signup_error'] = "Passwords do not match.";
        header("Location: signup_admins.php");
        exit();
    }

    // ✅ Admin verification code check per college
    $collegeAdminCodes = [
        1 => 'ADM-COT-BTAMS',
        2 => 'ADM-COB-BTAMS',
        3 => 'ADM-CAS-BTAMS',
        4 => 'ADM-CPAG-BTAMS',
        5 => 'ADM-CON-BTAMS',
        6 => 'ADM-COE-BTAMS'
    ];

    $expected_code = $collegeAdminCodes[$college_id] ?? null;
    if ($expected_code === null || $admin_code !== $expected_code) {
        $_SESSION['signup_error'] = "Invalid admin code for the selected college.";
        header("Location: signup_admins.php");
        exit();
    }

    // ✅ Check if email already exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result && $result->num_rows > 0) {
        $_SESSION['signup_error'] = "This email is already registered. Please log in instead.";
        header("Location: signup_admins.php");
        exit();
    }

    // ✅ Insert new admin record
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $insert = $conn->prepare("INSERT INTO users 
        (firstname, lastname, username, password, email, role, college_id, position_id, admin_code) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insert->bind_param("sssssssis", $firstname, $lastname, $username, $hashed_password, $email, $role, $college_id, $position_id, $admin_code);

    if ($insert->execute()) {
        $new_user_id = $conn->insert_id;

        $_SESSION['loggedin'] = true;
        $_SESSION['id'] = $new_user_id;
        $_SESSION['name'] = $firstname;
        $_SESSION['role'] = $role;
        $_SESSION['college_id'] = $college_id;

        // ✅ Always redirect to dashboard after successful signup
        header("Location: dashboard_admins.php");
        exit();
    } else {
        $_SESSION['signup_error'] = "Registration failed: " . $insert->error;
        header("Location: signup_admins.php");
        exit();
    }
}
?>
