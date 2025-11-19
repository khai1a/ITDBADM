<?php
include('../db_connect.php');
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Branch Manager') {
    header("Location: ../login_staff-admin.php");
    exit;
}

$manager_username = $_SESSION['username'];
$branchID = $_SESSION['branch_id'];


$stmt = $conn->prepare("CALL get_staff_by_branch(?)");
$stmt->bind_param("s", $branchID);
$stmt->execute();
$staffResult = $stmt->get_result();
$stmt->close();
$conn->next_result(); // free result for next queries
?>

<!DOCTYPE html>
<html>
<head>
    <title>Aurum Scents | Staff Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Poppins', sans-serif; margin: 0; padding: 0; }
        body { display: flex; width: 100vw; height: 100vh; background: #fffaf3; overflow: hidden; }
        .sidebar { width: 250px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar-top { background: #a3495a; padding: 30px 20px; height: 20%; display: flex; justify-content: center; align-items: center; }
        .sidebar-top h1 { color: white; font-size: 22px; }
        .sidebar-bottom { background: #662422; height: 80%; padding: 20px; }
        .sidebar-bottom a { display: block; color: white; text-decoration: none; margin: 12px 0; padding: 10px 15px; border-radius: 8px; }
        .sidebar-bottom a:hover { background: #842A3B; }
        .main { flex: 1; overflow-y: auto; padding: 40px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .manager-name { font-size: 20px; color: #662422; }
        h2, h3 { color: #662422; margin-bottom: 15px; }
        table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        th { background: #a3495a; color: white; }
        td, th { padding: 10px; border: 1px solid #d9b78e; text-align: center; }
        tr:nth-child(even) { background: #fff3cd; }
        .box { background: white; border: 1px solid #d9b78e; padding: 15px; border-radius: 12px; margin-bottom: 20px; }
    </style>
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
    <a href="sales_management.php">Sales Management</a> 
    <a href="staff_management.php">Staff Management</a>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="manager-name">Welcome, <?= htmlspecialchars($manager_username) ?></div>
        </div>

        <h2>Staff Management — Branch <?= htmlspecialchars($branchID) ?></h2>

        <div class="box">
            <h3>Staff List</h3>
            <table>
                <thead>
                    <tr>
                        <th>Staff ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Branch Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($staff = $staffResult->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($staff['staff_ID']) ?></td>
                            <td><?= htmlspecialchars($staff['username']) ?></td>
                            <td><?= htmlspecialchars($staff['role']) ?></td>
                            <td><?= htmlspecialchars($staff['branch_address']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>


