<?php
session_start();
require 'db_connect.php';

$customerID = $_SESSION['customer_ID'] ?? null;
$customerCountry = null;
if ($customerID !== null) {
    $stmt = $conn->prepare("SELECT country_ID FROM customers WHERE customer_ID = ?");
    $stmt->bind_param('s', $customerID);
    $stmt->execute();
    $res = $stmt->get_result();
    $customerCountry = $res->fetch_assoc()['country_ID'] ?? null;
    $stmt->close();
}

// inventory
$inventoryTotals = [];
$invRes = $conn->query("SELECT perfume_volume_ID, SUM(quantity) as total_qty FROM inventory GROUP BY perfume_volume_ID");
while ($row = $invRes->fetch_assoc()) {
    $inventoryTotals[$row['perfume_volume_ID']] = intval($row['total_qty']);
}

// add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $perfumeVolID = $_POST['perfume_volume_ID'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 1);

    if ($customerID && $perfumeVolID) {
        // check stock
        $stock = intval($inventoryTotals[$perfumeVolID] ?? 0);
        if ($quantity > $stock) {
            echo json_encode(['status'=>'error','message'=>"Cannot add more than available stock ($stock)."]);
            exit;
        }

        // get/create cart
        $cartStmt = $conn->prepare("SELECT cart_ID FROM cart WHERE customer_ID = ?");
        $cartStmt->bind_param("s", $customerID);
        $cartStmt->execute();
        $res = $cartStmt->get_result();
        if ($res->num_rows > 0) {
            $cartID = $res->fetch_assoc()['cart_ID'];
        } else {
            $cartCount = $conn->query("SELECT COUNT(*) as c FROM cart")->fetch_assoc()['c'];
            $cartID = 'CA' . str_pad($cartCount + 1, 5, '0', STR_PAD_LEFT);
            $insCart = $conn->prepare("INSERT INTO cart (cart_ID, customer_ID) VALUES (?, ?)");
            $insCart->bind_param("ss", $cartID, $customerID);
            $insCart->execute();
            $insCart->close();
        }
        $cartStmt->close();

        // cart item ID
        $cartItemCount = $conn->query("SELECT COUNT(*) as c FROM cart_items")->fetch_assoc()['c'];
        $cartItemID = 'CI' . str_pad($cartItemCount + 1, 6, '0', STR_PAD_LEFT);
        $insItem = $conn->prepare("INSERT INTO cart_items (cart_item_ID, cart_ID, perfume_volume_ID, quantity) VALUES (?, ?, ?, ?)");
        $insItem->bind_param("sssi", $cartItemID, $cartID, $perfumeVolID, $quantity);
        $insItem->execute();
        $insItem->close();

        echo json_encode(['status'=>'success','message'=>'Added to cart']);
        exit;
    }
    echo json_encode(['status'=>'error','message'=>'Unable to add to cart']);
    exit;
}

// filters
$filter_brand = $_GET['brand'] ?? 'All';
$filter_gender = $_GET['gender'] ?? 'All';
$filter_country = $_GET['country'] ?? '';
$sort = $_GET['sort'] ?? 'name_asc';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 9;

// currency
$filter_currency = $_GET['currency'] ?? 'USD';
$currencyList = $conn->query("SELECT currency, fromUSD, currency_sign FROM currencies ORDER BY currency ASC");

// selected currency
$currencyRate = 1.0;
$currencySign = '$';
$curStmt = $conn->prepare("SELECT fromUSD, currency_sign FROM currencies WHERE currency = ?");
$curStmt->bind_param('s', $filter_currency);
$curStmt->execute();
$curRes = $curStmt->get_result();
if ($curRes && $curRes->num_rows > 0) {
    $row = $curRes->fetch_assoc();
    $currencyRate = floatval($row['fromUSD']);
    $currencySign = $row['currency_sign'];
}
$curStmt->close();

$where = "1=1";
$params = [];
$types = '';

if ($filter_brand !== "All") {
    $where .= " AND b.brand_name = ?";
    $types .= 's';
    $params[] = $filter_brand;
}

