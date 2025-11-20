<?php
include('../db_connect.php');
require'check_session.php';
date_default_timezone_set('Asia/Manila');

$manager_username = $_SESSION['username'];
$branchID = $_SESSION['branch_id'];

// filter dates
$fromDate = NULL;
$toDate = NULL;

if (isset($_GET['filter'])) {
    switch ($_GET['filter']) {
        case "today":
            $fromDate = $toDate = date("Y-m-d");
            break;
        case "week":
            $fromDate = date("Y-m-d", strtotime("monday this week"));
            $toDate = date("Y-m-d", strtotime("sunday this week"));
            break;
        case "month":
            $fromDate = date("Y-m-01");
            $toDate = date("Y-m-t");
            break;
    }
}

if (!empty($_GET['from']) && !empty($_GET['to'])) {
    $fromDate = $_GET['from'];
    $toDate = $_GET['to'];
}

// get branch currecny
$stmtCurrency = $conn->prepare("CALL get_branch_currency(?)");
$stmtCurrency->bind_param("s", $branchID);
$stmtCurrency->execute();
$resultCurrency = $stmtCurrency->get_result();

$branchCurrency = "₱"; // default
if ($resultCurrency && $row = $resultCurrency->fetch_assoc()) {
    $branchCurrency = $row['currency_sign'];
}
$stmtCurrency->close();
$conn->next_result();

// call procedure for walk-in orders
$stmtWalk = $conn->prepare("CALL get_walkin_orders(?, ?, ?)");
$stmtWalk->bind_param("sss", $branchID, $fromDate, $toDate);
$stmtWalk->execute();
$walkinOrders = $stmtWalk->get_result();
$conn->next_result();

// call procedure for online orders
$stmtOnline = $conn->prepare("CALL get_online_orders(?, ?)");
$stmtOnline->bind_param("ss", $fromDate, $toDate);
$stmtOnline->execute();
$onlineOrders = $stmtOnline->get_result();
$conn->next_result();

// chart data
$stmtChart = $conn->prepare("CALL get_online_sales_chart(?, ?)");
$stmtChart->bind_param("ss",  $fromDate, $toDate);
$stmtChart->execute();
$resChart = $stmtChart->get_result();

$salesLabels = [];
$salesTotals = [];
$totalSalesPeriod = 0;

$dateRange = [];
if ($fromDate && $toDate) {
    $start = new DateTime($fromDate);
    $end = new DateTime($toDate);
    $end->modify('+1 day');

    foreach (new DatePeriod($start, new DateInterval('P1D'), $end) as $date) {
        $dateRange[$date->format("Y-m-d")] = 0;
    }
}

while ($row = $resChart->fetch_assoc()) {
    $dateRange[$row['sales_date']] = floatval($row['total_amount']);
    $totalSalesPeriod += floatval($row['total_amount']);
}

$salesLabels = array_keys($dateRange);
$salesTotals = array_values($dateRange);

$stmtChart->close();
$conn->next_result();

// order supply assignments
$sqlOSA = "
    SELECT 
        osa.order_supply_assignment_ID,
        od.order_ID,
        pv.perfume_ID,
        p.perfume_name,
        pv.volume,
        osa.quantity,
        od.unit_price,
        cur.currency_sign
    FROM order_supply_assignment osa
    INNER JOIN order_details od ON osa.order_detail_ID = od.order_detail_ID
    INNER JOIN inventory i ON osa.inventory_ID = i.inventory_ID
    INNER JOIN perfume_volume pv ON od.perfume_volume_ID = pv.perfume_volume_ID
    INNER JOIN perfumes p ON pv.perfume_ID = p.perfume_ID
    LEFT JOIN currencies cur ON od.currency = cur.currency
    ORDER BY od.order_ID ASC
";

$stmtOSA = $conn->prepare($sqlOSA);
$stmtOSA->execute();
$osaResult = $stmtOSA->get_result();
$stmtOSA->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Aurum Scents | Sales Management</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/ibm_sidebar.css" rel="stylesheet">
    <link href="../css/ibm_general.css" rel="stylesheet">
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
        padding-bottom: 6rem !important;
        overflow-y: auto;
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
        min-width: 200px;
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

    .box {
        background: white;
        border: 1px solid #d9b78e;
        padding: 15px;
        border-radius: 12px;
        max-width: 550px;
        margin-bottom: 20px;
    }

    h2, h3 {
        color: #662422;
    }

    table {
        width: 100%;
        margin-top: 20px;
        border-collapse: collapse;
    }

    th {
        background: #a3495a;
        color: white;
    }

    td, th {
        padding: 10px;
        border: 1px solid #d9b78e;
        text-align: center;
    }

    .completed {
        background: #d4edda;
    }

    .pending {
        background: #fff3cd;
    }

    .cancelled {
        background: #f8d7da;
        color: #a10000;
    }

    button {
        background: #842A3B;
        border: none;
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        cursor: pointer;
    }

    button:hover {
        background: #662422;
    }

    input {
        border: 1px solid #c7a786;
        padding: 6px;
        border-radius: 6px;
    }
