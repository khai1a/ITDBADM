<?php
session_start();
require 'db_connect.php';

$customerID = $_SESSION['customer_ID'] ?? null;
if (!$customerID) {
    header("Location: login_customer.php");
    exit;
}

function json_response($arr) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr);
    exit;
}

// currency
$filter_currency = $_GET['currency'] ?? $_SESSION['currency'] ?? 'USD';
$curStmt = $conn->prepare("SELECT fromUSD, currency_sign FROM currencies WHERE currency = ?");
$curStmt->bind_param('s', $filter_currency);
$curStmt->execute();
$curRes = $curStmt->get_result();
$currencyData = $curRes->fetch_assoc() ?? [];
$currencyRate = isset($currencyData['fromUSD']) ? floatval($currencyData['fromUSD']) : 1.0;
$currencySign = $currencyData['currency_sign'] ?? '$';

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
$customerName = $cuRes['first_name'] ?? '';
$pointsUSD = floatval($cuRes['points'] ?? 0.0); // points stored in USD
$customerBirthMonth = !empty($cuRes['birthday']) ? intval(date('m', strtotime($cuRes['birthday']))) : null;
$vatPercent = isset($cuRes['vat_percent']) ? floatval($cuRes['vat_percent']) : 0.12; // default if missing

// cart items
$selectedItemIDs = $_GET['items'] ?? null;
$cartItems = [];
$subtotal = 0.0;

if (!empty($selectedItemIDs) && is_array($selectedItemIDs)) {
    $placeholders = implode(',', array_fill(0, count($selectedItemIDs), '?'));
    $types = str_repeat('s', count($selectedItemIDs));
    $sql = "
        SELECT ci.cart_item_ID, pv.perfume_volume_ID, pv.perfume_ID, pv.volume, pv.selling_price, p.perfume_name, ci.quantity
        FROM cart_items ci
        JOIN perfume_volume pv ON ci.perfume_volume_ID = pv.perfume_volume_ID
        JOIN perfumes p ON pv.perfume_ID = p.perfume_ID
        JOIN cart c ON ci.cart_ID = c.cart_ID
        WHERE ci.cart_item_ID IN ($placeholders) AND c.customer_ID = ?
    ";
    $stmt = $conn->prepare($sql);
    $bind_names = [];
    foreach ($selectedItemIDs as $k => $id) $bind_names[] = $selectedItemIDs[$k];
    $bind_names[] = $customerID;
    $typesAll = $types . 's';
    $stmt->bind_param($typesAll, ...$bind_names);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $row['converted_unit'] = floatval($row['selling_price']) * $currencyRate;
        $row['converted_total'] = $row['converted_unit'] * intval($row['quantity']);
        $subtotal += $row['converted_total'];
        $cartItems[] = $row;
    }
    $stmt->close();
} else {
    $stmt = $conn->prepare("
        SELECT ci.cart_item_ID, pv.perfume_volume_ID, pv.perfume_ID, pv.volume, pv.selling_price, p.perfume_name, ci.quantity
        FROM cart_items ci
        JOIN perfume_volume pv ON ci.perfume_volume_ID = pv.perfume_volume_ID
        JOIN perfumes p ON pv.perfume_ID = p.perfume_ID
        JOIN cart c ON ci.cart_ID = c.cart_ID
        WHERE c.customer_ID = ?
    ");
    $stmt->bind_param("s", $customerID);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $row['converted_unit'] = floatval($row['selling_price']) * $currencyRate;
        $row['converted_total'] = $row['converted_unit'] * intval($row['quantity']);
        $subtotal += $row['converted_total'];
        $cartItems[] = $row;
    }
    $stmt->close();
}

// VAT
$vatAmount = $subtotal * $vatPercent;
$total = $subtotal + $vatAmount;

