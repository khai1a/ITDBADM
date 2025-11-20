<?php
include('../db_connect.php');
require 'check_session.php';
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

// chart data (ONLINE)
$stmtChart = $conn->prepare("CALL get_online_sales_chart(?, ?)");
$stmtChart->bind_param("ss", $fromDate, $toDate);
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
    <title>Inter-Branch Manager - Online Sales Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css"
          integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T"
          crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/ibm_sidebar.css" rel="stylesheet">
    <link href="../css/ibm_general.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
      .header .card-title {
        font-weight: bold;
        color: #A3485A;
      }

      .item {
        border: none;
        border-radius: 1em;
        background-color: rgba(231, 214, 213, 1);
        margin-bottom: 0.7rem;
      }

      .bottom-bar {
        background-color: #F5DAA7;
        z-index: 50;
        position: fixed;
        bottom: 0;
        left: 15rem;
        right: 0rem;
      }

      .main {
        padding-bottom: 6rem !important;
      }

      body {
        padding-bottom: 6rem !important;
      }

      a {
        color: black;
      }

      a:hover {
        text-decoration: none;
        color: black;
      }

      .chart-wrapper {
        width: 100%;
      }

      @media (min-width: 768px) {
        .chart-wrapper {
          width: 90%;
          max-width: 1000px;
          margin: 0 auto;
        }
      }

      .scroll-box {
    max-height: 300px;
    overflow-y: auto;
    padding-right: 8px;
}

.scroll-box::-webkit-scrollbar {
    width: 8px;
}

.scroll-box::-webkit-scrollbar-thumb {
    background: #c8a8a8;
    border-radius: 10px;
}

.scroll-box::-webkit-scrollbar-track {
    background: #f3e8e8;
}
    </style>
</head>
<body>

<?php require 'ibm_sidebar.php'; ?>

<div class="container flex-column main p-5">

  <div class="d-flex flex-row justify-content-between mb-4">
    <h3 class="page-title">
      Online Sales Management
    </h3>
  </div>

  <!-- filters -->
  <div class="card item">
    <div class="card-body">
      <div class="container text-left">
        <div class="row mb-2">
          <div class="col">
            <h5 class="card-title">Filter by Date</h5>
          </div>
        </div>
        <form method="GET">
          <div class="row align-items-end">
            <div class="col-md-3 mb-2">
              <label for="from">From:</label>
              <input id="from" type="date" name="from" class="form-control"
                     value="<?= htmlspecialchars($fromDate) ?>">
            </div>
            <div class="col-md-3 mb-2">
              <label for="to">To:</label>
              <input id="to" type="date" name="to" class="form-control"
                     value="<?= htmlspecialchars($toDate) ?>">
            </div>
            <div class="col-md-3 mb-2 d-flex">
              <button type="submit" class="btn btn-primary mr-2 align-self-end">
                Apply
              </button>
              <a href="ibm_sales_management.php" class="btn btn-secondary align-self-end">
                Clear
              </a>
            </div>
          </div>
        </form>

        <div class="row mt-3">
          <div class="col">
            <b>Quick Filters:</b>
          </div>
        </div>
        <div class="row mt-2">
          <div class="col d-flex flex-row">
            <a href="?filter=today" class="btn btn-secondary mr-2">Today</a>
            <a href="?filter=week" class="btn btn-secondary mr-2">This Week</a>
            <a href="?filter=month" class="btn btn-secondary">This Month</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- chart -->
  <div class="card item">
    <div class="card-body">
      <div class="container text-left">
        <div class="row">
          <div class="col">
            <h5 class="card-title">Daily Online Sales Overview</h5>
            <p class="mb-1">
              <?php if ($fromDate && $toDate): ?>
                <small><?= htmlspecialchars($fromDate) ?> to <?= htmlspecialchars($toDate) ?></small>
              <?php else: ?>
                <small>All available dates</small>
              <?php endif; ?>
            </p>
          </div>
        </div>
        <div class="row mt-3">
          <div class="col chart-wrapper">
            <canvas id="salesChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <h4 class="page-title mt-4 mb-3">Online Orders</h4>

  <!-- online orders list -->
