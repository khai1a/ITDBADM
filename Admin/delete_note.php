<?php 
$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $perfumeID = $_POST['perfume_ID'];
  $perfumeNoteID = $_POST['perfume_note_id'];

  try {
    $res = $conn->query("DELETE FROM perfume_notes WHERE perfume_note_id = '$perfumeNoteID'");
  } catch (Exception $e) {
    $message = "Error deleting note: " . $e->getMessage();
  }
}

 header("Location: admin_viewperfumedetails.php?id=" . $perfumeID);
exit();
?>