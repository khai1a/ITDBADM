<?php
session_start();
require 'db_connect.php';

$customerID = $_SESSION['customer_ID'] ?? null;
if (!$customerID) {
    header("Location: login_customer.php");
    exit;
}

// currency selection
if (isset($_GET['currency'])) {
    $_SESSION['currency'] = $_GET['currency'];
}
if (!isset($_SESSION['currency'])) {
    $_SESSION['currency'] = 'USD';
}
$filter_currency = $_SESSION['currency'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $cartItemID = $_POST['cart_item_ID'] ?? '';
    $quantity = $_POST['quantity'] ?? 1;

    // Get cart ID for this customer
    $cartRes = $conn->prepare("SELECT cart_ID FROM cart WHERE customer_ID = ?");
    $cartRes->bind_param("s", $customerID);
    $cartRes->execute();
    $cartIDRes = $cartRes->get_result();
    $cartID = $cartIDRes->fetch_assoc()['cart_ID'] ?? null;

    if (!$cartID) {
        echo json_encode(['status' => 'error', 'message' => 'Cart not found']);
        exit;
    }

    if ($action === 'update_quantity') {
        $quantity = max(1, intval($quantity));
        $stmt = $conn->prepare("UPDATE cart_items SET quantity=? WHERE cart_item_ID=? AND cart_ID=?");
        $stmt->bind_param("iss", $quantity, $cartItemID, $cartID);
        $stmt->execute();
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($action === 'remove_item') {
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_item_ID=? AND cart_ID=?");
        $stmt->bind_param("ss", $cartItemID, $cartID);
        $stmt->execute();
        echo json_encode(['status' => 'success']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    exit;
}

// cart items
// cart items
$cartRes = $conn->prepare("
    SELECT ci.cart_item_ID,
           pv.perfume_ID,
           pv.volume,
           pv.selling_price,
           p.perfume_name,
           ci.quantity,
           COALESCE(i.total_stock, 0) AS total_stock
    FROM cart_items ci
    JOIN perfume_volume pv ON ci.perfume_volume_ID = pv.perfume_volume_ID
    JOIN perfumes p ON pv.perfume_ID = p.perfume_ID
    JOIN cart c ON ci.cart_ID = c.cart_ID
    LEFT JOIN (
        SELECT perfume_volume_ID, SUM(quantity) AS total_stock
        FROM inventory
        GROUP BY perfume_volume_ID
    ) i ON pv.perfume_volume_ID = i.perfume_volume_ID
    WHERE c.customer_ID = ?
");
$cartRes->bind_param("s", $customerID);
$cartRes->execute();
$cartItems = $cartRes->get_result();

// conversion
$curStmt = $conn->prepare("SELECT fromUSD, currency_sign FROM currencies WHERE currency = ?");
$curStmt->bind_param('s', $filter_currency);
$curStmt->execute();
$curRes = $curStmt->get_result();
$currencyData = $curRes->fetch_assoc();
$currencyRate = isset($currencyData['fromUSD']) ? floatval($currencyData['fromUSD']) : 1.0;
$currencySign = $currencyData['currency_sign'] ?? '$';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Shopping Cart - Aurum Scents</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="cart.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- navbar -->
<nav class="navbar navbar-expand-lg shadow-sm">
<div class="container">
    <a class="navbar-brand" href="customer_home.php">Aurum Scents</a>
    <div class="collapse navbar-collapse" id="navbarNav">
        <div class="nav-links-container mx-auto">
            <a class="nav-link" href="customer_home.php">Home</a>
            <a class="nav-link" href="about_us.php">About Us</a>
            <a class="nav-link active" href="buy_here.php">Buy Here</a>
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

            <a class="nav-link" href="cart.php">
                <i class="fa fa-shopping-cart"></i>
            </a>

            <!-- currency -->
            <div class="dropdown currency-dropdown">
                <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    Currency: <?= htmlspecialchars($filter_currency) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <?php
                    $curRes2 = $conn->query("SELECT currency FROM currencies ORDER BY currency ASC");
                    while ($c = $curRes2->fetch_assoc()): ?>
                        <li>
                            <a class="dropdown-item" href="?currency=<?= urlencode($c['currency']) ?>">
                                <?= htmlspecialchars($c['currency']) ?>
                            </a>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
</nav>

<!-- cart  -->
<section class="cart-section py-5">
    <div class="container">
        <h2 class="cart-title text-center mb-4">Your Shopping Cart</h2>
        <div class="row">
            <div class="col-md-8">
                <?php if($cartItems && $cartItems->num_rows > 0): ?>
                <form id="checkoutForm" method="GET" action="checkout.php">
                    <div id="cartItems">
                        <?php while($item = $cartItems->fetch_assoc()):
                            $convertedPrice = $item['selling_price'] * $currencyRate;
                        ?>
                        <div class="cart-item p-3 mb-3 d-flex align-items-center" 
                             data-cart-item-id="<?= $item['cart_item_ID'] ?>" 
                             data-stock="<?= $item['total_stock'] ?>">
                            <div class="cart-details flex-grow-1">
                                <h5><?= htmlspecialchars($item['perfume_name']) ?> (<?= htmlspecialchars($item['volume']) ?>ml)</h5>
                                <p class="cart-price" data-usd="<?= $item['selling_price'] ?>">
                                    <?= $currencySign . number_format($convertedPrice, 2) ?>
                                </p>
                                <div class="d-flex align-items-center qty-controls">
                                    <button type="button" class="btn qty-btn minus-btn">-</button>
                                    <input type="number" class="form-control qty-input mx-2 text-center" value="<?= $item['quantity'] ?>" min="1">
                                    <button type="button" class="btn qty-btn plus-btn">+</button>
                                </div>
                                <p class="total-price">Total: <?= $currencySign . number_format($convertedPrice * $item['quantity'], 2) ?></p>
                            </div>
                            <button type="button" class="btn btn-remove ms-3"><i class="fa fa-trash"></i></button>

                            <input type="hidden" name="items[]" value="<?= $item['cart_item_ID'] ?>">
                            <input type="hidden" name="qtys[]" value="<?= $item['quantity'] ?>" class="hidden-qty">
                        </div>
                        <?php endwhile; ?>
                        <input type="hidden" name="currency" value="<?= htmlspecialchars($filter_currency) ?>">
                    </div>
                </form>
                <?php else: ?>
                    <p class="text-center fs-5 mt-4 no-perfumes"><b>Your cart is empty.</b></p>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <div class="cart-summary p-3 sticky-top" style="top:80px;">
                    <h4>Cart Summary</h4>
                    <p><strong>Subtotal:</strong> <span id="cartSubtotal"></span></p>
                    <button type="submit" form="checkoutForm" class="btn btn-checkout w-100" id="checkoutBtn" <?= ($cartItems->num_rows ?? 0) === 0 ? 'disabled' : '' ?>>
                        Proceed to Checkout
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Quantity update & subtotal calculation with inventory limit
document.querySelectorAll('.cart-item').forEach(item => {
    const qtyInput = item.querySelector('.qty-input');
    const totalEl = item.querySelector('.total-price');
    const hiddenQty = item.querySelector('.hidden-qty');
    const usdPrice = parseFloat(item.querySelector('.cart-price').dataset.usd);
    const convertedPrice = usdPrice * <?= $currencyRate ?>;
    const maxStock = parseInt(item.dataset.stock) || 9999;

    const minusBtn = item.querySelector('.minus-btn');
    const plusBtn = item.querySelector('.plus-btn');

    const updateQty = (delta = 0) => {
        let qty = parseInt(qtyInput.value) || 1;
        qty += delta;
        if (qty < 1) qty = 1;
        if (qty > maxStock) qty = maxStock;

        qtyInput.value = qty;
        hiddenQty.value = qty;
        totalEl.textContent = `Total: <?= $currencySign ?>${(convertedPrice * qty).toFixed(2)}`;

        minusBtn.disabled = qty <= 1;
        plusBtn.disabled = qty >= maxStock;

        updateSubtotal();
    };

    minusBtn.addEventListener('click', () => updateQty(-1));
    plusBtn.addEventListener('click', () => updateQty(1));
    qtyInput.addEventListener('input', () => updateQty(0));

    qtyInput.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); qtyInput.blur(); }
    });

    // Remove item
    item.querySelector('.btn-remove').addEventListener('click', () => {
        const cartItemID = item.getAttribute('data-cart-item-id');
        fetch('cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'remove_item', cart_item_ID: cartItemID})
        }).then(() => {
            item.remove();
            updateSubtotal();
            if(document.querySelectorAll('.cart-item').length === 0) {
                document.getElementById('checkoutBtn').disabled = true;
                document.getElementById('cartItems').innerHTML = '<p class="text-center fs-5 mt-4 no-perfumes"><b>Your cart is empty.</b></p>';
            }
        });
    });
});

function updateSubtotal() {
    let subtotal = 0;
    document.querySelectorAll('.cart-item').forEach(item => {
        const qty = parseInt(item.querySelector('.qty-input').value);
        const usdPrice = parseFloat(item.querySelector('.cart-price').dataset.usd);
        subtotal += usdPrice * <?= $currencyRate ?> * qty;
    });
    document.getElementById('cartSubtotal').textContent = '<?= $currencySign ?>' + subtotal.toFixed(2);
}
updateSubtotal();
</script>
</body>
</html>


