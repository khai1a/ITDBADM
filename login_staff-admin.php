<?php
include('db_connect.php');
session_start();

$message = "";
$message_type = "";

// Login handler
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // Check staff table
    $query_staff = "SELECT * FROM staff WHERE username='$username'";
    $result_staff = mysqli_query($conn, $query_staff);

    if (mysqli_num_rows($result_staff) > 0) {
        $staff = mysqli_fetch_assoc($result_staff);

        // changed nov 18
        /* Compare plain text password
        if ($password === $staff['password']) {
            $_SESSION['user_id'] = $staff['staff_ID'];
            $_SESSION['role'] = $staff['role'];
            $_SESSION['branch_id'] = $staff['branch_ID']; */ 
        if (hash('sha256', $password) === $staff['password']) {
            $_SESSION['user_id'] = $staff['staff_ID'];
            $_SESSION['role'] = $staff['role'];
            $_SESSION['branch_id'] = $staff['branch_ID'];
            $_SESSION['username'] = $staff['username'];

            switch ($staff['role']) {
                case 'Admin':
                    $message = "Welcome Admin! Redirecting...";
                    $message_type = "success";
                    echo "<meta http-equiv='refresh' content='2;url=admin_dashboard.php'>";
                    break;
                case 'Inter-Branch Manager':
                    $message = "Welcome Inter-Branch Manager! Redirecting...";
                    $message_type = "success";
                    echo "<meta http-equiv='refresh' content='2;url=manager_dashboard.php'>";
                    break;
                case 'Branch Manager':
                    $message = "Welcome Branch Manager! Redirecting...";
                    $message_type = "success";
                    echo "<meta http-equiv='refresh' content='2;url=BranchManager/manager_dashboard.php'>";
                    break;
                case 'Branch Employee':
                    $message = "Welcome Branch Employee! Redirecting...";
                    $message_type = "success";
                    echo "<meta http-equiv='refresh' content='2;url=BranchEmployee/employee_dashboard.php'>";
                    break;
                case 'Perfumer':
                    $message = "Welcome Perfumer! Redirecting...";
                    $message_type = "success";
                    echo "<meta http-equiv='refresh' content='2;url=perfumer_dashboard.php'>";
                    break;
                default:
                    $message = "Role not recognized.";
                    $message_type = "danger";
                    break;
            }
        } else {
            $message = "Incorrect password.";
            $message_type = "danger";
        }
    } else {
        $message = "User not found.";
        $message_type = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Aurum Scents</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

<link rel="stylesheet" href="login_staff-admin.css">
</head>

<body>
<div class="container">
  <div class="login-card">

    <!-- LEFT -->
    <div class="login-left">
      <h3>Admin/Staff Login</h3>

      <?php if($message): ?>
        <div class="alert <?= $message_type ?>"><?= $message ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-group">
          <input type="text" name="username" placeholder="Username" required>
        </div>
        <div class="form-group">
          <input type="password" name="password" placeholder="Password" required>
        </div>
        <button type="submit" class="btn-submit">Sign In</button>
      </form>
    </div>

    <!-- RIGHT -->
    <div class="login-right"></div>

  </div>
</div>
</body>
</html>


