<?php 
$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);
?>

<!DOCTYPE html>
<html>
  <head>
    <title>Inter-Branch Manager - Branches</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/ibm_sidebar.css" rel="stylesheet">
    <link href="../css/ibm_general.css" rel="stylesheet">
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
            <li><a class="dropdown-item" href="#">BR0001</a></li>
            <li><a class="dropdown-item" href="#">BR0002</a></li>
            <li><a class="dropdown-item" href="#">BR0003</a></li>
          </ul>
        </div>
      </div>
          <div class="card">
            <div class="card-header">
              <h4 >
                Branch BR0001
              </h4>
            </div>
            <ul class="list-group list-group-flush">
              <li class="list-group-item">
                <p class="card-text">
                  Country: Japan
                </p>
                <p class="card-text">
                  Location: Ginza District, Chuo City, Tokyo 104-0061
                </p>
              </li>
              <li class="list-group-item">
                <p class="card-text">
                  Total Sales Today: 16 <!-- Walk ins -->
                </p>
                <p class="card-text">
                  Revenue This Month: $12,972.00
                </p>
                <p class="card-text">
                  Top selling: Baccarat Rouge 540 <!-- Walk ins -->
                </p>
                <p class="card-text">
                  Active Order Assignments: 5 <!-- Order assignments that are associated with orders that have not been completed yet -->
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
                      <tr>
                        <td>IN91242</td>
                        <td>Black Phantom 100ML</td>
                        <td>3</td>
                        <td>11-11-2025 6:16:00 PM</td>
                        <td></td>
                      </tr>
                      <tr>
                        <td>INW82U5</td>
                        <td>Fleur Narcotique 100ML</td>
                        <td>2</td>
                        <td>11-11-2025 6:18:00 PM</td>
                        <td>!</td>
                      </tr>
                      <tr>
                        <td>IN91242</td>
                        <td>Black Phantom 100ML</td>
                        <td>3</td>
                        <td>11-11-2025 6:16:00 PM</td>
                        <td></td>
                      </tr>
                      <tr>
                        <td>INW82U5</td>
                        <td>Fleur Narcotique 100ML</td>
                        <td>2</td>
                        <td>11-11-2025 6:18:00 PM</td>
                        <td>!</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </li>
            </ul>
          </div>
    </div>

    <div class="spacer">*</div>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
  </body>
</html>