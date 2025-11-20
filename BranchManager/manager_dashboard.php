<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include('../db_connect.php');

// block access if not logged in as an employee
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Branch Manager') {
  header("Location: ../login_staff-admin.php");
  exit();
}

$employee_username = $_SESSION['username'];
$employee_role = $_SESSION['role'];
$employee_id = $_SESSION['user_id'];
$branch_id = $_SESSION['branch_id'];

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


$sqlSku = "SELECT COUNT(*) AS sku_count
           FROM inventory
           WHERE branch_ID = '$branch_id'";
$resSku = mysqli_query($conn, $sqlSku);
$skuRow = mysqli_fetch_assoc($resSku);


$sqlLow = "SELECT COUNT(*) AS low_stock_count
           FROM inventory
           WHERE branch_ID = '$branch_id'
             AND quantity < 30
             AND quantity > 0";
$resLow = mysqli_query($conn, $sqlLow);
$lowRow = mysqli_fetch_assoc($resLow);

$skuCount = $skuRow['sku_count'];
$lowStock = $lowRow['low_stock_count'];


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



$sqlPromo = "SELECT discount_code, discount_percent
             FROM discounts
             WHERE (customer_ID IS NULL OR customer_ID = ?)
               AND CURDATE() BETWEEN date_created AND valid_until
               LIMIT 1";

$stmt = $conn->prepare($sqlPromo);
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$resPromo = $stmt->get_result()->fetch_assoc();
$stmt->close();

function fetchAll($conn, $sql) {
  $res = $conn->query($sql);
  $rows = [];
  if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
  return $rows;
}

// charts
// accords
$popular_accords = fetchAll($conn, "
    SELECT a.accord_name, SUM(od.quantity) AS use_count
    FROM order_details od
    JOIN perfume_volume pv ON pv.perfume_volume_ID = od.perfume_volume_ID
    JOIN perfumes p ON p.perfume_ID = pv.perfume_ID
    JOIN perfume_accords pa ON pa.perfume_ID = p.perfume_ID
    JOIN accords a ON a.accord_ID = pa.accord_ID
    JOIN orders o ON od.order_id = o.order_id
    WHERE o.branch_ID = '$branch_id'
    GROUP BY pa.accord_ID
    ORDER BY use_count DESC
    LIMIT 10;
");

// most popular notes
$popular_notes = fetchAll($conn, "
  SELECT n.note_name, SUM(od.quantity) AS use_count
  FROM order_details od
  JOIN orders o ON o.order_ID = od.order_ID
  JOIN perfume_volume pv ON pv.perfume_volume_ID = od.perfume_volume_ID
  JOIN perfumes p ON p.perfume_ID = pv.perfume_ID
  JOIN perfume_notes pn ON pn.perfume_ID = p.perfume_ID
  JOIN notes n ON n.note_ID = pn.note_ID
  WHERE o.branch_ID = '$branch_id'
  GROUP BY pn.note_ID
  ORDER BY use_count DESC
  LIMIT 10;
");

// most popular
$concentration_count = fetchAll($conn, "
  SELECT p.concentration, SUM(od.quantity) AS total
  FROM order_details od
  JOIN orders o ON o.order_ID = od.order_ID
  JOIN perfume_volume pv ON pv.perfume_volume_ID = od.perfume_volume_ID
  JOIN perfumes p ON p.perfume_ID = pv.perfume_ID
  WHERE o.branch_ID = '$branch_id'
  GROUP BY p.concentration;
");

$data_accords = [
    "labels" => array_column($popular_accords, 'accord_name'),
    "data"   => array_map('intval', array_column($popular_accords, 'use_count'))
];

$data_notes = [
    "labels" => array_column($popular_notes, 'note_name'),
    "data"   => array_map('intval', array_column($popular_notes, 'use_count'))
];

$data_concentration = [
    "labels" => array_column($concentration_count, 'concentration'),
    "data"   => array_map('intval', array_column($concentration_count, 'total'))
];
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Aurum Scents | Branch Manager</title>
  <link rel="stylesheet" href="../BranchEmployee/employee_dashboard.css">
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
    <a href="sales_management.php">Sales Management</a> 
    <a href="staff_management.php">Staff Management</a>
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
    <p><?= htmlspecialchars($currency_sign), number_format($resOrders['total_sales'] ?? 0, 2) ?>
 total</p>
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

<div class="sales-chart">
  <div class="sales-chart-inner">
    <h2>Top Accords</h2>
    <canvas id="chartAccords"></canvas>
  </div>
</div>

<div class="sales-chart">
  <div class="sales-chart-inner">
    <h2>Top Notes</h2>
    <canvas id="chartNotes"></canvas>
  </div>
</div>

<div class="sales-chart">
  <div class="sales-chart-inner">
    <h2>Popular Concentration</h2>
    <canvas id="chartConcentration"></canvas>
  </div>
</div>




</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
function toggleDropdown() {
  const dropdown = document.getElementById("profile-dropdown");
  dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
}

const labels = <?= json_encode($labels) ?>;
const accords = <?= json_encode($data_accords) ?>;
const notes = <?= json_encode($data_notes) ?>;
const conc = <?= json_encode($data_concentration) ?>;

// top selers
new Chart(document.getElementById('topSellersChart'), {
    type: 'bar',
    data: {
        labels: accords.labels,
        datasets: [{
            label: "Units Sold",
            data: accords.data,
        }]
    },
    options: {
        plugins: {
            legend: { display: true }
        }
    }
});

// accords
new Chart(document.getElementById("chartAccords"), {
    type: 'bar',
    data: {
        labels: accords.labels,
        datasets: [{
            label: "Accord Count",
            data: accords.data,
        }]
    },
    options: {
        plugins: {
            legend: { display: true }
        }
    }
});

// notes
new Chart(document.getElementById("chartNotes"), {
    type: 'bar',
    data: {
        labels: notes.labels,
        datasets: [{
            label: "Note Count",
            data: notes.data,
        }]
    },
    options: {
        plugins: {
            legend: { display: true }
        }
    }
});

// concentration
new Chart(document.getElementById("chartConcentration"), {
    type: 'doughnut',
    data: {
        labels: conc.labels,
        datasets: [{
            label: "Perfume Count",
            data: conc.data,
        }]
    },
    options: {
        plugins: {
            legend: { display: true }
        }
    }
});

</script>


</body>
</html>

