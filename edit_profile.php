<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['customer_ID'])) {
    header("Location: login_customer.php");
    exit();
}

$customer_ID = $_SESSION['customer_ID'];
$messages = [];

// customer info
$sql = "SELECT c.first_name, c.last_name, c.country_ID, c.email, c.password AS hashed_password, c.mobile_number, c.birthday,
        ca.address_line1, ca.address_line2, ca.city, ca.province, ca.postal_code, ca.address_ID
        FROM customers c
        LEFT JOIN customer_addresses ca ON c.customer_ID = ca.customer_ID
        WHERE c.customer_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $customer_ID);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// countries
$countries = [];
$country_result = $conn->query("SELECT country_ID, country_name FROM countries ORDER BY country_name ASC");
while($row = $country_result->fetch_assoc()) {
    $countries[] = $row;
}

// POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first = trim($_POST['first_name']);
    $last = trim($_POST['last_name']);
    $country_ID = $_POST['country_ID'];
    $password = $_POST['password'];
    $mobile = trim($_POST['mobile_number']);
    $birthday = $_POST['birthday'];
    $address_line1 = trim($_POST['address_line1']);
    $address_line2 = trim($_POST['address_line2']);
    $city = trim($_POST['city']);
    $province = trim($_POST['province']);
    $postal_code = trim($_POST['postal_code']);

    $errors = [];

    // add + sa phone number
    if ($mobile && !str_starts_with($mobile, '+')) {
        $mobile = '+' . preg_replace('/\D/', '', $mobile);
    }

    // error handling
    if (empty($first)) $errors[] = "First name cannot be empty.";
    if (empty($last)) $errors[] = "Last name cannot be empty.";
    if (!empty($password) && !preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) 
        $errors[] = "Password must be at least 8 chars, include uppercase, lowercase, digit, and symbol.";
    if (!empty($mobile) && !preg_match('/^\+\d{6,15}$/', $mobile)) 
        $errors[] = "Mobile number must start with + and contain 6-15 digits.";
    if (empty($address_line1)) $errors[] = "Address Line 1 cannot be empty.";
    if (empty($city)) $errors[] = "City cannot be empty.";
    if (empty($postal_code)) $errors[] = "Postal code cannot be empty.";
    if (!in_array($country_ID, array_column($countries, 'country_ID'))) 
        $errors[] = "Invalid country selected.";

    // update if no error
    if (empty($errors)) {

        // hash pw back to db if changed
        $hashed_password = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : $user['hashed_password'];

        // update customer info to db
        $stmt = $conn->prepare("UPDATE customers SET first_name=?, last_name=?, country_ID=?, password=?, mobile_number=? WHERE customer_ID=?");
        $stmt->bind_param("ssssss", $first, $last, $country_ID, $hashed_password, $mobile, $customer_ID);
        $stmt->execute();
        $stmt->close();

        // update address
        if ($user['address_ID']) {
            $stmt = $conn->prepare("UPDATE customer_addresses SET address_line1=?, address_line2=?, city=?, province=?, postal_code=?, country_ID=? WHERE address_ID=?");
            $stmt->bind_param("sssssss", $address_line1, $address_line2, $city, $province, $postal_code, $country_ID, $user['address_ID']);
            $stmt->execute();
            $stmt->close();
        } else {
            $res = $conn->query("SELECT address_ID FROM customer_addresses ORDER BY CAST(SUBSTRING(address_ID,3) AS UNSIGNED) DESC LIMIT 1");
            if($row = $res->fetch_assoc()) {
                $num = (int)substr($row['address_ID'],2)+1;
                $address_ID = 'AD' . str_pad($num,4,'0',STR_PAD_LEFT);
            } else {
                $address_ID = 'AD0001';
            }
            $stmt = $conn->prepare("INSERT INTO customer_addresses (address_ID, customer_ID, address_line1, address_line2, city, province, postal_code, country_ID) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssssss", $address_ID, $customer_ID, $address_line1, $address_line2, $city, $province, $postal_code, $country_ID);
            $stmt->execute();
            $stmt->close();
        }

        header("Location: profile.php?updated=1");
        exit();
    } else {
        $messages = $errors;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Profile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/edit_profile.css">
</head>
<body>
<div class="container">
    <div class="profile-card">
        <h2>Edit Profile</h2>

        <?php if(!empty($messages)): ?>
            <?php foreach($messages as $msg): ?>
                <div class="alert alert-warning text-center"><?= htmlspecialchars($msg) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <form id="editProfileForm" class="profile-form" method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <input type="text" name="first_name" class="form-control" placeholder="First Name *" value="<?= htmlspecialchars($user['first_name']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <input type="text" name="last_name" class="form-control" placeholder="Last Name *" value="<?= htmlspecialchars($user['last_name']) ?>">
                </div>
            </div>

            <div class="mb-3">
                <input type="email" class="form-control" placeholder="Email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <input type="password" name="password" class="form-control" placeholder="New Password">
                </div>
                <div class="col-md-6 mb-3">
                    <input type="text" name="mobile_number" class="form-control" placeholder="Mobile Number *" value="<?= htmlspecialchars($user['mobile_number']) ?>">
                </div>
            </div>

            <div class="mb-3">
                <select name="country_ID" class="form-control">
                    <option value="" disabled <?= empty($user['country_ID'])?'selected':'' ?>>Select Country *</option>
                    <?php foreach($countries as $c): ?>
                        <option value="<?= $c['country_ID'] ?>" <?= $c['country_ID']==$user['country_ID']?'selected':'' ?>><?= $c['country_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <input type="text" name="address_line1" class="form-control" placeholder="Address Line 1 *" value="<?= htmlspecialchars($user['address_line1']) ?>">
            </div>
            <div class="mb-3">
                <input type="text" name="address_line2" class="form-control" placeholder="Address Line 2" value="<?= htmlspecialchars($user['address_line2']) ?>">
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <input type="text" name="city" class="form-control" placeholder="City *" value="<?= htmlspecialchars($user['city']) ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <input type="text" name="province" class="form-control" placeholder="Province" value="<?= htmlspecialchars($user['province']) ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <input type="text" name="postal_code" class="form-control" placeholder="Postal Code *" value="<?= htmlspecialchars($user['postal_code']) ?>">
                </div>
            </div>

            <div class="mb-3">
                <input type="date" name="birthday" class="form-control" placeholder="Birthday *" value="<?= htmlspecialchars($user['birthday']) ?>" readonly>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="profile.php" class="btn btn-back">Back to Profile</a>
                <button type="submit" class="btn btn-update" disabled>Update Profile</button>
            </div>
        </form>
    </div>
</div>

<script>
// button only seen if there are changes
const form = document.getElementById('editProfileForm');
const updateBtn = form.querySelector('.btn-update');

const originalValues = {};
Array.from(form.elements).forEach(el => {
    if(el.name) originalValues[el.name] = el.value;
});

function checkChanges() {
    let changed = false;
    Array.from(form.elements).forEach(el => {
        if(el.name) {
            if(el.name === 'password' && el.value.trim() !== '') {
                changed = true;
            } else if(el.value !== originalValues[el.name] && el.name !== 'password' && el.name !== 'birthday') {
                changed = true;
            }
        }
    });
    updateBtn.disabled = !changed;
}

Array.from(form.elements).forEach(el => {
    if(el.name) {
        el.addEventListener('input', checkChanges);
        el.addEventListener('change', checkChanges);
    }
});

checkChanges();
</script>
</body>
</html>

