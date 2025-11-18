<?php
include('db_connect.php');
session_start();

// --------------------
// AUTH CHECK
// --------------------
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Branch Manager') { // pwede ichange yung role
    header("Location: login.php");
    exit;
}

$manager_username = $_SESSION['username'];
$branchID = $_SESSION['branch_id'];

$message = "";
$message_type = "";

// --------------------
// HANDLE PASSWORD RESET
// --------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);

    // Check staff exists in this branch
    $stmtCheck = $conn->prepare("SELECT * FROM staff WHERE username = ? AND branch_ID = ?");
    $stmtCheck->bind_param("ss", $username, $branchID);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows > 0) {
        // Update password (hashed recommended)
        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
        $stmtUpdate = $conn->prepare("UPDATE staff SET password = ? WHERE username = ? AND branch_ID = ?");
        $stmtUpdate->bind_param("sss", $hashedPassword, $username, $branchID);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        $message = "Password successfully updated for user '$username'.";
        $message_type = "success";
    } else {
        $message = "User not found in your branch.";
        $message_type = "danger";
    }
    $stmtCheck->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Aurum Scents | Reset Staff Password</title>
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
        .box { background: white; border: 1px solid #d9b78e; padding: 25px; border-radius: 12px; margin-bottom: 20px; width: 100%; }
        .alert.success { color: green; margin-bottom: 10px; }
        .alert.danger { color: red; margin-bottom: 10px; }
        input { border: 1px solid #c7a786; padding: 8px; border-radius: 6px; margin-bottom: 12px; width: 100%; }
        button { background: #842A3B; border: none; color: white; padding: 10px; border-radius: 6px; cursor: pointer; width: 100%; }
        button:hover { background: #662422; }
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
            <h3>Reset Employee Password</h3>
            <?php if($message): ?>
                <div class="alert <?= $message_type ?>"><?= $message ?></div>
            <?php endif; ?>
            <form method="POST" onsubmit="return confirmReset();">
                <input type="hidden" name="reset_password" value="1">
                <label>Username:</label>
                <input type="text" name="username" required placeholder="Staff Username">
                <label>New Password:</label>
                <input type="password" name="new_password" required placeholder="New Password">
                <button type="submit">Reset Password</button>
            </form>
        </div>
    </div>

    <script>
        function confirmReset() {
            const username = document.querySelector('input[name="username"]').value;
            return confirm(`Are you sure you want to reset the password for "${username}"?`);
        }
    </script>
</body>
</html>
