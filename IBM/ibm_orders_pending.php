<?php 
$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);
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

    </style>
  </head>
  <body>
    
    <?php require('ibm_sidebar.php') ?>

    <div class="container flex-column p-5 main">
      <h3 class="page-title">
        Pending Order Assignments
      </h3>

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
              Assigned SKU(s) <!-- inventory_ID -->
            </h6>
          </div>
          <div class="col">
            <h6 class="card-title">
              Total Quantity Assigned
            </h6>
          </div>
          <div class="col">
            <h6 class="card-title">
              Remarks
            </h6>
          </div>
        </div>
      </div>
        </div>
      </div>

      <div class="card item mb-2">
        <div class="card-body">
          <div class="container text-left">
            <div class="row">
					<div class="col">
            <h6 class="card-text">
              OD19283 <!-- order_details_ID -->
            </h6>
					</div>
          <div class="col">
            <h6 class="card-text">
              PV91384
            </h6>
          </div>
          <div class="col">
            <h6 class="card-text">
              2
            </h6>
          </div>
          <div class="col">
            <h6 class="card-text">
              IN73284 <!-- inventory_ID -->
            </h6>
          </div>
          <div class="col">
            <h6 class="card-text">
              1
            </h6>
          </div>
          <div class="col">
            <h6 class="card-text">
              Waiting for available stock
            </h6>
          </div>
        </div>
          </div>
        </div>
      </div>
      
      <div class="card item mb-2">
        <div class="card-body">
          <div class="container text-left">
            <div class="row">
					<div class="col">
            <h6 class="card-text">
              OD12342 <!-- order_details_ID -->
            </h6>
					</div>
          <div class="col">
            <h6 class="caard-text">
              PV93422
            </h6>
          </div>
          <div class="col">
            <h6 class="card-text">
              1
            </h6>
          </div>
          <div class="col">
            <h6 class="card-text">
              - <!-- inventory_ID -->
            </h6>
          </div>
          <div class="col">
            <h6 class="card-text">
              0
            </h6>
          </div>
          <div class="col">
            <h6 class="card-text">
              No available stock
            </h6>
          </div>
        </div>
          </div>
        </div>
      </div>

      <div class="card item">
        <div class="card-body">
          <div class="container text-left">
            <div class="row">
					<div class="col">
            <h6 class="card-text">
              OD19283 <!-- order_details_ID -->
            </h6>
					</div>
          <div class="col">
            <h6 class="caard-text">
              PV912384
            </h6>
          </div>
          <div class="col">
            <h6 class="card-text">
              2
            </h6>
          </div>
          <div class="col">
            <h6 class="card-text">
              IN73284 <!-- inventory_ID -->
            </h6>
          </div>
          <div class="col">
            <h6 class="card-text">
              1
            </h6>
          </div>
          <div class="col">
            <h6 class="card-text">
              Waiting for available stock
            </h6>
          </div>
        </div>
          </div>
        </div>
      </div>
		</div>
    
    
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
  </body>
</html>
