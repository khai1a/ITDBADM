<?php 
$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $orderID = $_POST['order_id'];
  try {
    $query = "UPDATE orders SET order_status = 'Shipping' WHERE order_id = '$orderID'";
    $conn->query($query);
    $message = "Successfully shipped out order $orderID!";
  } catch (Exception $e) {
    $message = "Error shipping out order: " . $e->getMessage();
  }
}

header('Location: ibm_orders.php?msg='.$message);
exit();
?>
