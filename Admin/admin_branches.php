<?php

require'check_session.php';

$dbpath = dirname(__DIR__) . "/db_connect.php";
include($dbpath);

$countriesResult = $conn->query("SELECT country_ID, country_name FROM countries ORDER BY country_name");

$branchesResult = $conn->query("
    SELECT b.branch_ID, b.country_ID, b.address, c.country_name
    FROM branches b
    JOIN countries c ON b.country_ID = c.country_ID
    ORDER BY c.country_name, b.branch_ID
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - Branches</title>
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

        textarea {
            resize: vertical;
        }
    </style>
</head>
<body>
<?php require 'admin_sidebar.php'; ?>

<div class="container main mb-5 p-4">
    <h3 class="mb-4 page-title">Branches</h3>

    <?php if (isset($_GET['message']) && isset($_GET['status'])): 
        $message = $_GET['message']; 
        $status = $_GET['status']; ?>
        <div class="alert alert-<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-5">
            <h5>Add Branch</h5>
            <form method="post" class="mb-4" action="process_branches.php">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Country</label>
                    <select name="country_ID" class="form-control" required>
                        <option value="">-- Select Country --</option>
                        <?php if ($countriesResult && $countriesResult->num_rows > 0) { ?>
                            <?php while ($c = $countriesResult->fetch_assoc()) { ?>
                                <option value="<?= htmlspecialchars($c['country_ID']) ?>">
                                    <?= htmlspecialchars($c['country_name']) ?>
                                </option>
                            <?php } ?>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="3" placeholder="Branch full address" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    Add Branch
                </button>
            </form>
        </div>

        <div class="col-md-7">
            <h5>Existing Branches</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:80px;">ID</th>
                            <th>Country</th>
                            <th>Address</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($branchesResult && $branchesResult->num_rows > 0) { ?>
                        <?php while ($row = $branchesResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['branch_ID']) ?></td>
                                <td><?= htmlspecialchars($row['country_name']) ?></td>
                                <td><?= nl2br(htmlspecialchars($row['address'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php } else { ?>
                        <tr><td colspan="4" class="text-center">No branches yet.</td></tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</body>
</html>