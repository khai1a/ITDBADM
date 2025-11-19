<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include('../db_connect.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Branch Employee') {
    header("Location: ../login_staff-admin.php");
    exit();
}

$employee_username = $_SESSION['username'];
$branch_id         = $_SESSION['branch_id'];
$employee_role     = $_SESSION['role'];
$employee_id       = $_SESSION['user_id'];

// branch info
$branch_address  = "";
$branch_currency = "";
$vat_percent     = 0;
$currency_sign   = "";

$query = "SELECT b.address, c.currency, c.vat_percent, cur.currency_sign
          FROM branches b
          JOIN countries c ON b.country_ID = c.country_ID
          JOIN currencies cur ON c.currency = cur.currency
          WHERE b.branch_ID = ?";

if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("s", $branch_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $branch        = $res->fetch_assoc();
        $branch_address  = $branch['address'];
        $branch_currency = $branch['currency'];
        $vat_percent     = (float)$branch['vat_percent'];
        $currency_sign   = $branch['currency_sign'];
    }
    $stmt->close();
}

// helper function 1: generate ids in correct format
function generateID($conn, $table, $column, $prefix, $padLength) {
    $sql = "
      SELECT $column
      FROM $table
      ORDER BY CAST(SUBSTRING($column, LENGTH('$prefix') + 1) AS UNSIGNED) DESC
      LIMIT 1
    ";
    $res = $conn->query($sql);
    if ($res && $row = $res->fetch_assoc()) {
        $lastID = $row[$column];
        $num    = (int)substr($lastID, strlen($prefix));
        $newNum = $num + 1;
    } else {
        $newNum = 1;
    }
    return $prefix . str_pad($newNum, $padLength, "0", STR_PAD_LEFT);
}

// helper function 2: find customer by mobile
function findCustomerByMobile($conn, $mobile) {
    $q = "SELECT customer_ID, first_name, last_name, points, birthday
          FROM customers WHERE mobile_number = ?";
    if ($s = $conn->prepare($q)) {
        $s->bind_param("s", $mobile);
        $s->execute();
        $r = $s->get_result();
        if ($r && $r->num_rows > 0) {
            $row = $r->fetch_assoc();
            $s->close();
            return $row;
        }
        $s->close();
    }
    return null;
}

// AJAX endpoints

// discount lookup (called by js when staff enters a discount code)
if (isset($_GET['action']) && $_GET['action'] === 'discount_lookup') {
    $code = $_GET['code'] ?? '';
    $customerId = $_GET['customer_id'] ?? null;
    $resp = ['valid' => false, 'error' => 'Invalid discount'];

    if (!$customerId) {
        $resp['error'] = 'Customer required for discount';
        header('Content-Type: application/json');
        echo json_encode($resp);
        exit();
    }

    if ($code) {
        $sql = "SELECT discount_percent, valid_until, valid_from
                FROM discounts WHERE discount_code = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $code);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $row = $res->fetch_assoc()) {
                $now   = time();
                $from  = strtotime($row['valid_from']);
                $until = strtotime($row['valid_until']);

                if ($from <= $now && $until >= $now) {
                    $check = $conn->prepare("SELECT 1 FROM claimed_discounts WHERE discount_code = ? AND customer_ID = ?");
                    $check->bind_param("ss", $code, $customerId);
                    $check->execute();
                    $claimedRes = $check->get_result();
                    $claimed = $claimedRes && $claimedRes->num_rows > 0;
                    $check->close();

                    if (!$claimed) {
                        $resp = [
                            'valid'   => true,
                            'percent' => (float)$row['discount_percent']
                        ];
                    } else {
                        $resp['error'] = 'Discount already claimed';
                    }
                } else {
                    $resp['error'] = 'Discount not valid at this time';
                }
            }
            $stmt->close();
        }
    } else {
        $resp['error'] = 'Discount code required';
    }

    header('Content-Type: application/json');
    echo json_encode($resp);
    exit();
}

