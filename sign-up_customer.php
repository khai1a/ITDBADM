<?php
include('db_connect.php');
session_start();

$message = "";

// Fetch countries
$country_result = mysqli_query(
    $conn,
    "SELECT country_ID, country_name, currency FROM countries ORDER BY country_name ASC"
);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name  = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email      = mysqli_real_escape_string($conn, $_POST['email']);
    $password   = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    $mobile     = mysqli_real_escape_string($conn, $_POST['mobile_number']);
    $country    = mysqli_real_escape_string($conn, $_POST['country_ID']);
    $currency   = mysqli_real_escape_string($conn, $_POST['currency']);
    $birthday   = mysqli_real_escape_string($conn, $_POST['birthday']);
    $address    = mysqli_real_escape_string($conn, $_POST['address']);

    // Validate password match
    if ($password !== $confirm_password) {
        $message = "Passwords do not match!";
    } elseif (empty($country)) {
        $message = "Please select a country / region!";
    } else {
        // Generate new customer_ID
        $result = mysqli_query(
            $conn,
            "SELECT customer_ID FROM customers ORDER BY CAST(SUBSTRING(customer_ID, 5) AS UNSIGNED) DESC LIMIT 1"
        );

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $num = (int) substr($row['customer_ID'], 4) + 1;
            $customer_ID = 'CUST' . str_pad($num, 2, '0', STR_PAD_LEFT);
        } else {
            $customer_ID = 'CUST01';
        }

        // Check email/mobile
        $check_query = "SELECT * FROM customers WHERE email='$email' OR mobile_number='$mobile'";
        $check_result = mysqli_query($conn, $check_query);

        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $message = "Email or mobile number already exists!";
        } else {
            // Insert customer
            $insert_query = "
                INSERT INTO customers 
                (customer_ID, first_name, last_name, country_ID, email, password, mobile_number, birthday, address) 
                VALUES 
                ('$customer_ID', '$first_name', '$last_name', '$country', '$email', '$password', '$mobile', '$birthday', '$address')
            ";

            if (mysqli_query($conn, $insert_query)) {
                $message = "Sign-up successful! You can now <a href='login_customer.php'>login</a>.";
            } else {
                $message = "Error: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Aurum Scents</title>

  
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="sign-up_customer.css">
</head>

<body>
<div class="background-wrapper">
    <div class="register-box">
        <div class="register-left">
            <h2>Sign Up</h2>

            <?php if ($message): ?>
                <div class="alert alert-info"><?= $message ?></div>
            <?php endif; ?>

            <form method="POST" onsubmit="return validatePassword();">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <input type="text" name="first_name" class="form-control" placeholder="First Name" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <input type="text" name="last_name" class="form-control" placeholder="Last Name" required>
                    </div>
                </div>

                <div class="mb-3">
                    <input type="email" name="email" class="form-control" placeholder="Email" required>
                </div>

                <div class="mb-3">
                    <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
                </div>

                <div class="mb-3">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <input type="text" name="mobile_number" class="form-control" placeholder="Mobile Number" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <select name="country_ID" id="country_ID" class="form-control" onchange="setCurrency()" required>
                            <option value="" disabled selected style="color: rgba(2, 0, 2, 0.77);">Country / Region</option>

                            <?php
                                mysqli_data_seek($country_result, 0);
                                while($row = mysqli_fetch_assoc($country_result)):
                            ?>
                                <option value="<?= $row['country_ID'] ?>" data-currency="<?= $row['currency'] ?>" style="color: #000;">
                                    <?= $row['country_name'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>

                        <input type="hidden" name="currency" id="currency">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <input type="date" name="birthday" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <textarea name="address" class="form-control" placeholder="Address"></textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-login w-100">Sign Up</button>

                <div class="text-center mt-3">
                    Already have an account? <a href="login_customer.php">Login</a>
                </div>
            </form>
        </div>

        <div class="register-right">
            <h3>Welcome to Aurum Scents</h3>
            <p>Discover timeless fragrances that reflect your elegance.</p>
        </div>
    </div>
</div>

<script>
function validatePassword() {
    const password = document.getElementById("password").value;
    const confirm = document.getElementById("confirm_password").value;

    if (password !== confirm) {
        alert("Passwords do not match!");
        return false;
    }
    return true;
}

function setCurrency() {
    const select = document.getElementById("country_ID");
    const currencyInput = document.getElementById("currency");
    const selectedOption = select.options[select.selectedIndex];
    currencyInput.value = selectedOption ? selectedOption.getAttribute("data-currency") : "";
}

window.onload = setCurrency;
</script>

</body>
</html>
