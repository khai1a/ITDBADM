<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include('../db_connect.php');

// block access if not logged in as an employee
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Branch Employee') {
  header("Location: ../login_staff-admin.php");
  exit();
}

// use username from session
$employee_username = $_SESSION['username'];
$employee_role = $_SESSION['role'];
$employee_id = $_SESSION['user_id'];
$branch_id = $_SESSION['branch_id'];

// get branch info
$branch_address = "";
$currency_sign = ""; 

if ($branch_id) {
  $query = "SELECT b.address, cur.currency_sign 
            FROM branches b
            JOIN countries c ON b.country_ID = c.country_ID
            JOIN currencies cur ON c.currency = cur.currency
            WHERE b.branch_ID = '$branch_id'";
  $result = mysqli_query($conn, $query);

  if ($result && mysqli_num_rows($result) > 0) {
      $branch = mysqli_fetch_assoc($result);
      $branch_address = $branch['address'];
      $currency_sign = $branch['currency_sign'];
  }
}

// get monthly top 10 sellers

$stmt = $conn->prepare("CALL getMonthlyTopSellersPerBranch(?, ?)");
$limit = 10;
$stmt->bind_param("si", $branch_id, $limit);
$stmt->execute();
$result = $stmt->get_result();

$labels = [];
$data = [];
while ($row = $result->fetch_assoc()) {
    $labels[] = $row['perfume_name'];
    $data[]   = $row['total_qty'];
}

$stmt->free_result();
$conn->next_result(); 
$stmt->close();

// count all inventory for this branch
$sqlSku = "SELECT COUNT(*) AS sku_count
           FROM inventory
           WHERE branch_ID = '$branch_id'";
$resSku = mysqli_query($conn, $sqlSku);
$skuRow = mysqli_fetch_assoc($resSku);

// count inventory items with low stock (low stock threshold is <30)
$sqlLow = "SELECT COUNT(*) AS low_stock_count
           FROM inventory
           WHERE branch_ID = '$branch_id'
             AND quantity < 30
             AND quantity > 0";
$resLow = mysqli_query($conn, $sqlLow);
$lowRow = mysqli_fetch_assoc($resLow);

$skuCount = $skuRow['sku_count'];
$lowStock = $lowRow['low_stock_count'];

// orders today: total amount and total num
$sqlOrders = "SELECT COUNT(*) AS order_count,
                     SUM(order_total) AS total_sales
              FROM orders
              WHERE branch_ID = ?
                AND order_type = 'Walk-in'
                AND DATE(order_date) = CURDATE()";

$stmt = $conn->prepare($sqlOrders);
$stmt->bind_param("s", $branch_id);
$stmt->execute();
$resOrders = $stmt->get_result()->fetch_assoc();
$stmt->close();

// promo usage
$sqlPromoUsage = "
    SELECT d.discount_code, d.discount_percent, COUNT(*) AS usage_count
    FROM orders o
    JOIN discounts d ON o.discount_code = d.discount_code
    WHERE o.branch_ID = ?
      AND o.order_type = 'Walk-in'
      AND o.order_status = 'Completed'
      AND o.discount_code IS NOT NULL
    GROUP BY d.discount_code
    ORDER BY usage_count DESC
    LIMIT 1
";

$stmt = $conn->prepare($sqlPromoUsage);
$stmt->bind_param("s", $branch_id);
$stmt->execute();
$resPromoUsage = $stmt->get_result()->fetch_assoc();
$stmt->close();



// ongoing promo
$sqlPromo = "SELECT discount_code, discount_percent
             FROM discounts
             WHERE (customer_ID IS NULL OR customer_ID = ?)
               AND CURDATE() BETWEEN date_created AND valid_until";

$stmt = $conn->prepare($sqlPromo);
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$resPromo = $stmt->get_result()->fetch_assoc();
$stmt->close();

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
            <a href="employee_orders.php">Create Order</a>
            <a href="employee_returns.php">Returns</a>
            <a href="employee_view_orders.php" class="active">View Orders</a>
            <a href="reset_password_employee.php">Reset Password</a>
  </div>
</div>

<div class="main">
  <div class="topbar">
  <div class="employee-name"> 
  Welcome, <?= htmlspecialchars($employee_username) ?>
</div>

<div class="profile-container">
  <div class="profile-icon" onclick="toggleDropdown()">
    <img src="profileIcon.png" alt="Profile">
  </div>
  <div id="profile-dropdown" class="dropdown">
    <p><strong>Username:</strong> <?= htmlspecialchars($_SESSION['username']) ?></p>
    <p><strong>Role:</strong> <?= htmlspecialchars($_SESSION['role']) ?></p>
    <p><strong>Branch:</strong> <?= htmlspecialchars($branch_address) ?></p>
    <a href="logout.php" class="logout-btn">Logout</a>
  </div>
</div>
  </div> 

  <div class="widgets">
    <div class="card">
    <h3>Inventory Status</h3>
    <p><?= $skuCount ?> SKUs in stock</p>
    <p><?= $lowStock ?> low-stock items</p>
  </div>


  <div class="card">
    <h3>Walk-In Orders</h3>
    <p><?= $resOrders['order_count'] ?> orders today</p>
    <p><?= htmlspecialchars($currency_sign), number_format($resOrders['total_sales'] ?? 0, 2) ?> total</p>
  </div>

  <div class="card">
  <h3>Most Used Promo</h3>
<?php if ($resPromoUsage): ?>
  <p>Code: <?= htmlspecialchars($resPromoUsage['discount_code']) ?></p>
  <p><?= $resPromoUsage['discount_percent'] * 100 ?>% off</p>
  <p><?= $resPromoUsage['usage_count'] ?> uses</p>
<?php else: ?>
  <p>No promo usage</p>
<?php endif; ?>

  </div>

  <div class="card">
    <h3>Ongoing Promo</h3>
    <?php if ($resPromo): ?>
      <p>Code: <?= htmlspecialchars($resPromo['discount_code']) ?></p>
      <p><?= $resPromo['discount_percent'] * 100 ?>% off</p>
    <?php else: ?>
      <p>No active promotions</p>
    <?php endif; ?>
  </div>
</div>


<div class="sales-chart">
  <div class="sales-chart-inner">
    <h2>Monthly Top Sellers</h2>
    <canvas id="topSellersChart"></canvas>
  </div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
function toggleDropdown() {
  const dropdown = document.getElementById("profile-dropdown");
  dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
}

//bar chart
const labels = <?= json_encode($labels) ?>;
const data = {
  labels: labels,
  datasets: [
    {
      label: 'Units Sold',
      data: <?= json_encode($data) ?>,
      borderColor: 'rgba(219, 172, 52, 1)', 
      backgroundColor: 'rgba(219, 172, 52, 0.6)', 
      borderWidth: 2,
      borderRadius: 8, 
      borderSkipped: false,
    }
  ]
};

const config = {
  type: 'bar',
  data: data,
  options: {
    responsive: true,
    plugins: {
      legend: { position: 'top' },
      title: {
        display: true,
        font: { size: 18, weight: 'bold' },
        color: '#6b4e16'
      }
    },
    scales: {
      y: { beginAtZero: true }
    }
  }
};

new Chart(document.getElementById('topSellersChart'), config);
</script>


</body>
</html>
