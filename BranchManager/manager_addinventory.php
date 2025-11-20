<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include('../db_connect.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Branch Manager') {
    header("Location: ../login_staff-admin.php");
    exit();
}

$employee_username = $_SESSION['username'];
$employee_role     = $_SESSION['role'];
$employee_id       = $_SESSION['user_id'];
$branch_id         = $_SESSION['branch_id'];

$perfumes = $conn->query("SELECT perfume_ID, perfume_name 
                                FROM perfumes 
                                ORDER BY perfume_name ASC");

$message = "";
$status = "";

$selected_perfume = $_POST['perfume_ID'] ?? "";

$volumes = [];
if ($selected_perfume !== "") {
    $stmt = $conn->prepare("CALL getVolumesByPerfumeID(?)");
    $stmt->bind_param("s", $selected_perfume);
    $stmt->execute();
    $resultVol = $stmt->get_result();
    while ($v = $resultVol->fetch_assoc()) {
        $volumes[] = $v;
    }
    $stmt->close();
    $conn->next_result(); 
}

if (isset($_POST['add_inventory'])) {

    $pv  = $_POST['perfume_volume_ID'];
    $qty = $_POST['quantity'];

    $conn->query("SET @invID = ''");
    $conn->query("CALL getLastInventoryID(@invID)");
    $resultID = $conn->query("SELECT @invID");
    $inventoryID = $resultID->fetch_assoc()['@invID'];

    try {
        $check = $conn->prepare("SELECT inventory_ID 
                                        FROM inventory 
                                        WHERE branch_ID = ? 
                                        AND perfume_volume_ID = ?");
        $check->bind_param("ss", $branch_id, $pv);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            throw new Exception("This perfume with this volume is already in your inventory.");
        }
        $check->close();

        $stmt2 = $conn->prepare("
            INSERT INTO inventory (inventory_ID, branch_ID, perfume_volume_ID, quantity)
            VALUES (?, ?, ?, ?)
        ");
        $stmt2->bind_param("sssi", $inventoryID, $branch_id, $pv, $qty);
        $stmt2->execute();
        $stmt2->close();

        $message = "Inventory added successfully!";
        $status = "success";

    } catch (Exception $e) {
        $message = $e->getMessage();
        $status = "danger";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Inventory | Branch Manager</title>
    <link rel="stylesheet" href="manager_dashboard.css">
    <link rel="stylesheet" href="manager_inventory.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>

<div class="sidebar">
    <div class="sidebar-top"><h1>Aurum Scents</h1></div>
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
        <h2>Add Inventory</h2>
        <div class="profile-container">
            <div class="profile-icon" onclick="toggleDropdown()">
                <img src="../BranchEmployee/profileIcon.png">
            </div>
            <div id="profile-dropdown" class="dropdown">
                <p><strong>User:</strong> <?= $employee_username ?></p>
                <p><strong>Role:</strong> <?= $employee_role ?></p>
                <p><strong>Branch:</strong> <?= $branch_address ?></p>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="page-container">

        <?php if (!empty($message)): ?>
            <div class="alert"><?= $message ?></div>
        <?php endif; ?>

        <div class="card">

            <form method="POST">

                <div class="form-row">
                    <label>Select Perfume</label>
                    <select name="perfume_ID" onchange="this.form.submit()" required>
                        <option value="" disabled <?= $selected_perfume==""?'selected':'' ?>>Choose perfume...</option>

                        <?php foreach ($perfumes as $p): ?>
                            <option value="<?= $p['perfume_ID'] ?>" 
                                <?= $selected_perfume == $p['perfume_ID'] ? 'selected' : '' ?>>
                                <?= $p['perfume_name'] ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <?php if ($selected_perfume !== ""): ?>
                    <div class="form-row">
                        <label>Select Volume</label>
                        <select name="perfume_volume_ID" required>
                            <option value="" disabled selected>Choose volume...</option>

                            <?php foreach ($volumes as $v): ?>
                                <option value="<?= $v['perfume_volume_ID'] ?>">
                                    <?= $v['volume'] ?> ml
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="form-row">
                        <label>Initial Quantity</label>
                        <input type="number" name="quantity" min="0" required>
                    </div>

                    <button class="btn-primary" type="submit" name="add_inventory">Add Inventory</button>
                    <a class="back-link" href="manager_inventory.php">Cancel</a>

                <?php endif; ?>

            </form>

        </div>
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

