<?php 
$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

  $perfumeID = $_POST['perfume_id'];
  $perfumeVolumeIDs = $_POST['pvID'];
  $volumes = $_POST['volume_ml'];
  $sellingPrices = $_POST['price'];

  $count = count($perfumeVolumeIDs);

  $conn->query("START TRANSACTION");

  try {
    for ($i = 0; $i < $count; $i++) {
      $conn->query("UPDATE perfume_volume 
                  SET volume = $volumes[$i], 
                  selling_price = $sellingPrices[$i]
                  WHERE perfume_volume_ID = '$perfumeVolumeIDs[$i]'");
    }
    $conn->query("COMMIT");
  } catch (Exception $e) {
    $conn->query("ROLLBACK");
    $message = "Error updating volumes: " . $e->getMessage();
  }


} 
  header("Location: admin_viewperfumedetails.php?id=" . $perfumeID);
exit();

?>