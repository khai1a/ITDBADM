<?php 
$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $orderID = $_POST['order_id'];
  $action = $_POST['action'];
  try {
    if ($action == 'ship') {
      $query = "UPDATE orders SET order_status = 'Shipping' WHERE order_id = '$orderID'";
      $conn->query($query);
      $message = "Successfully shipped out order $orderID!";
    } else {
      $query = "UPDATE orders SET order_status = 'Completed' WHERE order_id = '$orderID'";
      $conn->query($query);
      $message = "Successfully completed $orderID!";
    }
  } catch (Exception $e) {
    if ($action = 'Shipping') {
      $message = "Error shipping out order: " . $e->getMessage();
    } else {
      $message = "Error completing order: " . $e->getMessage();
    }
  }
}

header('Location: ibm_orders.php?msg='.$message);
exit();
?>
