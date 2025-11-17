<?php
session_start();
require 'db_connect.php'; // Your database connection file

// Check if user is logged in
if (!isset($_SESSION['customer_ID'])) {
    header("Location: login_customer.php");
    exit();
}

$customer_ID = $_SESSION['customer_ID'];

// Fetch user data with JOIN to countries and address table
$sql = "SELECT 
            c.first_name, 
            c.last_name, 
            ctr.country_name AS country,
            c.email, 
            c.password, 
            c.mobile_number, 
            a.address_line1, 
            a.address_line2,
            a.city,
            a.province,
            a.postal_code,
            c.birthday
        FROM customers c
        LEFT JOIN countries ctr ON c.country_ID = ctr.country_ID
        LEFT JOIN customer_addresses a ON c.customer_ID = a.customer_ID
        WHERE c.customer_ID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $customer_ID); // VARCHAR ID, use "s"
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();
$conn->close();

// No user found
if (!$user) {
    die("User not found.");
}

// Build full address string
$full_address = $user['address_line1'];
if (!empty($user['address_line2'])) $full_address .= ', ' . $user['address_line2'];
$full_address .= ', ' . $user['city'];
if (!empty($user['province'])) $full_address .= ', ' . $user['province'];
$full_address .= ' ' . $user['postal_code'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/profile.css">
</head>
<body>
<div class="container mt-5">
    <div class="profile-card">
        <h2 class="mb-4 text-center">User Profile</h2>

        <table class="table table-bordered">
            <tr><th>First Name</th><td><?= htmlspecialchars($user['first_name']) ?></td></tr>
            <tr><th>Last Name</th><td><?= htmlspecialchars($user['last_name']) ?></td></tr>
            <tr><th>Country</th><td><?= htmlspecialchars($user['country'] ?? 'N/A') ?></td></tr>
            <tr><th>Email</th><td><?= htmlspecialchars($user['email']) ?></td></tr>
            <tr><th>Password</th><td><?= str_repeat('*', strlen($user['password'])) ?></td></tr>
            <tr><th>Mobile Number</th><td><?= htmlspecialchars($user['mobile_number']) ?></td></tr>
            <tr><th>Address</th><td><?= htmlspecialchars($full_address) ?></td></tr>
            <tr><th>Birthday</th><td><?= htmlspecialchars($user['birthday']) ?></td></tr>
        </table>

        <div class="d-flex justify-content-between mt-3">
            <a href="customer_home.php" class="btn btn-back">Back</a>
            <a href="edit_profile.php" class="btn btn-update">Edit Profile</a>
        </div>
    </div>
</div>
</body>
</html>
