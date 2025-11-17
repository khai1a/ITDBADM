<?php 
$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);
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

    </style>
  </head>
  <body>

    <?php require 'ibm_sidebar.php'; ?>

    <div class="container flex-column p-5 main">
      <div class="d-flex flex-row justify-content-between">
        <h3 class="page-title">
          Orders
        </h3>

        <div class="dropdown">
          <a class="btn btn-primary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Select Status Filter
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#">Placed</a></li>
            <li><a class="dropdown-item" href="#">Preparing</a></li>
            <li><a class="dropdown-item" href="#">Ready</a></li>
            <li><a class="dropdown-item" href="#">Shipping</a></li>
            <li><a class="dropdown-item" href="#">Cancelled</a></li>
          </ul>
        </div>
      </div>

      <div class="bottom-bar p-3">
        <div class="d-flex flex-row justiy-content-around">
          <a class="btn btn-primary">
            Ship out ready orders
          </a>
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

      <div class="card item">
        <div class="card-body">
          <div class="container text-left">
            <div class="row">
              <div class="col">
                <p class="card-text">
                  O129341
                </p>
              </div>
              <div class="col">
                <p class="card-text">
                  11-11-2025
                </p>
              </div>
              <div class="col">
                <p class="card-text">
                  Preparing
                </p>
              </div>
              <div class="col">
                <p class="card-text">
                  11-11-2025 11:00:00 AM
                </p>
              </div>
              <div class="col">
                <a class="btn btn-primary disabled">
                  Ship out
                </a>
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
                <p class="card-text">
                  O12341
                </p>
              </div>
              <div class="col">
                <p class="card-text">
                  11-09-2025
                </p>
              </div>
              <div class="col">
                <p class="card-text">
                  Ready
                </p>
              </div>
              <div class="col">
                <p class="card-text">
                  11-10-2025 12:43:00 PM
                </p>
              </div>
              <div class="col">
                <a class="btn btn-primary">
                  Ship out
                </a>
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
                <p class="card-text">
                  O000231
                </p>
              </div>
              <div class="col">
                <p class="card-text">
                  11-02-2025
                </p>
              </div>
              <div class="col">
                <p class="card-text">
                  Ready
                </p>
              </div>
              <div class="col">
                <p class="card-text">
                  11-10-2025 12:43:00 PM
                </p>
              </div>
              <div class="col">
                <a class="btn btn-primary">
                  Ship out
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
</html>