<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Branch Employee') {
    header("Location: login_staff-admin.php");
    exit();
}

include_once '../db_connect.php';

// get branch ID from session
$branch_id = mysqli_real_escape_string($conn, $_SESSION['branch_id']);

// capture filter from GET 
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// query inventory for this branch
$sql = "SELECT i.inventory_ID, p.perfume_name, pv.volume, i.quantity
        FROM inventory i
        JOIN perfume_volume pv ON i.perfume_volume_ID = pv.perfume_volume_ID
        JOIN perfumes p ON pv.perfume_ID = p.perfume_ID
        WHERE i.branch_ID = '$branch_id'";

// apply filter
if ($filter === 'in') {
  $sql .= " AND i.quantity >= 50";
} elseif ($filter === 'low') {
  $sql .= " AND i.quantity > 0 AND i.quantity < 50";
} elseif ($filter === 'out') {
  $sql .= " AND i.quantity = 0";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inventory_id'], $_POST['quantity'])) {
  $id = mysqli_real_escape_string($conn, $_POST['inventory_id']);
  $qty = (int)$_POST['quantity'];

  $update = "UPDATE inventory SET quantity = $qty WHERE inventory_ID = '$id'";
  mysqli_query($conn, $update);
}

$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Inventory | Aurum Scents</title>
  <link rel="stylesheet" href="employee_inventory.css">
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
    <h2>Inventory</h2>
    <div class="profile-icon">
      <img src="profileIcon.png" alt="Profile Icon">
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
      <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
          <?php
            $statusClass = "ok";
            $statusText = "In Stock";
            if ($row['quantity'] == 0) {
                $statusClass = "critical";
                $statusText = "Out of Stock";
            } elseif ($row['quantity'] < 50) {
                $statusClass = "low";
                $statusText = "Low Stock";
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
            <td></td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="5">No inventory found for this branch.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

</div>
</body>
</html>

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
</script>

