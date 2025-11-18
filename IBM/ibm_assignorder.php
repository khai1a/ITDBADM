<?php 
$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $orderDetailID = $_POST['order_detail_id'];

  $conn->query("SET @createosa = ''");
  $conn->query("CALL create_osa('$orderDetailID', @createosa)");
  $res = $conn->query("SELECT @createosa")->fetch_assoc()['@createosa'];

  if ($res != null) {
    $message = "Successfully assigned order to branch $res!";
  } else {
    $message = "Unable to find available stock for order $orderDetailID at the moment.";
  }
}

header("Location: ibm_orders_pending.php?msg=" . $message );
exit();