<div class="card header mt-4">
  <div class="card-body">
    <div class="container text-left">
      <div class="row">
        <div class="col"><h5 class="card-title">Order ID</h5></div>
        <div class="col"><h5 class="card-title">Total</h5></div>
        <div class="col"><h5 class="card-title">Date</h5></div>
        <div class="col"><h5 class="card-title">Status</h5></div>
      </div>
    </div>
  </div>
</div>

<div class="scroll-box mt-2">
<?php while ($row = $onlineOrders->fetch_assoc()) : ?>
  <?php
    $statusClass = ($row['order_status'] === 'Completed')
      ? 'badge-success'
      : (($row['order_status'] === 'Cancelled') ? 'badge-danger' : 'badge-warning');
  ?>
  <div class="card item">
    <div class="card-body">
      <div class="container text-left">
        <div class="row">
          <div class="col">
            <p class="card-text mb-1"><?= htmlspecialchars($row['order_ID']) ?></p>
          </div>
          <div class="col">
            <p class="card-text mb-1">
              <?= htmlspecialchars($row['currency_sign'] ?: '₱') ?>
              <?= number_format($row['order_total'], 2) ?>
            </p>
          </div>
          <div class="col">
            <p class="card-text mb-1"><?= htmlspecialchars($row['order_date']) ?></p>
          </div>
          <div class="col">
            <span class="badge <?= $statusClass ?>">
              <?= htmlspecialchars($row['order_status']) ?>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endwhile; ?>
</div>


  <!-- OSA -->
 <h4 class="page-title mt-4 mb-3">Order Supply Assignments (OSA)</h4>

    <div class="card header">
    <div class="card-body">
        <div class="container text-left">
        <div class="row">
            <div class="col"><h5 class="card-title">OSA ID</h5></div>
            <div class="col"><h5 class="card-title">Order ID</h5></div>
            <div class="col"><h5 class="card-title">Perfume</h5></div>
            <div class="col"><h5 class="card-title">Volume</h5></div>
            <div class="col"><h5 class="card-title">Qty</h5></div>
            <div class="col"><h5 class="card-title">Unit Price</h5></div>
        </div>
        </div>
    </div>
    </div>

    <div class="scroll-box mt-2">
    <?php while ($row = $osaResult->fetch_assoc()) : ?>
    <div class="card item">
        <div class="card-body">
        <div class="container text-left">
            <div class="row">
            <div class="col">
                <p class="card-text mb-1"><?= htmlspecialchars($row['order_supply_assignment_ID']) ?></p>
            </div>
            <div class="col">
                <p class="card-text mb-1"><?= htmlspecialchars($row['order_ID']) ?></p>
            </div>
            <div class="col">
                <p class="card-text mb-1">
                <?= htmlspecialchars($row['perfume_ID']) ?> — <?= htmlspecialchars($row['perfume_name']) ?>
                </p>
            </div>
            <div class="col">
                <p class="card-text mb-1"><?= htmlspecialchars($row['volume']) ?></p>
            </div>
            <div class="col">
                <p class="card-text mb-1"><?= htmlspecialchars($row['quantity']) ?></p>
            </div>
            <div class="col">
                <p class="card-text mb-1">
                <?= htmlspecialchars($row['currency_sign']) ?><?= number_format($row['unit_price'], 2) ?>
                </p>
            </div>
            </div>
        </div>
        </div>
    </div>
    <?php endwhile; ?>
    </div>


    <!-- total sales -->
    <div class="bottom-bar p-3">
    <div class="d-flex flex-row justify-content-around">
        Total online sales (selected period): $<?= number_format($totalSalesPeriod, 2) ?>
    </div>
    </div>

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
                borderColor: '#A3485A',
                backgroundColor: 'rgba(163,73,90,0.25)',
                borderWidth: 3,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    </script>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
            integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo"
            crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
            integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
            crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js"
            integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y"
            crossorigin="anonymous"></script>
    </body>
</html>
