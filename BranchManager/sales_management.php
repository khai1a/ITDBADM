<?php
include('../db_connect.php');
session_start();
date_default_timezone_set('Asia/Manila');

// BLOCK if not branch manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Branch Manager') {
    header("Location: ../login_staff-admin.php");
    exit;
}

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

// get branch currency
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
$stmtChart = $conn->prepare("CALL get_walkin_sales_chart(?, ?, ?)");
$stmtChart->bind_param("sss", $branchID, $fromDate, $toDate);
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
    WHERE i.branch_ID = ?
    ORDER BY od.order_ID ASC
";

$stmtOSA = $conn->prepare($sqlOSA);
$stmtOSA->bind_param("s", $branchID);
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
    <style>
    :root {
        --bg-main: #fffaf3;
        --bg-accent: #f9e2cf;
        --primary: #a3495a;
        --primary-dark: #842A3B;
        --primary-deep: #662422;
        --border-soft: #d9b78e;
        --text-main: #3b2320;
        --success: #d4edda;
        --warning: #fff3cd;
        --danger: #f8d7da;
    }

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
        background: radial-gradient(circle at top left, #fbe5d2, var(--bg-main));
        overflow: hidden;
        color: var(--text-main);
    }

    .sidebar {
        width: 250px;
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        box-shadow: 4px 0 16px rgba(0,0,0,0.08);
        z-index: 2;
    }

    .sidebar-top {
        background: linear-gradient(135deg, var(--primary), var(--primary-deep));
        padding: 30px 20px;
        height: 20%;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .sidebar-top h1 {
        color: white;
        font-size: 22px;
        letter-spacing: 1px;
    }

    .sidebar-bottom {
        background: var(--primary-deep);
        height: 80%;
        padding: 20px 16px;
    }

    .sidebar-bottom a {
        display: block;
        color: white;
        text-decoration: none;
        margin: 8px 0;
        padding: 10px 14px;
        border-radius: 10px;
        font-size: 14px;
        transition: background 0.15s, transform 0.1s;
    }

    .sidebar-bottom a:hover {
        background: var(--primary-dark);
        transform: translateX(3px);
    }

    .main {
        flex: 1;
        overflow-y: auto;
        padding: 30px 40px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 1px solid rgba(102, 36, 34, 0.15);
    }

    .manager-name {
        font-size: 20px;
        color: var(--primary-deep);
        font-weight: 600;
    }

    .profile-container {
        position: relative;
        display: inline-block;
    }

    .profile-icon img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
        border: 2px solid var(--primary);
        object-fit: cover;
    }

    .dropdown {
        display: none;
        position: absolute;
        right: 0;
        background: white;
        min-width: 220px;
        border: 1px solid #f0d1ac;
        border-radius: 10px;
        padding: 12px;
        margin-top: 8px;
        box-shadow: 0 10px 24px rgba(0,0,0,0.1);
        z-index: 100;
        font-size: 13px;
    }

    .dropdown p {
        margin: 4px 0;
    }

    .logout-btn {
        display: block;
        background: var(--primary-dark);
        color: white;
        text-align: center;
        padding: 7px;
        border-radius: 8px;
        margin-top: 10px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
    }

    .logout-btn:hover {
        background: var(--primary-deep);
    }