// customer lookup (called by js when staff enter a mobile num)
if (isset($_GET['action']) && $_GET['action'] === 'lookup') {
    $mobile = $_GET['mobile'] ?? '';
    $cust = null;
    if ($mobile !== "") {
        $cust = findCustomerByMobile($conn, $mobile);
    }
    if ($cust) {
        echo json_encode([
            'found'    => true,
            'customer_ID' => $cust['customer_ID'],
            'name'     => $cust['first_name'].' '.$cust['last_name'],
            'points'   => (int)$cust['points'],
            'birthday' => $cust['birthday']
        ]);
    } else {
        echo json_encode(['found' => false]);
    }
    exit();
}

// perfume options in branch w/ positive stock
$perfumeOptions = [];
$rateQ = $conn->prepare("SELECT fromUSD FROM currencies WHERE currency = ?");
$rateQ->bind_param("s", $branch_currency);
$rateQ->execute();
$rateRes = $rateQ->get_result();
$branchRate = 1.0;
if ($rateRes && $row = $rateRes->fetch_assoc()) {
    $branchRate = (float)$row['fromUSD'];
}
$rateQ->close();

$sql = "SELECT pv.perfume_volume_ID, p.perfume_name, pv.volume, pv.selling_price, i.quantity
        FROM inventory i
        JOIN perfume_volume pv ON i.perfume_volume_ID = pv.perfume_volume_ID
        JOIN perfumes p ON pv.perfume_ID = p.perfume_ID
        WHERE i.branch_ID = ?
          AND i.quantity > 0
        ORDER BY p.perfume_name, pv.volume";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $branch_id);
$stmt->execute();
$res = $stmt->get_result();

$perfumeOptions = [];
while ($row = $res->fetch_assoc()) {
    $row['selling_price_branch'] = (float)$row['selling_price'] * $branchRate;
    $perfumeOptions[] = $row;
}
$stmt->close();


