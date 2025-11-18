<?php 
$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  try {
    $readyOrders = $conn->query("SELECT order_ID FROM orders WHERE order_status = 'Ready'");
    $conn->query("UPDATE orders SET order_status = 'Shipping' WHERE order_status = 'Ready'");

    
    $count = $readyOrders->num_rows;
    $currRow = 1;

    if ($count == 0) {
      $message = "No orders shipped out.";
    } else {
      $message = "Shipped out orders: ";
    }
    while ($row = $readyOrders->fetch_assoc()) {
      $message .= $row['order_ID'];
     if ($currRow != $count) {
       $message .= ', ';
     }
     $currRow++;
    }
  } catch (Exception $e) {
    $message = "Error shipping out orders: " . $e->getMessage();
  }
}

header('Location: ibm_orders.php?msg='.$message);
exit();
?>