</style>

</head>
<body>
<?php require'ibm_sidebar.php'; ?>
<div class="container main mb-5 p-4">
    <div class="topbar">
        <div class="manager-name">Welcome, <?= htmlspecialchars($manager_username) ?></div>
        <div class="profile-container">
            <div class="profile-icon" onclick="toggleDropdown()">
                <img src="../BranchEmployee/profileIcon.png" alt="Profile">
            </div>
            <div id="profile-dropdown" class="dropdown">
                <p><strong>Username:</strong> <?= htmlspecialchars($_SESSION['username']) ?></p>
                <p><strong>Role:</strong> <?= htmlspecialchars($_SESSION['role']) ?></p>
                <p><strong>Branch:</strong> <?= htmlspecialchars($branchID) ?></p>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <h2>Sales Management — Online</h2>

    <!-- FILTER -->
    <div class="box">
        <form method="GET">
            <label>From:</label>
            <input type="date" name="from" value="<?= htmlspecialchars($fromDate) ?>">
            <label>To:</label>
            <input type="date" name="to" value="<?= htmlspecialchars($toDate) ?>">
            <button type="submit">Apply</button>
            <a href="sales_management.php"><button type="button">Clear</button></a>
        </form>
        <br><b>Quick Filters:</b><br><br>
        <a href="?filter=today"><button type="button">Today</button></a>
        <a href="?filter=week"><button type="button">This Week</button></a>
        <a href="?filter=month"><button type="button">This Month</button></a>
    </div>

    <!-- WALK-IN CHART -->
    <h3>Daily Online Sales Overview</h3>
    <div style="width:90%; max-width:1000px;">
        <canvas id="salesChart"></canvas>
    </div>
    <p><strong>Total Sales: $<?= number_format($totalSalesPeriod, 2) ?></strong></p>

    <script>
    const labels = <?= json_encode($salesLabels) ?>;
    const data = <?= json_encode($salesTotals) ?>;

    new Chart(document.getElementById('salesChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Online Sales ($)',
                data: data,
                borderColor: '#a3495a',
                backgroundColor: 'rgba(163,73,90,0.25)',
                borderWidth: 3,
                tension: 0.3,
                fill: true
            }]
        },
        options: { responsive: true }
    });

    function toggleDropdown() {
        const dropdown = document.getElementById("profile-dropdown");
        dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    }
    </script>

    <!-- ONLINE ORDERS TABLE -->
    <br><h3>Online Orders</h3>
    <table>
        <tr>
            <th>Order ID</th><th>Total</th><th>Date</th><th>Status</th>
        </tr>
        <?php while ($row = $onlineOrders->fetch_assoc()): ?>
        <tr class="<?= ($row['order_status']==='Completed') ? 'completed' : (($row['order_status']==='Cancelled') ? 'cancelled' : 'pending') ?>">
            <td><?= htmlspecialchars($row['order_ID']) ?></td>
            <td><?= htmlspecialchars($row['currency_sign'] ?: '₱') ?><?= number_format($row['order_total'], 2) ?></td>
            <td><?= htmlspecialchars($row['order_date']) ?></td>
            <td><?= htmlspecialchars($row['order_status']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <!-- OSA TABLE -->
    <br><h3>Order Supply Assignments (OSA)</h3>
    <table>
        <tr>
            <th>OSA ID</th><th>Order ID</th><th>Perfume ID</th><th>Perfume Name</th><th>Volume (ml)</th><th>Qty Assigned</th><th>Unit Price</th>
        </tr>
        <?php while ($row = $osaResult->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['order_supply_assignment_ID']) ?></td>
            <td><?= htmlspecialchars($row['order_ID']) ?></td>
            <td><?= htmlspecialchars($row['perfume_ID']) ?></td>
            <td><?= htmlspecialchars($row['perfume_name']) ?></td>
            <td><?= htmlspecialchars($row['volume']) ?></td>
            <td><?= htmlspecialchars($row['quantity']) ?></td>
            <td><?= htmlspecialchars($row['currency_sign']) ?><?= number_format($row['unit_price'], 2) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
