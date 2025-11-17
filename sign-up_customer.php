<?php
include('db_connect.php');
session_start();

$message = "";
$success = false;

// Fetch countries for dropdown
$country_result = mysqli_query($conn, "SELECT country_ID, country_name, currency FROM countries ORDER BY country_name ASC");

// Pre-fill values if session exists
$signup_data = $_SESSION['signup'] ?? [];

// Email validation function
function validate_strict_email($email) {
    return preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,10}$/', $email);
}


// Password validation function
function validate_password($password) {
    return preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W_]).{8,}$/', $password);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    /* -----------------------------------------------
       STEP 1 — SAVE PERSONAL DATA IN SESSION
    -------------------------------------------------*/
    if (isset($_POST['step']) && $_POST['step'] == 1) {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            $message = "Passwords do not match!";
        } elseif (!validate_password($password)) {
            $message = "Password must be at least 8 characters, include uppercase, lowercase, number, and symbol.";
        } elseif (!validate_strict_email($_POST['email'])) {
        $message = "Invalid email format! Must contain a domain and a valid ending (e.g. .com, .ph, .net, .co.jp)";
        } else {
            $_SESSION['signup'] = [
                'first_name' => mysqli_real_escape_string($conn, $_POST['first_name']),
                'last_name' => mysqli_real_escape_string($conn, $_POST['last_name']),
                'email' => mysqli_real_escape_string($conn, $_POST['email']),
                'password' => $password,
                'confirm_password' => $confirm_password,
                'mobile' => mysqli_real_escape_string($conn, $_POST['mobile_number']),
                'birthday' => $_POST['birthday'],
                'country_ID' => $_POST['country_ID']
            ];
            header("Location: sign-up_customer.php?step=2");
            exit();
        }
    }

    /* -----------------------------------------------
       STEP 2 — INSERT CUSTOMER + ADDRESS
    -------------------------------------------------*/
    if (isset($_POST['step']) && $_POST['step'] == 2) {

        $data = $_SESSION['signup'];
        $first_name = $data['first_name'];
        $last_name = $data['last_name'];
        $email = $data['email'];
        $password = $data['password'];
        $mobile = $data['mobile'];
        $birthday = $data['birthday'];
        $country_ID = $data['country_ID'];

        $address_line1 = mysqli_real_escape_string($conn, $_POST['address_line1']);
        $address_line2 = mysqli_real_escape_string($conn, $_POST['address_line2']);
        $city = mysqli_real_escape_string($conn, $_POST['city']);
        $province = mysqli_real_escape_string($conn, $_POST['province']);
        $postal_code = mysqli_real_escape_string($conn, $_POST['postal_code']);

        // Check duplicates
        $check = mysqli_query($conn, "SELECT * FROM customers WHERE email='$email' OR mobile_number='$mobile'");
        if (mysqli_num_rows($check) > 0) {
            $message = "Email or mobile number already exists!";
        } else {

            /* ---- Generate customer_ID ---- */
            $res = mysqli_query($conn, "SELECT customer_ID
                            FROM customers
                            ORDER BY CAST(SUBSTRING(customer_ID,3) AS UNSIGNED) DESC
                            LIMIT 1");

                if ($row = mysqli_fetch_assoc($res)) {
                    $num = (int)substr($row['customer_ID'], 2) + 1;  // get the number after 'CU'
                    $customer_ID = 'CU' . str_pad($num, 4, '0', STR_PAD_LEFT); // pad to 4 digits
                } else {
                    $customer_ID = 'CU0001';
                }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            /* ---- Insert into customers ---- */
            $insert_customer = "INSERT INTO customers 
                (customer_ID, first_name, last_name, email, password, mobile_number, country_ID, birthday) 
                VALUES 
                ('$customer_ID','$first_name','$last_name','$email','$hashed_password','$mobile','$country_ID','$birthday')";

            if (mysqli_query($conn, $insert_customer)) {

                /* ---- Generate address_ID (AD0001...) ---- */
                $resAdd = mysqli_query($conn, "SELECT address_ID 
                                               FROM customer_addresses 
                                               ORDER BY CAST(SUBSTRING(address_ID,3) AS UNSIGNED) DESC 
                                               LIMIT 1");

                if ($rowAdd = mysqli_fetch_assoc($resAdd)) {
                    $numA = (int)substr($rowAdd['address_ID'], 2) + 1;
                    $address_ID = 'AD' . str_pad($numA, 4, '0', STR_PAD_LEFT);
                } else {
                    $address_ID = 'AD0001';
                }

                /* ---- Insert into customer_addresses ---- */
                $insert_address = "INSERT INTO customer_addresses 
                    (address_ID, customer_ID, address_line1, address_line2, city, province, postal_code, country_ID) 
                    VALUES 
                    ('$address_ID', '$customer_ID', '$address_line1', '$address_line2', '$city', '$province', '$postal_code', '$country_ID')";

                if (mysqli_query($conn, $insert_address)) {
                    $success = true;
                    unset($_SESSION['signup']);
                    $message = "Sign-up successful! You can now <a href='login_customer.php'>Login</a>.";
                } else {
                    $message = "Error inserting address: " . mysqli_error($conn);
                }
            } else {
                $message = "Error inserting customer: " . mysqli_error($conn);
            }
        }
    }
}

$step = $_GET['step'] ?? 1;
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - Aurum Scents</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { margin:0; background:maroon; font-family:'Poppins',sans-serif; }
.background-wrapper { background:url('images/pinkperfume.jpg') center 30%/cover fixed no-repeat; height:100vh; display:flex; justify-content:center; align-items:center; padding:40px; }
.register-box { background:rgba(121,4,4,0.7); border-radius:20px; color:#fff; width:90%; max-width:900px; display:flex; flex-wrap:wrap; overflow:hidden; }
.register-left { flex:1 1 60%; padding:30px; }
.register-right { flex:1 1 40%; background:rgba(255,249,170,0.815); display:flex; align-items:center; justify-content:center; flex-direction:column; text-align:center; padding:20px; }
.register-right h3 { font-weight:600; color: rgb(136,10,6); }
.register-right p { color: rgb(158,24,19); }
.register-right .btn-login { margin-top:20px; background-color:bisque; color:#000; border-radius:25px; font-weight:600; transition:all 0.3s ease; text-decoration:none; padding:12px 25px; display:inline-block; font-size:1.1rem; }
.register-right .btn-login:hover { background-color: rgb(136,10,6); color:#fff; border:2px solid bisque; }
h2 { text-align:center; font-weight:600; margin-bottom:25px; }
h4 { font-weight:600; margin-top:20px; margin-bottom:15px; }
.form-control { background: rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.3); color:rgba(255,255,255,0.85); border-radius:10px; }
.form-control::placeholder { color:rgba(255,255,255,0.85)!important; opacity:1!important; }
.form-control:focus { background:rgba(245,218,167,0.15); box-shadow:none; color:#fff; }
select.form-control { background-color: rgba(121,4,4,0.9); color: #fff; }
select.form-control option { background-color: #790404; color: #fff; }
.btn-login { background-color: bisque; color:#000; border-radius:25px; font-weight:600; transition:all 0.3s ease; }
.btn-login:hover { background-color:transparent; color:#fff; border:2px solid bisque; }
.btn-back { background-color: transparent; color: #fff; border: 2px solid #fff; border-radius: 25px; font-weight:600; padding:10px 25px; margin-right:10px; transition:all 0.3s ease; font-size:1rem; }
.btn-back:hover { background-color:#fff; color:#000; }
.step-indicators { text-align:center; margin-bottom:20px; }
.step-indicators span { display:inline-block; width:20px; height:20px; line-height:20px; border-radius:50%; background: rgba(255,255,255,0.3); margin:0 5px; font-size:12px; color:#000; }
.step-indicators .active { background: bisque; color:#000; }
.step { display:none; }
.step.active { display:block; }
.alert-info { background-color: rgba(255,228,196,0.15); color:#ffdfb3; border:1px solid #ffdfb3; text-align:center; margin-bottom:15px; padding:10px; border-radius:10px; }
</style>
</head>
<body>
<div class="background-wrapper">
    <div class="register-box">
        <div class="register-left">
            <h2>Sign Up</h2>
            <?php if($message): ?><div class="alert alert-info"><?= $message ?></div><?php endif; ?>
            <?php if(!$success): ?>
            <div class="step-indicators">
                <span id="step1-indicator" class="<?= $step==1?'active':'' ?>">1</span>
                <span id="step2-indicator" class="<?= $step==2?'active':'' ?>">2</span>
            </div>
            <form method="POST">
                <?php if($step==1): ?>
                <input type="hidden" name="step" value="1">
                <div id="step1" class="step active">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <input type="text" name="first_name" class="form-control" placeholder="First Name *" required value="<?= $signup_data['first_name'] ?? '' ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <input type="text" name="last_name" class="form-control" placeholder="Last Name *" required value="<?= $signup_data['last_name'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <input type="email" name="email" class="form-control" placeholder="Email *" required value="<?= $signup_data['email'] ?? '' ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <input type="password" name="password" class="form-control" placeholder="Password *" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password *" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="mobile_number" class="form-control" placeholder="Mobile Number *" required value="<?= $signup_data['mobile'] ?? '' ?>">
                    </div>
                    <div class="mb-3">
                        <input type="date" name="birthday" class="form-control" placeholder="Birthday *" required value="<?= $signup_data['birthday'] ?? '' ?>">
                    </div>
                    <div class="mb-3">
                        <select name="country_ID" class="form-control" required>
                            <option value="" disabled <?= empty($signup_data['country_ID']) ? 'selected' : '' ?>>Select Country *</option>
                            <?php mysqli_data_seek($country_result,0); while($row=mysqli_fetch_assoc($country_result)): ?>
                                <option value="<?= $row['country_ID'] ?>" <?= (isset($signup_data['country_ID']) && $signup_data['country_ID']==$row['country_ID']) ? 'selected' : '' ?>><?= $row['country_name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-login w-100">Next</button>
                </div>
                <?php else: ?>
                <input type="hidden" name="step" value="2">
                <div id="step2" class="step active">
                    <h4>Address Information</h4>
                    <div class="mb-3">
                        <input type="text" name="address_line1" class="form-control" placeholder="Address Line 1 *" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="address_line2" class="form-control" placeholder="Address Line 2">
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <input type="text" name="city" class="form-control" placeholder="City *" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <input type="text" name="province" class="form-control" placeholder="Province / State">
                        </div>
                        <div class="col-md-4 mb-3">
                            <input type="text" name="postal_code" class="form-control" placeholder="Postal Code *" required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn-back" onclick="window.location='sign-up_customer.php?step=1'">Back</button>
                        <button type="submit" class="btn btn-login">Sign Up</button>
                    </div>
                </div>
                <?php endif; ?>
            </form>
            <?php endif; ?>
        </div>

        <div class="register-right">
            <h3>Welcome to Aurum Scents</h3>
            <p>Discover timeless fragrances that reflect your elegance.</p>
            <a href="login_customer.php" class="btn btn-login">Login</a>
        </div>
    </div>
</div>
</body>
</html>
