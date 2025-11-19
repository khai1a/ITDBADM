<?php
session_start();
require 'db_connect.php';

$customer_ID = $_SESSION['customer_ID'] ?? null;
if (!$customer_ID) {
    header("Location: login_customer.php");
    exit();
}

$errors = [];
$success = "";

//load perfumes
$sqlPerfumes = "SELECT perfume_ID, perfume_name FROM perfumes ORDER BY perfume_name";
$perfumeList = $conn->query($sqlPerfumes);

//form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    $perfume_ID = $_POST['perfume_ID'] ?? null;
    $rating = $_POST['rating'] ?? null;
    $review_comment = trim($_POST['message'] ?? '');

    //validation
    if (!$perfume_ID) $errors[] = "Please select a perfume.";
    if (empty($rating)) {
        $errors[] = "Please provide a rating.";
    } elseif (!is_numeric($rating) || $rating < 1 || $rating > 5 || intval($rating) != $rating) {
        $errors[] = "Rating must be a whole number between 1 and 5.";
    }

    //insert review to db
    if (empty($errors)) {
        $last_sql = "SELECT review_ID FROM reviews ORDER BY review_ID DESC LIMIT 1";
        $last_res = $conn->query($last_sql);
        $num = ($last_res->num_rows > 0) ? intval(substr($last_res->fetch_assoc()['review_ID'], 2)) + 1 : 1;
        $review_ID = 'RV' . str_pad($num, 5, '0', STR_PAD_LEFT);

        $insert = $conn->prepare("
            INSERT INTO reviews (review_ID, perfume_ID, customer_ID, review_comment, rating)
            VALUES (?, ?, ?, ?, ?)
        ");
        $insert->bind_param("ssssi", $review_ID, $perfume_ID, $customer_ID, $review_comment, $rating);

        if ($insert->execute()) {
            $success = "Thank you for your review!";
            $_SESSION['success_message'] = $success;
            header("Location: rating.php");
            exit();
        } else {
            $errors[] = "Error saving review: " . $insert->error;
        }
        $insert->close();
    }
}

//load success
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Rate Us - Aurum Scents</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="css/rating.css">
</head>
<body>

<!-- navbar -->
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
                <a class="nav-link" href="contact_us.php">Contact Us</a>
                <a class="nav-link active" href="rating.php">Rate Us</a>
            </div>

            <!-- icons -->
            <div class="icons-container">
                <!-- user -->
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

                <!-- cart -->
                <a class="nav-link" href="cart.php">
                    <i class="fa fa-shopping-cart"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<section class="rating-section">
    <div class="container d-flex flex-column align-items-center justify-content-center">

        <div class="col-md-6">
            <div class="rating-card">

                <!-- mssgs -->
                <?php if ($errors): ?>
                    <?php foreach ($errors as $error): ?>
                        <div class="message error"><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="message success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <h2 class="text-center mb-4">Rate Your Experience</h2>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Choose Perfume</label>
                        <select name="perfume_ID" class="form-select">
                            <option value="">-- Select Perfume --</option>
                            <?php while ($row = $perfumeList->fetch_assoc()): ?>
                                <option value="<?= $row['perfume_ID'] ?>">
                                    <?= htmlspecialchars($row['perfume_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-4 text-center">
                        <label class="form-label mb-2">Your Rating:</label>
                        <div class="stars">
                            <?php
                            for ($i = 5; $i >= 1; $i--) {
                                $id = 'star' . $i;
                                echo '<input type="radio" name="rating" id="'.$id.'" value="'.$i.'">';
                                echo '<label for="'.$id.'" class="full"></label>';
                            }
                            ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="message" class="form-control" rows="4" placeholder="Leave a message..."></textarea>
                    </div>

                    <button type="submit" name="submit_rating" class="submit-btn w-100">Submit</button>
                </form>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


