<?php 
$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $perfumeID = $_POST['perfume_ID'];

  $conn->query("SET @pa_id = ''");
  $conn->query("CALL getLastPerfumeAccordID(@pa_id)");
  $perfumeAccordID = $conn->query("SELECT @pa_id")->fetch_assoc()['@pa_id'];
  $accordID = $_POST['accord'];

  try {
    $conn->query("INSERT INTO perfume_accords (perfume_accord_id, accord_id, perfume_ID, is_primary) VALUE
                  ('$perfumeAccordID', '$accordID', '$perfumeID', 0)");
    $message = "Successfully added accord.";
  } catch (Exception $e) {
    $message = "Error adding accord: " . $e->getMessage();
  }
}

 header("Location: admin_viewperfumedetails.php?id=" . $perfumeID . "&message=" . $message);
exit();