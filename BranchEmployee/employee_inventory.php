<?php
// ok nov 17
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include('../db_connect.php');

// block access if not logged in as an employee
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Branch Employee') {
    header("Location: ../login_staff-admin.php");
    exit();
}

$employee_username = $_SESSION['username'];
$employee_role     = $_SESSION['role'];
$employee_id       = $_SESSION['user_id'];
$branch_id         = $_SESSION['branch_id'];

// branch info
$branch_address = "";
if ($branch_id) {
  $query = "SELECT address FROM branches WHERE branch_ID = ?";
  if ($stmt = $conn->prepare($query)) {
      $stmt->bind_param("s", $branch_id); 
      $stmt->execute();
      $result_branch = $stmt->get_result();
      if ($result_branch && $result_branch->num_rows > 0) {
          $branch = $result_branch->fetch_assoc();
          $branch_address = $branch['address'];
      }
      $stmt->close();
  }
  
}

// capture filter from GET
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// POST: handles quantity update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inventory_id'], $_POST['quantity'])) {
  $id  = $_POST['inventory_id'];
  $qty = (int)$_POST['quantity'];

  try {
      // start transaction
      $conn->begin_transaction();

      // lock row to prevent concurrent checkout updates
      $stmt = $conn->prepare("SELECT quantity FROM inventory WHERE inventory_ID=? FOR UPDATE");
      $stmt->bind_param("s", $id);
      $stmt->execute();
      $stmt->close();

      if ($qty < 0) {
          throw new Exception("Invalid quantity");
      }

      $update = "UPDATE inventory SET quantity = ? WHERE inventory_ID = ?";
      $stmt = $conn->prepare($update);
      $stmt->bind_param("is", $qty, $id);
      $stmt->execute();
      $stmt->close();

      $conn->commit();
      $update_message = "Quantity updated successfully.";
  } catch (Exception $e) {
      $conn->rollback();
      $update_message = "Failed to update quantity: " . $e->getMessage();
  }
}

// build inventory query with filter
$sql = "SELECT i.inventory_ID, p.perfume_name, pv.volume, i.quantity
        FROM inventory i
        JOIN perfume_volume pv ON i.perfume_volume_ID = pv.perfume_volume_ID
        JOIN perfumes p ON pv.perfume_ID = p.perfume_ID
        WHERE i.branch_ID = ?";

if ($filter === 'in') {
    $sql .= " AND i.quantity >= 30";
} elseif ($filter === 'low') {
    $sql .= " AND i.quantity > 0 AND i.quantity < 30";
} elseif ($filter === 'out') {
    $sql .= " AND i.quantity = 0";
}

$rows = [];
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $branch_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Inventory | Aurum Scents</title>
  <link rel="stylesheet" href="employee_inventory.css">
  <link rel="stylesheet" href="employee_dashboard.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
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
    <h2>Inventory</h2>
    <div class="profile-container">
      <div class="profile-icon" onclick="toggleDropdown()">
        <img src="profileIcon.png" alt="Profile Icon">
      </div>
      <div id="profile-dropdown" class="dropdown">
        <p><strong>Username:</strong> <?= htmlspecialchars($employee_username) ?></p>
        <p><strong>Role:</strong> <?= htmlspecialchars($employee_role) ?></p>
        <p><strong>Branch:</strong> <?= htmlspecialchars($branch_address) ?></p>
        <a href="logout.php" class="logout-btn">Logout</a>
      </div>
    </div>
  </div>

  <div class="inventory-controls">
    <form method="get" action="employee_inventory.php" style="display:inline;">
      <select name="filter" class="stock-filter" onchange="this.form.submit()">
        <option value="all" <?= $filter==='all'?'selected':'' ?>>All</option>
        <option value="in" <?= $filter==='in'?'selected':'' ?>>In Stock</option>
        <option value="low" <?= $filter==='low'?'selected':'' ?>>Low Stock</option>
        <option value="out" <?= $filter==='out'?'selected':'' ?>>Out of Stock</option>
      </select>
    </form>
    <input type="text" placeholder="Search perfume..." class="search-bar">
  </div>

  <?php if (!empty($update_message)): ?>
    <div class="alert"><?= htmlspecialchars($update_message) ?></div>
  <?php endif; ?>

  <table class="inventory-table">
    <thead>
      <tr>
        <th>Perfume</th>
        <th>Volume</th>
        <th>Quantity</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($rows)): ?>
        <?php foreach ($rows as $row): ?>
          <?php
            $statusClass = "ok";
            $statusText  = "In Stock";
            if ($row['quantity'] == 0) {
                $statusClass = "critical";
                $statusText  = "Out of Stock";
            } elseif ($row['quantity'] < 30) {
                $statusClass = "low";
                $statusText  = "Low Stock";
            }
          ?>
          <tr>
            <td><?= htmlspecialchars($row['perfume_name']) ?></td>
            <td><?= htmlspecialchars($row['volume']) ?>ml</td>
            <td>
              <form method="post" style="display:inline;">
                <input type="hidden" name="inventory_id" value="<?= $row['inventory_ID'] ?>">
                <input type="number" name="quantity" value="<?= $row['quantity'] ?>" min="0" class="qty-input">
                <button type="submit">Save</button>
              </form>
            </td>
            <td class="<?= $statusClass ?>"><?= $statusText ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="4">No inventory found for this branch.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const searchBar = document.querySelector(".search-bar");
  const rows = document.querySelectorAll(".inventory-table tbody tr");

  searchBar.addEventListener("input", () => {
    const term = searchBar.value.toLowerCase();
    rows.forEach(row => {
      const perfumeName = row.querySelector("td:first-child").textContent.toLowerCase();
      row.style.display = perfumeName.includes(term) ? "" : "none";
    });
  });
});

function toggleDropdown() {
  const dropdown = document.getElementById("profile-dropdown");
  dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
}
</script>
</body>
</html>



