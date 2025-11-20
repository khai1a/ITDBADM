<?php
include('db_connect.php');
session_start();

$message = "";
$success = false;

// Get countries for the dropdown
$country_result = mysqli_query($conn, "SELECT country_ID, country_name, currency FROM countries ORDER BY country_name ASC");

$signup_data = $_SESSION['signup'] ?? [];

// check email
function validate_strict_email($email) {
    return preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,10}$/', $email);
}

// check password
function validate_password($password) {
    return preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W_]).{8,}$/', $password);
}

// validate mobile numbers
function validate_phone($phone) {
    return preg_match('/^\+\d{6,15}$/', $phone);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

// save personal data
    if (isset($_POST['step']) && $_POST['step'] == 1) {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            $message = "Passwords do not match!";
        } elseif (!validate_password($password)) {
            $message = "Password must be at least 8 characters, include uppercase, lowercase, number, and symbol.";
        } elseif (!validate_strict_email($_POST['email'])) {
            $message = "Invalid email format! Must contain a domain and a valid ending (e.g. .com, .ph, .net)";
        } elseif (!validate_phone($_POST['mobile_number'])) {
            $message = "Invalid phone number! Must start with + and include 6–15 digits.";
        } else {
            $_SESSION['signup'] = [
                'first_name' => mysqli_real_escape_string($conn, $_POST['first_name']),
                'last_name' => mysqli_real_escape_string($conn, $_POST['last_name']),
                'email' => mysqli_real_escape_string($conn, $_POST['email']),
                'password' => $password,
                'confirm_password' => $confirm_password,
                'mobile_number' => mysqli_real_escape_string($conn, $_POST['mobile_number']),
                'birthday' => $_POST['birthday'],
                'country_ID' => $_POST['country_ID']
            ];
            header("Location: sign-up_customer.php?step=2");
            exit();
        }
    }

    if (isset($_POST['step']) && $_POST['step'] == 2) {

        $data = $_SESSION['signup'];
        $first_name = $data['first_name'];
        $last_name = $data['last_name'];
        $email = $data['email'];
        $password = $data['password'];
        $mobile = $data['mobile_number'];
        $birthday = $data['birthday'];
        $country_ID = $data['country_ID'];

        $address_line1 = mysqli_real_escape_string($conn, $_POST['address_line1']);
        $address_line2 = mysqli_real_escape_string($conn, $_POST['address_line2']);
        $city = mysqli_real_escape_string($conn, $_POST['city']);
        $province = mysqli_real_escape_string($conn, $_POST['province']);
        $postal_code = mysqli_real_escape_string($conn, $_POST['postal_code']);

        // Check if number or email is repeated
        $check = mysqli_query($conn, "SELECT * FROM customers WHERE email='$email' OR mobile_number='$mobile'");
        if (mysqli_num_rows($check) > 0) {
            $message = "Email or mobile number already exists!";
        } else {

            // customer id generation
            $res = mysqli_query($conn, "SELECT customer_ID
                            FROM customers
                            ORDER BY CAST(SUBSTRING(customer_ID,3) AS UNSIGNED) DESC
                            LIMIT 1");

            if ($row = mysqli_fetch_assoc($res)) {
                $num = (int)substr($row['customer_ID'], 2) + 1;
                $customer_ID = 'CU' . str_pad($num, 4, '0', STR_PAD_LEFT);
            } else {
                $customer_ID = 'CU0001';
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // insert to customers
            $insert_customer = "INSERT INTO customers 
                (customer_ID, first_name, last_name, email, password, mobile_number, country_ID, birthday) 
                VALUES 
                ('$customer_ID','$first_name','$last_name','$email','$hashed_password','$mobile','$country_ID','$birthday')";

            if (mysqli_query($conn, $insert_customer)) {

                // address id generation
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
<link rel="stylesheet" href="sign-up_customer.css">
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
        <form method="POST" id="signupForm">
        <?php if($step==1): ?>
        <input type="hidden" name="step" value="1">
        <div id="step1" class="step active">
        <div class="row">
            <div class="col-md-6 mb-3">
                <input type="text" name="first_name" class="form-control" placeholder="First Name *" required value="<?= $signup_data['first_name'] ?? '' ?>">
                <span class="error-msg" id="first_name_error"></span>
            </div>
            <div class="col-md-6 mb-3">
                <input type="text" name="last_name" class="form-control" placeholder="Last Name *" required value="<?= $signup_data['last_name'] ?? '' ?>">
                <span class="error-msg" id="last_name_error"></span>
            </div>
        </div>
        <div class="mb-3">
            <input type="email" name="email" class="form-control" placeholder="Email *" required value="<?= $signup_data['email'] ?? '' ?>">
            <span class="error-msg" id="email_error"></span>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <input type="password" name="password" class="form-control" placeholder="Password *" required>
                <span class="error-msg" id="password_error"></span>
            </div>
            <div class="col-md-6 mb-3">
                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password *" required>
                <span class="error-msg" id="confirm_password_error"></span>
            </div>
        </div>
        <div class="mb-3">
            <input type="text" name="mobile_number" class="form-control" placeholder="Mobile Number *" required value="<?= $signup_data['mobile_number'] ?? '' ?>">
            <span class="error-msg" id="mobile_number_error"></span>
        </div>
        <div class="mb-3">
            <input type="date" name="birthday" class="form-control" placeholder="Birthday *" required value="<?= $signup_data['birthday'] ?? '' ?>">
            <span class="error-msg" id="birthday_error"></span>
        </div>
        <div class="mb-3">
            <select name="country_ID" class="form-control" required>
                <option value="" disabled <?= empty($signup_data['country_ID']) ? 'selected' : '' ?>>Select Country *</option>
                <?php mysqli_data_seek($country_result,0); while($row=mysqli_fetch_assoc($country_result)): ?>
                    <option value="<?= $row['country_ID'] ?>" <?= (isset($signup_data['country_ID']) && $signup_data['country_ID']==$row['country_ID']) ? 'selected' : '' ?>><?= $row['country_name'] ?></option>
                <?php endwhile; ?>
            </select>
            <span class="error-msg" id="country_ID_error"></span>
        </div>
        <button type="submit" class="btn btn-login w-100" id="nextBtn" disabled>Next</button>
    </div>
    <?php else: ?>
    <!-- Step 2 part of signup -->
    <input type="hidden" name="step" value="2">
    <div id="step2" class="step active">
        <h4>Address Information</h4>
        <div class="mb-3">
            <input type="text" name="address_line1" class="form-control" placeholder="Address Line 1 *" required>
            <span class="error-msg" id="address_line1_error"></span>
        </div>
        <div class="mb-3">
            <input type="text" name="address_line2" class="form-control" placeholder="Address Line 2">
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <input type="text" name="city" class="form-control" placeholder="City *" required>
                <span class="error-msg" id="city_error"></span>
            </div>
            <div class="col-md-4 mb-3">
                <input type="text" name="province" class="form-control" placeholder="Province / State">
            </div>
            <div class="col-md-4 mb-3">
                <input type="text" name="postal_code" class="form-control" placeholder="Postal Code *" required>
                <span class="error-msg" id="postal_code_error"></span>
            </div>
        </div>
        <div class="d-flex justify-content-between">
            <button type="button" class="btn-back" onclick="window.location='sign-up_customer.php?step=1'">Back</button>
            <button type="submit" class="btn btn-login" id="signupBtn" disabled>Sign Up</button>
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

<script>
// validation function
function validateEmail(email) {
    return /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,10}$/.test(email);
}
function validatePassword(p) {
    return /^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W_]).{8,}$/.test(p);
}
function validatePhone(p) {
    // start the number with a plus (+) sign
    return /^\+\d{6,15}$/.test(p);
}

let touchedStep1 = {};
let touchedStep2 = {};

// error messages
function getErrorMessage(f, value) {
    if(value.trim() === '') return 'Please fill out this field';
    if(f === 'email' && !validateEmail(value)) return 'Invalid email format (example: name@example.com)';
    if(f === 'password' && !validatePassword(value)) return 'Password must have 8+ characters, uppercase, lowercase, number, symbol';
    if(f === 'confirm_password' && value !== document.querySelector('input[name="password"]').value) return 'Passwords do not match';
    if(f === 'mobile_number' && !validatePhone(value)) return 'Invalid number! Must start with + and have 6–15 digits';
    return '';
}

// Step 1 
function checkStep1() {
    let fields = ['first_name','last_name','email','password','confirm_password','mobile_number','birthday','country_ID'];
    let btn = document.getElementById('nextBtn');
    let allValid = true;

    fields.forEach(f => {
        let el = document.querySelector(`[name="${f}"]`);
        let msg = getErrorMessage(f, el.value);
        let errorEl = document.getElementById(f+'_error');

        
        if(touchedStep1[f]) {
            if(msg) {
                el.classList.add('invalid'); el.classList.remove('valid');
                errorEl.textContent = msg;
                errorEl.style.display = 'block';
            } else {
                el.classList.add('valid'); el.classList.remove('invalid');
                errorEl.style.display = 'none';
            }
        }

        if(msg) allValid = false;
    });

    btn.disabled = !allValid;
}

// Step 2
function checkStep2() {
    let fields = ['address_line1','city','postal_code'];
    let btn = document.getElementById('signupBtn');
    let allValid = true;

    fields.forEach(f => {
        let el = document.querySelector(`[name="${f}"]`);
        let msg = el.value.trim() === '' ? 'Please fill out this field' : '';
        let errorEl = document.getElementById(f+'_error');

        if(touchedStep2[f]) {
            if(msg) {
                el.classList.add('invalid'); el.classList.remove('valid');
                errorEl.textContent = msg;
                errorEl.style.display = 'block';
            } else {
                el.classList.add('valid'); el.classList.remove('invalid');
                errorEl.style.display = 'none';
            }
        }

        if(msg) allValid = false;
    });

    btn.disabled = !allValid;
}


document.querySelectorAll('#step1 input, #step1 select').forEach(el => {
    el.addEventListener('input', e => {
        touchedStep1[e.target.name] = true;

     
        if(e.target.name === 'mobile_number') {
            if(e.target.value && !e.target.value.startsWith('+')) {
                e.target.value = '+' + e.target.value.replace(/^\+*/,'');
            }
        }

        checkStep1();
    });

    el.addEventListener('blur', e => {
        touchedStep1[e.target.name] = true;
        checkStep1();
    });
});

document.querySelectorAll('#step2 input').forEach(el => {
    el.addEventListener('input', e => {
        touchedStep2[e.target.name] = true;
        checkStep2();
    });
    el.addEventListener('blur', e => {
        touchedStep2[e.target.name] = true;
        checkStep2();
    });
});
</script>
</body>
</html>
