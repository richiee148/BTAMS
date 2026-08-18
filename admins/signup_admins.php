<?php
session_start();
require '../config.php';

// Fetch positions from DB
$positions = [];
$posResult = $conn->query("SELECT position_id, position_name FROM positions ORDER BY position_name ASC");
while ($posRow = $posResult->fetch_assoc()) {
    $positions[] = $posRow;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Signup | BukSU Budget System</title>
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
        <p>Administrative Registration for SBO <br>Allocation & Management System</p>
    </div>

    <div class="container-fluid vh-100 d-flex justify-content-center align-items-center">
        <div class="card p-4 shadow signup-card">
            <h3 class="fw-bold text-center mb-3">Admin Signup</h3>

            <!-- Success/Error Messages -->
            <div class="mt-2">
              <?php
              if (isset($_SESSION['signup_error'])){
                echo '<div class="alert alert-danger text-center py-2">' . $_SESSION['signup_error'] . '</div>';
                unset($_SESSION['signup_error']);
              }
              if (isset($_SESSION['signup_success'])){
                echo '<div class="alert alert-success text-center py-2">' . $_SESSION['signup_success'] . '</div>';
                unset($_SESSION['signup_success']);
              }
              ?>
            </div>

            <form action="signup_validate_admins.php" method="POST">
                <input type="hidden" name="role" value="admin">

                <div class="row mb-3">
                    <div class="col-6 text-start">
                        <label class="form-label">First Name</label>
                        <input type="text" name="firstname" class="form-control" placeholder="First Name" required>
                    </div>
                    <div class="col-6 text-start">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="lastname" class="form-control" placeholder="Last Name" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-6 text-start">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="Create username" required>
                    </div>
                    <div class="col-6 text-start">
                        <label class="form-label">Position</label>
                        <select name="position_id" class="form-select" required>
                            <option value="" disabled selected>Select Position</option>
                            <?php foreach ($positions as $pos): ?>
                                <option value="<?php echo htmlspecialchars($pos['position_id']); ?>">
                                    <?php echo htmlspecialchars($pos['position_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3 text-start">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="@student.buksu.edu.ph" required>
                </div>

                <div class="mb-3 text-start">
                    <label class="form-label">Assigned Department</label>
                    <select name="college_id" class="form-select" required>
                        <option value="" disabled selected>Select the College</option>
                        <option value="1">COT - College of Technology</option>
                        <option value="2">COB - College of Business</option>
                        <option value="3">CAS - College of Arts & Sciences</option>
                        <option value="4">CPAG - Public Administration & Governance</option>
                        <option value="5">CON - College of Nursing</option>
                        <option value="6">COE - College of Education</option>
                    </select>
                </div>

                <div class="row mb-4">
                    <div class="col-6 text-start">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-6 text-start">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>

                <!-- 🔑 Admin Verification Code -->
                <div class="mb-3 text-start">
                    <label class="form-label">Admin Verification Code</label>
                    <input type="text" name="admin_code" class="form-control" placeholder="Enter your admin code" required>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mb-3 rounded-pill" style="border: none;">
                    Create Admin Account
                </button>
            </form>

            <div class="text-center">
                <p class="small mb-0">Already have an account? 
                    <a href="login_admins.php" class="text-decoration-none fw-bold">Login</a>
                </p>
            </div>
        </div>
    </div>

</body>
</html>
