<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['customer_ID'])) {
    header("Location: login_customer.php");
    exit;
}

$customerID = $_SESSION['customer_ID'];

// fetch info from db
$sql = "
SELECT o.order_ID, o.order_date, o.order_status, o.currency, od.quantity, od.unit_price, pv.perfume_ID, p.perfume_name, c.currency_sign
FROM orders o
JOIN order_details od ON o.order_ID = od.order_ID
JOIN perfume_volume pv ON od.perfume_volume_ID = pv.perfume_volume_ID
JOIN perfumes p ON pv.perfume_ID = p.perfume_ID
JOIN currencies c ON o.currency = c.currency
WHERE o.customer_ID = ?
ORDER BY o.order_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $customerID);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while($row = $result->fetch_assoc()){
    $oid = $row['order_ID'];
    if(!isset($orders[$oid])){
        $orders[$oid] = [
            'order_date' => $row['order_date'],
            'order_status' => $row['order_status'],
            'currency_sign' => $row['currency_sign'],
            'items' => []
        ];
    }
    $orders[$oid]['items'][] = [
        'name' => $row['perfume_name'],
        'quantity' => $row['quantity'],
        'unit_price' => $row['unit_price']
    ];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Orders - Aurum Scents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="orders.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <a class="nav-link active" href="customer_home.php">Home</a>
                <a class="nav-link" href="about_us.php">About Us</a>
                <a class="nav-link" href="buy_here.php">Buy Here</a>
                <a class="nav-link" href="contact_us.php">Contact Us</a>
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

<section class="orders-section py-5">
    <div class="container">
        <h2 class="text-center mb-4" style="color:#eacb99; font-weight:700;">My Orders</h2>
        <div class="table-responsive">
            <table class="table orders-table text-center">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Order Status</th>
                        <th>Order Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($orders)): ?>
                        <tr>
                            <td colspan="6" class="text-center" style="padding: 30px; font-weight: 600; color: #5a0f1a;">
                                You have no orders yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($orders as $orderID => $order): ?>
                        <tr>
                            <td>#<?= htmlspecialchars($orderID) ?></td>
                            <td>
                                <?php foreach($order['items'] as $item): ?>
                                    <?= htmlspecialchars($item['name']) ?><br>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <?php foreach($order['items'] as $item): ?>
                                    <?= $item['quantity'] ?><br>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <?php foreach($order['items'] as $item): ?>
                                    <?= htmlspecialchars($order['currency_sign']) . number_format($item['unit_price'] * $item['quantity'], 2) ?><br>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <span class="badge <?= strtolower($order['order_status']) ?>">
                                    <?= htmlspecialchars($order['order_status']) ?>
                                </span>
                            </td>
                            <td><?= date("M d, Y", strtotime($order['order_date'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


