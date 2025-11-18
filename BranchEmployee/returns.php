<?php
// file handles backend php logic for returns
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

include('../db_connect.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Branch Employee') {
  header("Location: ../login_staff-admin.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['order_id'], $_POST['branch_id'], $_POST['staff_id'], $_POST['return_qty'])) {
  header("Location: employee_returns.php");
  exit();
}

$order_id   = $_POST['order_id'];
$branch_id  = $_POST['branch_id'];
$staff_id   = $_POST['staff_id'];
$reason     = isset($_POST['reason']) ? trim($_POST['reason']) : null;
$refund_method = isset($_POST['refund_method']) ? $_POST['refund_method'] : null;

$returnMap = $_POST['return_qty'];

$conn->begin_transaction();

try {
    foreach ($returnMap as $order_detail_ID => $qty) {
        $qty = (int)$qty;
        if ($qty <= 0) {
            continue;
        }

        // get order detail to validate and get pricing + its quantity
        $sqlDetail = "SELECT od.order_ID, od.perfume_volume_ID, od.quantity AS qty_ordered, od.unit_price, od.currency
                      FROM order_details od
                      WHERE od.order_detail_ID = ?";
        $stmt = $conn->prepare($sqlDetail);
        $stmt->bind_param("s", $order_detail_ID);
        $stmt->execute();
        $detail = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$detail || $detail['order_ID'] !== $order_id) {
            throw new Exception("Invalid order_detail_ID for this order.");
        }
        if ($qty > (int)$detail['qty_ordered']) {
            throw new Exception("Return quantity exceeds ordered quantity.");
        }

        // get the customer_ID from the order
        $sqlOrder = "SELECT customer_ID FROM orders WHERE order_ID = ?";
        $stmt = $conn->prepare($sqlOrder);
        $stmt->bind_param("s", $order_id);
        $stmt->execute();
        $orderRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $customer_ID = $orderRow ? $orderRow['customer_ID'] : null;


        // check how many units/pieces of specific order detail/item has already been returned
        $sqlReturned = "SELECT COALESCE(SUM(quantity),0) AS qty_returned
        FROM returns
            WHERE order_detail_ID = ? AND status IN ('Approved','Refunded')";
            $stmt = $conn->prepare($sqlReturned);
            $stmt->bind_param("s", $order_detail_ID);
            $stmt->execute();
            $returnedRow = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $alreadyReturned = (int)$returnedRow['qty_returned'];
            $remainingQty = (int)$detail['qty_ordered'] - $alreadyReturned;

        if ($qty > $remainingQty) {
        throw new Exception("Return quantity exceeds remaining eligible quantity.");
        }


        // compute refund
        $sqlVat = "SELECT c.vat_percent
        FROM branches b
        JOIN countries c ON b.country_ID = c.country_ID
        WHERE b.branch_ID = ?";
        $stmt = $conn->prepare($sqlVat);
        $stmt->bind_param("s", $branch_id);
        $stmt->execute();
        $vatRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $vatRate = $vatRow ? (float)$vatRow['vat_percent'] : 0.0;

        $refund_amount = number_format(
        $qty * (float)$detail['unit_price'] * (1 + $vatRate),
        2,
        '.',
        ''
        );



        // insert to retuns tavle
        // generate next return_ID. format is R00001
            $sqlMax = "SELECT MAX(return_ID) AS max_id FROM returns";
            $resMax = $conn->query($sqlMax);
            $rowMax = $resMax->fetch_assoc();
            $lastID = $rowMax['max_id'] ?? null;

            if ($lastID) {
                $num = (int)substr($lastID, 1); 
                $nextNum = $num + 1;
            } else {
                $nextNum = 1;
            }

            $return_ID = 'R' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);


        $sqlInsert = "INSERT INTO returns
                      (return_ID, order_detail_ID, customer_ID, 
                      staff_ID, quantity, reason, status, refund_amount, refund_method)
                      VALUES (?, ?, ?, ?, ?, ?, 'Refunded', ?, ?)";
        $stmt = $conn->prepare($sqlInsert);
        $stmt->bind_param(
            "ssssisss",
            $return_ID,
            $order_detail_ID,
            $customer_ID,
            $staff_id,
            $qty,
            $reason,
            $refund_amount,
            $refund_method
        );
        $stmt->execute();
        $stmt->close();

        // add returned quantity back to inventory for the branch
        $pvID = $detail['perfume_volume_ID'];

        $sqlInvCheck = "SELECT inventory_ID, quantity FROM inventory
                        WHERE branch_ID = ? AND perfume_volume_ID = ?";
        $stmt = $conn->prepare($sqlInvCheck);
        $stmt->bind_param("ss", $branch_id, $pvID);
        $stmt->execute();
        $inv = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $newQty = (int)$inv['quantity'] + $qty;
        $sqlInvUpd = "UPDATE inventory 
                    SET quantity = ? 
                    WHERE inventory_ID = ?";
        $stmt = $conn->prepare($sqlInvUpd);
        $stmt->bind_param("is", $newQty, $inv['inventory_ID']);
        $stmt->execute();
        $stmt->close();
    }
    $conn->commit();
    header("Location: employee_returns.php?success=1&order=" . urlencode($order_id));
    exit();

} catch (Exception $e) {
    $conn->rollback();
    header("Location: employee_returns.php?error=" . urlencode($e->getMessage()));
    exit();
}
