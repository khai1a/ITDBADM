<?php 
$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	$perfumeID = $_POST['perfume_id'];

	$primaryPerfumeAccordID = $_POST['secondary_perfume_accord_id'];
	
	$conn->query("START TRANSACTION");
	try {
		$res = $conn->query("DELETE FROM perfume_accords 
							WHERE perfume_accord_id = '$primaryPerfumeAccordID'");
		$conn->query("COMMIT");
		$message = "Successfully deleted accord.";
	} catch (Exception $e) {
		$conn->query("ROLLBACK;");
		$message = "Error deleting accord: " . $e->getMessage();
	}
}

 header("Location: admin_viewperfumedetails.php?id=" . $perfumeID . '&message=' . $message);
exit();
?>
