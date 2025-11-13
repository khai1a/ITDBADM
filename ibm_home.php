<?php 
$dbpath = "db_connect.php";
$dbpath = realpath($dbpath);

include($dbpath);

?>

<!DOCTYPE html>
<html>
  <head>
    <title>Inter-Branch Manager - Home</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="css/ibm_sidebar.css" rel="stylesheet">
    <link href="css/ibm_general.css" rel="stylesheet">
    <style>
      .card-title {
        font-weight: bold;
      }

      .card {
        box-shadow: 0 5px 10px rgba(0,0,0,0.2);
      }

      .summary-header {
        font-weight: bold;
      }

      .greeting {
        color: #662222;
      }

    </style>
  </head>
  <body>
    
    <?php include(realpath('ibm_sidebar.php')); ?>

    <div class="container flex-column p-5 main">
        <h3 class="greeting">
          Hi there! :&rpar;
        </h3>
      <div class="container text-center dashboard">
        <div class="row mb-4">
          <div class="col">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">
                Order Supply Requests
                </h5>
                <p class="card-text">
                  6 pending orders
                </p>
                <a class="btn btn-primary" href="ibm_orders_pending.php">
                  View Pending Orders
                </a>
              </div>
            </div>
          </div>

          <div class="col">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">
                  Low Stock Inventory
                </h5>
                <p class="card-text">
                  10 SKUs low on stock across 3 branches
                </p>
                <a class="btn btn-primary" href="#">
                  View Inventories
                </a>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col">
            <div class="card bottom-card">
              <div class="card-body">
                <h5 class="card-title">
                  Branch Sales Summary
                </h5>

                <!-- INNER GRID -->
                <div class="container mb-3">
                  <div class="row mb-2">
                    <div class="col">
                      <h6 class="card-text summary-header">
                        Branch Name
                      </h6>
                    </div>
                    <div class="col">
                      <h6 class="card-text summary-header">
                        Total Sales Today
                      </h6>
                    </div>
                    <div class="col">
                      <h6 class="card-text summary-header">
                        Monthly Revenue
                      </h6>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col">
                      <div class="card-text">
                        BR0001
                      </div>
                    </div>
                    <div class="col">
                      <div class="card-text">
                        12
                      </div>
                    </div>
                    <div class="col">
                      $23,287.10
                    </div>
                  </div>

                  <div class="row">
                    <div class="col">
                      <div class="card-text">
                        BR0002
                      </div>
                    </div>
                    <div class="col">
                      <div class="card-text">
                        3
                      </div>
                    </div>
                    <div class="col">
                      $7,341.0
                    </div>
                  </div>

                  <div class="row">
                    <div class="col">
                      <div class="card-text">
                        BR0003
                      </div>
                    </div>
                    <div class="col">
                      <div class="card-text">
                        25
                      </div>
                    </div>
                    <div class="col">
                      $63,205.82
                    </div>
                  </div>
                </div>
                <!-- INNER GRID -->

                <a class="btn btn-primary" href="ibm_branches.php">
                  View All Branches
                </a>
              </div>
            </div>
          </div>

          <div class="col">
            <div class="card bottom-card">
              <div class="card-body">
                <h5 class="card-title">
                  Orders By Status
                </h5>

                <!-- INNER GRID -->
                <div class="container mb-3">
                  <div class="row">
                    <div class="col">
                      <div class="summary-header">
                        Status
                      </div>
                    </div>
                    <div class="col">
                      <div class="summary-header">
                        Order count
                      </div>                    
                    </div>
                  </div>

                  <div class="row">
                    <div class="col">
                      <div class="card-text">
                        Placed
                      </div>
                    </div>
                    <div class="col">
                      <div class="card-text">
                        21
                      </div>
                    </div>
                  </div>

                   <div class="row">
                    <div class="col">
                      <div class="card-text">
                        Preparing
                      </div>
                    </div>
                    <div class="col">
                      <div class="card-text">
                        13
                      </div>
                    </div>
                  </div>

                   <div class="row">
                    <div class="col">
                      <div class="card-text">
                        Ready
                      </div>
                    </div>
                    <div class="col">
                      <div class="card-text">
                        4
                      </div>
                    </div>
                  </div>

                   <div class="row">
                    <div class="col">
                      <div class="card-text">
                        Shipping
                      </div>
                    </div>
                    <div class="col">
                      <div class="card-text">
                        32
                      </div>
                    </div>
                  </div>
                </div>
                <!-- INNER GRID -->

                <a class="btn btn-primary" href="ibm_orders.php">
                  Details
                </a>
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
