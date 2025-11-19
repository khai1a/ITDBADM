<?php
session_start();
require 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['customer_ID'])) {
    header("Location: login_customer.php");
    exit();
}

$customer_ID = $_SESSION['customer_ID'];

// Fetch customer info
$sql = "SELECT first_name, last_name, points FROM customers WHERE customer_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $customer_ID);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch points transaction history
$trans_sql = "SELECT order_ID, points_change, transaction_type, transaction_date 
              FROM points_transactions 
              WHERE customer_ID = ? 
              ORDER BY transaction_date DESC";
$stmt = $conn->prepare($trans_sql);
$stmt->bind_param("s", $customer_ID);
$stmt->execute();
$transactions = $stmt->get_result();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Points - Aurum Scents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/points.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="points-container">
        <div class="points-header text-center">
            <h1>Hello, <span class="user-name"><?= htmlspecialchars($user['first_name']) ?></span>!</h1>
            <p>You currently have <span class="points-count"><?= htmlspecialchars($user['points']) ?></span> points.</p>
        </div>

        <div class="points-card">
            <h3 class="text-center mb-4">Points Transaction History</h3>

            <?php if ($transactions->num_rows > 0): ?>
                <table class="table points-table table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Points</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $transactions->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['order_ID'] ?: '-') ?></td>
                                <td class="<?= $row['transaction_type'] === 'Redeemed' ? 'redeemed' : 'earned' ?>">
                                    <?= $row['transaction_type'] === 'Redeemed' ? '-' : '+' ?>
                                    <?= htmlspecialchars($row['points_change']) ?>
                                </td>
                                <td><?= date("M d, Y", strtotime($row['transaction_date'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center no-transactions">You haven't earned or redeemed any points yet.</p>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="customer_home.php" class="btn btn-back">Back to Home</a>
            </div>
        </div>
    </div>
</body>
</html>
