<?php

require'check_session.php';


$dbpath = dirname(__DIR__) . "/db_connect.php";
include($dbpath);

$notesResult = $conn->query("SELECT * FROM notes ORDER BY note_name");
$accordsResult = $conn->query("SELECT * FROM accords ORDER BY accord_name");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - Notes & Accords</title>
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
    <h3 class="mb-4 page-title">Notes & Accords</h3>

    <?php if (isset($_GET['message']) && isset($_GET['status'])): 
        $message = $_GET['message']; 
        $status = $_GET['status']; ?>
        <div class="alert alert-<?= $status ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <h5>Add Note</h5>
            <form method="post" class="mb-4" action="process_notes_accords.php">
                <input type="hidden" name="entity" value="note">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label>Note Name</label>
                    <input type="text" name="note_name" class="form-control" placeholder="i.e. Lavender" required>
                </div>

                <button type="submit" class="btn btn-primary">
                    Add note
                </button>
            </form>
        </div>

        <div class="col-md-6">
            <h5>Existing Notes</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th style="width:140px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($notesResult->num_rows > 0): ?>
                        <?php while ($row = $notesResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['note_ID']) ?></td>
                                <td><?= htmlspecialchars($row['note_name']) ?></td>
                                <td>
                                    <form method="POST" action="process_notes_accords.php">
                                        <input type="hidden" name="note_ID" value="<?= $row['note_ID'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="entity" value="note">
                                        <button class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete note <?= $row['note_name'] ?>?');">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="text-center">No notes yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <hr class="my-5">

    <div class="row">
        <div class="col-md-6">
            <h5>Add Accord</h5>
            <form method="post" class="mb-4" action="process_notes_accords.php">
                <input type="hidden" name="entity" value="accord">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label>Accord Name</label>
                    <input type="text" name="accord_name" placeholder="i.e. Musky" class="form-control">
                </div>

                <button type="submit" class="btn btn-primary">
                    Add Accord
                </button>
            </form>
        </div>

        <div class="col-md-6">
            <h5>Existing Accords</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th style="width:140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($accordsResult->num_rows > 0): ?>
                        <?php while ($row = $accordsResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['accord_ID']) ?></td>
                                <td><?= htmlspecialchars($row['accord_name']) ?></td>
                                <td>
                                    <form method="POST" action="process_notes_accords.php">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="entity" value="accord">
                                        <input type="hidden" name="accord_ID" value="<?= $row['accord_ID'] ?>">
                                        <button class="btn btn-sm btn-danger"
                                        onclick="return confirm('Delete accord <?= $row['accord_name'] ?>?');">
                                        <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                    
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="text-center">No accords yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</body>
</html>