<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['customer_ID'])) {
    header("Location: login_customer.php");
    exit;
}

$customerID = $_SESSION['customer_ID'];

// POST data
$cartItemIDs = $_POST['items'] ?? [];
$qtys = $_POST['qtys'] ?? [];
$currency = $_POST['currency'] ?? 'USD';

// discount & points from POST
$discountCode = $_POST['discount_code'] ?? ($_SESSION['checkout']['discount_applied'] ?? null);
$discountPercent = floatval($_POST['discount_percent'] ?? ($_SESSION['checkout']['discount_percent'] ?? 0.0));
$pointsToRedeem = floatval($_POST['points_used_usd'] ?? ($_SESSION['checkout']['points_used_usd'] ?? 0.0));

$discountCode = trim($discountCode);
if ($discountCode === '') $discountCode = null;

// currency rate tas symbol 
$currencyRate = 1.0;
$currencySign = '$';
$curStmt = $conn->prepare("SELECT fromUSD, currency_sign FROM currencies WHERE currency = ?");
$curStmt->bind_param('s', $currency);
$curStmt->execute();
$curRes = $curStmt->get_result();
if ($curRes && $curRes->num_rows) {
    $row = $curRes->fetch_assoc();
    $currencyRate = floatval($row['fromUSD']);
    $currencySign = $row['currency_sign'];
}
$curStmt->close();

