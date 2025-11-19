<?php 
$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
  $perfumeVolumeID = $_GET['id'];
  $perfumeID = $_GET['pid'];

  if (isset($_GET['id'])){
    try {
        $res = $conn->query("DELETE FROM perfume_volume WHERE perfume_volume_id = '$perfumeVolumeID'");
        $message = "Successfully deleted volume.";
    } catch (Exception $e) {
        $message = "Error deleting volume: " . $e->getMessage();
    }
  }
}

 header("Location: admin_viewperfumedetails.php?id=" . $perfumeID . "&message=" . $message);
exit();
?>