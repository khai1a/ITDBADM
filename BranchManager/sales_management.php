<?php
include('../db_connect.php');
session_start();
date_default_timezone_set('Asia/Manila'); // fixes "Today" showing previous day

// --------------------
// AUTH CHECK
// --------------------
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Branch Manager') {
    header("Location: ../login_staff-admin.php");
    exit;
}

$manager_username = $_SESSION['username'];
$branchID = $_SESSION['branch_id'];

// --------------------
// HANDLE FILTERS
// --------------------
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

if (isset($_GET['from'], $_GET['to']) && $_GET['from'] !== "" && $_GET['to'] !== "") {
    $fromDate = $_GET['from'];
    $toDate = $_GET['to'];
}

// --------------------
// GET BRANCH CURRENCY (PROC 1)
// --------------------
$stmtCurrency = $conn->prepare("CALL get_branch_currency(?)");
$stmtCurrency->bind_param("s", $branchID);
$stmtCurrency->execute();
$resultCurrency = $stmtCurrency->get_result();
$branchCurrency = ($resultCurrency && $row = $resultCurrency->fetch_assoc()) 
    ? $row['currency_sign'] 
    : '₱';
$stmtCurrency->close();
$conn->next_result(); // free result set so we can run next CALL

// --------------------
// FETCH WALK-IN ORDERS (PROC 2) — only this branch
// --------------------
$stmtWalk = $conn->prepare("CALL get_walkin_orders(?, ?, ?)");
$stmtWalk->bind_param("sss", $branchID, $fromDate, $toDate);
$stmtWalk->execute();
$walkinOrders = $stmtWalk->get_result(); // iterate later

// --------------------
// FETCH ONLINE ORDERS (PROC 3) — all branches
// --------------------
$conn->next_result();
$stmtOnline = $conn->prepare("CALL get_online_orders(?, ?)");
$stmtOnline->bind_param("ss", $fromDate, $toDate);
$stmtOnline->execute();
$onlineOrders = $stmtOnline->get_result();

// --------------------
// SALES CHART DATA (PROC 4) — summarize only walk-in for this branch
// --------------------
$conn->next_result();
$stmtChart = $conn->prepare("CALL get_walkin_sales_chart(?, ?, ?)");
$stmtChart->bind_param("sss", $branchID, $fromDate, $toDate);
$stmtChart->execute();
$resChart = $stmtChart->get_result();

// Prepare chart data
$salesLabels = [];
$salesTotals = [];
$totalSalesPeriod = 0;

// generate full date range for chart (only if filter provided)
$dateRange = [];
if ($fromDate && $toDate) {
    $start = new DateTime($fromDate);
    $end = new DateTime($toDate);
    $end->modify('+1 day'); // include end date
    $period = new DatePeriod($start, new DateInterval('P1D'), $end);
    foreach ($period as $date) {
        $dateRange[$date->format('Y-m-d')] = 0; // default 0 sales for each day
    }
}

// Fill dateRange with results returned
while ($row = $resChart->fetch_assoc()) {
    $dateKey = $row['sales_date'];
    $dateRange[$dateKey] = floatval($row['total_amount']);
    $totalSalesPeriod += floatval($row['total_amount']);
}

// Build labels & totals arrays
$salesLabels = array_keys($dateRange);
$salesTotals = array_values($dateRange);

// Close statements & free results
$stmtWalk->close();
$stmtOnline->close();
$stmtChart->close();
$conn->next_result();

// --------------------
// FETCH ORDER SUPPLY ASSIGNMENTS (OSA) — only for this branch
// --------------------
$sqlOSA = "
    SELECT osa.order_supply_assignment_ID, od.order_ID, pv.perfume_ID, p.perfume_name, pv.volume, osa.quantity, od.unit_price, cur.currency_sign
    FROM order_supply_assignment osa
    INNER JOIN order_details od ON osa.order_detail_ID = od.order_detail_ID
    INNER JOIN inventory i ON osa.inventory_ID = i.inventory_ID
    INNER JOIN perfume_volume pv ON od.perfume_volume_ID = pv.perfume_volume_ID
    INNER JOIN perfumes p ON pv.perfume_ID = p.perfume_ID
    LEFT JOIN currencies cur ON od.currency = cur.currency
    WHERE i.branch_ID = ?
    ORDER BY od.order_ID ASC
