<?php
// admin_brands.php
$dbpath = dirname(__DIR__) . "/db_connect.php";
include($dbpath); 


if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

if(isset($_GET['status'])) {
    $status = $_GET['status'];
}

$brandsResult = $conn->query("SELECT * FROM brands ORDER BY brand_name");

$editBrand = null;
if (isset($_GET['edit_brand'])) {
    $id = $_GET['edit_brand'];
    $stmt = $conn->prepare("SELECT * FROM brands WHERE brand_ID = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $editBrand = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - Brands</title>
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
        .table td, 
        .table th {
             vertical-align: middle; 
        }
    </style>
</head>
<body>
<?php if (file_exists('admin_sidebar.php')) require 'admin_sidebar.php'; ?>

<div class="container main mb-5 p-4">
    <h3 class="page-title mb-5">Brands</h3>

    <?php if (isset($message)): ?>
        <div class="alert alert-<?= $status ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-5">
            <h5><?= $editBrand ? 'Edit Brand' : 'Add Brand' ?></h5>
            <form method="post" class="mb-4" action="process_brand.php">
                <input type="hidden" name="action" value="<?= $editBrand ? 'update' : 'create' ?>">

                <div class="form-group">
                    <label>Brand Name</label>
                    <input type="text" name="brand_name" class="form-control"
                           value="<?= $editBrand['brand_name'] ?? '' ?>" required>
                </div>
                <div class="form-group">
                    <label>Brand Type</label>
                    <select name="brand_type" class="form-control" required>
                        <option value="" disabled <?= $editBrand ? '' : 'selected' ?>>Select type</option>
                        <option value="Designer"
                            <?= ($editBrand && $editBrand['brand_type'] === 'Designer') ? 'selected' : '' ?>>
                            Designer
                        </option>
                        <option value="Niche"
                            <?= ($editBrand && $editBrand['brand_type'] === 'Niche') ? 'selected' : '' ?>>
                            Niche
                        </option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    <?= $editBrand ? 'Update Brand' : 'Add Brand' ?>
                </button>
                <?php if ($editBrand): ?>
                    <a href="admin_brands.php" class="btn btn-secondary ml-2">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="col-md-7">
            <h5>Existing Brands</h5>
            <table class="table table-bordered table-sm">
                <thead class="thead-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th style="width:140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $brandsResult->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['brand_ID']) ?></td>
                        <td><?= htmlspecialchars($row['brand_name']) ?></td>
                        <td><?= htmlspecialchars($row['brand_type']) ?></td>
                        <td>
                            <a href="?edit_brand=<?= $row['brand_ID'] ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?delete_brand=<?= $row['brand_ID'] ?>"
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Delete brand <?= $row['brand_name'] ?>?');">
                               <i class="fa-solid fa-trash-can"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($brandsResult->num_rows === 0): ?>
                    <tr><td colspan="4" class="text-center">No brands yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>