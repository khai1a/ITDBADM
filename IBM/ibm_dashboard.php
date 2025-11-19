<?php 

require'check_session.php';

$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

$query = "SELECT branch_ID, currency FROM branches b
          JOIN countries c ON b.country_ID = c.country_ID";
$resultBranches =$conn->query($query);

$query = "SELECT COUNT(order_detail_ID) AS count
          FROM order_details od
          LEFT JOIN order_supply_assignment osa ON od.order_detail_ID = osa.order_detail_ID
          WHERE order_supply_assignment_ID = NULL";
$resultOrderDetails = $conn->query("SELECT count(*) AS unassigned_orders
          FROM order_details od
          LEFT JOIN order_supply_assignment osa ON od.order_detail_ID = osa.order_detail_ID
          JOIN orders o ON od.order_ID = o.order_ID
          WHERE o.order_type = 'Online' AND osa.order_supply_assignment_ID IS NULL;");
$numUnassignedOrders = $resultOrderDetails->fetch_assoc()['unassigned_orders'];

$resultLowSKUs = $conn->query("SELECT 
                                COUNT(*) AS total_low_skus, 
                                COUNT(DISTINCT branch_ID) AS affected_branches
                                FROM inventory
                                WHERE quantity < 30;")->fetch_assoc();

function getTotalCompletedOrdersToday($branchID, $conn) {
  $conn->query("SET @out = 0");
  $conn->query("CALL getTotalCompletedOrdersToday('$branchID', @out)");
  $ordersToday = $conn->query("SELECT @out")->fetch_assoc()['@out'];

  return $ordersToday;
}

function getBranchRevenue($branchID, $conn) {
  $conn->query("SET @out = 0");
  $conn->query("CALL getBranchMonthlyRevenue('$branchID', @out)");
  $ordersToday = $conn->query("SELECT @out")->fetch_assoc()['@out'];

  return $ordersToday;
}

function getOrderCount($status, $conn) {
  $conn->query("SET @out = 0");
  $conn->query("CALL count_orders_by_status('$status', 'Online', @out)");
  $ordersToday = $conn->query("SELECT @out")->fetch_assoc()['@out'];

  return $ordersToday;
}

?>


<!DOCTYPE html>
<html>
  <head>
    <title>Inter-Branch Manager - Home</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/ibm_sidebar.css" rel="stylesheet">
    <link href="../css/ibm_general.css" rel="stylesheet">
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

      .branch-summary .col{
        font-size: 15px;
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
                  <?= number_format($numUnassignedOrders, 0) ?> pending orders
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
                  <?= number_format($resultLowSKUs['total_low_skus']) ?> SKUs low on stock across 
                  <?= number_format($resultLowSKUs['affected_branches']) ?> branches
                </p>
                <a class="btn btn-primary" href="ibm_branches.php">
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
                        Orders Completed Today
                      </h6>
                    </div>
                    <div class="col">
                      <h6 class="card-text summary-header">
                        Revenue This Month
                      </h6>
                    </div>
                  </div>

                  <?php while ($rowBranch = $resultBranches->fetch_assoc()) { ?>
                  <div class="row branch-summary">
                    <div class="col">
                      <div class="card-text ">
                        <?= $rowBranch['branch_ID'] ?>
                      </div>
                    </div>
                    <div class="col">
                      <div class="card-text">
                        <?= number_format(getTotalCompletedOrdersToday($rowBranch['branch_ID'], $conn),0)?>
                      </div>
                    </div>
                    <div class="col">
                      $<?= number_format(getBranchRevenue($rowBranch['branch_ID'], $conn), 2) ?>
                    </div>
                  </div>
                  <?php } ?>
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
                        <?= getOrderCount('Placed', $conn) ?>
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
                        <?= getOrderCount('Preparing', $conn) ?>
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
                        <?= getOrderCount('Ready', $conn) ?>
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
                        <?= getOrderCount('Shipping', $conn) ?>
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
