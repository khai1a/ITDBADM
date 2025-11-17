<?php 
$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

$resultStaff = $conn->query("SELECT * FROM staff");
?>

<!DOCTYPE html>
<html>
  <head>
    <title> Admin Panel - View Staff </title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/admin_sidebar.css" rel="stylesheet">
    <link href="../css/admin_general.css" rel="stylesheet">
    <style>
      .main {
        align-content: center;
      }

      .card-header {
        background-color: #842A3B;
      }
    </style>
  </head>
  <body>

    <?php require'admin_sidebar.php'; ?>

    <div class="container main">
      <div class="card shadow-sm mt-5 mb-5">
        <div class="card-body">
          <div class="container">
            <div class="row">
              <div class="col">
                <h5 class="card-text">
                  Staff ID
                </h5>
              </div>
              <div class="col">
                <h5 class="card-title">
                  Branch ID
                </h5>
              </div>
              <div class="col">
                <h5 class="card-title">
                  Role
                </h5>
              </div>
              <div class="col">
                <h5 class="card-title">
                  Username
                </h5>
              </div>
              <div class="col">
                <h5 class="card-title">
                  Count of Active Returns
                </h5>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
    <?php $conn->close(); ?>
  </body>
</html>