// customer info tas VAT
$cuStmt = $conn->prepare("
    SELECT c.first_name, c.points, c.birthday, c.country_ID, co.vat_percent
    FROM customers c
    LEFT JOIN countries co ON c.country_ID = co.country_ID
    WHERE c.customer_ID = ?
");
$cuStmt->bind_param("s", $customerID);
$cuStmt->execute();
$cuRes = $cuStmt->get_result()->fetch_assoc() ?? [];
$cuStmt->close();

$customerName = $cuRes['first_name'] ?? '';
$pointsUSD = floatval($cuRes['points'] ?? 0);
$vatPercent = floatval($cuRes['vat_percent'] ?? 0.12);

// mga items nasa checkout
$checkoutItems = [];
foreach ($cartItemIDs as $i => $cartItemID) {
    $quantity = intval($qtys[$i] ?? 1);
    $stmt = $conn->prepare("
        SELECT pv.perfume_volume_ID, pv.selling_price
        FROM cart_items ci
        JOIN perfume_volume pv ON ci.perfume_volume_ID = pv.perfume_volume_ID
        WHERE ci.cart_item_ID = ? AND ci.cart_ID = (SELECT cart_ID FROM cart WHERE customer_ID = ?)
    ");
    $stmt->bind_param('ss', $cartItemID, $customerID);
    $stmt->execute();
    $itemData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($itemData) {
        $checkoutItems[] = [
            'cart_item_ID' => $cartItemID,
            'perfume_volume_ID' => $itemData['perfume_volume_ID'],
            'unit_price' => floatval($itemData['selling_price']),
            'quantity' => $quantity
        ];
    }
}

if (empty($checkoutItems)) die("No items in cart.");

// total in usd
$subtotalUSD = 0;
foreach ($checkoutItems as $item) $subtotalUSD += $item['unit_price'] * $item['quantity'];
$discountAmountUSD = $subtotalUSD * $discountPercent;
$subtotalAfterDiscount = max(0, $subtotalUSD - $discountAmountUSD - $pointsToRedeem);
$vatAmountUSD = $subtotalAfterDiscount * $vatPercent;
$orderTotalUSD = $subtotalAfterDiscount + $vatAmountUSD;

// convert total to chosen currency
$displaySubtotal = $subtotalUSD * $currencyRate;
$displayDiscount = $discountAmountUSD * $currencyRate;
$displayPoints = $pointsToRedeem * $currencyRate;
$displayVAT = $vatAmountUSD * $currencyRate;
$displayTotal = $orderTotalUSD * $currencyRate;

// order ID
function generateOrderID($conn) {
    $res = $conn->query("SELECT order_ID FROM orders ORDER BY order_ID DESC LIMIT 1");
    $lastID = $res->fetch_assoc()['order_ID'] ?? null;
    $num = $lastID ? intval(substr($lastID, 1)) + 1 : 1;
    return 'O' . str_pad($num, 5, '0', STR_PAD_LEFT);
}

// order details ID
function generateOrderDetailID($conn) {
    $res = $conn->query("SELECT order_detail_ID FROM order_details ORDER BY order_detail_ID DESC LIMIT 1");
    $lastID = $res->fetch_assoc()['order_detail_ID'] ?? null;
    $num = $lastID ? intval(substr($lastID, 2)) + 1 : 1;
    return 'OD' . str_pad($num, 6, '0', STR_PAD_LEFT);
}

// payment ID
function generatePaymentID($conn) {
    $res = $conn->query("SELECT payment_ID FROM payments ORDER BY payment_ID DESC LIMIT 1");
    $lastID = $res->fetch_assoc()['payment_ID'] ?? null;
    $num = $lastID ? intval(substr($lastID, 2)) + 1 : 1;
    return 'PM' . str_pad($num, 4, '0', STR_PAD_LEFT);
}

// points_transactions ID if used
function generatePointsTransactionID($conn) {
    $res = $conn->query("SELECT transaction_ID FROM points_transactions ORDER BY transaction_ID DESC LIMIT 1");
    $lastID = $res->fetch_assoc()['transaction_ID'] ?? null;
    $num = $lastID ? intval(substr($lastID, 2)) + 1 : 1;
    return 'PT' . str_pad($num, 5, '0', STR_PAD_LEFT);
}

// claimed discounts ID if used
function generateClaimID($conn) {
    $res = $conn->query("SELECT claim_ID FROM claimed_discounts ORDER BY claim_ID DESC LIMIT 1");
    $lastID = $res->fetch_assoc()['claim_ID'] ?? null;
    $num = $lastID ? intval(substr($lastID, 2)) + 1 : 1;
    return 'CD' . str_pad($num, 6, '0', STR_PAD_LEFT);
}

// transac begins here
$conn->begin_transaction();

try {
    $orderID = generateOrderID($conn);

    // add order to db
    $insOrder = $conn->prepare("
        INSERT INTO orders
        (order_ID, customer_ID, order_total, currency, discount_code, discount_percent, order_type)
        VALUES (?, ?, ?, ?, ?, ?, 'Online')
    ");
    $insOrder->bind_param('ssdssd', $orderID, $customerID, $displayTotal, $currency, $discountCode, $discountPercent);
    $insOrder->execute();
    $insOrder->close();

    // add order deets to db
    foreach ($checkoutItems as $item) {
        $orderDetailID = generateOrderDetailID($conn);
        $insOD = $conn->prepare("
            INSERT INTO order_details
            (order_detail_ID, order_ID, perfume_volume_ID, quantity, unit_price, currency)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $unitPriceInCurrency = $item['unit_price'] * $currencyRate;
        $insOD->bind_param('sssids', $orderDetailID, $orderID, $item['perfume_volume_ID'], $item['quantity'], $unitPriceInCurrency, $currency);
        $insOD->execute();
        $insOD->close();
    }

    // add payment record
    $paymentID = generatePaymentID($conn);
    $insPay = $conn->prepare("
        INSERT INTO payments
        (payment_ID, amount, method, status, order_ID, customer_ID)
        VALUES (?, ?, 'Card', 'Processing', ?, ?)
    ");
    $insPay->bind_param('sdss', $paymentID, $displayTotal, $orderID, $customerID);
    $insPay->execute();
    $insPay->close();

    // update points
    if ($pointsToRedeem > 0) {
        $pointsInt = intval(round($pointsToRedeem));
        $updPoints = $conn->prepare("UPDATE customers SET points = points - ? WHERE customer_ID = ?");
        $updPoints->bind_param('is', $pointsInt, $customerID);
        $updPoints->execute();
        $updPoints->close();

        $transactionID = generatePointsTransactionID($conn);
        $insPT = $conn->prepare("
            INSERT INTO points_transactions
            (transaction_ID, customer_ID, order_ID, points_change, transaction_type)
            VALUES (?, ?, ?, ?, 'Redeemed')
        ");
        $insPT->bind_param('sssi', $transactionID, $customerID, $orderID, $pointsInt);
        $insPT->execute();
        $insPT->close();
    }

    // claim discount
    if ($discountCode) {
        $claimID = generateClaimID($conn);
        $insClaim = $conn->prepare("INSERT INTO claimed_discounts (claim_ID, discount_code, customer_ID) VALUES (?, ?, ?)");
        $insClaim->bind_param('sss', $claimID, $discountCode, $customerID);
        $insClaim->execute();
        $insClaim->close();
    }

    // clear cart
    $cartIDRes = $conn->prepare("SELECT cart_ID FROM cart WHERE customer_ID = ?");
    $cartIDRes->bind_param('s', $customerID);
    $cartIDRes->execute();
    $cartID = $cartIDRes->get_result()->fetch_assoc()['cart_ID'] ?? null;
    $cartIDRes->close();
    if ($cartID) $conn->query("DELETE FROM cart_items WHERE cart_ID='$cartID'");

    $conn->commit();
    unset($_SESSION['checkout']);

} catch (Exception $e) {
    $conn->rollback();
    die("Transaction failed: " . $e->getMessage());
}

// resibo
$receiptItems = [];
$res = $conn->prepare("
    SELECT od.quantity, od.unit_price, p.perfume_name
    FROM order_details od
    JOIN perfume_volume pv ON od.perfume_volume_ID = pv.perfume_volume_ID
    JOIN perfumes p ON pv.perfume_ID = p.perfume_ID
    WHERE od.order_ID = ?
");
$res->bind_param('s', $orderID);
$res->execute();
$result = $res->get_result();
while ($row = $result->fetch_assoc()) $receiptItems[] = $row;
$res->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order Confirmation - Aurum Scents</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="order_confirmation.css">
</head>
<body>

<section class="confirmation-section py-5">
    <div class="container">
        <h2 class="text-center mb-4">Thank You for Your Purchase!</h2>
        <p class="text-center mb-5">Your order has been successfully placed. Below is your receipt:</p>

        <div class="receipt p-4 mx-auto">
            <h4 class="mb-3">Order Receipt</h4>
            <?php foreach($receiptItems as $item): ?>
                <div class="receipt-item d-flex justify-content-between mb-2">
                    <span><?= htmlspecialchars($item['perfume_name']) ?> x<?= $item['quantity'] ?></span>
                    <span><?= $currencySign . number_format($item['unit_price'] * $currencyRate * $item['quantity'], 2) ?></span>
                </div>
            <?php endforeach; ?>
            <hr>
            <div class="d-flex justify-content-between mb-2">
                <strong>Subtotal</strong>
                <strong><?= $currencySign . number_format($displaySubtotal, 2) ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <strong>Discount</strong>
                <strong>-<?= $currencySign . number_format($displayDiscount, 2) ?></strong>
            </div>
            <?php if($pointsToRedeem > 0): ?>
            <div class="d-flex justify-content-between mb-2">
                <strong>Points Redeemed</strong>
                <strong>-<?= $currencySign . number_format($displayPoints, 2) ?></strong>
            </div>
            <?php endif; ?>
            <div class="d-flex justify-content-between mb-2">
                <strong>VAT</strong>
                <strong><?= $currencySign . number_format($displayVAT, 2) ?></strong>
            </div>
            <hr>
            <div class="d-flex justify-content-between">
                <strong>Total</strong>
                <strong><?= $currencySign . number_format($displayTotal, 2) ?></strong>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="customer_home.php" class="btn btn-home">Return to Home</a>
        </div>
    </div>
</section>
</body>
</html>


