<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

include('../db_connect.php');

// block access if not branch employee
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Branch Employee') {
  header("Location: ../login_staff-admin.php");
  exit();
}

$employee_username = $_SESSION['username'];
$employee_id       = $_SESSION['user_id'];
$branch_id         = $_SESSION['branch_id'];

// get branch info
$branch_address = "";
$currency_sign = ""; 
$vat_percent = "";

if ($branch_id) {
  $query = "SELECT b.address, cur.currency_sign, c.vat_percent 
            FROM branches b
            JOIN countries c ON b.country_ID = c.country_ID
            JOIN currencies cur ON c.currency = cur.currency
            WHERE b.branch_ID = '$branch_id'";
  $result = mysqli_query($conn, $query);

  if ($result && mysqli_num_rows($result) > 0) {
      $branch = mysqli_fetch_assoc($result);
      $branch_address = $branch['address'];
      $currency_sign = $branch['currency_sign'];
      $vat_percent = (float)$branch['vat_percent'];

  }
}

$order_id    = "";
$order_date  = "";
$order_total = "";

// order receipt
$details = []; // for order_detail rows 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['orderID'])) {
    $inputOrderID = trim($_POST['orderID']);

    // find order using order id
    $sqlOrder = "SELECT order_ID, order_date, order_total, currency, order_type, branch_ID
                 FROM orders
                 WHERE order_ID = ? AND branch_ID = ? AND order_type = 'Walk-in'";
    $stmt = $conn->prepare($sqlOrder);
    $stmt->bind_param("ss", $inputOrderID, $branch_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($order) {
        $order_id    = $order['order_ID'];
        $order_date  = date("F j, Y g:i A", strtotime($order['order_date']));
        $order_total = $order['order_total'];

        // find order details
            $sqlDetails = 
            "SELECT 
            od.order_detail_ID,
            p.perfume_name,
            od.perfume_volume_ID,
            pv.volume AS volume_ml,
            od.quantity AS qty_ordered,
            od.unit_price,
            od.currency,
            COALESCE(SUM(r.quantity), 0) AS qty_returned,
            o.customer_ID
            FROM order_details od
            JOIN perfume_volume pv ON od.perfume_volume_ID = pv.perfume_volume_ID
            JOIN perfumes p ON pv.perfume_ID = p.perfume_ID
            JOIN orders o ON od.order_ID = o.order_ID
            LEFT JOIN returns r 
                ON r.order_detail_ID = od.order_detail_ID
                AND r.status IN ('Approved','Refunded')
            WHERE od.order_ID = ?
            GROUP BY od.order_detail_ID, p.perfume_name, od.perfume_volume_ID, od.quantity, od.unit_price, od.currency, o.customer_ID;
            ";

        $stmt = $conn->prepare($sqlDetails);
        $stmt->bind_param("s", $order_id);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $details[] = $row; 
        }
        
        $stmt->close();
    } else {
        $lookup_error = "Order not found for this branch or not a walk-in.";
    }    
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Aurum Scents | Walk-In Returns</title>
  <link rel="stylesheet" href="employee_dashboard.css">
  <link rel="stylesheet" href="employee_returns.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
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
    <h2>Manage Returns</h2>
    <div class="profile-container">
      <div class="profile-icon" onclick="toggleDropdown()">
        <img src="profileIcon.png" alt="Profile Icon">
      </div>
      <div id="profile-dropdown" class="dropdown">
        <p><strong>Username:</strong> <?= htmlspecialchars($employee_username) ?></p>
        <p><strong>Role:</strong> <?= htmlspecialchars($_SESSION['role']) ?></p>
        <p><strong>Branch:</strong> <?= htmlspecialchars($branch_address) ?></p>
        <a href="logout.php" class="logout-btn">Logout</a>
      </div>
    </div>
  </div>

  <div class="return-form">

    <form method="post" action="employee_returns.php">
      <label for="orderID">Enter Order ID (Receipt #):</label>
      <input type="text" id="orderID" name="orderID" required>
      <button type="submit">Lookup Order</button>
    </form>

    <?php if (!empty($lookup_error)): ?>
      <div class="customer-card" style="margin-top:12px;">
        <?= htmlspecialchars($lookup_error) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($order_id)): ?>

      <div class="customer-card" style="margin-top:15px;">
        <h4>Order Info</h4>
        <div class="customer-info">
          <strong>Order ID:</strong> <?= htmlspecialchars($order_id) ?><br>
          <strong>Date Ordered:</strong> <?= htmlspecialchars($order_date) ?><br>
          <strong>Order Total:</strong> <?= htmlspecialchars($currency_sign), number_format($order_total, 2) ?>
        </div>
      </div>

      <form method="post" action="returns.php" id="returnsSubmitForm">
        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_id) ?>">
        <input type="hidden" name="branch_id" value="<?= htmlspecialchars($branch_id) ?>">
        <input type="hidden" name="staff_id" value="<?= htmlspecialchars($employee_id) ?>">

        <h3>Order Details</h3>
        <table class="returns-table">
          <thead>
            <tr>
              <th>Perfume</th>
              <th>Volume</th>
              <th>Qty Ordered</th>
              <th>Returned</th>
              <th>Unit Price</th>
              <th>Return Qty</th>
              <th>Refund Amount</th>
              
            </tr>
          </thead>
          <tbody>

        <?php $hasReturnable = false; ?>
          <?php foreach ($details as $d): ?>
            <?php 
                $qtyOrdered  = (int)$d['qty_ordered'];
                $qtyReturned = (int)$d['qty_returned'];
                $qtyRemaining = max(0, $qtyOrdered - $qtyReturned);
                if ($qtyRemaining > 0) {
                    $hasReturnable = true;
                }
            ?>
            <tr>
              <td><?= htmlspecialchars($d['perfume_name']) ?></td>
              <td><?= htmlspecialchars($d['volume_ml']) ?> ml</td>
              <td><?= (int)$d['qty_ordered'] ?></td>
              <td><?= (int)$d['qty_returned'] ?></td>
              <td><?= htmlspecialchars($currency_sign), number_format($d['unit_price'], 2) ?></td>

              <td>
                <select 
                class="return-qty" 
                name="return_qty[<?= htmlspecialchars($d['order_detail_ID']) ?>]" 
                data-unit-price="<?= htmlspecialchars($d['unit_price']) ?>"
                >
                <?php for ($i=0; $i <= $qtyRemaining; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?></option>
                <?php endfor; ?>
                </select>

              </td>

              <td><span class="refund-amount">0.00</span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>

        <!-- only display if has returnable items -->
        <?php if ($hasReturnable): ?>
            <label for="reason">Reason for Return:</label>
            <textarea id="reason" name="reason" placeholder="Optional notes for audit"></textarea>

            <label for="refund_method">Refund Method:</label>
            <select id="refund_method" name="refund_method" required>
                <option value="Cash">Cash</option>
                <option value="Card">Card</option>
            </select>

            <button type="submit" class="submit-btn">Submit Return</button>
            <?php else: ?>
            <div class="customer-card" style="margin-top:12px;">
                All items in this order have already been returned. Nothing left to process.
            </div>
            <?php endif; ?>

      </form>
    <?php endif; ?>
  </div>
</div>

<script>

function toggleDropdown() {
  const dropdown = document.getElementById("profile-dropdown");
  dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
}

// when cashier picks a return qty, compute refund_amount = qty * unit_price
const vatRate = <?= json_encode($vat_percent) ?>; 
  document.querySelectorAll('.return-qty').forEach(function(sel) {
    sel.addEventListener('change', function() {
      const unitPrice = parseFloat(sel.dataset.unitPrice || '0');
      const qty = parseInt(sel.value || '0', 10);
      const refundCell = sel.closest('tr').querySelector('.refund-amount');
      const amount = (unitPrice * qty * (1 + vatRate)) || 0;
      refundCell.textContent = amount.toFixed(2);
    });
  });
</script>

</body>
</html>
