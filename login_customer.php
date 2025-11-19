<?php
include('db_connect.php');
session_start();

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $query_cust = "SELECT * FROM customers WHERE email='$email'";
    $result_cust = mysqli_query($conn, $query_cust);

    if (mysqli_num_rows($result_cust) > 0) {
        $customer = mysqli_fetch_assoc($result_cust);

        // ✅ Correct password verification
        if (password_verify($password, $customer['password'])) {
            $_SESSION['customer_ID'] = $customer['customer_ID'];
            $_SESSION['role'] = 'customer';
            $message = "Login successful! Redirecting...";
            $message_type = "success";
            echo "<meta http-equiv='refresh' content='2;url=perfumes.php'>";
        } else {
            $message = "Incorrect password.";
            $message_type = "danger";
        }
    } else {
        $message = "Customer not found.";
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
<link rel="stylesheet" href="login_customer.css">
</head>

<body>
<div class="container">
  <div class="login-card">

    <!-- LEFT -->
    <div class="login-left">
      <h3>Welcome Back to <br>Aurum Scents</h3>

      <?php if($message): ?>
        <div class="alert <?= $message_type ?>"><?= $message ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <input type="email" name="email" placeholder="Email" required>
        </div>
        <div class="form-group">
          <input type="password" name="password" placeholder="Password" required>
        </div>
        <button type="submit" class="btn-submit">Sign In</button>
      </form>
    </div>

    <!-- RIGHT -->
    <div class="login-right">
      <div class="login-right-overlay">
        <h4>New Here?</h4>
        <p>Create an account and explore our world of elegant, timeless fragrances made just for you.</p>
        <a href="sign-up_customer.php" class="signup-btn">Create Account</a>
      </div>
    </div>

  </div>
</div>
</body>
</html>