";
$stmtOSA = $conn->prepare($sqlOSA);
$stmtOSA->bind_param("s", $branchID);
$stmtOSA->execute();
$osaResult = $stmtOSA->get_result();
$stmtOSA->close();
$conn->next_result();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Aurum Scents | Sales Management</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .box { background: white; border: 1px solid #d9b78e; padding: 15px; border-radius: 12px; max-width: 550px; margin-bottom: 20px; }
        h2, h3 { color: #662422; }
        table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        th { background: #a3495a; color: white; }
        td, th { padding: 10px; border: 1px solid #d9b78e; text-align: center; }
        .completed { background: #d4edda; }
        .pending { background: #fff3cd; }
        .cancelled { background: #f8d7da; color: #a10000; }
        button { background: #842A3B; border: none; color: white; padding: 8px 12px; border-radius: 6px; cursor: pointer; }
        button:hover { background: #662422; }
        input { border: 1px solid #c7a786; padding: 6px; border-radius: 6px; }
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

        <h2>Sales Management — Branch <?= htmlspecialchars($branchID) ?></h2>

        <div class="box">
            <form method="GET">
                <label>From:</label>
                <input type="date" name="from" value="<?= htmlspecialchars($fromDate) ?>">
                <label>To:</label>
                <input type="date" name="to" value="<?= htmlspecialchars($toDate) ?>">
                <button type="submit">Apply</button>
                <a href="sales_management.php"><button type="button">Clear</button></a>
            </form>
            <br>
            <b>Quick Filters:</b><br><br>
            <a href="?filter=today"><button type="button">Today</button></a>
            <a href="?filter=week"><button type="button">This Week</button></a>
            <a href="?filter=month"><button type="button">This Month</button></a>
        </div>

        <h3>Daily Walk-in Sales Overview</h3>
        <div style="width:90%; max-width:1000px;">
            <canvas id="salesChart"></canvas>
        </div>
        <p><strong>Total Sales: <?= htmlspecialchars($branchCurrency) ?><?= number_format($totalSalesPeriod, 2) ?></strong></p>

        <script>
            const labels = <?= json_encode($salesLabels) ?>;
            const data = <?= json_encode($salesTotals) ?>;
            new Chart(document.getElementById('salesChart'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Daily Walk-in Sales (<?= $branchCurrency ?>)',
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
        </script>

        <br>
        <h3>Walk-in Orders</h3>
        <table>
            <tr>
                <th>Order ID</th>
                <th>Total</th>
                <th>Date</th>
                <th>Status</th>
            </tr>
            <?php while($row = $walkinOrders->fetch_assoc()): ?>
                <tr class="completed">
                    <td><?= $row['order_ID'] ?></td>
                    <td><?= htmlspecialchars($row['currency_sign']) ?><?= number_format($row['order_total'], 2) ?></td>
                    <td><?= $row['order_date'] ?></td>
                    <td>Completed</td>
                </tr>
            <?php endwhile; ?>
        </table>

        <br>
        <h3>Online Orders</h3>
        <table>
            <tr>
                <th>Order ID</th>
                <th>Total</th>
                <th>Date</th>
                <th>Status</th>
                <th>Branch</th>
            </tr>
            <?php while($row = $onlineOrders->fetch_assoc()): ?>
                <tr class="<?= ($row['order_status'] === 'Completed') ? 'completed' : (($row['order_status'] === 'Cancelled') ? 'cancelled' : 'pending') ?>">
                    <td><?= htmlspecialchars($row['order_ID']) ?></td>
                    <td><?= htmlspecialchars($row['currency_sign']) ?><?= number_format($row['order_total'], 2) ?></td>
                    <td><?= htmlspecialchars($row['order_date']) ?></td>
                    <td><?= htmlspecialchars($row['order_status']) ?></td>
                    <td><?= htmlspecialchars($row['branch_address']) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>

        <br>
        <h3>Order Supply Assignments (OSA) — Branch <?= htmlspecialchars($branchID) ?></h3>
        <table>
            <tr>
                <th>OSA ID</th>
                <th>Order ID</th>
                <th>Perfume ID</th>
                <th>Perfume Name</th>
                <th>Volume (ml)</th>
                <th>Quantity Assigned</th>
                <th>Unit Price</th>
            </tr>
            <?php while($row = $osaResult->fetch_assoc()): ?>
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
