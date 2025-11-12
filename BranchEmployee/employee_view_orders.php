<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include('../db_connect.php');

// block access if not logged in as an employee
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Branch Employee') {
  header("Location: ../login_staff-admin.php");
  exit();
}

$employee_username = $_SESSION['username'] ?? "Employee";
$branch_id = mysqli_real_escape_string($conn, $_SESSION['branch_id']);

// fetch orders for this branch 
$sql = "SELECT o.order_ID,
               COALESCE(CONCAT(c.first_name, ' ', c.last_name), 'Anonymous') AS customer,
               o.order_total,
               o.order_status
        FROM orders o
        LEFT JOIN customers c ON o.customer_ID = c.customer_ID
        WHERE o.branch_ID = '$branch_id' AND o.order_type = 'Walk-in'
        ORDER BY o.order_date DESC";

$res = mysqli_query($conn, $sql);

$orders = [];
if ($res) {
  while ($row = mysqli_fetch_assoc($res)) {
    // fetch items for each order
    $orderID = $row['order_ID'];
    $itemsRes = mysqli_query($conn, "SELECT p.perfume_name, pv.volume, od.quantity
                                     FROM order_details od
                                     JOIN perfume_volume pv ON od.perfume_volume_ID = pv.perfume_volume_ID
                                     JOIN perfumes p ON pv.perfume_ID = p.perfume_ID
                                     WHERE od.order_ID = '$orderID'");
    $items = [];
    while ($itemsRes && $itemRow = mysqli_fetch_assoc($itemsRes)) {
      $items[] = $itemRow['perfume_name'] . " (" . $itemRow['volume'] . "ml) x" . $itemRow['quantity'];
    }
    $row['items'] = $items;
    $orders[] = $row;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Walk-In Orders | Aurum Scents</title>
  <link rel="stylesheet" href="employee_dashboard.css">
  <link rel="stylesheet" href="employee_view_orders.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>

<div class="sidebar">
  <div class="sidebar-top"><h1>Aurum Scents</h1></div>
  <div class="sidebar-bottom">
    <a href="employee_dashboard.php">Dashboard</a>
    <a href="employee_inventory.php">Inventory</a>
    <a href="employee_orders.php">Walk-In Orders</a>
    <a href="employee_returns.php">Returns</a>
    <a href="employee_view_orders.php" class="active">View Orders</a>
  </div>
</div>

<div class="main">
  <div class="topbar">
    <h2>View Walk-In Orders</h2>
    <div class="profile-icon"><img src="profileIcon.png" alt="Profile Icon"></div>
  </div>

  <div class="orders-container">
    <h3>Walk-In Orders</h3>
    <table class="orders-table">
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Customer</th>
          <th>Total</th>
          <th>Status</th>
          <th>View</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $order): ?>
          <tr>
            <td><?= htmlspecialchars($order['order_ID']) ?></td>
            <td><?= htmlspecialchars($order['customer']) ?></td>
            <td>₱<?= number_format($order['order_total'], 2) ?></td>
            <td class="status-<?= strtolower($order['order_status']) ?>">
              <?= htmlspecialchars($order['order_status']) ?>
            </td>
            <td>
              <button class="view-btn"
                onclick='viewDetails(<?= json_encode($order) ?>)'>View</button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="details-modal" id="detailsModal">
  <h4>Order Details</h4>
  <div class="details-section"><strong>Order ID:</strong> <span id="detailOrderID"></span></div>
  <div class="details-section"><strong>Customer:</strong> <span id="detailCustomer"></span></div>
  <div class="details-section"><strong>Total:</strong> <span id="detailTotal"></span></div>
  <div class="details-section"><strong>Status:</strong> <span id="detailStatus"></span></div>
  <div class="details-section"><strong>Items:</strong></div>
  <ul class="items-list" id="detailItems"></ul>
  <button class="close-btn" onclick="closeModal()">Close</button>
</div>

<script>
function viewDetails(order) {
  document.getElementById("detailOrderID").textContent = order.order_ID;
  document.getElementById("detailCustomer").textContent = order.customer;
  document.getElementById("detailTotal").textContent = "₱" + parseFloat(order.order_total).toLocaleString();
  document.getElementById("detailStatus").textContent = order.status;

  const itemsList = document.getElementById("detailItems");
  itemsList.innerHTML = "";
  order.items.forEach(i => {
    const li = document.createElement("li");
    li.textContent = i;
    itemsList.appendChild(li);
  });

  document.getElementById("detailsModal").style.display = "block";
}

function closeModal() {
  document.getElementById("detailsModal").style.display = "none";
}
</script>

</body>
</html>
