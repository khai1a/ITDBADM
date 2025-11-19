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
      $price = (float)$sellingPrices[$i];
      $pvID  = $conn->real_escape_string($perfumeVolumeIDs[$i]);

      $sql = "UPDATE perfume_volume 
              SET selling_price = $price
              WHERE perfume_volume_ID = '$pvID'";

      if (!$conn->query($sql)) {
        throw new Exception("SQL Error: " . $conn->error);
      }
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
