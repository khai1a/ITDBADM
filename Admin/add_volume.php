<?php 
$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $conn->query("SET @pv_id = ''");
  $conn->query("CALL getLastPerfumeVolumeID(@pv_id)");
  $perfumeVolumeID = $conn->query("SELECT @pv_id")->fetch_assoc()['@pv_id'];
  $perfumeID = $_POST['perfume_ID'];
  $newVolume = $_POST['new_volume'];
  $sellingPrice = $_POST['new_price'];

  try {
    $conn->query("INSERT INTO perfume_volume (perfume_volume_ID, perfume_ID, volume, selling_price) 
                  VALUES ('$perfumeVolumeID','$perfumeID', $newVolume, '$sellingPrice')");
  } catch (Exception $e) {
    $message = "Error adding new volume: " . $e->getMessage();
  }
}

 header("Location: admin_viewperfumedetails.php?id=" . $perfumeID);
exit();

?>