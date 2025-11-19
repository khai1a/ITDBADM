<?php
session_start();
require 'db_connect.php';

$customerID = $_SESSION['customer_ID'] ?? null;
$customerCountry = null;

// country ng customer
if ($customerID) {
    $stmt = $conn->prepare("SELECT country_ID FROM customers WHERE customer_ID = ?");
    $stmt->bind_param("s", $customerID);
    $stmt->execute();
    $res = $stmt->get_result();
    $customerCountry = $res->fetch_assoc()['country_ID'] ?? null;
    $stmt->close();
}

// currency logic
$filter_currency = $_GET['currency'] ?? 'USD';
$currencyRate = 1.0;
$currencySign = '$';
$curStmt = $conn->prepare("SELECT fromUSD, currency_sign FROM currencies WHERE currency = ?");
$curStmt->bind_param("s", $filter_currency);
$curStmt->execute();
$curRes = $curStmt->get_result();
if ($curRes && $curRes->num_rows > 0) {
    $row = $curRes->fetch_assoc();
    $currencyRate = floatval($row['fromUSD']);
    $currencySign = $row['currency_sign'];
}
$curStmt->close();

// perfume volume ID
$perfumeVolID = $_GET['perfume_volume_ID'] ?? '';
if (!$perfumeVolID) {
    header("Location: buy_here.php");
    exit;
}

// perfume deets
$stmt = $conn->prepare("
    SELECT p.*, b.brand_name, c.country_name, pv.volume, pv.selling_price, pv.currency
    FROM perfume_volume pv
    JOIN perfumes p ON pv.perfume_ID = p.perfume_ID
    JOIN brands b ON p.brand_ID = b.brand_ID
    LEFT JOIN countries c ON p.country_ID = c.country_ID
    WHERE pv.perfume_volume_ID = ?
");
$stmt->bind_param("s", $perfumeVolID);
$stmt->execute();
$perfume = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$perfume) {
    echo "Perfume not found.";
    exit;
}

// country exclusivity
$isRestricted = false;
if ($perfume['is_exclusive'] == 1 && $customerCountry !== null && !empty($perfume['country_ID']) && $perfume['country_ID'] != $customerCountry) {
    $isRestricted = true;
}

// accords
$accords = [];
$stmt = $conn->prepare("
    SELECT a.accord_name
    FROM perfume_accords pa
    JOIN accords a ON pa.accord_ID = a.accord_ID
    WHERE pa.perfume_ID = ?
");
$stmt->bind_param("s", $perfume['perfume_ID']);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $accords[] = $row['accord_name'];
}
$stmt->close();

// notes
$notes = [];
$stmt = $conn->prepare("
    SELECT n.note_name, pn.note_level
    FROM perfume_notes pn
    JOIN notes n ON pn.note_ID = n.note_ID
    WHERE pn.perfume_ID = ?
    ORDER BY FIELD(pn.note_level,'top','middle','base')
");
$stmt->bind_param("s", $perfume['perfume_ID']);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $notes[] = ucfirst($row['note_level']) . ": " . $row['note_name'];
}
$stmt->close();

// inventory
$stmt = $conn->prepare("SELECT SUM(quantity) as total_qty FROM inventory WHERE perfume_volume_ID = ?");
$stmt->bind_param("s", $perfumeVolID);
$stmt->execute();
$totalStock = intval($stmt->get_result()->fetch_assoc()['total_qty'] ?? 0);
$stmt->close();

$lowStock = ($totalStock > 0 && $totalStock < 30);
$soldOut = ($totalStock == 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($perfume['perfume_name']) ?> - Aurum Scents</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/perfume_details.css">
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
                            <a class="dropdown-item" href="?currency=<?= urlencode($c['currency']) ?>&perfume_volume_ID=<?= urlencode($perfumeVolID) ?>">
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

<section class="perfume-details my-4">
<div class="container">
    <button class="btn btn-back mb-3" onclick="window.location.href='buy_here.php'">
        <i class="fa fa-arrow-left"></i> Back to Catalogue
    </button>

    <div class="row shadow p-3 rounded bg-light details-card">
      <div class="col-md-5">
        <img src="images/<?= htmlspecialchars($perfume['image_name']) ?>" class="img-fluid" alt="<?= htmlspecialchars($perfume['perfume_name']) ?>">
      </div>
      <div class="col-md-7">
        <h2><?= htmlspecialchars($perfume['perfume_name']) ?></h2>
        <p><strong>Brand:</strong> <?= htmlspecialchars($perfume['brand_name']) ?></p>
        <?php if ($perfume['is_exclusive'] && !empty($perfume['country_name'])): ?>
            <p><strong>Country Exclusive:</strong> <?= htmlspecialchars($perfume['country_name']) ?></p>
        <?php endif; ?>
        <p><strong>Price:</strong> <?= $currencySign . number_format($perfume['selling_price'] * $currencyRate, 2) ?></p>
        <p><strong>Concentration:</strong> <?= htmlspecialchars($perfume['concentration']) ?></p>
        <p><strong>Volume:</strong> <?= htmlspecialchars($perfume['volume']) ?> ml</p>
        <p><strong>Accords:</strong> <?= implode(", ", $accords) ?: "None" ?></p>
        <p><strong>Notes:</strong> <?= implode(", ", $notes) ?: "None" ?></p>

        <?php if ($lowStock): ?>
            <p class="text-warning"><strong>Low Stock!</strong></p>
        <?php elseif ($soldOut): ?>
            <p class="text-danger"><strong>Sold Out</strong></p>
        <?php endif; ?>

        <div class="d-flex align-items-center my-3 qty-controls">
          <button id="qtyMinus" class="btn btn-outline-secondary" <?= ($soldOut || $isRestricted) ? 'disabled' : '' ?>>-</button>
          <input type="text" id="qtyInput" value="1" class="form-control mx-2" style="width:60px;" <?= ($soldOut || $isRestricted) ? 'disabled' : '' ?>>
          <button id="qtyPlus" class="btn btn-outline-secondary" <?= ($soldOut || $isRestricted) ? 'disabled' : '' ?>>+</button>
        </div>

        <button id="addToCartBtn" class="btn btn-primary" <?= ($soldOut || $isRestricted) ? 'disabled' : '' ?>>
            <?= ($soldOut || $isRestricted) ? 'Not Available' : 'Add to Cart' ?>
        </button>
      </div>
    </div>
</div>
</section>

<script>
const qtyInput = document.getElementById('qtyInput');
document.getElementById('qtyPlus')?.addEventListener('click', () => {
    qtyInput.value = parseInt(qtyInput.value) + 1;
});
document.getElementById('qtyMinus')?.addEventListener('click', () => {
    if (parseInt(qtyInput.value) > 1) qtyInput.value = parseInt(qtyInput.value) - 1;
});

document.getElementById('addToCartBtn')?.addEventListener('click', () => {
    const qty = parseInt(qtyInput.value);
    fetch('buy_here.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add_to_cart&perfume_volume_ID=<?= $perfumeVolID ?>&quantity=${qty}`
    }).then(res => res.json()).then(data => {
        alert(data.message);
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


