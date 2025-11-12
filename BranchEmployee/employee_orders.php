<?php
session_start();
$_SESSION['employee_name'] = 'Janella Cruz';

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
    <a href="employee_walkin_orders.php" class="active">Walk-In Orders</a>
    <a href="employee_returns.php">Returns</a>
    <a href="employee_view_orders.php">View Orders</a>

  </div>
</div>

<div class="main">
  <div class="topbar">
    <h2>Create New Walk-In Order</h2>
    <div class="profile-icon"><img src="profileIcon.png" alt="Profile Icon"></div>
  </div>

  <!-- order Form -->
  <form method="post" action="employee_orders.php" class="order-form">

    <!-- customer Details Card -->
    <div class="customer-card">
      <h4>Customer Lookup</h4>
      <label for="mobileNumber">Mobile Number:</label>
      <input type="text" id="mobileNumber" name="mobile_number" placeholder="Enter mobile number">

      <div id="customerInfo" class="customer-info" style="display:none;">
        <p><strong>Name:</strong> <span id="customerName"></span></p>
        <p><strong>Available Points:</strong> <span id="availablePoints"></span></p>
        <label for="redeemPoints">Redeem Points:</label>
        <input type="number" id="redeemPoints" name="redeem_points" min="0" value="0">
        <small>(Max: 10% of order total)</small>
      </div>

      <div id="anonymousInfo" class="anonymous-info" style="display:none;">
        <p><em>No account found. Order will be anonymous (discount codes only).</em></p>
      </div>
    </div>

    <!-- items Table -->
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
      <tbody id="order-items"></tbody>
    </table>

    <button type="button" class="add-btn" onclick="addItem()">+ Add Item</button>

    <div class="grand-total">
      <h3>Grand Total: ₱<span id="grandTotal">0.00</span></h3>
    </div>

    <!-- discount Code -->
    <label for="discount">Discount Code:</label>
    <input type="text" id="discount" name="discount_code">

    <label for="paymentMethod">Payment Method:</label>
    <select id="paymentMethod" name="payment_method" required>
        <option value="">-- Select Payment Method --</option>
        <option value="Cash">Cash</option>
        <option value="Card">Card</option>
    </select>

    <div id="cashDetails" style="display:none;">
    <label for="cashGiven">Cash Given:</label>
    <input type="number" id="cashGiven" name="cash_given" min="0" step="0.01">
    <p>Change: ₱<span id="cashChange">0.00</span></p>
    </div>



    <button type="submit" class="submit-btn">Submit Order</button>
  </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  const perfumes = {
    "P001": {name: "Oud Royale (50ml)", price: 3250},
    "P002": {name: "Amber Luxe (100ml)", price: 4500},
    "P003": {name: "Velvet Bloom (30ml)", price: 2200},
    "P004": {name: "Rose Noir (50ml)", price: 3000}
  };

  $(document).ready(function() {
    addItem();

    // mobile number lookup 
    $('#mobileNumber').on('blur', function() {
  const mobile = $(this).val().trim();

  if (mobile === "") {
    // hide both sections
    $('#customerInfo').hide();
    $('#anonymousInfo').hide();

    // reset points input
    $('#redeemPoints').val(0);

    // re-enable discount code
    $('#discount').prop('disabled', false);
    $('#discountWarning').hide();
    return;
  }

  // sample only for now
  if (mobile === "09171234567") {
    $('#customerName').text("Maria Santos");
    $('#availablePoints').text("150");
    $('#customerInfo').show();
    $('#anonymousInfo').hide();
  } else {
    $('#customerInfo').hide();
    $('#anonymousInfo').show();
  }
});


    // enforce redemption rules
    $('#redeemPoints').on('input', function() {
      const grandTotal = parseFloat($('#grandTotal').text().replace(/,/g, "")) || 0;
      const maxRedeem = grandTotal * 0.10; // 10% cap
      let entered = parseFloat($(this).val()) || 0;

      if (entered > maxRedeem) {
        $(this).val(Math.floor(maxRedeem));
        alert("Points redeemed cannot exceed 10% of order total.");
      }

      // if points are being redeemed, disable discount code
      if (entered > 0) {
        $('#discount').prop('disabled', true);
        $('#discountWarning').show();
      } else {
        $('#discount').prop('disabled', false);
        $('#discountWarning').hide();
      }
    });
  });

  function addItem() {
    const rowID = Date.now();
    const rowHTML = `
      <tr id="row-${rowID}">
        <td>
          <select class="perfume-select" onchange="updateRow(${rowID})" required>
            <option value="">-- Select Perfume --</option>
            ${Object.entries(perfumes).map(([id, p]) => `<option value="${id}">${p.name}</option>`).join('')}
          </select>
        </td>
        <td>₱<span id="unit-${rowID}">0.00</span></td>
        <td><input type="number" id="qty-${rowID}" min="1" value="1" onchange="updateRow(${rowID})"></td>
        <td>₱<span id="total-${rowID}">0.00</span></td>
        <td><img src="deleteIcon.png" alt="Delete" class="delete-icon" onclick="removeItem(${rowID})"></td>
      </tr>
    `;
    document.getElementById('order-items').insertAdjacentHTML('beforeend', rowHTML);
    $('.perfume-select').select2({ width: '300px' });
  }

  function updateRow(rowID) {
    const perfumeID = document.querySelector(`#row-${rowID} select`).value;
    const qty = parseInt(document.getElementById(`qty-${rowID}`).value) || 0;
    if (perfumeID && perfumes[perfumeID]) {
      const unitPrice = perfumes[perfumeID].price;
      const totalPrice = unitPrice * qty;
      document.getElementById(`unit-${rowID}`).innerText = unitPrice.toLocaleString('en-PH', {minimumFractionDigits: 2});
      document.getElementById(`total-${rowID}`).innerText = totalPrice.toLocaleString('en-PH', {minimumFractionDigits: 2});
    } else {
      document.getElementById(`unit-${rowID}`).innerText = "0.00";
      document.getElementById(`total-${rowID}`).innerText = "0.00";
    }
    updateGrandTotal();
  }

  function updateGrandTotal() {
    let grandTotal = 0;
    document.querySelectorAll('[id^="total-"]').forEach(span => {
      grandTotal += parseFloat(span.innerText.replace(/,/g, "")) || 0;
    });
    document.getElementById('grandTotal').innerText = grandTotal.toLocaleString('en-PH', {minimumFractionDigits: 2});
  }

  function removeItem(rowID) {
    document.getElementById(`row-${rowID}`).remove();
    updateGrandTotal();
  }

  $('#paymentMethod').on('change', function() {
  if ($(this).val() === 'Cash') {
    $('#cashDetails').show();
  } else {
    $('#cashDetails').hide();
    $('#cashGiven').val('');
    $('#cashChange').text('0.00');
  }
});

$('#cashGiven').on('input', function() {
  const grandTotal = parseFloat($('#grandTotal').text().replace(/,/g, "")) || 0;
  const given = parseFloat($(this).val()) || 0;
  const change = given - grandTotal;
  $('#cashChange').text(change >= 0 ? change.toLocaleString('en-PH', {minimumFractionDigits: 2}) : "0.00");
});

</script>

</body>
</html>
