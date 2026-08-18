<?php
require_once '../vendor/autoload.php';
require_once '../config.php';
session_start();

function getUniqueUsername($conn, $username, $excludeId = null) {
    $candidate = trim($username);
    if ($candidate === '') {
        $candidate = 'user';
    }
    $baseUsername = $candidate;
    $suffix = 1;

    while (true) {
        if ($excludeId !== null) {
            $checkSql = "SELECT id FROM users WHERE username = ? AND id != ?";
            $check = $conn->prepare($checkSql);
            $check->bind_param("si", $candidate, $excludeId);
        } else {
            $checkSql = "SELECT id FROM users WHERE username = ?";
            $check = $conn->prepare($checkSql);
            $check->bind_param("s", $candidate);
        }

        $check->execute();
        $result = $check->get_result();

        if (!$result || $result->num_rows === 0) {
            return $candidate;
        }

        $candidate = $baseUsername . ' ' . $suffix;
        $suffix++;
    }
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
$client->addScope('email');
$client->addScope('profile');

if (isset($_GET['code'])) {
    $client->authenticate($_GET['code']);
    $token = $client->getAccessToken();
    
    if (!isset($token['error'])) {
        $oauth2 = new Google_Service_Oauth2($client);
        $userInfo = $oauth2->userinfo->get();

        $email   = $userInfo->email;
        $name    = $userInfo->name;
        $picture = $userInfo->picture;
        
        // Check if user exists
        $check = $conn->prepare("SELECT id, role, college_id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();
        
        $selectedRole = $_SESSION['role'] ?? null;
        $isNewUser = false;

        if ($result && $result->num_rows > 0) {
            // Existing user
            $row = $result->fetch_assoc();
            $user_id = $row['id'];
            $role    = $row['role'];
            $college_id = $row['college_id'];

            // If user picked a role and it's different, update DB
            if ($selectedRole && $selectedRole !== $role) {
                $role = $selectedRole;
                $updateRole = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                $updateRole->bind_param("si", $role, $user_id);
                $updateRole->execute();
            }

            // Update username if needed
            $username = getUniqueUsername($conn, $name, $user_id);
            $update = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
            $update->bind_param("si", $username, $user_id);
            $update->execute();

        } else {
            // New user - parse name: last word is lastname, rest is firstname
            $nameParts = explode(' ', trim($name));
            if (count($nameParts) > 1) {
                $lastname = array_pop($nameParts);
                $firstname = implode(' ', $nameParts);
            } else {
                $firstname = $name;
                $lastname = '';
            }
            $role = $selectedRole ?? 'student';
            $isNewUser = true;

            $username = getUniqueUsername($conn, $name);
            // Insert with dept_id = NULL (foreign key safe)
            $insert = $conn->prepare("INSERT INTO users (firstname, lastname, username, email, role, college_id) VALUES (?, ?, ?, ?, ?, NULL)");
            $insert->bind_param("sssss", $firstname, $lastname, $username, $email, $role);

            if ($insert->execute()) {
                $user_id = $conn->insert_id;
            } else {
                $_SESSION['login_error'] = "Failed to create account: " . $insert->error;
                header("Location: google_login.php");
                exit();
            }
        }

        // Save to session
        $_SESSION['loggedin']   = true;
        $_SESSION['id']         = $user_id;
        $_SESSION['user_type']  = 'google';
        $_SESSION['name']       = $name;
        $_SESSION['user_name']  = $username ?? $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_image'] = $picture;
        $_SESSION['role']       = $role ?? 'student';
        $_SESSION['success']    = "Login with Google!";
        
        // New users must select department before accessing dashboard
        if ($isNewUser) {
            header('Location: /SBO-BTAMS/select_college.php?user_id=' . $user_id);
            exit();
        }
        
        // Existing users without school/college must select college
        if (empty($college_id)) {
            header('Location: /SBO-BTAMS/select_college.php?user_id=' . $user_id);
            exit();
        }
        
        // Existing users with college can go to their dashboard
        if ($_SESSION['role'] === 'student') {
            header('Location: /SBO-BTAMS/students/dashboard_students.php');
        } elseif ($_SESSION['role'] === 'parent') {
            header('Location: /SBO-BTAMS/parents/dashboard_parents.php');
        } elseif ($_SESSION['role'] === 'admin') {
            header('Location: /SBO-BTAMS/admins/dashboard_admins.php');
        } else {
            header('Location: /SBO-BTAMS/students/dashboard_students.php');
        }
        exit();
    } else {
        $_SESSION['login_error'] = "Google authentication failed. Please try again.";
        header('Location: google_login.php');
        exit();
    }
} else {
    $_SESSION['login_error'] = "No authentication code received. Please try again.";
    header('Location: google_login.php');
    exit();
}
