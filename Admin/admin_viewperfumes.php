<?php 
$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

$resultPerfumes = $conn->query("SELECT p.perfume_ID, p.perfume_name, p.concentration, p.Gender, b.brand_name, p.image_name
                                      FROM perfumes p
                                      JOIN brands b ON b.brand_ID = p.brand_ID
                                      JOIN perfume_volume pv ON pv.perfume_ID = p.perfume_ID
                                      ORDER BY perfume_name ASC");
?>

<!DOCTYPE html>
<html>
  <head>
    <title>Admin Panel - View Perfumes</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="../css/admin_sidebar.css" rel="stylesheet">
    <link href="../css/admin_general.css" rel="stylesheet">
    <style>
      img {
        height: 12rem;
        object-fit: contain;
      }

      .cardrow {
        justify-content: space-between;
      }

      .btn-light {
        background-color: #F5DAA7;
      }

       .perfume-card img {
        height: 12rem;
        object-fit: contain;
       }

      .card-icons {
        position: absolute;
        top: 10px;
        right: 10px;
        display: flex;
        gap: 10px;
      }

      .icon-btn {
        width: 34px;
        height: 34px;
        background: white;
        color: #333;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        text-decoration: none;
        box-shadow: 0 2px 5px rgba(0,0,0,0.15);
        transition: 0.2s;
      }

      .icon-btn:hover {
        background: #F5DAA7;
        color: black;
        transform: scale(1.05);
      }

      .icon-btn i {
        font-size: 16px;
      }

    </style>
  </head>
  <body>
  <?php require'admin_sidebar.php'; ?>

  <div class="container main mb-5 p-4">

  <h3 class="page-title mb-5 mt-3">Perfumes</h3>

  <div class="container d-flex flex-wrap">
  <?php while ($row = $resultPerfumes->fetch_assoc()) { ?>

  <div class="card perfume-card shadow-sm mr-4 mb-4" style="width: 16rem; position: relative;">
    
    <div class="card-icons">
      <a class="icon-btn info-btn" title="View Info" href="admin_viewperfumedetails.php?id=<?= $row['perfume_ID'] ?>">
        <i class="fa-solid fa-circle-info"></i>
      </a>
    </div>
    
    <img class="card-img-top"

    <?php 
      $image_file = $row['image_name'];
      if ($image_file != NULL) { ?>
        src="../images/<?= $row['image_name'] ?>" 
      <?php } else { ?>
        src="https://png.pngtree.com/png-vector/20250319/ourmid/pngtree-elegant-pink-perfume-bottle-for-women-clipart-illustration-png-image_15771804.png" 
     <?php } ?>

     alt="Image of <?= $row['perfume_name'] ?>">

    <div class="card-body">
      <h5 class="card-title"><?= $row['perfume_name'] ?></h5>
      <p class="card-text"><?= $row['concentration'] ?></p>
      <p class="card-text"><?= $row['brand_name'] ?></p>
      <p class="card-text"><?= $row['Gender'] ?></p>
    </div>
  </div>

  <?php } ?>
  </div>

</div>

  </body>
</html>