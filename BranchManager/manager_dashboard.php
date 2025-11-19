<?php
include('../db_connect.php');
session_start();

// block access if not logged in as a branch manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Branch Manager') {
  header("Location: ../login_staff-admin.php");
  exit();
}

// use username from session
$manager_username = $_SESSION['username'];
$manager_role = $_SESSION['role'];
$manager_id = $_SESSION['user_id'];
$branch_id = $_SESSION['branch_id'];

// get branch info
$branch_address = "";

if ($branch_id) {
  $query = "SELECT address 
            FROM branches  
            WHERE branch_ID = '$branch_id'";
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
  <title>Aurum Scents | Branch Manager</title>
  <link rel="stylesheet" href="manager_dashboard.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>

<div class="sidebar">
  <div class="sidebar-top">
    <h1>Aurum Scents</h1>
  </div>
  <div class="sidebar-bottom">
        <a href="manager_dashboard.php">Dashboard</a>
    <a href="manager_inventory.php">Inventory</a>
    <a href="manager_orders.php">Walk-In Orders</a>
    <a href="manager_returns.php">Returns</a>
    <a href="manager_view_orders.php">View Orders</a>
    <a href="sales_management.php">Sales Management</a> <!-- change to file name -->
    <a href="staff_management.php">Staff Management</a>
  </div>
</div>

<div class="main">
  <div class="topbar">
  <div class="manager-name"> 
  Welcome, <?= htmlspecialchars($manager_username) ?>
</div>

<div class="profile-container">
  <div class="profile-icon" onclick="toggleDropdown()">
    <img src="../BranchEmployee/profileIcon.png" alt="Profile">
  </div>
  <div id="profile-dropdown" class="dropdown">
    <p><strong>Username:</strong> <?= htmlspecialchars($_SESSION['username']) ?></p>
    <p><strong>Role:</strong> <?= htmlspecialchars($_SESSION['role']) ?></p>
    <p><strong>Branch:</strong> <?= htmlspecialchars($branch_address) ?></p>
    <a href="logout.php" class="logout-btn">Logout</a>
  </div>
</div>

<script>
function toggleDropdown() {
  const dropdown = document.getElementById("profile-dropdown");
  dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
}
</script>
</body>
</html>
