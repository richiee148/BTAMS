<?php
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id    = (int)$_POST['user_id'];
    $college_id = (int)$_POST['college_id'];
    $role       = $_SESSION['role'];

    // Admin codes per college (BTAMS format)
    $adminCodes = [
        1 => 'ADM-COT-BTAMS',
        2 => 'ADM-COB-BTAMS',
        3 => 'ADM-CAS-BTAMS',
        4 => 'ADM-CPAG-BTAMS',
        5 => 'ADM-CON-BTAMS',
        6 => 'ADM-COE-BTAMS'
    ];

    // Load the college's code from DB
    $collegeCode = null;
    $stmt = $conn->prepare("SELECT college_code FROM colleges WHERE college_id = ?");
    $stmt->bind_param("i", $college_id);
    $stmt->execute();
    $collegeResult = $stmt->get_result();
    if ($collegeResult && $collegeResult->num_rows > 0) {
        $collegeRow = $collegeResult->fetch_assoc();
        $collegeCode = strtoupper(trim($collegeRow['college_code']));
    }
    $stmt->close();

    if ($role === 'admin') {
        $position   = (int)$_POST['position_id'];
        $admin_code = strtoupper(trim($_POST['admin_code']));

        // Ensure position is unique per college
        $check = $conn->prepare("SELECT id FROM users WHERE college_id=? AND position_id=? AND id!=?");
        $check->bind_param("iii", $college_id, $position, $user_id);
        $check->execute();
        $result = $check->get_result();
        if ($result->num_rows > 0) {
            $_SESSION['error'] = "This position is already taken in the selected college.";
            header("Location: /SBO-BTAMS/select_college.php?user_id=" . $user_id);
            exit();
        }

        // Validate admin code
        $expectedAdminCode = strtoupper($adminCodes[$college_id] ?? '');
        if ($admin_code !== $expectedAdminCode) {
            $_SESSION['error'] = "Invalid admin code for the selected college.";
            header("Location: /SBO-BTAMS/select_college.php?user_id=" . $user_id);
            exit();
        }

        $update = $conn->prepare("UPDATE users SET college_id=?, position_id=?, admin_code=? WHERE id=?");
        $update->bind_param("issi", $college_id, $position, $admin_code, $user_id);

    } elseif ($role === 'student') {
        $student_id  = trim($_POST['student_id_number']);
        $enteredCode = strtoupper(trim($_POST['users_code']));

        // Ensure student_id_number is unique
        $check = $conn->prepare("SELECT id FROM users WHERE student_id_number=? AND id!=?");
        $check->bind_param("si", $student_id, $user_id);
        $check->execute();
        $result = $check->get_result();
        if ($result->num_rows > 0) {
            $_SESSION['error'] = "Student ID already exists.";
            header("Location: /SBO-BTAMS/select_college.php?user_id=" . $user_id);
            exit();
        }

        // Validate against college_code
        if ($collegeCode === null || $enteredCode !== $collegeCode) {
            $_SESSION['error'] = "Invalid college code for the selected college.";
            header("Location: /SBO-BTAMS/select_college.php?user_id=" . $user_id);
            exit();
        }

        $update = $conn->prepare("UPDATE users SET college_id=?, student_id_number=? WHERE id=?");
        $update->bind_param("isi", $college_id, $student_id, $user_id);

    } elseif ($role === 'parent') {
        $child_student_id = trim($_POST['child_student_id']);
        $enteredCode      = strtoupper(trim($_POST['users_code']));

        // Lookup child by student_id_number
        $lookup = $conn->prepare("SELECT id, college_id FROM users WHERE student_id_number=?");
        $lookup->bind_param("s", $child_student_id);
        $lookup->execute();
        $result = $lookup->get_result();

        if ($result->num_rows > 0) {
            $child = $result->fetch_assoc();
            $child_user_id    = $child['id'];
            $child_college_id = $child['college_id'];

            if ((int)$college_id !== (int)$child_college_id) {
                $_SESSION['error'] = "Selected college does not match your child’s college.";
                header("Location: /SBO-BTAMS/select_college.php?user_id=" . $user_id);
                exit();
            }

            if ($collegeCode === null || $enteredCode !== $collegeCode) {
                $_SESSION['error'] = "Invalid college code for the selected college.";
                header("Location: /SBO-BTAMS/select_college.php?user_id=" . $user_id);
                exit();
            }

            $update = $conn->prepare("UPDATE users SET college_id=?, child_user_id=? WHERE id=?");
            $update->bind_param("iii", $college_id, $child_user_id, $user_id);

        } else {
            $_SESSION['error'] = "Child student ID not found. Please try again.";
            header("Location: /SBO-BTAMS/select_college.php?user_id=" . $user_id);
            exit();
        }
    }

    // ✅ Finalize update and redirect to role-specific dashboard
    if (isset($update) && $update->execute()) {
        $_SESSION['college_id']    = $college_id;
        $_SESSION['code_verified'] = true;

        if ($role === 'admin') {
            header("Location: /SBO-BTAMS/admins/dashboard_admins.php");
        } elseif ($role === 'student') {
            header("Location: /SBO-BTAMS/students/dashboard_students.php");
        } elseif ($role === 'parent') {
            header("Location: /SBO-BTAMS/parents/dashboard_parents.php");
        } else {
            // fallback if role is unknown
            header("Location: /SBO-BTAMS/index.php");
        }
        exit();
    } else {
        $_SESSION['error'] = "Failed to save college.";
        header("Location: /SBO-BTAMS/select_college.php?user_id=" . $user_id);
        exit();
    }
}
?>
