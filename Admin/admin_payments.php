<?php 

require'check_session.php';

$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

if (isset($_GET['filter'])) {
  $filter = $_GET['filter'];
  $resultPayments = $conn->query("SELECT * FROM payments p
                                          JOIN orders o ON o.order_ID = p.order_ID 
                                          AND status = '$filter'");
} else {
  $resultPayments = $conn->query("SELECT * FROM payments p
                                          JOIN orders o ON o.order_ID = p.order_ID");
}
  $totalReceived = $conn->query("SELECT SUM(p.amount / c.fromUSD) AS total FROM payments p
                                        JOIN orders o ON o.order_ID = p.order_ID
                                        JOIN currencies c ON c.currency = o.currency
                                        WHERE p.status = 'Received'")->fetch_assoc()['total'];

?>

<!DOCTYPE html>
<html>
  <head>
    <title>Admin - Payments</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/admin_sidebar.css" rel="stylesheet">
    <link href="../css/admin_general.css" rel="stylesheet">
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



    </style>
  </head>
  <body>

    <?php require 'admin_sidebar.php'; ?>

    <div class="container flex-column main p-5">
      <div class="d-flex flex-row justify-content-between mb-4">
        <h3 class="page-title">
          Orders
        </h3>

        <div class="dropdown">
          <a class="btn btn-primary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Select Status Filter
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="admin_payments.php?filter=Received">Received</a></li>
            <li><a class="dropdown-item" href="admin_payments.php?filter=Processing">Processing</a></li>
            <li><a class="dropdown-item" href="admin_payments.php?filter=Refunded">Refunded</a></li>
          </ul>
        </div>
      </div>

      <?php if (isset($_GET['msg'])) { ?>
      <div class="alert alert-info" role="alert">
        <?= $_GET['msg'] ?>
      </div>
      <?php } ?>

      <div class="bottom-bar p-3">
        <div class="d-flex flex-row justiy-content-around">
          <form method="POST" action="ship_all_ready_orders.php">
            <p>Total Received: $<?= number_format($totalReceived, 2) ?></p>
          </form>
        </div>
      </div>

      <div class="card header">
        <div class="card-body">
          <div class="container text-left">
            <div class="row">
              <div class="col">
                <h5 class="card-title">
                  Payment ID
                </h5>
              </div>
              <div class="col">
                <h5 class="card-title">
                  Amount
                </h5>
              </div>
              <div class="col">
                <h5 class="card-title">
                  Status
                </h5>
              </div>
              <div class="col">
                <h5 class="card-title">
                  Method
                </h5>
              </div>
              <div class="col">
                <h5 class="card-title">
                  Customer ID
                </h5>
              </div>
              <div class="col">
                <h5 class="card-title">
                  Order ID
                </h5>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php while ($row = $resultPayments->fetch_assoc()) { ?>
      <div class="card item">
        <div class="card-body">
          <div class="container text-left">
            <div class="row">
              <div class="col">
                <p class="card-text">
                  <?= $row['payment_ID'] ?>
                </p>
              </div>
              <div class="col">
                <p class="card-text">
                  <?php
                      $amt = $row['amount'];
                      $curr = $row['currency'];
                      $conn->query("SET @out = 0");
                      $conn->query("CALL convert_to_usd($amt,'$curr', @out)");
                      $res = $conn->query("SELECT @out")->fetch_assoc()['@out'];
                      echo "$" . number_format($res, 2);
                  ?>
                </p>
              </div>
              <div class="col">
                <p class="card-text">
                  <?= $row['status'] ?>
                </p>
              </div>
              <div class="col">
                <p class="card-text">
                  <?= $row['method'] ?>
                </p>
              </div>
              <div class="col">
                <p class="card-text">
                  <?= $row['customer_ID'] ?>
                </p>
              </div>
              <div class="col">
                <p class="card-text">
                  <?= $row['order_ID'] ?>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php } ?>
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
</html>