// POST form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST inputs
    $mobile         = $_POST['mobile_number'] ?? '';
    $posted_customer_id = $_POST['customer_id'] ?? '';
    $discount_code  = trim($_POST['discount_code'] ?? '');
    $redeem_points  = (int)($_POST['redeem_points'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? '';
    $cash_given     = (float)($_POST['cash_given'] ?? 0);

    $customer_id = $posted_customer_id ?: null;

    if (!$customer_id && !empty($mobile)) {
        $cust = findCustomerByMobile($conn, $mobile);
        if ($cust) {
            $customer_id = $cust['customer_ID'];
            $available_points = (int)$cust['points'];
        } else {
            $available_points = 0;
        }
    } else {
        if ($customer_id) {
            $c = $conn->prepare("SELECT points FROM customers WHERE customer_ID = ?");
            $c->bind_param("s", $customer_id);
            $c->execute();
            $cr = $c->get_result();
            if ($cr && $crow = $cr->fetch_assoc()) {
                $available_points = (int)$crow['points'];
            } else {
                $available_points = 0;
            }
            $c->close();
        } else {
            $available_points = 0;
        }
    }

    // compute subtotal in branch currency
    $items    = $_POST['items'] ?? [];
    $subtotal = 0.0;
    foreach ($items as $it) {
        if (empty($it['perfume_volume_ID'])) continue;
        $pv_id = $it['perfume_volume_ID'];
        $qty   = max(0, (int)$it['quantity']);

        $q = "SELECT selling_price FROM perfume_volume WHERE perfume_volume_ID = ?";
        if ($s = $conn->prepare($q)) {
            $s->bind_param("s", $pv_id);
            $s->execute();
            $r = $s->get_result();
            if ($r && $row = $r->fetch_assoc()) {
                $priceUSD = (float)$row['selling_price'];
                $priceBranch = $priceUSD * $branchRate;
                $subtotal += $priceBranch * $qty;
            }
            $s->close();
        }
    }

    if ($redeem_points > 0 && $discount_code !== '') {
        echo "<p style='color:red'>Order failed: Cannot use both points and discount code.</p>";
        exit();
    }

    $redeemValueBranch = 0.0;
    $discount_percent = 0.0;
    $discountValueBranch = 0.0;

    if ($customer_id && $redeem_points > 0) {
        if ($redeem_points > $available_points) {
            echo "<p style='color:red'>Order failed: Insufficient points for requested redemption.</p>";
            exit();
        }
        $maxRedeemValueBranch = $subtotal * 0.10;
        $redeemValueBranch = $redeem_points * $branchRate;
        if ($redeemValueBranch > $maxRedeemValueBranch) {
            $maxRedeemPoints = (int)floor($maxRedeemValueBranch / $branchRate);
            $redeem_points = min($redeem_points, $maxRedeemPoints);
            $redeemValueBranch = $redeem_points * $branchRate;
        }
    } else {
        $redeem_points = 0;
        $redeemValueBranch = 0.0;
    }

    // discount also only for registered customers
    if ($customer_id && $discount_code !== '') {
        $dq = "SELECT discount_percent, valid_from, valid_until
               FROM discounts WHERE discount_code = ?";
        if ($ds = $conn->prepare($dq)) {
            $ds->bind_param("s", $discount_code);
            $ds->execute();
            $dr = $ds->get_result();
            if ($dr && $drow = $dr->fetch_assoc()) {
                $now   = time();
                $from  = strtotime($drow['valid_from']);
                $until = strtotime($drow['valid_until']);

                if ($from <= $now && $until >= $now) {
                    // check if already claimed
                    $check = $conn->prepare("SELECT 1 FROM claimed_discounts WHERE discount_code = ? AND customer_ID = ?");
                    $check->bind_param("ss", $discount_code, $customer_id);
                    $check->execute();
                    $claimedRes = $check->get_result();
                    $claimed = $claimedRes && $claimedRes->num_rows > 0;
                    $check->close();

                    if ($claimed) {
                        echo "<p style='color:red'>Order failed: Discount already claimed by this customer.</p>";
                        exit();
                    }
                    $discount_percent = (float)$drow['discount_percent'];
                } else {
                    echo "<p style='color:red'>Order failed: Discount expired or not yet valid.</p>";
                    exit();
                }
            }
            $ds->close();
        }
    } else {
        $discount_code = null;
        $discount_percent = 0.0;
    }

    // apply points or discount (before tax)
    $adjustedSubtotal = $subtotal;
    if ($redeemValueBranch > 0) {
        $adjustedSubtotal -= $redeemValueBranch;
    } elseif ($discount_percent > 0) {
        $discountValueBranch = $adjustedSubtotal * $discount_percent;
        $adjustedSubtotal -= $discountValueBranch;
    }

    // tax + grand total
    $tax = $adjustedSubtotal * $vat_percent;
    $grand_total = $adjustedSubtotal + $tax;

    // DB transaction 
    try {
        // miht change isolation lvl
        $conn->query("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
        $conn->begin_transaction();

        $order_id   = generateID($conn, "orders", "order_ID", "O", 5);
        $payment_id = generateID($conn, "payments", "payment_ID", "PM", 5);

        if ($payment_method === 'Cash') {
            if ($cash_given < $grand_total) {
                throw new Exception("Cash given is insufficient for the total amount.");
            }
            $cash_change = $cash_given - $grand_total;
        }

        $db_customer_id = $customer_id ?: null;
        $db_discount_code = $discount_code ?: null;

        $sql = "INSERT INTO orders
            (order_ID, customer_ID, order_status, order_total, currency, order_type, branch_ID, discount_code, discount_percent)
            VALUES (?, ?, 'Completed', ?, ?, 'Walk-in', ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Prepare failed for orders: " . $conn->error);

        $stmt->bind_param(
            "ssdsssd",
            $order_id,
            $db_customer_id,
            $grand_total,
            $branch_currency,
            $branch_id,
            $db_discount_code,
            $discount_percent
        );
        if (!$stmt->execute()) throw new Exception($stmt->error);
        $stmt->close();

        foreach ($items as $it) {
            if (empty($it['perfume_volume_ID'])) continue;
            $od_id = generateID($conn, "order_details", "order_detail_ID", "OD", 6);
            $pv_id = $it['perfume_volume_ID'];
            $qty   = max(0, (int)$it['quantity']);

            $iq = "SELECT i.quantity, pv.selling_price
                   FROM inventory i
                   JOIN perfume_volume pv ON pv.perfume_volume_ID = i.perfume_volume_ID
                   WHERE i.branch_ID = ? AND i.perfume_volume_ID = ? FOR UPDATE";
            $is = $conn->prepare($iq);
            $is->bind_param("ss", $branch_id, $pv_id);
            $is->execute();
            $ir = $is->get_result();
            if (!$ir || $ir->num_rows === 0) {
                throw new Exception("Inventory record not found for selected item.");
            }
            $invRow = $ir->fetch_assoc();
            $is->close();

            if ((int)$invRow['quantity'] < $qty) {
                throw new Exception("Insufficient stock for selected item.");
            }

            $unit_price_usd = (float)$invRow['selling_price'];
            $unit_price_branch = $unit_price_usd * $branchRate;

            $sql = "INSERT INTO order_details
                    (order_detail_ID, order_ID, perfume_volume_ID, quantity, unit_price, currency)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssids", $od_id, $order_id, $pv_id, $qty, $unit_price_branch, $branch_currency);
            if (!$stmt->execute()) throw new Exception($stmt->error);
            $stmt->close();
        }

        $sql = "INSERT INTO payments
                (payment_ID, amount, method, status, order_ID, customer_ID)
                VALUES (?, ?, ?, 'Received', ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdsss", $payment_id, $grand_total, $payment_method, $order_id, $db_customer_id);
        if (!$stmt->execute()) throw new Exception($stmt->error);
        $stmt->close();

        // points transactions (redeem only since earn is via trigger)
        if ($customer_id && $redeem_points > 0) {
            $pt_id = generateID($conn, "points_transactions", "transaction_ID", "PT", 5);
            $sql = "INSERT INTO points_transactions
                    (transaction_ID, customer_ID, order_ID, points_change, transaction_type)
                    VALUES (?, ?, ?, ?, 'Redeemed')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $pt_id, $customer_id, $order_id, $redeem_points);
            if (!$stmt->execute()) throw new Exception($stmt->error);
            $stmt->close();

            $up = $conn->prepare("UPDATE customers
                                  SET points = points - ?
                                  WHERE customer_ID = ? AND points >= ?");
            $up->bind_param("isi", $redeem_points, $customer_id, $redeem_points);
            if (!$up->execute() || $up->affected_rows === 0) {
                throw new Exception("Insufficient points for redemption.");
            }
            $up->close();
        }

        if ($customer_id && $discount_code) {
            $claim_id = generateID($conn, "claimed_discounts", "claim_ID", "CD", 5);
            $sql = "INSERT INTO claimed_discounts
                    (claim_ID, discount_code, customer_ID)
                    VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $claim_id, $discount_code, $customer_id);
            if (!$stmt->execute()) {
                if ($conn->errno == 1062) {
                    throw new Exception("Discount already claimed by this customer.");
                }
                throw new Exception($stmt->error ?: "Discount claim insert failed.");
            }
            $stmt->close();
        }
        $conn->commit();
        echo "<script>alert('Order success! Order ID: $order_id');</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Order failed: " . $e->getMessage() . "');</script>";

    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Walk-In Orders | Aurum Scents</title>

  <link rel="stylesheet" href="employee_dashboard.css">
  <link rel="stylesheet" href="employee_orders.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body>

  <div class="sidebar">
    <div class="sidebar-top"><h1>Aurum Scents</h1></div>
    <div class="sidebar-bottom">
      <a href="employee_dashboard.php">Dashboard</a>
            <a href="employee_inventory.php">Inventory</a>
            <a href="employee_orders.php">Create Order</a>
            <a href="employee_returns.php">Returns</a>
            <a href="employee_view_orders.php" class="active">View Orders</a>
            <a href="reset_password_employee.php">Reset Password</a>
    </div>
  </div>

  <div class="main">

    <div class="topbar">
      <h2>Create New Walk-In Order</h2>
      <div class="profile-container">
        <div class="profile-icon" onclick="toggleDropdown()">
          <img src="profileIcon.png" alt="Profile Icon">
        </div>
        <div id="profile-dropdown" class="dropdown" style="display:none;">
          <p><strong>Username:</strong> <?= htmlspecialchars($employee_username) ?></p>
          <p><strong>Role:</strong> <?= htmlspecialchars($employee_role) ?></p>
          <p><strong>Branch:</strong> <?= htmlspecialchars($branch_address) ?></p>
          <a href="logout.php" class="logout-btn">Logout</a>
        </div>
      </div>
    </div>

    <form method="post" action="employee_orders.php" class="order-form">

      <div class="customer-card">
        <h4>Customer Lookup</h4>
        <label for="mobileNumber">Mobile Number:</label>
        <input type="text" id="mobileNumber" name="mobile_number" placeholder="Enter mobile number" autocomplete="off">

        <input type="hidden" id="customerId" name="customer_id" value="">

        <div id="customerInfo" class="customer-info" style="display:none;">
          <p><strong>Name:</strong> <span id="customerName"></span></p>
          <p><strong>Available Points:</strong> <span id="availablePoints" data-points="0">0 pts</span></p>
        <div style="display:flex; align-items:center; gap:10px; margin-top:8px;">


            <input type="hidden" id="redeemPoints" name="redeem_points" value="0">
            <button type="button" id="redeemButton" class="small-btn">Redeem Max Points</button>
            <button type="button" id="btnShowDiscount" class="small-btn">Use Discount</button>

        </div>

        <small id="pointsInfo" style="color:green; display:none;"></small>
        <br>
          <small id="discountWarning" style="color:red; display:none;">Cannot use discount when redeeming points.</small>
          <small id="pointsWarning" style="color:red; display:none;">Cannot redeem points when using a discount.</small>
        
        </div>

        <div id="anonymousInfo" class="anonymous-info" style="display:none;">
          <p><em>No account found. Order will be anonymous (discount codes & points disabled).</em></p>
        </div>
      </div>

      <table class="orders-table">
        <thead>
          <tr>
            <th>Perfume</th>
            <th>Unit Price</th>
            <th>Quantity</th>
            <th>Total</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="order-items">
          <tr id="row-1">
            <td>
              <select class="perfume-select" name="items[0][perfume_volume_ID]" onchange="updateRow(1)" required>
                <option value="">-- Select Perfume --</option>
                <?php foreach ($perfumeOptions as $opt): ?>
                    <option value="<?= htmlspecialchars($opt['perfume_volume_ID']) ?>"
                        data-price="<?= htmlspecialchars($opt['selling_price_branch']) ?>">
                    <?= htmlspecialchars($opt['perfume_name']) ?> (<?= htmlspecialchars($opt['volume']) ?>ml)
                    - <?= htmlspecialchars($currency_sign) . number_format($opt['selling_price_branch'], 2) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><?= htmlspecialchars($currency_sign) ?><span id="unit-1">0.00</span></td>
            <td><input type="number" id="qty-1" name="items[0][quantity]" min="1" value="1" onchange="updateRow(1)"></td>
            <td><?= htmlspecialchars($currency_sign) ?><span id="total-1">0.00</span></td>
            <td>
              <img src="deleteIcon.png" alt="Delete" class="delete-icon" onclick="removeItem(1)">
            </td>
          </tr>
        </tbody>
      </table>

      <button type="button" class="add-btn" onclick="addItem()">+ Add Item</button>

      <div class="grand-total" style="margin-top:12px;">
        <p><strong>Subtotal:</strong> <?= htmlspecialchars($currency_sign) ?><span id="subtotal">0.00</span></p>
        <p><strong>Tax (<?= htmlspecialchars($vat_percent*100) ?>%):</strong> <?= htmlspecialchars($currency_sign) ?><span id="tax">0.00</span></p>
        <p><strong>Redeemed Points:</strong> <?= htmlspecialchars($currency_sign) ?><span id="redeemAmount">0.00</span></p>
        <p><strong>Discount:</strong> <?= htmlspecialchars($currency_sign) ?><span id="discountAmount">0.00</span></p>
        <h3>Grand Total: <?= htmlspecialchars($currency_sign) ?><span id="grandTotal">0.00</span></h3>
      </div>

      <div id="discountSection" style="display:none; margin-top:8px;">
        <label for="discount">Discount Code:</label>
        <input type="text" id="discount" name="discount_code">
        <small id="discountError" style="display:none;color:red;">Invalid or expired discount code.</small>
      </div>

      <label for="paymentMethod">Payment Method:</label>
      <select id="paymentMethod" name="payment_method" required>
          <option value="">-- Select Payment Method --</option>
          <option value="Cash">Cash</option>
          <option value="Card">Card</option>
      </select>

      <div id="cashDetails" style="display:none;margin-top:8px;">
        <label for="cashGiven">Cash Given:</label>
        <input type="number" id="cashGiven" name="cash_given" min="0" step="0.01">
        <p>Change: <?= htmlspecialchars($currency_sign) ?><span id="cashChange">0.00</span></p>
        <small id="cashError" style="color:red; display:none;"></small>
      </div>

      <button type="submit" class="submit-btn" style="margin-top:12px;">Submit Order</button>
    </form>
  </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.css"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function () {

    const branchRate = <?= json_encode($branchRate) ?>;
    const branchSign = <?= json_encode($currency_sign) ?>;
    const taxRate = <?= json_encode($vat_percent) ?>;

    window.currentDiscountPercent = 0;

    $('.perfume-select').select2({ width: '250px' });

    $('#mobileNumber').data('last', '');

    resetCustomerUI();

    // lookup customer
    $('#mobileNumber').on('blur', function () {
        const mobile = $(this).val().trim();
        const last = $(this).data('last') || '';

        if (mobile === last) return;

        $(this).data('last', mobile);

        if (mobile === "") {
            resetCustomerUI();
            updateGrandTotal();
            return;
        }

        $.getJSON('employee_orders.php', { action: 'lookup', mobile: mobile }, function (data) {

            if (data && data.found) {
                $('#customerInfo').show();
                $('#anonymousInfo').hide();
                $('#customerId').val(data.customer_ID);

                const pts = parseInt(data.points) || 0;
                const ptsValue = pts * branchRate;

                $('#customerName').text(data.name);
                $('#availablePoints')
                    .text(pts + " pts (≈ " + branchSign + ptsValue.toLocaleString('en-PH', { minimumFractionDigits: 2 }) + ")")
                    .data("points", pts);

                window.currentDiscountPercent = 0;
                $('#redeemPoints').val(0);
                $('#pointsInfo').hide();
                $('#discount').val('');
                hideWarnings();

                $('#discountSection').hide();

            } else {
                resetCustomerUI();
                $('#anonymousInfo').show();
            }

            updateGrandTotal();
        }).fail(function () {
            resetCustomerUI();
            updateGrandTotal();
        });
    });

    function resetCustomerUI() {
        $('#customerInfo').hide();
        $('#anonymousInfo').hide();
        $('#customerId').val('');

        $('#availablePoints').text("0 pts").data("points", 0);
        $('#customerName').text("");

        $('#redeemPoints').val(0);
        $('#pointsInfo').hide();

        $('#discountSection').hide();
        $('#discount').val('');
        window.currentDiscountPercent = 0;

        hideWarnings();
    }

    function hideWarnings() {
        $('#discountError').hide();
        $('#discountWarning').hide();
        $('#pointsWarning').hide();
    }

    // for redeeming pts
    $('#redeemButton').on('click', function () {

        const custId = $('#customerId').val();
        if (!custId) {
            alert("Cannot redeem points for anonymous customer.");
            return;
        }

        const subtotal = getSubtotal();
        const available = parseInt($('#availablePoints').data('points')) || 0;

        const maxRedeemValue = subtotal * 0.10;
        const maxPts = Math.floor(maxRedeemValue / branchRate);
        const redeemPts = Math.min(maxPts, available);

        if (redeemPts <= 0) {
            $('#pointsInfo').show().text("No redeemable points for this subtotal.");
            return;
        }

        $('#redeemPoints').val(redeemPts);
        $('#pointsInfo').show()
            .text("Redeeming " + redeemPts + " pts (≈ " + branchSign + (redeemPts * branchRate).toLocaleString('en-PH', { minimumFractionDigits: 2 }) + ")");

        window.currentDiscountPercent = 0;
        $('#discount').val('');
        $('#discountSection').hide();
        $('#discountWarning').show();
        $('#pointsWarning').hide();

        updateGrandTotal();
    });

    // show discount
    $('#btnShowDiscount').on('click', function () {

        const custId = $('#customerId').val();
        if (!custId) {
            alert("Cannot apply discount for anonymous customer.");
            return;
        }

        $('#redeemPoints').val(0);
        $('#pointsInfo').hide();
        $('#discountWarning').hide();
        $('#pointsWarning').hide();

        $('#discountSection').show();
        $('#discount').focus();
        window.currentDiscountPercent = 0;

        updateGrandTotal();
    });

    // lookup discount
    $('#discount').on('input blur', function () {
        const code = $(this).val().trim();
        const custId = $('#customerId').val();

        if (!custId) {
            $('#discountError').show().text("Discount only available to registered customers.");
            window.currentDiscountPercent = 0;
            updateGrandTotal();
            return;
        }

        if (code === "") {
            window.currentDiscountPercent = 0;
            $('#discountError').hide();
            $('#pointsWarning').hide();
            updateGrandTotal();
            return;
        }

        $.getJSON('employee_orders.php', {
            action: 'discount_lookup',
            code: code,
            customer_id: custId
        }, function (data) {

            if (data && data.valid) {
                window.currentDiscountPercent = parseFloat(data.percent) || 0;

                $('#redeemPoints').val(0);
                $('#pointsInfo').hide();
                $('#pointsWarning').show();
                $('#discountError').hide();
            } else {
                window.currentDiscountPercent = 0;
                $('#pointsWarning').hide();
                $('#discountError').show().text(data.error || "Invalid or expired discount.");
            }

            updateGrandTotal();

        }).fail(function () {
            window.currentDiscountPercent = 0;
            $('#discountError').show().text("Failed to validate discount.");
            updateGrandTotal();
        });
    });

    // payment method
    $('#paymentMethod').on('change', function () {
        if ($(this).val() === "Cash") {
            $('#cashDetails').show();
        } else {
            $('#cashDetails').hide();
            $('#cashGiven').val('');
            $('#cashChange').text('0.00');
            $('#cashError').hide();
        }
    });

    $('#cashGiven').on('input', function () {
        const grand = getGrandTotal();
        const given = parseFloat($(this).val()) || 0;
        const change = given - grand;

        $('#cashChange').text(
            change >= 0
                ? change.toLocaleString('en-PH', { minimumFractionDigits: 2 })
                : "0.00"
        );

        if (given < grand) {
            $('#cashError').show().text("Cash given is insufficient.");
        } else {
            $('#cashError').hide();
        }
    });

    // form validation
    $('.order-form').on('submit', function (e) {

        const discountCode = $('#discount').val().trim();
        const redeemPoints = parseInt($('#redeemPoints').val()) || 0;

        if (discountCode !== "" && redeemPoints > 0) {
            alert("You cannot use both discount and points.");
            e.preventDefault();
            return false;
        }

        if (discountCode !== "" && (!window.currentDiscountPercent || window.currentDiscountPercent === 0)) {
            alert("Invalid or already-claimed discount code.");
            e.preventDefault();
            return false;
        }

        const subtotal = getSubtotal();
        if (subtotal <= 0) {
            alert("Add at least one item before submitting the order.");
            e.preventDefault();
            return false;
        }

        if ($('#paymentMethod').val() === 'Cash') {
            const grand = getGrandTotal();
            const given = parseFloat($('#cashGiven').val()) || 0;
            if (given < grand) {
                $('#cashError').show().text("Cash given is insufficient.");
                e.preventDefault();
                return false;
            }
        }
    });

});


// calculation helpers
function getSubtotal() {
    let subtotal = 0;
    document.querySelectorAll('[id^="total-"]').forEach(span => {
        const val = parseFloat(span.innerText.replace(/,/g, "")) || 0;
        subtotal += val;
    });
    return subtotal;
}

function getGrandTotal() {
    const gt = parseFloat($('#grandTotal').text().replace(/,/g, "")) || 0;
    return gt;
}

// update rows
let rowCount = 1;
function addItem() {
    rowCount++;
    const idx = rowCount - 1; 
    const rowHTML = `
      <tr id="row-${rowCount}">
        <td>
          <select class="perfume-select" name="items[${idx}][perfume_volume_ID]" onchange="updateRow(${rowCount})" required>
            <option value="">-- Select Perfume --</option>
            <?php foreach ($perfumeOptions as $opt): ?>
                <option value="<?= htmlspecialchars($opt['perfume_volume_ID']) ?>"
                        data-price="<?= htmlspecialchars($opt['selling_price_branch']) ?>">
                    <?= htmlspecialchars($opt['perfume_name']) ?> (<?= htmlspecialchars($opt['volume']) ?>ml)
                    - <?= $currency_sign . number_format($opt['selling_price_branch'], 2) ?>
                </option>
            <?php endforeach; ?>
          </select>
        </td>
        <td><?= htmlspecialchars($currency_sign) ?><span id="unit-${rowCount}">0.00</span></td>
        <td><input type="number" id="qty-${rowCount}" name="items[${idx}][quantity]" min="1" value="1" onchange="updateRow(${rowCount})"></td>
        <td><?= htmlspecialchars($currency_sign) ?><span id="total-${rowCount}">0.00</span></td>
        <td>
          <img src="deleteIcon.png" alt="Delete" class="delete-icon" onclick="removeItem(${rowCount})">
        </td>
      </tr>
    `;
    document.getElementById('order-items').insertAdjacentHTML('beforeend', rowHTML);
    $(`#row-${rowCount} .perfume-select`).select2({ width: '250px' });
}

function updateRow(id) {
    const select = $(`#row-${id} select`)[0];
    const qty = parseInt($(`#qty-${id}`).val()) || 0;

    const unit = parseFloat(select?.selectedOptions[0]?.dataset.price) || 0;
    const total = unit * qty;

    $(`#unit-${id}`).text(unit.toLocaleString('en-PH', { minimumFractionDigits: 2 }));
    $(`#total-${id}`).text(total.toLocaleString('en-PH', { minimumFractionDigits: 2 }));

    updateGrandTotal();
}

// calculate grand total
function updateGrandTotal() {

    const subtotal = getSubtotal();
    let adjusted = subtotal;

    let redeemDisplay = 0;
    let discountDisplay = 0;

    const redeemPoints = parseInt($('#redeemPoints').val()) || 0;
    if (redeemPoints > 0) {
        const redeemValue = redeemPoints * <?= json_encode($branchRate) ?>;
        const maxRedeem = subtotal * 0.10;
        redeemDisplay = Math.min(redeemValue, maxRedeem);
        adjusted -= redeemDisplay;
    }

    const percent = window.currentDiscountPercent || 0;
    if (percent > 0 && redeemDisplay === 0) {
        discountDisplay = subtotal * percent;
        adjusted -= discountDisplay;
    }

    const tax = adjusted * <?= json_encode($vat_percent) ?>;
    const grand = adjusted + tax;

    $('#subtotal').text(subtotal.toLocaleString('en-PH', { minimumFractionDigits: 2 }));
    $('#tax').text(tax.toLocaleString('en-PH', { minimumFractionDigits: 2 }));
    $('#redeemAmount').text(redeemDisplay.toLocaleString('en-PH', { minimumFractionDigits: 2 }));
    $('#discountAmount').text(discountDisplay.toLocaleString('en-PH', { minimumFractionDigits: 2 }));
    $('#grandTotal').text(grand.toLocaleString('en-PH', { minimumFractionDigits: 2 }));
}

// remove row
function removeItem(id) {
    $(`#row-${id}`).remove();
    updateGrandTotal();
}

// profile dropdown
function toggleDropdown() {
    const d = document.getElementById("profile-dropdown");
    d.style.display = d.style.display === "block" ? "none" : "block";
}

</script>

</body>
</html>
