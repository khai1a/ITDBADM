<?php 

require'check_session.php';

$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

if (isset($_GET['filter'])) {
  $filter = $_GET['filter'];
  $resultOrders = $conn->query("SELECT order_id, order_status, order_date, last_update 
                              FROM orders 
                              WHERE order_type = 'Online'
                                AND order_status = '$filter'");
} else {
  $resultOrders = $conn->query("SELECT order_id, order_status, order_date, last_update 
                              FROM orders 
                              WHERE order_type = 'Online'
                                AND order_status != 'Completed'");
}
?>

<!DOCTYPE html>
<html>
  <head>
    <title>Inter-Branch Manager - Orders</title>
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



    </style>
  </head>
  <body>

    <?php require 'ibm_sidebar.php'; ?>

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
            <li><a class="dropdown-item" href="ibm_orders.php?filter=Preparing">Preparing</a></li>
            <li><a class="dropdown-item" href="ibm_orders.php?filter=Ready">Ready</a></li>
            <li><a class="dropdown-item" href="ibm_orders.php?filter=Shipping">Shipping</a></li>
            <li><a class="dropdown-item" href="ibm_orders.php?filter=Cancelled">Cancelled</a></li>
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
            <button class="btn btn-primary">
              Ship out ready orders
            </button>
          </form>
        </div>
      </div>

      <div class="card header">
        <div class="card-body">
          <div class="container text-left">
            <div class="row">
              <div class="col">
                <h5 class="card-title">
                  Order ID
                </h5>
              </div>
              <div class="col">
                <h5 class="card-title">
                  Date Placed
                </h5>
              </div>
              <div class="col">
                <h5 class="card-title">
                  Status
                </h5>
              </div>
              <div class="col">
                <h5 class="card-title">
                  Last Update
                </h5>
              </div>
              <div class="col">

              </div>
            </div>
          </div>
        </div>
      </div>

      <?php while ($row = $resultOrders->fetch_assoc()) { ?>
      <div class="card item">
        <div class="card-body">
          <div class="container text-left">
            <div class="row">
              <div class="col">
                <p class="card-text">
                  <?= $row['order_id'] ?>
                </p>
              </div>
              <div class="col">
                <p class="card-text">
                  <?= $row['order_date'] ?>
                </p>
              </div>
              <div class="col">
                <p class="card-text">
                  <?= $row['order_status'] ?>
                </p>
              </div>
              <div class="col">
                <p class="card-text">
                  <?= $row['last_update'] ?>
                </p>
              </div>
              <div class="col">
                <form method="POST" action="ship_out_order.php">
                  <input type="hidden" value="<?= $row['order_id'] ?>" name="order_id">
                  <button class="btn btn-primary 
                    <?php if ($row['order_status'] != 'Ready') { echo 'disabled'; } ?>"
                    <?php if ($row['order_status'] != 'Ready') { echo 'disabled'; } ?>>
                    Ship out
                  </button>
                </form>
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