if ($filter_gender !== "All") {
    $gender_db = $filter_gender === "Women" ? "For her" : ($filter_gender === "Men" ? "For him" : "Unisex");
    $where .= " AND p.Gender = ?";
    $types .= 's';
    $params[] = $gender_db;
}

if ($filter_country === "exclusive") {
    $where .= " AND p.is_exclusive = 1";
}

// sort filters
$sortSQL = "p.perfume_name ASC";
switch ($sort) {
    case "name_desc": $sortSQL = "p.perfume_name DESC"; break;
    case "brand_asc": $sortSQL = "b.brand_name ASC"; break;
    case "brand_desc": $sortSQL = "b.brand_name DESC"; break;
    case "price_low": $sortSQL = "pv.selling_price ASC"; break;
    case "price_high": $sortSQL = "pv.selling_price DESC"; break;
}

// total perfumes
$countSQL = "SELECT COUNT(*) as total
FROM perfumes p
JOIN brands b ON p.brand_ID = b.brand_ID
JOIN perfume_volume pv ON p.perfume_ID = pv.perfume_ID
LEFT JOIN countries c ON p.country_ID = c.country_ID
WHERE $where";
$stmt = $conn->prepare($countSQL);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$totalProducts = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();
$pages = ($totalProducts > 0) ? ceil($totalProducts / $perPage) : 1;
$offset = ($page - 1) * $perPage;

// perfumes
$productSQL = "SELECT p.perfume_ID, p.perfume_name, p.Gender, p.image_name, b.brand_name, c.country_name, p.country_ID, p.is_exclusive, pv.perfume_volume_ID, pv.selling_price
FROM perfumes p
JOIN brands b ON p.brand_ID = b.brand_ID
JOIN perfume_volume pv ON p.perfume_ID = pv.perfume_ID
LEFT JOIN countries c ON p.country_ID = c.country_ID
WHERE $where
ORDER BY $sortSQL
LIMIT ?, ?";
$stmt = $conn->prepare($productSQL);
$execParams = $params;
$execTypes = $types . 'ii';
$execParams[] = $offset;
$execParams[] = $perPage;
$stmt->bind_param($execTypes, ...$execParams);
$stmt->execute();
$products = $stmt->get_result();
$stmt->close();

