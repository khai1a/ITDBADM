<?php
session_start();
require 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['customer_ID'])) {
    header("Location: login_customer.php");
    exit();
}

$customer_ID = $_SESSION['customer_ID'];

// Fetch logged-in user's info
$sql = "SELECT first_name, last_name, email, points FROM customers WHERE customer_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $customer_ID);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($user['first_name'] . ' ' . $user['last_name']);
    $email = $user['email'];
    $content = trim($_POST['message']);

    // Validate message
    if (empty($content)) {
        $message = "Message cannot be empty.";
        $message_type = "danger";
    } else {
        // Generate concern_ID: CC0001, CC0002, etc.
        $last_sql = "SELECT concern_ID FROM concerns ORDER BY concern_ID DESC LIMIT 1";
        $last_result = $conn->query($last_sql);
        if ($last_result->num_rows > 0) {
            $last_row = $last_result->fetch_assoc();
            $last_id = intval(substr($last_row['concern_ID'], 2)) + 1; // remove 'CC'
        } else {
            $last_id = 1;
        }
        $concern_ID = 'CC' . str_pad($last_id, 4, '0', STR_PAD_LEFT);

        // Insert into concerns
        $insert_sql = "INSERT INTO concerns (concern_ID, customer_ID, full_name, email, message) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("sssss", $concern_ID, $customer_ID, $full_name, $email, $content);

        if ($stmt->execute()) {
            $message = "Your message has been sent successfully!";
            $message_type = "success";
        } else {
            $message = "Failed to send message. Please try again.";
            $message_type = "danger";
        }
        $stmt->close();
    }
}

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

<!-- Navigation Bar -->
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
                <a class="nav-link" href="cart.php">
                    <i class="fa fa-shopping-cart"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<section class="contact-section">
    <div class="container">
        <h2 class="text-center mb-4">Get in Touch</h2>
        <p class="text-center mb-5 contact-sub">
            We'd love to hear from you! For questions, concerns, or collaboration inquiries, contact us anytime.
        </p>

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

                    <?php if($message): ?>
                        <div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" name="message" rows="4" placeholder="Your message..." required></textarea>
                        </div>
                        <button type="submit" class="btn submit-btn w-100">Send</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
