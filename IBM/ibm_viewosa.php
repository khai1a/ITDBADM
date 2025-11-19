<?php 

require'check_session.php';

$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

if (isset($_GET['id'])) {
  $order_ID = $_GET['id'];
  $resultOSA = $conn->query("SELECT 
                                        osa.order_supply_assignment_ID AS id,
                                        CONCAT(p.perfume_name, ' ', pv.volume, 'ml') AS item,
                                        osa.inventory_ID AS sku,
                                        osa.quantity,
                                        b.branch_ID AS branch
                            FROM order_supply_assignment osa
                            JOIN order_details od ON od.order_detail_ID = osa.order_detail_ID
                            JOIN orders o ON o.order_ID = od.order_ID
                            JOIN perfume_volume pv ON pv.perfume_volume_ID = od.perfume_volume_ID
                            JOIN perfumes p ON p.perfume_ID = pv.perfume_ID
                            JOIN inventory i ON i.inventory_ID = osa.inventory_ID
                            JOIN branches b ON b.branch_ID = i.branch_ID
                            WHERE o.order_ID = '$order_ID'");
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
          Order Supply Assignments - Order <?= $order_ID ?>
        </h3>
      </div>

      <div class="bottom-bar p-3">
        <div class="d-flex flex-row justiy-content-around">
          <form method="GET" action="ibm_orders.php">
            <button class="btn btn-primary">
              Back
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
                  OSA ID
                </h5>
              </div>
              <div class="col">
                <h5 class="card-title">
                  Order Item
                </h5>
              </div>
              <div class="col">
                <h5 class="card-title">
                  SKU
                </h5>
              </div>
              <div class="col">
                <h5 class="card-title">
                  Branch
                </h5>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php while ($row = $resultOSA->fetch_assoc()) { ?>
      <a href="#">
        <div class="card item">
        <div class="card-body">
          <div class="container text-left">
            <div class="row">
              <div class="col">
                <p class="card-text">
                  <?= $row['id'] ?>
                </p>
              </div>
              <div class="col">
                <p class="card-text">
                  <?= $row['item'] ?>
                </p>
              </div>
              <div class="col">
                <p class="card-text">
                  <?= $row['sku'] ?>
                </p>
              </div>
              <div class="col">
                <p class="card-text">
                  <?= $row['branch'] ?>
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