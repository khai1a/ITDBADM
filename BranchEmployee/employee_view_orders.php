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
$branch_id = $_SESSION['branch_id']; 

// branch info
$branch_address = "";
$branch_currency = "₱";
if ($branch_id) {
    $query = "SELECT b.address, cur.currency_sign
              FROM branches b
              JOIN countries c ON b.country_ID = c.country_ID
              JOIN currencies cur ON c.currency = cur.currency
              WHERE b.branch_ID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $branch_id);
    $stmt->execute();
    $result_branch = $stmt->get_result();
    if ($result_branch && $result_branch->num_rows > 0) {
        $branch = $result_branch->fetch_assoc();
        $branch_address  = $branch['address'];
        $branch_currency = $branch['currency_sign'];
    }
    $stmt->close();
}

// get orders via stored procedure
$orders = [];
$stmt = $conn->prepare("CALL getWalkinOrders(?)");
$stmt->bind_param("s", $branch_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $orderID = $row['order_ID'];

    if (!isset($orders[$orderID])) {
        $orders[$orderID] = [
            'order_ID'     => $row['order_ID'],
            'customer'     => $row['customer'],   
            'order_total'  => $row['order_total'],
            'order_status' => $row['order_status'],
            'order_date'   => $row['order_date'],
            'items'        => []
        ];
    }

    $orders[$orderID]['items'][] =
        $row['perfume_name'] . " (" . $row['volume'] . "ml) x" . $row['quantity'];
}

$stmt->free_result();
$conn->next_result();
$stmt->close();

$orders = array_values($orders);


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
    <div class="profile-container">
      <div class="profile-icon" onclick="toggleDropdown()">
        <img src="profileIcon.png" alt="Profile Icon">
      </div>
      <div id="profile-dropdown" class="dropdown">
        <p><strong>Username:</strong> <?= htmlspecialchars($employee_username) ?></p>
        <p><strong>Role:</strong> <?= htmlspecialchars($_SESSION['role']) ?></p>
        <p><strong>Branch:</strong> <?= htmlspecialchars($branch_address) ?></p>
        <a href="logout.php" class="logout-btn">Logout</a>
      </div>
    </div>
  </div>

  <div class="orders-container">
    <table class="orders-table">
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Customer</th>
          <th>Total</th>
          <th>Status</th>
          <th>Date</th>
          <th>View</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($orders)): ?>
          <?php foreach ($orders as $order): ?>
            <tr>
              <td><?= htmlspecialchars($order['order_ID']) ?></td>
              <td><?= htmlspecialchars($order['customer']) ?></td>
              <td><?= htmlspecialchars($branch_currency) ?><?= number_format($order['order_total'], 2) ?></td>
              <td class="status-<?= strtolower($order['order_status']) ?>">
                <?= htmlspecialchars($order['order_status']) ?>
              </td>
              <td><?= htmlspecialchars(date("M d, Y H:i", strtotime($order['order_date']))) ?></td> 
              <td>
                <button class="view-btn"
                  onclick='viewDetails(<?= json_encode($order) ?>)'>View</button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="5">No orders found for this branch.</td></tr>
        <?php endif; ?>
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
  <div class="details-section"><strong>Date:</strong> <span id="detailDate"></span></div>
  <div class="details-section"><strong>Items:</strong></div>
  <ul class="items-list" id="detailItems"></ul>
  <button class="close-btn" onclick="closeModal()">Close</button>
</div>

<script>
function viewDetails(order) {
  document.getElementById("detailOrderID").textContent = order.order_ID;
  document.getElementById("detailCustomer").textContent = order.customer;
  document.getElementById("detailTotal").textContent = "₱" + parseFloat(order.order_total).toLocaleString();
  document.getElementById("detailStatus").textContent = order.order_status;
  document.getElementById("detailDate").textContent = new Date(order.order_date).toLocaleString(); 

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

function toggleDropdown() {
  const dropdown = document.getElementById("profile-dropdown");
  dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
}
</script>

</body>
</html>

