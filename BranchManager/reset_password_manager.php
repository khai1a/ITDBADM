<?php
include('../db_connect.php');
session_start();

// check if branch manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Branch Manager') {
    header("Location: ../login_staff-admin.php");
    exit;
}

$manager_id = $_SESSION['user_id'];
$manager_username = $_SESSION['username'];

$message = "";
$message_type = "";

// password reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "danger";
    } else {
        $hashedPassword = hash('sha256', $new_password);
        $stmtUpdate = $conn->prepare("UPDATE staff SET password = ? WHERE staff_ID = ?");
        $stmtUpdate->bind_param("ss", $hashedPassword, $manager_id);

        if ($stmtUpdate->execute()) {
            $message = "Your password has been successfully updated.";
            $message_type = "success";
        } else {
            $message = "Error updating password. Please try again.";
            $message_type = "danger";
        }
        $stmtUpdate->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Aurum Scents | Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
    * {
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
        margin: 0;
        padding: 0;
    }

    body {
        display: flex;
        width: 100vw;
        height: 100vh;
        background: #fffaf3;
        overflow: hidden;
    }

    .sidebar {
        width: 250px;
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
    }

    .sidebar-top {
        background: #a3495a;
        padding: 30px 20px;
        height: 20%;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .sidebar-top h1 {
        color: white;
        font-size: 22px;
    }

    .sidebar-bottom {
        background: #662422;
        height: 80%;
        padding: 20px;
    }

    .sidebar-bottom a {
        display: block;
        color: white;
        text-decoration: none;
        margin: 12px 0;
        padding: 10px 15px;
        border-radius: 8px;
    }

    .sidebar-bottom a:hover {
        background: #842A3B;
    }

    .main {
        flex: 1;
        overflow-y: auto;
        padding: 40px;
    }

    .topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .manager-name {
        font-size: 20px;
        color: #662422;
    }

    h2, h3 {
        color: #662422;
        margin-bottom: 15px;
    }

    .box {
        background: white;
        border: 1px solid #d9b78e;
        padding: 25px;
        border-radius: 12px;
        margin-bottom: 20px;
        width: 100%;
        max-width: 500px;
    }

    .alert.success {
        color: green;
        margin-bottom: 10px;
    }

    .alert.danger {
        color: red;
        margin-bottom: 10px;
    }

    input {
        border: 1px solid #c7a786;
        padding: 8px;
        border-radius: 6px;
        margin-bottom: 12px;
        width: 100%;
    }

    button {
        background: #842A3B;
        border: none;
        color: white;
        padding: 10px;
        border-radius: 6px;
        cursor: pointer;
        width: 100%;
    }

    button:hover {
        background: #662422;
    }
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
            <a href="reset_password_manager.php">Reset Password</a>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="manager-name">Welcome, <?= htmlspecialchars($manager_username) ?></div>
        </div>

        <h2>Reset Your Password</h2>

        <div class="box">
            <h3>Change Password</h3>
            <?php if($message): ?>
                <div class="alert <?= $message_type ?>"><?= $message ?></div>
            <?php endif; ?>
            <form method="POST" onsubmit="return confirmReset();">
                <input type="hidden" name="reset_password" value="1">
                <label>New Password:</label>
                <input type="password" name="new_password" required placeholder="Enter new password">
                <label>Confirm Password:</label>
                <input type="password" name="confirm_password" required placeholder="Confirm new password">
                <button type="submit">Update Password</button>
            </form>
        </div>
    </div>

    <script>
        function confirmReset() {
            return confirm("Are you sure you want to change your password?");
        }
    </script>
</body>
</html>
