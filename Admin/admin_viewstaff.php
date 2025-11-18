<?php
$dbpath = dirname(__DIR__) . "/db_connect.php";
include($dbpath);

$message = $_GET['message'];

$resultBranches = $conn->query("SELECT branch_ID, address FROM branches ORDER BY branch_ID");

$branches = [];
if ($resultBranches) {
    while ($b = $resultBranches->fetch_assoc()) {
        $branches[] = $b;
    }
}

// Get staff list
$resultStaff = $conn->query("
    SELECT s.staff_ID, s.username, s.role, s.branch_ID, b.address
    FROM staff s
    LEFT JOIN branches b ON s.branch_ID = b.branch_ID
    ORDER BY s.staff_ID
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Manage Staff</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/admin_sidebar.css" rel="stylesheet">
    <link href="../css/admin_general.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .page-title {
            font-weight: 600;
            color: #A3485A;
        }
        .card-staff {
            border-radius: 1rem;
            margin-bottom: 1rem;
            background-color: #f7f3f3;
        }
        .card-staff .card-header {
            background-color: transparent;
            border-bottom: none;
            font-weight: 600;
        }
        .card-staff .form-control,
        .card-staff .custom-select {
            font-size: 0.9rem;
        }
        .btn-update {
            background-color: #A3485A;
            border-color: #A3485A;
            color: whitesmoke;
        }
        .btn-update:hover {
            background-color: #8c3b4b;
            border-color: #8c3b4b;
            color: whitesmoke;
        }
    </style>
</head>
<body>

<?php require 'admin_sidebar.php'; ?>

<div class="container main p-5">
    <div class="d-flex flex-row justify-content-between mb-4">
        <h3 class="page-title">Manage Staff Accounts</h3>
    </div>

    <?php if (isset($_GET['message'])) { ?>
        <div class="alert alert-info" role="alert">
            <?= $message ?>
        </div>
    <?php } ?>

    <div class="card mb-3">
        <div class="card-body">
            <p class="mb-0">
                Leave the password field blank if you do not want to change the password.
            </p>
        </div>
    </div>

    <?php if ($resultStaff && $resultStaff->num_rows > 0) { ?>
        <?php while ($row = $resultStaff->fetch_assoc()) { ?>
            <div class="card card-staff">
                <div class="card-header">
                    Staff ID: <?= htmlspecialchars($row['staff_ID']) ?>
                </div>
                <div class="card-body">
                    <form method="POST" action="update_staff.php">
                        <input type="hidden" name="staff_ID" value="<?= htmlspecialchars($row['staff_ID']) ?>">

                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Username</label>
                                <input 
                                    type="text" 
                                    name="username" 
                                    class="form-control" 
                                    value="<?= htmlspecialchars($row['username']) ?>" 
                                    required>
                            </div>

                            <div class="form-group col-md-3">
                                <label>Role</label>
                                <select name="role" class="custom-select" required>
                                    <?php
                                    $roles = ['Branch Manager','Branch Employee','Perfumer','Inter-Branch Manager','Admin'];
                                    foreach ($roles as $r) {
                                        $selected = ($row['role'] === $r) ? 'selected' : '';
                                        echo "<option value=\"$r\" $selected>$r</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group col-md-3">
                                <label>Branch</label>
                                <select name="branch_ID" class="custom-select">
                                    <option value="">(No branch)</option>
                                    <?php foreach ($branches as $b) { 
                                        $selected = ($row['branch_ID'] === $b['branch_ID']) ? 'selected' : '';
                                    ?>
                                        <option value="<?= htmlspecialchars($b['branch_ID']) ?>" <?= $selected ?>>
                                            <?= htmlspecialchars($b['branch_ID']) ?> - <?= htmlspecialchars($b['address']) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="form-group col-md-3">
                                <label>New Password</label>
                                <input 
                                    type="password" 
                                    name="password" 
                                    class="form-control" 
                                    placeholder="Leave blank to keep current">
                            </div>
                        </div>

                        <div class="text-right">
                            <button type="submit" class="btn btn-update">
                                Update Staff
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php } ?>
    <?php } else { ?>
        <div class="alert alert-secondary">
            No staff records found.
        </div>
    <?php } ?>
</div>

<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js"></script>
</body>
</html>
