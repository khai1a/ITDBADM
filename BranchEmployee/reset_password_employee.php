<?php
include('../db_connect.php');
session_start();

// allow only if branch employee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Branch Employee') {
    header("Location: ../login_staff-admin.php");
    exit;
}

$employee_id = $_SESSION['user_id'];
$employee_username = $_SESSION['username'];

// branch address
$branch_address = "";
$stmtBranch = $conn->prepare("SELECT address FROM branches WHERE branch_ID = ?");
$stmtBranch->bind_param("s", $_SESSION['branch_id']);
$stmtBranch->execute();
$resultBranch = $stmtBranch->get_result();
if ($resultBranch && $row = $resultBranch->fetch_assoc()) {
    $branch_address = $row['address'];
}
$stmtBranch->close();

$message = "";
$message_type = "";

// reset password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($new_password) < 8) {
        $message = "Password must be at least 8 characters long.";
        $message_type = "danger";
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "danger";
    } else {
        $hashedPassword = hash('sha256', $new_password);

        // reset password procedure from sql
        $stmt = $conn->prepare("CALL reset_manager_password(?, ?, @message, @success)");
        $stmt->bind_param("ss", $employee_id, $hashedPassword);
        $stmt->execute();
        $stmt->close();

        // output
        $result = $conn->query("SELECT @message AS message, @success AS success");
        if ($result) {
            $row = $result->fetch_assoc();
            $message = $row['message'];
            $message_type = $row['success'] == 1 ? "success" : "danger";
        } else {
            $message = "An error occurred while resetting password.";
            $message_type = "danger";
        }
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

    /* Side bar */
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

    /* Main info */
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

    .employee-name {
        font-size: 20px;
        color: #662422;
    }

    /* profile */
    .profile-container {
        position: relative;
        display: inline-block;
    }

    .profile-icon img {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        cursor: pointer;
    }

    .dropdown {
        display: none;
        position: absolute;
        right: 0;
        background: white;
        min-width: 220px;
        border: 1px solid #c7a786;
        border-radius: 8px;
        padding: 10px;
        z-index: 100;
    }

    .dropdown p {
        margin: 5px 0;
    }

    .logout-btn {
        display: block;
        background: #842A3B;
        color: white;
        text-align: center;
        padding: 6px;
        border-radius: 6px;
        margin-top: 8px;
        text-decoration: none;
    }

    .logout-btn:hover {
        background: #662422;
    }

    /* form and alerts */
    h2,
    h3 {
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
            <a href="employee_dashboard.php">Dashboard</a>
            <a href="employee_inventory.php">Inventory</a>
            <a href="employee_orders.php">Create Order</a>
            <a href="employee_returns.php">Returns</a>
            <a href="employee_view_orders.php" class="active">View Orders</a>
            <a href="reset_password_employee.php">Reset Password</a>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="employee-name">Welcome, <?= htmlspecialchars($employee_username) ?></div>
            <div class="profile-container">
                <div class="profile-icon" onclick="toggleDropdown()">
                    <img src="profileIcon.png" alt="Profile">
                </div>
                <div id="profile-dropdown" class="dropdown">
                    <p><strong>Username:</strong> <?= htmlspecialchars($employee_username) ?></p>
                    <p><strong>Role:</strong> <?= htmlspecialchars($_SESSION['role']) ?></p>
                    <p><strong>Branch:</strong> <?= htmlspecialchars($branch_address) ?></p>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </div>

        <h2>Reset Your Password</h2>

        <div class="box">
            <h3>Change Password</h3>
            <?php if($message): ?>
                <div class="alert <?= $message_type ?>"><?= $message ?></div>
            <?php endif; ?>
            <form method="POST" onsubmit="return validatePassword();">
                <input type="hidden" name="reset_password" value="1">
                <label>New Password:</label>
                <input type="password" name="new_password" id="new_password" required placeholder="Enter new password">
                <label>Confirm Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" required placeholder="Confirm new password">
                <button type="submit">Update Password</button>
            </form>
        </div>
    </div>

    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById("profile-dropdown");
            dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
        }

        window.onclick = function(event) {
            if (!event.target.matches('.profile-icon img')) {
                const dropdown = document.getElementById("profile-dropdown");
                if(dropdown.style.display === "block") dropdown.style.display = "none";
            }
        }

        function validatePassword() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword.length < 8) {
                alert("Password must be at least 8 characters long.");
                return false;
            }
            if (newPassword !== confirmPassword) {
                alert("Passwords do not match.");
                return false;
            }

            return confirm("Are you sure you want to change your password?");
        }
    </script>
</body>
</html>
