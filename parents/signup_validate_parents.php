<?php
session_start();
require '../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname   = trim($_POST["firstname"]);
    $lastname    = trim($_POST["lastname"]);
    $username    = trim($_POST["username"]);
    $email       = trim($_POST["email"]);
    $current_password = $_POST["password"]; 
    $new_password     = $_POST["confirm_password"]; 
    $role        = 'parent';
    $college_id  = $_POST["college_id"];
    $entered_student_id = $_POST['student_id_number']; 

    $verify_student = $conn->prepare("SELECT id, college_id FROM users WHERE role='student' AND student_id_number = ?");
    $verify_student->bind_param("s", $entered_student_id);
    $verify_student->execute();
    $student_result = $verify_student->get_result();

    if ($student_result->num_rows === 0) {
        $_SESSION['error'] = "Invalid Student ID. Please enter a valid student ID number.";
        header("Location: signup_parents.php");
        exit();
    }

    $student_row = $student_result->fetch_assoc();
    $child_user_id = $student_row['id']; 
    $student_college_id = $student_row['college_id'];

    if ($college_id !== $student_college_id) {
        $_SESSION['error'] = "College mismatch. Please select the correct college of your child.";
        header("Location: signup_parents.php");
        exit();
    }

    $check = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_id = $row['id'];
        $stored_hash = $row['password'];

        if (!password_verify($current_password, $stored_hash)) {
            $_SESSION['error'] = "Current password is incorrect.";
            header("Location: signup_parents.php");
            exit();
        }

        $hashed_password = !empty($new_password) 
            ? password_hash($new_password, PASSWORD_DEFAULT) 
            : $stored_hash;

        $update = $conn->prepare("UPDATE users 
            SET firstname=?, lastname=?, username=?, password=?, college_id=?, role=?, child_user_id=? 
            WHERE id=?");
        $update->bind_param("ssssssii", $firstname, $lastname, $username, $hashed_password, $college_id, $role, $child_user_id, $user_id);

        if ($update->execute()) {
            $_SESSION['loggedin'] = true;
            $_SESSION['id'] = $user_id;
            $_SESSION['name'] = $firstname;
            $_SESSION['role'] = $role;
            $_SESSION['college_id'] = $college_id;

            header("Location: dashboard_parents.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to update account: " . $update->error;
            header("Location: signup_parents.php");
            exit();
        }
    } else {
        $hashed_password = password_hash($current_password, PASSWORD_DEFAULT);

        $insert = $conn->prepare("INSERT INTO users 
            (firstname, lastname, username, email, password, role, college_id, child_user_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insert->bind_param("sssssssi", $firstname, $lastname, $username, $email, $hashed_password, $role, $department, $child_user_id);

        if ($insert->execute()) {
            $new_user_id = $conn->insert_id;

            $_SESSION['loggedin'] = true;
            $_SESSION['id'] = $new_user_id;
            $_SESSION['name'] = $firstname;
            $_SESSION['role'] = $role;
            $_SESSION['dept'] = $department;

            header("Location: dashboard_parents.php");
            exit();
        } else {
            $_SESSION['error'] = "Registration failed: " . $insert->error;
            header("Location: signup_parents.php");
            exit();
        }
    }
}
?>
