<?php 

require'check_session.php';

$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

$resultPendingOrders = $conn->query("SELECT 
                                      od.order_detail_ID, 
                                      TIMESTAMPDIFF(HOUR, o.order_date, NOW()) AS hours_waiting, 
                                      od.perfume_volume_ID, 
                                      od.quantity
                                    FROM orders o
                                    JOIN order_details od ON od.order_ID = o.order_ID
                                    LEFT JOIN order_supply_assignment osa ON osa.order_detail_ID = od.order_detail_ID
                                    WHERE o.order_type = 'Online' AND o.order_status IN ('Placed','Preparing') AND osa.order_detail_ID IS NULL
                                    ORDER BY hours_waiting DESC;");
?>

<!DOCTYPE html>
<html>
  <head>
    <title>Inter-Branch Manager - Pending Orders</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/ibm_sidebar.css" rel="stylesheet">
    <link href="../css/ibm_general.css" rel="stylesheet">
    <style>
      .card-title {
        font-weight: bold;
      }

      .item{
        border: none;
        border-radius: 1em;
        background-color: rgba(231, 214, 213, 1);
        align-content: center;
      }

      .nav-item {
        margin-bottom: 0.7rem;
      }

      .nav-tabs .active,
      .nav-tabs .nav-link{
        color: #662222 !important;
      }

      .header .card-title {
        color: #A3485A;
      }

      .bottom-bar {
        background-color: #F5DAA7;
        z-index: 50;
        position: fixed;
        bottom: 0;
        left: 15rem;
        right: 0rem;
      }

    </style>
  </head>
  <body>
    
    <?php require('ibm_sidebar.php') ?>

    <div class="bottom-bar p-3">
        <div class="d-flex flex-row justiy-content-around">
          <form method="POST" action="ibm_assignallorders.php">
            <button class="btn btn-primary">
              Assign all pending orders
            </button>
          </form>
        </div>
      </div>

    <div class="container flex-column p-5 main">
      <h3 class="page-title">
        Pending Order Assignments
      </h3>

      <?php if (isset($_GET['msg'])) { ?>
        <div class="alert alert-info" role="alert">
          <?= $_GET['msg'] ?>
        </div>
      <?php } ?>

      <div class="card">
        <div class="card-body">
        <div class="container text-left">
				<div class="row header">
					<div class="col">
            <h6 class="card-title">
              Order Detail ID <!-- order_details_ID -->
            </h6>
					</div>
          <div class="col">
            <h6 class="card-title">
              Item ID <!-- perfume_volume_ID -->
            </h6>
          </div>
          <div class="col">
            <h6 class="card-title">
              Order Quantity
            </h6>
          </div>
          <div class="col">
            <h6 class="card-title">
              Hours Waiting
            </h6>
          </div>
          <div class="col">
            <h6 class="card-title">
              
            </h6>
          </div>
        </div>
      </div>
        </div>
      </div>

      <?php while ($row = $resultPendingOrders->fetch_assoc()) { ?>
      <div class="card item mb-2">
        <div class="card-body">
          <div class="container text-left">
            <div class="row">
					<div class="col">
            <h6 class="card-text">
              <?= $row['order_detail_ID'] ?>
            </h6>
					</div>
          <div class="col">
            <h6 class="card-text">
              <?= $row['perfume_volume_ID'] ?>
            </h6>
          </div>
          <div class="col">
            <h6 class="card-text">
              <?= $row['quantity'] ?>
            </h6>
          </div>
          <div class="col">
            <h6 class="card-text">
              <?= $row['hours_waiting'] ?>
            </h6>
          </div>
          <div class="col">
            <form method="POST" action="ibm_assignorder.php">
              <input type="hidden" value="<?= $row['order_detail_ID'] ?>" name="order_detail_id">
              <button class="btn btn-primary">
                 Assign Order
              </button>
            </form>
          </div>
        </div>
          </div>
        </div>
      </div>
      <?php } ?>
		</div>
    
    
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
  </body>
</html>
