<?php 
$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	$perfumeID = $_POST['perfume_id'];

	$newAccordID = $_POST['primaryAccord'];
	$primaryPerfumeAccordID = $_POST['perfume_accord_id_primary'];
	
	$conn->query("START TRANSACTION");
	try {
		$res = $conn->query("UPDATE perfume_accords 
												 SET accord_ID = '$newAccordID' 
												 WHERE perfume_accord_id = '$primaryPerfumeAccordID'");
		$conn->query("COMMIT");
		$message = "Successfully updated main accord!";
	} catch (Exception $e) {
		$conn->query("ROLLBACK;");
		$message = "Error updating main accord: " . $e->getMessage();
	}
}

 header("Location: admin_viewperfumedetails.php?id=" . $perfumeID);
exit();

?>