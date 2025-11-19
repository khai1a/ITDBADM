<?php
session_start();
require 'db_connect.php';

// check if logged in
if (!isset($_SESSION['customer_ID'])) {
    header("Location: login_customer.php");
    exit();
}

$customer_ID = $_SESSION['customer_ID'];

// user info
$sql = "SELECT first_name, last_name, email, points FROM customers WHERE customer_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $customer_ID);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// initialize messages
$message = "";
$message_type = "";
$return_message = "";
$return_type = "";

// contact form
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['contact_submit'])) {
    $full_name = trim($user['first_name'] . ' ' . $user['last_name']);
    $email = $user['email'];
    $content = trim($_POST['message']);

    if (empty($content)) {
        $_SESSION['message'] = ["text" => "Message cannot be empty.", "type" => "danger"];
    } else {
        // concern ID
        $last_sql = "SELECT concern_ID FROM concerns ORDER BY concern_ID DESC LIMIT 1";
        $last_result = $conn->query($last_sql);
        $last_id = ($last_result->num_rows > 0) ? intval(substr($last_result->fetch_assoc()['concern_ID'], 2)) + 1 : 1;
        $concern_ID = 'CC' . str_pad($last_id, 4, '0', STR_PAD_LEFT);

        $insert_sql = "INSERT INTO concerns (concern_ID, customer_ID, full_name, email, message) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("sssss", $concern_ID, $customer_ID, $full_name, $email, $content);
        if ($stmt->execute()) {
            $_SESSION['message'] = ["text" => "Your message has been sent successfully!", "type" => "success"];
        } else {
            $_SESSION['message'] = ["text" => "Failed to send message. Please try again.", "type" => "danger"];
        }
        $stmt->close();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// return form
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['return_submit'])) {
    $order_detail_ID = $_POST['return_order_detail'] ?? '';
    $reason = trim($_POST['return_reason'] ?? '');

    if (!empty($order_detail_ID) && !empty($reason)) {
        // check if return exists
        $check_sql = "SELECT return_ID FROM returns WHERE order_detail_ID = ? LIMIT 1";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $order_detail_ID);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $_SESSION['return_message'] = ["text" => "A return request has already been submitted for this order item.", "type" => "danger"];
        } else {
            // return ID
            $last_sql = "SELECT return_ID FROM returns ORDER BY return_ID DESC LIMIT 1";
            $last_result = $conn->query($last_sql);
            $last_id = ($last_result->num_rows > 0) ? intval(substr($last_result->fetch_assoc()['return_ID'], 1)) + 1 : 1;
            $return_ID = 'R' . str_pad($last_id, 5, '0', STR_PAD_LEFT);

            $insert_sql = "INSERT INTO returns (return_ID, order_detail_ID, customer_ID, reason) VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($insert_sql);
            $stmt_insert->bind_param("ssss", $return_ID, $order_detail_ID, $customer_ID, $reason);
            if ($stmt_insert->execute()) {
                $_SESSION['return_message'] = ["text" => "Your return request has been submitted successfully!", "type" => "success"];
            } else {
                $_SESSION['return_message'] = ["text" => "Failed to submit return request. Please try again.", "type" => "danger"];
            }
            $stmt_insert->close();
        }
        $stmt->close();
    } else {
        $_SESSION['return_message'] = ["text" => "Please select an order item and provide a reason.", "type" => "danger"];
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// show messages and clear them
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message']['text'];
    $message_type = $_SESSION['message']['type'];
    unset($_SESSION['message']);
}

if (isset($_SESSION['return_message'])) {
    $return_message = $_SESSION['return_message']['text'];
    $return_type = $_SESSION['return_message']['type'];
    unset($_SESSION['return_message']);
}

// orders dropdown
$order_options = [];
$order_sql = "SELECT od.order_detail_ID, o.order_ID, p.perfume_name, od.quantity
              FROM order_details od
              JOIN orders o ON od.order_ID = o.order_ID
              JOIN perfume_volume pv ON od.perfume_volume_ID = pv.perfume_volume_ID
              JOIN perfumes p ON pv.perfume_ID = p.perfume_ID
              WHERE o.customer_ID = ?";
$stmt = $conn->prepare($order_sql);
$stmt->bind_param("s", $customer_ID);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $order_options[] = $row;
}
$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Contact Us - Aurum Scents</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/contact_us.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="customer_home.php">Aurum Scents</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="nav-links-container mx-auto">
                <a class="nav-link" href="customer_home.php">Home</a>
                <a class="nav-link" href="about_us.php">About Us</a>
                <a class="nav-link" href="buy_here.php">Buy Here</a>
                <a class="nav-link active" href="contact_us.php">Contact Us</a>
                <a class="nav-link" href="rating.php">Rate Us</a>
            </div>
            <div class="icons-container">
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fa fa-user"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                        <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
                        <li><a class="dropdown-item" href="points.php">My Points</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="login_customer.php">Log Out</a></li>
                    </ul>
                </div>
                <a class="nav-link" href="cart.php"><i class="fa fa-shopping-cart"></i></a>
            </div>
        </div>
    </div>
</nav>

<!-- Contact Section -->
<section class="contact-section">
<div class="container">
    <h2 class="text-center mb-4">Get in Touch</h2>
    <p class="text-center mb-5 contact-sub">We'd love to hear from you! For questions, concerns, or collaboration inquiries, contact us anytime.</p>
    <div class="row justify-content-center align-items-center">
        <div class="col-md-5 text-center mb-4 mb-md-0">
            <h2 class="mb-4">Connect With Us!</h2>
            <div class="socials">
                <p><i class="fa-brands fa-facebook"></i> Aurum Scents Official</p>
                <p><i class="fa-brands fa-instagram"></i> @aurumscents.ph</p>
                <p><i class="fa-brands fa-tiktok"></i> @aurumscents</p>
                <p><i class="fa-solid fa-envelope"></i> aurumscents@gmail.com</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="contact-card p-4">
                <h4 class="mb-3 text-center">Send Us a Message</h4>
                <?php if($message): ?><div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="contact_submit" value="1">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="message" rows="4" placeholder="Your message..." required></textarea>
                    </div>
                    <button type="submit" class="btn submit-btn">Send</button>
                </form>
            </div>
        </div>
    </div>
</div>
</section>

<!-- Returns Section -->
<section class="returns-section">
    <div class="container d-flex justify-content-center">
        <div class="contact-card p-4 col-md-6">
            <h4 class="text-center mb-3">File a Return Request</h4>
            <?php if($return_message): ?>
                <div class="alert alert-<?= $return_type ?>"><?= htmlspecialchars($return_message) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="return_submit" value="1">

                <div class="mb-3">
                    <label for="returnOrder" class="form-label">Select Order Item</label>
                    <select id="returnOrder" name="return_order_detail" required class="form-control scroll-dropdown">
                        <option value="">-- Select an Order --</option>
                        <?php foreach($order_options as $o): ?>
                            <option value="<?= htmlspecialchars($o['order_detail_ID']) ?>">
                                Order <?= htmlspecialchars($o['order_ID']) ?> - <?= htmlspecialchars($o['perfume_name']) ?> (Qty: <?= htmlspecialchars($o['quantity']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="returnReason" class="form-label">Reason for Return</label>
                    <textarea id="returnReason" name="return_reason" rows="3" placeholder="Reason for return..." class="form-control" required></textarea>
                </div>

                <button type="submit" id="submitReturn" class="btn submit-btn">Submit Return Request</button>
            </form>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
