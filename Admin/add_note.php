<?php 
$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $perfumeID = $_POST['perfume_ID'];
  $noteID = $_POST['note'];
  $noteLevel = $_POST['note_level'];
  $conn->query("SET @p_n_id = ''");
  $conn->query("CALL getLastPerfumeNoteID(@p_n_id)");
  $perfumeNoteID = $conn->query("SELECT @p_n_id")->fetch_assoc()['@p_n_id'];

  try {
    $conn->query("INSERT INTO perfume_notes (perfume_note_id, perfume_ID, note_ID, note_level) VALUE
    ('$perfumeNoteID', '$perfumeID', '$noteID', '$noteLevel')");
  } catch (Exception $e) {
    $message = "Error adding note: " . $e->getMessage();
  }
}

 header("Location: admin_viewperfumedetails.php?id=" . $perfumeID);
exit();
?>