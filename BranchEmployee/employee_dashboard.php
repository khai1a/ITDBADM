<?php

session_start();
include('../db_connect.php');

// block access if not logged in as an employee
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Branch Employee') {
  header("Location: login_staff-admin.php");
  exit();
}

// use username from session
$employee_username = $_SESSION['username'];

// get branch info
$branch_name = "";
$branch_address = "";

if (isset($_SESSION['branch_id'])) {
  $branch_id = mysqli_real_escape_string($conn, $_SESSION['branch_id']);
  $query = "SELECT b.address 
            FROM branches b 
            WHERE b.branch_ID = '$branch_id'";
  $result = mysqli_query($conn, $query);

  if ($result && mysqli_num_rows($result) > 0) {
      $branch = mysqli_fetch_assoc($result);
      $branch_address = $branch['address'];
  }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Aurum Scents | Branch Employee</title>
  <link rel="stylesheet" href="employee_dashboard.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>

<div class="sidebar">
  <div class="sidebar-top">
    <h1>Aurum Scents</h1>
  </div>
  <div class="sidebar-bottom">
    <a href="employee_dashboard.php">Dashboard</a>
    <a href="employee_inventory.php">Inventory</a>
    <a href="employee_orders.php">Walk-In Orders</a>
    <a href="employee_returns.php">Returns</a>
    <a href="employee_view_orders.php">View Orders</a>
  </div>
</div>

<div class="main">
  <div class="topbar">
     <div class="employee-name"> 
      Welcome, <?= htmlspecialchars($employee_username) ?>
    </div>
    <div class="profile-icon">
      <img src="profileIcon.png">
    </div>
  </div>
  <div class="branch-info">
  Branch: <?= htmlspecialchars($branch_address) ?>
  </div>  

  <div class="widgets">
    <div class="card">
      <h3>Inventory Status</h3>
      <p>42 SKUs in stock</p>
      <p>4 low-stock items</p>
      <button onclick="window.location.href='employee_inventory.php'">View Inventory</button>

    </div>
    <div class="card">
      <h3>Walk-In Orders</h3>
      <p>9 orders today</p>
      <p>₱13,250 total</p>
      <button onclick="window.location.href='employee_orders.php'">Create New Order</button>
    </div>

    <div class="card">
      <h3>Returns</h3>
      <p>2 pending requests</p>
      <button>Manage Returns</button>
    </div>

    <div class="card">
      <h3>Payments</h3>
      <p>View and manage walk-in payments</p>
      <button onclick="window.location.href='employee_payments.php'">Manage Payments</button>
    </div>
</div>

<div class="sales-summary">
    <h2> Sales Summary (Walk-In)</h2>
    <p><strong>Daily Total:</strong> ₱<?= number_format($sales['total_sales'] ?? 0, 2) ?></p>
    <p><strong>Orders Today:</strong> <?= $sales['order_count'] ?? 0 ?></p>
    <p><strong>Top Seller:</strong> <?= $top['perfume_name'] ?? 'N/A' ?></p>
  </div>

</div>



</body>
</html>
