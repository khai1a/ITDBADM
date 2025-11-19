<?php

$dbpath = dirname(__DIR__) . "/db_connect.php";
include($dbpath);

$message = '';
$status = '';

$currenciesResult = $conn->query("SELECT * FROM currencies ORDER BY currency");
$countriesResult = $conn->query("
    SELECT c.country_ID, c.country_name, c.currency, c.vat_percent
    FROM countries c
    ORDER BY c.country_name
");

$allCurrencies = $conn->query("SELECT currency FROM currencies ORDER BY currency");


$editCurrency = null;
$editCountry = null;

if (isset($_GET['edit_currency'])) {
    $code = $_GET['edit_currency'];
    $stmt = $conn->prepare("SELECT * FROM currencies WHERE currency = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $editCurrency = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (isset($_GET['edit_country'])) {
    $id = $_GET['edit_country'];
    $stmt = $conn->prepare("SELECT * FROM countries WHERE country_ID = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $editCountry = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - Currencies & Countries</title>
   <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="../css/admin_sidebar.css" rel="stylesheet">
    <link href="../css/admin_general.css" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .main { margin-top: 20px; }
        .table td, .table th { vertical-align: middle; }
    </style>
</head>
<body>
<?php if (file_exists('admin_sidebar.php')) require 'admin_sidebar.php'; ?>

<div class="container main mb-5 p-4">
    <h3 class="mb-4 page-title">Currencies & Countries</h3>

    <?php if (isset($_GET['message']) && isset($_GET['status'])): 
        $message = $_GET['message']; 
        $status = $_GET['status']; ?>
        <div class="alert alert-<?= $status ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

 
    <div class="row">
        <div class="col-md-6">
            <h5><?= $editCurrency ? 'Edit Currency' : 'Add Currency' ?></h5>
            <form method="post" class="mb-4" action="admin_process_countries_currencies.php">
                <input type="hidden" name="entity" value="currency">
                <input type="hidden" name="action" value="<?= $editCurrency ? 'update' : 'create' ?>">

                <div class="form-group">
                    <label>Currency Code (3 letters)</label>
                    <input type="text" name="currency" maxlength="3" class="form-control"
                           value="<?= $editCurrency['currency'] ?? '' ?>"
                           <?= $editCurrency ? 'readonly' : '' ?> required>
                </div>
                <div class="form-group">
                    <label>Conversion from USD (fromUSD)</label>
                    <input type="number" step="0.01" name="fromUSD" class="form-control"
                           value="<?= $editCurrency['fromUSD'] ?? '1.00' ?>" required>
                </div>
                <div class="form-group">
                    <label>Currency Sign</label>
                    <input type="text" name="currency_sign" maxlength="3" class="form-control"
                           value="<?= $editCurrency['currency_sign'] ?? '' ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">
                    <?= $editCurrency ? 'Update Currency' : 'Add Currency' ?>
                </button>
                <?php if ($editCurrency): ?>
                    <a href="admin_countries_currencies.php" class="btn btn-secondary ml-2">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="col-md-6">
            <h5>Existing Currencies</h5>
            <table class="table table-bordered table-sm">
                <thead class="thead-light">
                    <tr>
                        <th>Code</th>
                        <th>fromUSD</th>
                        <th>Sign</th>
                        <th style="width:120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $currenciesResult->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['currency']) ?></td>
                        <td><?= htmlspecialchars($row['fromUSD']) ?></td>
                        <td><?= htmlspecialchars($row['currency_sign']) ?></td>
                        <td>
                            <a href="?edit_currency=<?= $row['currency'] ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="admin_process_countries_currencies.php?delete_currency=<?= $row['currency'] ?>"
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Delete currency <?= $row['currency'] ?>? This may fail if countries use it.');">
                               <i class="fa-solid fa-trash-can"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($currenciesResult->num_rows === 0): ?>
                    <tr><td colspan="4" class="text-center">No currencies yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <hr class="my-5">

    <div class="row">
        <div class="col-md-6">
            <h5><?= $editCountry ? 'Edit Country' : 'Add Country' ?></h5>
            <form method="post" class="mb-4" action="admin_process_countries_currencies.php">
                <input type="hidden" name="entity" value="country">
                <input type="hidden" name="action" value="<?= $editCountry ? 'update' : 'create' ?>">

                <div class="form-group">
                    <label>Country ID (5 chars)</label>
                    <input type="text" name="country_ID" maxlength="5" class="form-control"
                           value="<?= $editCountry['country_ID'] ?? '' ?>"
                           <?= $editCountry ? 'readonly' : '' ?> required>
                </div>
                <div class="form-group">
                    <label>Country Name</label>
                    <input type="text" name="country_name" class="form-control"
                           value="<?= $editCountry['country_name'] ?? '' ?>" required>
                </div>
                <div class="form-group">
                    <label>Currency</label>
                    <select name="currency" class="form-control" required>
                        <option value="" disabled <?= $editCountry ? '' : 'selected' ?>>Select currency</option>
                        <?php
                        // reset currencies result pointer for dropdown
                        $allCurrencies->data_seek(0);
                        while ($c = $allCurrencies->fetch_assoc()):
                            $selected = ($editCountry && $editCountry['currency'] === $c['currency']) ? 'selected' : '';
                        ?>
                            <option value="<?= $c['currency'] ?>" <?= $selected ?>><?= $c['currency'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>VAT Percent (e.g. 0.12 for 12%)</label>
                    <input type="number" step="0.01" name="vat_percent" class="form-control"
                           value="<?= $editCountry['vat_percent'] ?? '0.12' ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">
                    <?= $editCountry ? 'Update Country' : 'Add Country' ?>
                </button>
                <?php if ($editCountry): ?>
                    <a href="admin_countries_currencies.php" class="btn btn-secondary ml-2">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="col-md-6">
            <h5>Existing Countries</h5>
            <table class="table table-bordered table-sm">
                <thead class="thead-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Currency</th>
                        <th>VAT %</th>
                        <th style="width:120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $countriesResult->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['country_ID']) ?></td>
                        <td><?= htmlspecialchars($row['country_name']) ?></td>
                        <td><?= htmlspecialchars($row['currency']) ?></td>
                        <td><?= htmlspecialchars($row['vat_percent']) ?></td>
                        <td>
                            <a href="?edit_country=<?= $row['country_ID'] ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="admin_process_countries_currencies.php?delete_country=<?= $row['country_ID'] ?>"
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Delete country <?= $row['country_ID'] ?>?');">
                               <i class="fa-solid fa-trash-can"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($countriesResult->num_rows === 0): ?>
                    <tr><td colspan="5" class="text-center">No countries yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</body>
</html>