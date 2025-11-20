<?php 

require'check_session.php';

$dbpath = dirname(__DIR__) . "/db_connect.php";
include($dbpath);

$result = $conn->query("SELECT * FROM customers");

$resultDiscounts = $conn->query("SELECT * FROM discounts");

?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - Discounts</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="../css/admin_sidebar.css" rel="stylesheet">
    <link href="../css/admin_general.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif; 
        }

        .main {
            margin-top: 20px; 
        }

        .table td, .table th { 
            vertical-align: middle; 
        }
    </style>
</head>
<body>
<?php require 'admin_sidebar.php'; ?>

<div class="container main mb-5 p-4">
    <h3 class="mb-4 page-title">Discounts</h3>

    <?php if (isset($_GET['message']) && isset($_GET['status'])): 
        $message = $_GET['message']; 
        $status = $_GET['status']; ?>
        <div class="alert alert-<?= $status ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

 
    <div class="row">
        <div class="col-md-6">
            <h5>Add Discount</h5>
            <form method="post" class="mb-4" action="process_discounts.php">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Discount Code</label>
                    <input type="text" name="discount" maxlength="10" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Discount Percent</label>
                    <input type="number" step="0.01" name="discount_percent" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Customer</label>
                    <select name="customer_ID" class="form-control">
                        <option selected value="">None</option>
                        <?php while ($rowCustomers = $result->fetch_assoc()) { ?>
                        <option value="<?= $rowCustomers['customer_ID']?>"><?= $rowCustomers['email'] ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Valid From</label>
                    <input type="date" name="valid_from" class="form-control">
                </div>
                <div class="form-group">
                    <label>Valid Until</label>
                    <input type="date" name="valid_until" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">
                    Add discount
                </button>
            </form>
        </div>

        <div class="col-md-6">
            <h5>Existing Discounts</h5>
            <table class="table table-bordered table-sm">
                <thead class="thead-light">
                    <tr>
                        <th>Code</th>
                        <th>Discount Percent</th>
                        <th>Customer</th>
                        <th>Valid from</th>
                        <th>Valid until</th>
                        <th style="width:120px;"></th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $resultDiscounts->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['discount_code']) ?></td>
                        <td><?= htmlspecialchars($row['discount_percent']) ?></td>
                        <td><?= htmlspecialchars($row['customer_ID']) ?></td>
                        <td><?= htmlspecialchars($row['valid_from']) ?></td>
                        <td><?= htmlspecialchars($row['valid_until']) ?></td>
                        <td>
                            <form method="POST" action="process_discounts.php">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="discount_code" value="<?= $row['discount_code'] ?>">
                                <button class="btn btn-sm btn-danger"
                                 onclick="return confirm('Delete discount <?= $row['discount_code'] ?>?');">
                                <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($resultDiscounts->num_rows === 0) { ?>
                    <tr><td colspan="4" class="text-center">No Discounts yet.</td></tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</body>
</html>