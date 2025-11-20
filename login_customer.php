<?php
session_start();
include('db_connect.php');

$message = "";
$message_type = "";
$email_value = "";
$lock_time = 0; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $email_value = htmlspecialchars($email); // if login fails, retain the email

    // customer exists checker
    $query = "SELECT * FROM customers WHERE email = '$email'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $customer = mysqli_fetch_assoc($result);

        if (password_verify($password, $customer['password'])) {
            // successful login
            $_SESSION['customer_ID'] = $customer['customer_ID'];
            $_SESSION['role'] = 'customer';
            $message = "Login successful! Redirecting...";
            $message_type = "success";

            $stmt = $conn->prepare("INSERT INTO login_attempts (email, success) VALUES (?, 1)");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->close();

            header("Refresh:2; url=customer_home.php");

        } else {
            
            try {
                $stmt = $conn->prepare("INSERT INTO login_attempts (email, success) VALUES (?, 0)");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->close();

                $message = "Incorrect password.";
                $message_type = "danger";

            } catch (mysqli_sql_exception $e) {
            
                if (strpos($e->getMessage(), 'Too many failed login attempts') !== false) {
                    $message = $e->getMessage(); 
                    $message_type = "danger";
                    $lock_time = 10; 
                } else {
                
                    $message = "Database error: " . $e->getMessage();
                    $message_type = "danger";
                }
            }
        }
    } else {
        // customer not found in db
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
    <div class="login-left">
      <h3>Welcome Back to <br>Aurum Scents</h3>

      <div class="alert-container">
        <?php if($message): ?>
          <div class="alert <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
      </div>

      <form method="POST" id="login-form">
        <div class="form-group">
          <input type="email" name="email" placeholder="Email" required value="<?= $email_value ?>">
        </div>
        <div class="form-group">
          <input type="password" name="password" placeholder="Password" id="password-field" required>
        </div>
        <button type="submit" class="btn-submit" id="submit-btn">Sign In</button>
      </form>
    </div>

    <div class="login-right">
      <div class="login-right-overlay">
        <h4>New Here?</h4>
        <p>Create an account and explore our world of elegant, timeless fragrances made just for you.</p>
        <a href="sign-up_customer.php" class="signup-btn">Create Account</a>
      </div>
    </div>
  </div>
</div>

<?php if($lock_time > 0): ?>
<script>
    const passwordField = document.getElementById('password-field');
    const submitBtn = document.getElementById('submit-btn');

    passwordField.disabled = true;
    submitBtn.disabled = true;

    let countdown = <?= $lock_time ?>;
    passwordField.placeholder = "Locked for " + countdown + "s";

    const interval = setInterval(() => {
        countdown--;
        passwordField.placeholder = "Locked for " + countdown + "s";
        if(countdown <= 0){
            clearInterval(interval);
            passwordField.disabled = false;
            submitBtn.disabled = false;
            passwordField.placeholder = "Password";
        }
    }, 1000);
</script>
<?php endif; ?>

</body>
</html>
