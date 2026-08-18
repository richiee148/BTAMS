<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Signup | BukSU Budget System</title>
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

    <div class="description-container d-none d-lg-block">
        <h1>Empowering <br>BukSU Finance</h1>
        <p>Student Registration for SBO <br>Allocation & Management System</p>
    </div>

    <div class="container-fluid vh-100 d-flex justify-content-center align-items-center">
        <div class="card p-4 shadow signup-card">
            <h3 class="fw-bold text-center mt-2">Student Signup</h3>

<!-- Success/Error Messages -->
<div class="mt-2">
  <?php
  if (isset($_SESSION['error'])){
    echo '<div class="alert alert-danger text-center py-2">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
  }
  if (isset($_SESSION['success'])){
    echo '<div class="alert alert-success text-center py-2">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
  }
  ?>
</div>

            <form action="signup_validate_students.php" method="POST">
                <input type="hidden" name="role" value="student">

                <div class="row g-2 mt-2">
                    <div class="col-md-6 text-start">
                        <label class="form-label">First Name</label>
                        <input type="text" name="firstname" class="form-control" placeholder="Enter first name" required>
                    </div>
                    <div class="col-md-6 text-start">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="lastname" class="form-control" placeholder="Enter last name" required>
                    </div>
                </div>

                <div class="row g-2 mt-2">
                    <div class="col-md-6 text-start">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="Create username" required>
                    </div>
                    <div class="col-md-6 text-start">
                        <label class="form-label">Student ID Number</label>
                        <input type="text" name="student_id_number" class="form-control" placeholder="Enter your student ID" required>
                    </div>
                </div>

                <div class="mb-2 text-start">
                    <label class="form-label mt-2">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="name@student.buksu.edu.ph" required>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-md-12 text-start">
                        <label class="form-label">Department</label>
                        <select name="department" class="form-select" required>
                            <option value="" disabled selected>Select your College</option>
                            <option value="COT">COT - College of Technology</option>
                            <option value="COB">COB - College of Business</option>
                            <option value="CAS">CAS - College of Arts & Sciences</option>
                            <option value="CPAG">CPAG - Public Administration & Governance</option>
                            <option value="CON">CON - College of Nursing</option>
                            <option value="COE">COE - College of Education</option>
                        </select>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6 text-start">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder=" " required>
                    </div>
                    <div class="col-md-6 text-start">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder=" " required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mb-3 rounded-pill" style="border: none;">
                    Create Student Account
                </button>
            </form>

            <div class="text-center">
                <p class="small mb-0">Already have an account? <a href="login_students.php" class="text-decoration-none fw-bold">Login</a></p>
            </div>
        </div>
    </div>

</body>
</html>