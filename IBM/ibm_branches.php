<?php 

require'check_session.php';

$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

 $resultBranches = $conn->query("SELECT branch_ID FROM branches ORDER BY branch_ID");

if (isset($_GET['bid'])){
  $selectedBranchID =  $_GET['bid'];
  $selectedBranchRow = $conn->query("SELECT * FROM branches b
                                            JOIN countries co ON co.country_ID = b.country_ID 
                                            WHERE branch_ID = '$selectedBranchID'")->fetch_assoc();
  $conn->query("SET @count = 0");
  $conn->query("CALL getTotalCompletedOrdersToday('$selectedBranchID', @count)");
  $completedOrders = $conn->query("SELECT @count")->fetch_assoc()['@count'];

  $conn->query("SET @`text` = '';");
  $conn->query("CALL getTop3Fragrances('$selectedBranchID', @`text`);");
  $top3 = $conn->query("SELECT @`text`;")->fetch_assoc()['@`text`'];
  
  $resultInventory = $conn->query("SELECT 
                                    i.inventory_ID, 
                                    CONCAT(p.perfume_name,' ', pv.volume, 'ml') AS perfume, 
                                    i.quantity, 
                                    i.last_update, 
                                    (i.quantity < 30) AS is_low 
                                  FROM inventory i
                                  JOIN perfume_volume pv ON pv.perfume_volume_ID = i.perfume_volume_ID
                                  JOIN perfumes p ON p.perfume_ID = pv.perfume_ID
                                  WHERE branch_ID = '$selectedBranchID'");

  $conn->query("SET @usd = 0");
  $conn->query("CALL getBranchMonthlyRevenue('$selectedBranchID', @usd)");
  $monthRevenue = $conn->query("SELECT @usd")->fetch_assoc()['@usd'];
  if ($monthRevenue == null) {
    $monthRevenue = 0;
  };
}
?>

<!DOCTYPE html>
<html>
  <head>
    <title>Inter-Branch Manager - Branches</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/ibm_sidebar.css" rel="stylesheet">
    <link href="../css/ibm_general.css" rel="stylesheet">
    <script src='https://kit.fontawesome.com/a076d05399.js' crossorigin='anonymous'></script>
    <style>
      .card {
        box-shadow: 0px 5px 10px rgba(0,0,0,0.2);
      }
    </style>
  </head>
  <body>

    <?php require'ibm_sidebar.php'; ?>

    <div class="container flex-column p-5 main">
      <div class="d-flex flex-row justify-content-between mb-5 align-items-center">
        <h3 class="page-title">
          Branches
        </h3>

        <div class="dropdown">
          <a class="btn btn-secondary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Select a Branch to View
          </a>

          <ul class="dropdown-menu">
            <?php while ($rowBranch = $resultBranches->fetch_assoc()) { ?>
            <li><a class="dropdown-item" href="ibm_branches.php?bid=<?= $rowBranch['branch_ID'] ?>"><?= $rowBranch['branch_ID'] ?></a></li>
            <?php } ?>
          </ul>
        </div>
      </div>

      <?php if (isset($_GET['bid'])) { ?>
      <div class="card">
        <div class="card-header">
          <h4 >
            Branch <?= $selectedBranchRow['branch_ID'] ?>
          </h4>
        </div>
        <ul class="list-group list-group-flush">
          <li class="list-group-item">
            <p class="card-text">
              Country: <?= $selectedBranchRow['country_name'] ?>
            </p>
            <p class="card-text">
              Location: <?= $selectedBranchRow['address'] ?>
            </p>
          </li>
          <li class="list-group-item">
            <p class="card-text">
              Completed Orders Today: <?= $completedOrders ?><!-- Walk ins -->
            </p>
            <p class="card-text">
              Revenue This Month: $<?= number_format($monthRevenue, 2) ?>
            </p>
            <p class="card-text">
              Top selling: <?= $top3 ?> <!-- Walk ins -->
            </p>
          </li>
          <li class="list-group-item">
            <h5 class="card-text">
              Branch Inventory
            </h5>
            <div class="container mt-3 ">
              <table class="table">
                <thead>
                  <th scope="col">SKU</th>
                  <th scope="col">Perfume</th>
                  <th scope="col">Quantity</th>
                  <th scope="col">Last Update</th>
                  <th scope="col"></th>
                </thead>
                <tbody>
                  <?php while ($rowInventory = $resultInventory->fetch_assoc()) { ?>
                  <tr>
                    <td><?= $rowInventory['inventory_ID'] ?></td>
                    <td><?= $rowInventory['perfume'] ?></td>
                    <td><?= $rowInventory['quantity'] ?></td>
                    <td><?= $rowInventory['last_update'] ?></td>
                    <td>
                      <?php if ($rowInventory['is_low'] == 1) { ?>
                       Low stock!
                      <?php } ?>
                    </td>
                  </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
          </li>
        </ul>
      </div>

      <?php } ?>
    </div>

    <div class="spacer">*</div>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
    
  </body>
</html>