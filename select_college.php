<?php
require_once 'config.php';
session_start();

$user_id = $_GET['user_id'] ?? null;
if (!$user_id) {
    header('Location: /SBO-BTAMS/google_login.php');
    exit();
}

$role    = $_SESSION['role'] ?? 'student';

// Load colleges dynamically from DB
$colleges = [];
$result = $conn->query("SELECT college_id, college_name FROM colleges ORDER BY college_name ASC");
while ($row = $result->fetch_assoc()) {
    $colleges[] = $row;
}

// Load positions dynamically from DB (for admins)
$positions = [];
$result = $conn->query("SELECT position_id, position_name FROM positions ORDER BY position_name ASC");
while ($row = $result->fetch_assoc()) {
    $positions[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select College</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/select_college.css">
</head>
<body>
    <div class="d-flex justify-content-center align-items-center vh-100">
        <div class="card college-card shadow-lg">
            <div class="card-body">
                <h2 class="card-title text-center mb-4 mt-2">
                    <?php echo ($role === 'parent') ? 'Select your child’s college' : 'Select your college'; ?>
                </h2>

                <!-- Error message -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger text-center">
                        <?php 
                            echo htmlspecialchars($_SESSION['error']); 
                            unset($_SESSION['error']); // clear after showing
                        ?>
                    </div>
                <?php endif; ?>

                <form action="/SBO-BTAMS/save_college.php" method="POST">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">

                    <!-- College dropdown -->
                    <div class="mb-4 mt-2">
                        <select name="college_id" class="form-select" required>
                            <option value="">-- Select College --</option>
                            <?php foreach ($colleges as $college): ?>
                                <option value="<?php echo htmlspecialchars($college['college_id']); ?>">
                                    <?php echo htmlspecialchars($college['college_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Extra fields based on role -->
                    <?php if ($role === 'admin'): ?>
                        <div class="mb-3">
                            <select name="position_id" class="form-select" required>
                                <option value="">-- Select Position --</option>
                                <?php foreach ($positions as $pos): ?>
                                    <option value="<?php echo htmlspecialchars($pos['position_id']); ?>">
                                        <?php echo htmlspecialchars($pos['position_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <input type="text" name="admin_code" class="form-control" placeholder="Enter admin access code" required>
                        </div>
                    <?php elseif ($role === 'student'): ?>
                        <div class="mb-3">
                            <input type="text" name="student_id_number" class="form-control" placeholder="Enter your student ID" required>
                        </div>
                        <div class="mb-3">
                            <input type="text" name="users_code" class="form-control" placeholder="Enter your college code" required>
                        </div>
                    <?php elseif ($role === 'parent'): ?>
                        <div class="mb-3">
                            <input type="text" name="child_student_id" class="form-control" placeholder="Enter your child’s student ID" required>
                        </div>
                        <div class="mb-3">
                            <input type="text" name="users_code" class="form-control" placeholder="Enter your college code" required>
                        </div>
                    <?php endif; ?>

                    <div class="d-grid mt-2">
                        <button type="submit" class="btn btn-primary w-100 rounded-pill">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