if (!isset($_SESSION['checkout'])) {
    $_SESSION['checkout'] = [
        'discount_applied' => null,
        'discount_percent' => 0.0,
        'points_redeemed_amount' => 0.0,
        'points_used_usd' => 0.0
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    header('Content-Type: application/json; charset=utf-8');

    // claim discount
    if ($action === 'claim_discount') {

        // cannot have more than 1 discount
        if (!empty($_SESSION['checkout']['discount_applied'])) {
            json_response(['status'=>'error','message'=>'A discount was already reserved — only one discount is allowed per transaction.']);
        }
        if (!empty($_SESSION['checkout']['points_redeemed_amount']) && floatval($_SESSION['checkout']['points_redeemed_amount']) > 0) {
            json_response(['status'=>'error','message'=>'Points already reserved — cannot apply a discount.']);
        }

        $discountCode = strtoupper(trim($_POST['discount_code'] ?? ''));
        if ($discountCode === '') {
            json_response(['status'=>'error','message'=>'Please enter a discount code.']);
        }

        // check disc record in db
        $dStmt = $conn->prepare("SELECT discount_percent, valid_from, valid_until, customer_ID, birth_month FROM discounts WHERE discount_code = ?");
        $dStmt->bind_param("s", $discountCode);
        $dStmt->execute();
        $dRes = $dStmt->get_result()->fetch_assoc() ?? null;
        $dStmt->close();

        if (!$dRes) {
            json_response(['status'=>'error','message'=>'Discount code does not exist.']);
        }

        // date validation
        $today = date('Y-m-d');
        if (isset($dRes['valid_from']) && strtotime($dRes['valid_from']) > strtotime($today)) {
            json_response(['status'=>'error','message'=>'This discount is not yet valid.']);
        }
        if (isset($dRes['valid_until']) && strtotime($dRes['valid_until']) < strtotime($today)) {
            json_response(['status'=>'error','message'=>'Discount code has expired.']);
        }

        // customer specific discounts
        if (!empty($dRes['customer_ID']) && $dRes['customer_ID'] !== $customerID) {
            json_response(['status'=>'error','message'=>'This discount is not valid for your account.']);
        }

        // bday discount
        if (!empty($dRes['birth_month'])) {
            $discountMonth = intval($dRes['birth_month']);
            if ($customerBirthMonth === null || $discountMonth !== $customerBirthMonth) {
                json_response(['status'=>'error','message'=>'This discount is only valid for customers born in month '.$discountMonth.'.']);
            }
        }

        // check if claimed
        $checkStmt = $conn->prepare("SELECT claim_ID FROM claimed_discounts WHERE discount_code = ? LIMIT 1");
        $checkStmt->bind_param("s", $discountCode);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();
        $checkStmt->close();
        if ($checkRes && $checkRes->num_rows > 0) {
            // already claimed
            json_response(['status'=>'error','message'=>'This discount code has already been claimed and cannot be reserved.']);
        }

        // no errors
        $_SESSION['checkout']['discount_applied'] = $discountCode;
        $_SESSION['checkout']['discount_percent'] = floatval($dRes['discount_percent'] ?? 0.0);

        json_response([
            'status'=>'success',
            'message'=>'Discount reserved in session. It will be finalized when you confirm the purchase.',
            'discount_percent' => floatval($dRes['discount_percent'] ?? 0.0)
        ]);
    }

    // redeem points
    if ($action === 'redeem_points') {

        // no redeem if in use
        if (!empty($_SESSION['checkout']['discount_applied'])) {
            json_response(['status'=>'error','message'=>'A discount is already reserved — cannot redeem points.']);
        }

        $currentSubtotal = floatval($subtotal);
        if ($currentSubtotal <= 0.0) {
            json_response(['status'=>'error','message'=>'Cart subtotal is zero — nothing to redeem points on.']);
        }

        // convert points to currency chosen
        $pointsConverted = $pointsUSD * $currencyRate;
        if ($pointsConverted <= 0) {
            json_response(['status'=>'error','message'=>'You have no points to redeem.']);
        }

        $maxAllowed = $currentSubtotal * 0.10; // points can only use 10% of subtotal
        $useAmountDisplayed = min($pointsConverted, $maxAllowed);
        $useAmountUSD = $useAmountDisplayed / max(0.00001, $currencyRate);

        $_SESSION['checkout']['points_redeemed_amount'] = floatval(round($useAmountDisplayed, 2));
        $_SESSION['checkout']['points_used_usd'] = floatval(round($useAmountUSD, 6));

        json_response([
            'status'=>'success',
            'message'=>'Points reserved in session. They will be deducted from your account when you confirm the purchase.',
            'points_deduction_display' => round($useAmountDisplayed, 2),
            'points_deduction_currency' => $currencySign,
            'points_redeemed_usd' => round($useAmountUSD, 6)
        ]);
    }

    // remove disc
    if ($action === 'remove_discount') {
        $_SESSION['checkout']['discount_applied'] = null;
        $_SESSION['checkout']['discount_percent'] = 0.0;
        json_response(['status'=>'success','message'=>'Discount reservation removed.']);
    }

    // remove points
    if ($action === 'remove_points') {
        $_SESSION['checkout']['points_redeemed_amount'] = 0.0;
        $_SESSION['checkout']['points_used_usd'] = 0.0;
        json_response(['status'=>'success','message'=>'Points reservation removed.']);
    }

    // reset checkout when page left
    if ($action === 'reset_checkout') {
        $_SESSION['checkout'] = [
            'discount_applied' => null,
            'discount_percent' => 0.0,
            'points_redeemed_amount' => 0.0,
            'points_used_usd' => 0.0
        ];
        // no change sa db
        json_response(['status'=>'success','message'=>'Checkout reset.']);
    }

    json_response(['status'=>'error','message'=>'Invalid action.']);
}

// totals
$appliedDiscount = $_SESSION['checkout']['discount_applied'] ?? null;
$appliedDiscountPercent = floatval($_SESSION['checkout']['discount_percent'] ?? 0.0);
$pointsRedeemedDisplay = floatval($_SESSION['checkout']['points_redeemed_amount'] ?? 0.0);

$displaySubtotal = $subtotal;
$displayDiscountAmount = $appliedDiscountPercent > 0 ? $displaySubtotal * $appliedDiscountPercent : 0.0;
$displayPointsDeduction = $pointsRedeemedDisplay;
$displayTotal = max(0.0, $displaySubtotal - $displayDiscountAmount - $displayPointsDeduction);
$displayVAT = $displayTotal * $vatPercent;
$displayGrandTotal = $displayTotal + $displayVAT;

$pointsConvertedDisplay = $pointsUSD * $currencyRate;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Checkout - Aurum Scents</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="checkout.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>
<body>

<!-- navbar -->
<nav class="navbar navbar-expand-lg shadow-sm">
  <div class="container">
    <a class="navbar-brand" href="customer_home.php">Aurum Scents</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <div class="nav-links-container mx-auto">
        <a class="nav-link active" href="customer_home.php">Home</a>
        <a class="nav-link" href="about_us.php">About Us</a>
        <a class="nav-link" href="buy_here.php">Buy Here</a>
        <a class="nav-link" href="contact_us.php">Contact Us</a>
        <a class="nav-link" href="rating.php">Rate Us</a>
      </div>
      <div class="icons-container">
        <div class="dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
            <i class="fa fa-user"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
            <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
            <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
            <li><a class="dropdown-item" href="points.php">My Points</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="login_customer.php">Log Out</a></li>
          </ul>
        </div>
        <a class="nav-link" href="cart.php"><i class="fa fa-shopping-cart"></i></a>
      </div>
    </div>
  </div>
</nav>

<!-- checkout -->
<section class="checkout-section py-5">
  <div class="container">
    <div class="checkout-box mx-auto">

      <a href="cart.php?currency=<?= urlencode($filter_currency) ?>" class="back-btn">
        <i class="fa fa-arrow-left me-2"></i> Back to Cart
      </a>

      <h2 class="title text-center">Checkout</h2>

      <!-- cart items -->
      <div class="items-box mb-4">
        <h4 class="section-title">Your Items</h4>
        <?php if(count($cartItems) > 0): ?>
            <?php foreach($cartItems as $item): ?>
                <div class="item">
                    <p><?= htmlspecialchars($item['perfume_name']) ?> (x<?= intval($item['quantity']) ?>)</p>
                    <span><?= $currencySign . number_format($item['converted_total'], 2) ?></span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center"><b>Your cart is empty.</b></p>
        <?php endif; ?>
      </div>

      <!-- summary -->
      <div class="summary-box mb-4">
        <h4 class="section-title">Summary</h4>
        <p class="summary-line">Subtotal: <span><?= $currencySign . number_format($displaySubtotal, 2) ?></span></p>

        <?php if ($appliedDiscount && $displayDiscountAmount > 0): ?>
            <p class="summary-line">
              Discount (<?= htmlspecialchars($appliedDiscount) ?>):
              <span>- <?= $currencySign . number_format($displayDiscountAmount, 2) ?></span>
              <button class="remove-btn ms-2" id="removeDiscountBtn">Remove</button>
            </p>
        <?php endif; ?>

        <?php if ($pointsRedeemedDisplay > 0): ?>
            <p class="summary-line">
              Points Deduction:
              <span>- <?= $currencySign . number_format($pointsRedeemedDisplay, 2) ?></span>
              <button class="remove-btn ms-2" id="removePointsBtn">Remove</button>
            </p>
        <?php endif; ?>

        <p class="summary-line">VAT (<?= ($vatPercent*100) ?>%): <span><?= $currencySign . number_format($displayVAT, 2) ?></span></p>
        <p class="summary-line total">Total: <span><?= $currencySign . number_format($displayGrandTotal, 2) ?></span></p>
      </div>

      <!-- points -->
      <div class="points-box mb-3">
        <h4 class="section-title">Your Points</h4>
        <p>Points balance (stored as USD): <strong><?= number_format($pointsUSD, 2) ?> USD</strong></p>
        <p>Converted to <?= htmlspecialchars($filter_currency) ?>: <strong><?= $currencySign . number_format($pointsConvertedDisplay, 2) ?></strong></p>
        <small class="muted">You may redeem up to 10% of the subtotal. Click Redeem to reserve points (they won't be deducted until you confirm).</small>

        <div class="d-flex gap-2 mt-2">
            <button id="redeemPointsBtn" class="apply-btn" <?= (count($cartItems)===0 || $pointsRedeemedDisplay>0 ? 'disabled' : '') ?>>Redeem Max Points</button>
            <div id="pointsFeedback" class="feedback <?= ($pointsRedeemedDisplay>0)?'success':'' ?>">
                <?php if ($pointsRedeemedDisplay>0): ?>
                    Reserved: <?= $currencySign . number_format($pointsRedeemedDisplay,2) ?> (will be deducted on confirmation)
                <?php endif; ?>
            </div>
        </div>
      </div>

      <!-- discount -->
      <div class="discount-box mb-3">
        <h4 class="section-title">Discount Code</h4>
        <div class="d-flex gap-2">
          <input type="text" id="discountCode" class="discount-input" placeholder="Enter discount code" <?= ($appliedDiscount? 'disabled':'' ) ?>>
          <button id="applyDiscount" class="apply-btn" <?= (count($cartItems)===0 || $appliedDiscount? 'disabled':'') ?>>Apply</button>
        </div>
        <div id="discountFeedback" class="feedback <?= ($appliedDiscount? 'success':'') ?>">
            <?php if ($appliedDiscount): ?>
                Reserved: <?= htmlspecialchars($appliedDiscount) ?> (<?= ($appliedDiscountPercent*100) ?>% ) — will be finalized on purchase.
            <?php endif; ?>
        </div>
      </div>

      <!-- payment only card -->
      <div class="payment-box mb-3">
        <h4 class="section-title">Payment Method</h4>
        <label class="payment-option">
          <input type="radio" name="payment_method" value="card" checked>
          <span>Card</span>
        </label>
      </div>

      <!-- confirm to oc para macarry over values -->
      <form id="confirmForm" method="POST" action="order_confirmation.php">
        <?php if (!empty($cartItems)): foreach($cartItems as $item): ?>
            <input type="hidden" name="items[]" value="<?= htmlspecialchars($item['cart_item_ID']) ?>">
            <input type="hidden" name="qtys[]" value="<?= intval($item['quantity']) ?>">
        <?php endforeach; endif; ?>
        
         <!-- currency -->
        <input type="hidden" name="currency" value="<?= htmlspecialchars($filter_currency) ?>">

        <!-- discount -->
        <input type="hidden" name="discount_code" value="<?= htmlspecialchars($appliedDiscount ?? '') ?>">
        <input type="hidden" name="discount_percent" value="<?= number_format($appliedDiscountPercent, 6, '.', '') ?>">

        <!-- points -->
        <input type="hidden" name="points_redeemed_amount" value="<?= number_format($pointsRedeemedDisplay, 2, '.', '') ?>">
        <input type="hidden" name="points_used_usd" value="<?= number_format($_SESSION['checkout']['points_used_usd'] ?? 0.0, 6, '.', '') ?>">

        <div class="button-group">
            <a href="cart.php?currency=<?= urlencode($filter_currency) ?>" class="back-btn-mobile btn">Back to Cart</a>
            <button type="submit" class="confirm-btn btn btn-primary" id="confirmPurchaseBtn" <?= (count($cartItems)===0 ? 'disabled' : '') ?>>Confirm Purchase</button>
        </div>
      </form>

    </div>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function setFeedback(elem, text, type='error') {
    elem.textContent = text;
    elem.classList.remove('error','success');
    elem.classList.add(type === 'error' ? 'error' : 'success');
}

// apply discount
const applyBtn = document.getElementById('applyDiscount');
const discountInput = document.getElementById('discountCode');
const discountFeedback = document.getElementById('discountFeedback');
const redeemBtn = document.getElementById('redeemPointsBtn');
const pointsFeedback = document.getElementById('pointsFeedback');

if (applyBtn) {
    applyBtn.addEventListener('click', () => {
        const code = discountInput.value.trim();
        if (!code) {
            setFeedback(discountFeedback, 'Enter a discount code.', 'error');
            return;
        }
        if (redeemBtn && redeemBtn.disabled && redeemBtn.dataset.redeemed === '1') {
            setFeedback(discountFeedback, 'Points reserved — cannot apply a discount.', 'error');
            return;
        }
        const data = new URLSearchParams();
        data.append('action','claim_discount');
        data.append('discount_code', code);
        fetch('checkout.php', { method:'POST', body: data })
            .then(r => r.json()).then(resp => {
                if (resp.status === 'success') {
                    setFeedback(discountFeedback, resp.message || 'Discount reserved.', 'success');
                    discountInput.disabled = true;
                    applyBtn.disabled = true;
                    if (redeemBtn) redeemBtn.disabled = true;
                    setTimeout(() => location.reload(), 700);
                } else {
                    setFeedback(discountFeedback, resp.message || 'Invalid discount', 'error');
                }
            }).catch(()=> setFeedback(discountFeedback, 'Server error.', 'error'));
    });
}

// redeem points
if (redeemBtn) {
    redeemBtn.addEventListener('click', () => {
        if (discountInput && discountInput.disabled) {
            setFeedback(pointsFeedback, 'A discount is reserved — cannot redeem points.', 'error');
            return;
        }
        const data = new URLSearchParams();
        data.append('action','redeem_points');
        fetch('checkout.php', { method:'POST', body: data })
            .then(r => r.json()).then(resp => {
                if (resp.status === 'success') {
                    setFeedback(pointsFeedback, resp.message || 'Points reserved.', 'success');
                    redeemBtn.disabled = true;
                    redeemBtn.dataset.redeemed = '1';
                    if (discountInput) discountInput.disabled = true;
                    if (applyBtn) applyBtn.disabled = true;
                    setTimeout(() => location.reload(), 700);
                } else {
                    setFeedback(pointsFeedback, resp.message || 'Unable to redeem points', 'error');
                }
            }).catch(()=> setFeedback(pointsFeedback, 'Server error.', 'error'));
    });
}

// remove discount
const removeDiscountBtn = document.getElementById('removeDiscountBtn');
if (removeDiscountBtn) {
    removeDiscountBtn.addEventListener('click', () => {
        const data = new URLSearchParams();
        data.append('action','remove_discount');
        fetch('checkout.php', { method:'POST', body: data })
            .then(r => r.json()).then(resp => {
                if (resp.status === 'success') {
                    location.reload();
                } else {
                    alert(resp.message || 'Unable to remove discount');
                }
            }).catch(()=> alert('Server error'));
    });
}

// remove points
const removePointsBtn = document.getElementById('removePointsBtn');
if (removePointsBtn) {
    removePointsBtn.addEventListener('click', () => {
        const data = new URLSearchParams();
        data.append('action','remove_points');
        fetch('checkout.php', { method:'POST', body: data })
            .then(r => r.json()).then(resp => {
                if (resp.status === 'success') {
                    location.reload();
                } else {
                    alert(resp.message || 'Unable to remove points');
                }
            }).catch(()=> alert('Server error'));
    });
}

// reset checkout
window.addEventListener('unload', function () {
    try {
        const url = 'checkout.php';
        const params = new URLSearchParams();
        params.append('action','reset_checkout');
        // send as beacon
        if (navigator.sendBeacon) {
            const blob = new Blob([params.toString()], { type: 'application/x-www-form-urlencoded' });
            navigator.sendBeacon(url, blob);
        } else {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', url, false);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send(params.toString());
        }
    } catch (e) {
    }
});
</script>
</body>
</html>

