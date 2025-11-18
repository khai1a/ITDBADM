<?php 
$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

$resultPendingOrders = $conn->query("SELECT 
                                      od.order_detail_ID
                                    FROM orders o
                                    JOIN order_details od ON od.order_ID = o.order_ID
                                    LEFT JOIN order_supply_assignment osa ON osa.order_detail_ID = od.order_detail_ID
                                    WHERE o.order_type = 'Online' 
                                      AND o.order_status IN ('Placed','Preparing') 
                                      AND osa.order_detail_ID IS NULL");

$od_ids = [];
$skus = [];

while ($row = $resultPendingOrders->fetch_assoc()) {
  $conn->query("SET @createosa = ''");
  $od_id = $row['order_detail_ID'];
  $conn->query("CALL create_osa('$od_id', @createosa)");
  $resSku = $conn->query("SELECT @createosa")->fetch_assoc()['@createosa'];

  if ($resSku != null) {
    $od_ids[] = $od_id;
    $skus[] = $resSku;
  }
}

if (count($od_ids) > 0) {
  $message = "Successfully assigned orders to branches: ";

  for ($i = 0; $i < count($od_ids); $i++) {
    $message = $message . $od_ids[$i] . " to inventory " . $skus[$i];
    if ($i != count($od_ids) - 1) {
      $message .=  ", ";
    }
  }
} else {
  $message = "No available stock found at the moment.";
}

header('Location: ibm_orders_pending.php?msg=' . $message);
exit();

?>