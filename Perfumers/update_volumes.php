<?php 
$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

  $perfumeID = $_POST['perfume_id'];
  $perfumeVolumeIDs = $_POST['pvID'];
  $sellingPrices = $_POST['price'];

  $count = count($perfumeVolumeIDs);

  $conn->query("START TRANSACTION");

  try {
    for ($i = 0; $i < $count; $i++) {
      $conn->query("UPDATE perfume_volume 
                  selling_price = $sellingPrices[$i]
                  WHERE perfume_volume_ID = '$perfumeVolumeIDs[$i]'");
    }
    $conn->query("COMMIT");
    $message = "Successfully updated selling price(s).";
  } catch (Exception $e) {
    $conn->query("ROLLBACK");
    $message = "Error updating prices: " . $e->getMessage();
  }


} 
  header("Location: admin_viewperfumedetails.php?id=" . $perfumeID . "&message=" . $message);
exit();

?>