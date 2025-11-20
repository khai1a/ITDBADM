<?php
require'check_session.php';

$dbpath = dirname(__DIR__) . "/db_connect.php";
include($dbpath);

if(isset($_GET['filter'])) {
    $filter = $_GET['filter'];
    $resultPayments = $conn->query("SELECT p.payment_ID, amount, p.status, p.customer_id, o.order_ID, p.customer_ID, o.currency FROM payments p
                                JOIN orders o ON o.order_ID = p.order_ID
                                WHERE o.order_type = 'Online' AND p.status = '$filter'");

} else {
    $resultPayments = $conn->query("SELECT p.payment_ID, amount, p.status, p.customer_id, o.order_ID, p.customer_ID, o.currency FROM payments p
                                JOIN orders o ON o.order_ID = p.order_ID
                                WHERE o.order_type = 'Online'");
}

$receivedPayments = $conn->query("SELECT p.amount, o.currency, c.fromUSD FROM payments p
                                      JOIN orders o ON o.order_ID = p.order_ID
                                      JOIN currencies c ON c.currency = o.currency
                                      WHERE p.status = 'Received' AND o.order_type = 'Online'");
$total = 0;
while ($row = $receivedPayments->fetch_assoc()) {
  $usd = $row['amount'] / $row['fromUSD'];
  $total = $total + $usd;
}

?>

<!DOCTYPE html>
<html>
  <head>
    <title>Inter-Branch Manager - Online Payments</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/ibm_sidebar.css" rel="stylesheet">
    <link href="../css/ibm_general.css" rel="stylesheet">
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

    </style>
  </head>
  <body>

    <?php require 'ibm_sidebar.php'; ?>

    <div class="container flex-column main p-5">
      <div class="d-flex flex-row justify-content-between mb-4">
        <h3 class="page-title">
          Online Payments
        </h3>

        <div class="dropdown">
          <a class="btn btn-primary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Select Status Filter
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="ibm_online_payments.php">All</a></li>
            <li><a class="dropdown-item" href="?filter=Processing">Processing</a></li>
            <li><a class="dropdown-item" href="?filter=Received">Received</a></li>
            <li><a class="dropdown-item" href="?filter=Refunded">Refunded</a></li>
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
           Total of received payments: $<?= number_format($total, 2 ) ?>
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
                  Order ID
                </h5>
              </div>
              <div class="col">
                <h5 class="card-title">
                    Customer ID
                </h5>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php while ($row = $resultPayments->fetch_assoc()) { ?>
      <a href="ibm_viewosa.php?id=<?= $row['order_ID'] ?>">
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
                  <?= $row['amount'] ?> <?= $row['currency'] ?>
                </p>
              </div>
              <div class="col">
                <p class="card-text">
                  <?= $row['status'] ?>
                </p>
              </div>
              <div class="col">
                <p class="card-text">
                  <?= $row['order_ID'] ?>
                </p>
              </div>
              <div class="col">
                <p class="card-text">
                  <?= $row['customer_ID'] ?>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
      </a>
      
      <?php } ?>
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
</html>