// brands
$brandList = $conn->query("SELECT brand_name FROM brands ORDER BY brand_name ASC");
$countryList = $conn->query("SELECT country_ID, country_name FROM countries ORDER BY country_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Buy Here - Aurum Scents</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/buy_here.css">
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

            <a class="nav-link me-3" href="cart.php"><i class="fa fa-cart-shopping"></i></a>

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

<!-- filters and catalog -->
<div class="container mt-4">
<h2 class="fw-bold">All Perfumes</h2>
<?php
$activeFilters = [];
if ($filter_brand !== "All") $activeFilters[] = "Brand: <strong>" . htmlspecialchars($filter_brand) . "</strong>";
if ($filter_gender !== "All") $activeFilters[] = "Gender: <strong>" . htmlspecialchars($filter_gender) . "</strong>";
if ($filter_country === "exclusive") $activeFilters[] = "Country Exclusive Only";
$sortLabels = [
    "name_asc" => "Name (A-Z)",
    "name_desc" => "Name (Z-A)",
    "brand_asc" => "Brand (A-Z)",
    "brand_desc" => "Brand (Z-A)",
    "price_low" => "Price: Low to High",
    "price_high" => "Price: High to Low"
];
if ($sort !== "name_asc") $activeFilters[] = "Sort: <strong>{$sortLabels[$sort]}</strong>";
?>
<?php if (!empty($activeFilters)): ?>
    <p class="filters-applied">Showing: <?= implode(" • ", $activeFilters) ?></p>
    <a href="buy_here.php" class="btn btn-sm btn-clear-filters btn-outline-secondary">Clear Filters</a>
<?php else: ?>
    <p class="filters-applied">No filters applied — showing all perfumes.</p>
<?php endif; ?>
</div>

<section class="catalog-section mt-3">
<div class="container">
<div class="row">

    <!-- sidebar -->
    <div class="col-md-3 mb-4">
        <form method="GET" action="buy_here.php" class="filter-card p-3">
            <h4>Filters</h4>
            <label>Brand</label>
            <select name="brand" class="form-select mb-3">
                <option value="All">All</option>
                <?php while($b = $brandList->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($b['brand_name']) ?>" <?= $filter_brand == $b['brand_name'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['brand_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Gender</label>
            <select name="gender" class="form-select mb-3">
                <option value="All">All</option>
                <option value="Women" <?= $filter_gender=="Women"?"selected":"" ?>>Women</option>
                <option value="Men" <?= $filter_gender=="Men"?"selected":"" ?>>Men</option>
                <option value="Unisex" <?= $filter_gender=="Unisex"?"selected":"" ?>>Unisex</option>
            </select>

            <label>Country Exclusive</label>
            <select name="country" class="form-select mb-3">
                <option value="" <?= $filter_country==""?'selected':'' ?>>All Perfumes</option>
                <option value="exclusive" <?= $filter_country=="exclusive"?'selected':'' ?>>Country Exclusive Only</option>
            </select>

            <label>Sort By</label>
            <select name="sort" class="form-select mb-3">
                <option value="name_asc" <?= $sort=="name_asc"?"selected":"" ?>>Name (A-Z)</option>
                <option value="name_desc" <?= $sort=="name_desc"?"selected":"" ?>>Name (Z-A)</option>
                <option value="brand_asc" <?= $sort=="brand_asc"?"selected":"" ?>>Brand (A-Z)</option>
                <option value="brand_desc" <?= $sort=="brand_desc"?"selected":"" ?>>Brand (Z-A)</option>
                <option value="price_low" <?= $sort=="price_low"?"selected":"" ?>>Price: Low to High</option>
                <option value="price_high" <?= $sort=="price_high"?"selected":"" ?>>Price: High to Low</option>
            </select>

            <input type="hidden" name="currency" value="<?= htmlspecialchars($filter_currency) ?>">
            <button type="submit" class="btn btn-dark w-100">Apply Filters</button>
        </form>
    </div>

<!-- perfumes -->
<div class="col-md-9">
    <div class="row g-4" id="perfumeCatalog">
        <?php if ($products && $products->num_rows > 0): ?>
            <?php while($prod = $products->fetch_assoc()): 
                $isRestricted = false;
                if ($prod['is_exclusive'] == 1 && !empty($prod['country_ID']) && $customerCountry !== null && $prod['country_ID'] != $customerCountry)
                    $isRestricted = true;

                $totalStock = $inventoryTotals[$prod['perfume_volume_ID']] ?? 0;
                $soldOut = $totalStock == 0;

                $convertedPrice = floatval($prod['selling_price']) * $currencyRate;
                $unitPriceUsd = floatval($prod['selling_price']);
            ?>
            <div class="col-md-4">
                <div class="product-card p-3 position-relative">
                    <input type="checkbox" class="checkout_selected mb-2" value="<?= htmlspecialchars($prod['perfume_volume_ID']) ?>">
                    <a href="perfume_details.php?perfume_volume_ID=<?= htmlspecialchars($prod['perfume_volume_ID']) ?>" class="text-decoration-none text-dark">
                        <img src="images/<?= htmlspecialchars($prod['image_name']) ?>" class="card-img-top" alt="<?= htmlspecialchars($prod['perfume_name']) ?>">
                        <h5 class="mt-2"><?= htmlspecialchars($prod['perfume_name']) ?></h5>
                        <p class="text-muted"><?= htmlspecialchars($prod['brand_name']) ?></p>
                    </a>
                    <?php if (!empty($prod['country_name'])): ?>
                        <p class="country-label badge bg-info mb-2"><?= htmlspecialchars($prod['country_name']) ?></p>
                    <?php endif; ?>

                    <p class="price"
                       data-usd="<?= htmlspecialchars(number_format($unitPriceUsd, 2, '.', '')) ?>"
                       data-rate="<?= htmlspecialchars(number_format($currencyRate, 6, '.', '')) ?>"
                       data-sign="<?= htmlspecialchars($currencySign) ?>"
                       data-stock="<?= $totalStock ?>">
                        <?= htmlspecialchars($currencySign) . number_format($convertedPrice, 2) ?>
                    </p>

                    <?php if ($isRestricted): ?>
                        <p class="badge bg-danger text-white text-center mb-2">Not for Sale</p>
                    <?php elseif ($soldOut): ?>
                        <p class="badge bg-danger text-white text-center mb-2">Sold Out</p>
                    <?php else: ?>
                        <form class="add-to-cart-form">
                            <input type="hidden" name="perfume_volume_ID" value="<?= htmlspecialchars($prod['perfume_volume_ID']) ?>">
                            <input type="hidden" name="quantity" class="qty-input-hidden" value="1">

                            <div class="quantity d-flex justify-content-center align-items-center mb-2">
                                <button type="button" class="btn qty-btn minus-btn">-</button>
                                <input type="number" value="1" min="1" class="form-control mx-2 text-center qty-input">
                                <button type="button" class="btn qty-btn plus-btn">+</button>
                            </div>

                            <p class="total-price text-center mb-2">Total: <?= htmlspecialchars($currencySign) . number_format($convertedPrice, 2) ?></p>
                            <button type="button" class="btn btn-buy w-100 add-to-cart-btn">Add to Cart</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <p class="text-center fs-5 mt-4 no-perfumes"><b>No perfumes found.</b></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// qty buttons and total
document.querySelectorAll('.product-card').forEach(card => {
    const qtyInput = card.querySelector('.qty-input');
    const hiddenQty = card.querySelector('.qty-input-hidden');
    const totalPriceEl = card.querySelector('.total-price');
    if (!qtyInput || !hiddenQty || !totalPriceEl) return;

    const minusBtn = card.querySelector('.minus-btn');
    const plusBtn = card.querySelector('.plus-btn');
    const priceEl = card.querySelector('.price');
    const unitPriceUSD = parseFloat(priceEl.dataset.usd);
    const currencyRate = parseFloat(priceEl.dataset.rate);
    const currencySign = priceEl.dataset.sign;
    const maxStock = Math.min(parseInt(priceEl.dataset.stock), 9999);

    qtyInput.max = maxStock;

    const updateQty = () => {
        let qty = parseInt(qtyInput.value) || 1;
        if (qty < 1) qty = 1;
        if (qty > maxStock) qty = maxStock;
        qtyInput.value = qty;
        hiddenQty.value = qty;
        totalPriceEl.textContent = `Total: ${currencySign}${(unitPriceUSD * currencyRate * qty).toFixed(2)}`;

        minusBtn.disabled = qty <= 1;
        plusBtn.disabled = qty >= maxStock;
    };

    minusBtn.addEventListener('click', () => {
        qtyInput.stepDown();
        updateQty();
    });

    plusBtn.addEventListener('click', () => {
        qtyInput.stepUp();
        updateQty();
    });

    qtyInput.addEventListener('input', updateQty);

    updateQty();
});

// add to cart
document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const form = btn.closest('.add-to-cart-form');
        const perfumeVolID = form.querySelector('[name="perfume_volume_ID"]').value;
        const quantity = parseInt(form.querySelector('[name="quantity"]').value);
        const maxStock = parseInt(form.closest('.product-card').querySelector('.price').dataset.stock);

        if (quantity > maxStock) {
            alert(`Cannot add more than ${maxStock} item(s) to cart.`);
            return;
        }

        fetch('buy_here.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'add_to_cart',
                perfume_volume_ID: perfumeVolID,
                quantity: quantity
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Added to cart!');
            } else {
                alert('Error: ' + data.message);
            }
        });
    });
});

document.querySelectorAll('.qty-input').forEach(input => {
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            input.blur();
        }
    });
});
</script>
</body>
</html>
