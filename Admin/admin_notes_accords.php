<?php
// admin_notes_accords.php
$dbpath = dirname(__DIR__) . "/db_connect.php";
include($dbpath);

$message = '';
$status  = '';

// HANDLE CREATE / UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entity = $_POST['entity'] ?? '';
    $action = $_POST['action'] ?? '';

    if ($entity === 'note') {
        $note_ID   = strtoupper(trim($_POST['note_ID']));
        $note_name = trim($_POST['note_name']);

        if ($action === 'create') {
            $sql  = "INSERT INTO notes (note_ID, note_name) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $note_ID, $note_name);
        } elseif ($action === 'update') {
            $sql  = "UPDATE notes SET note_name = ? WHERE note_ID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $note_name, $note_ID);
        }

        if (isset($stmt)) {
            try {
                $stmt->execute();
                $message = "Note saved successfully.";
                $status  = "success";
            } catch (Exception $e) {
                $message = "Error saving note: " . $e->getMessage();
                $status  = "danger";
            }
            $stmt->close();
        }
    }

    if ($entity === 'accord') {
        $accord_ID   = strtoupper(trim($_POST['accord_ID']));
        $accord_name = trim($_POST['accord_name']);

        if ($action === 'create') {
            $sql  = "INSERT INTO accords (accord_ID, accord_name) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $accord_ID, $accord_name);
        } elseif ($action === 'update') {
            $sql  = "UPDATE accords SET accord_name = ? WHERE accord_ID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $accord_name, $accord_ID);
        }

        if (isset($stmt)) {
            try {
                $stmt->execute();
                $message = "Accord saved successfully.";
                $status  = "success";
            } catch (Exception $e) {
                $message = "Error saving accord: " . $e->getMessage();
                $status  = "danger";
            }
            $stmt->close();
        }
    }
}

// HANDLE DELETE
if (isset($_GET['delete_note'])) {
    $id   = $_GET['delete_note'];
    $stmt = $conn->prepare("DELETE FROM notes WHERE note_ID = ?");
    $stmt->bind_param("s", $id);
    try {
        $stmt->execute();
        $message = "Note $id deleted.";
        $status  = "success";
    } catch (Exception $e) {
        $message = "Error deleting note: " . $e->getMessage();
        $status  = "danger";
    }
    $stmt->close();
}

if (isset($_GET['delete_accord'])) {
    $id   = $_GET['delete_accord'];
    $stmt = $conn->prepare("DELETE FROM accords WHERE accord_ID = ?");
    $stmt->bind_param("s", $id);
    try {
        $stmt->execute();
        $message = "Accord $id deleted.";
        $status  = "success";
    } catch (Exception $e) {
        $message = "Error deleting accord: " . $e->getMessage();
        $status  = "danger";
    }
    $stmt->close();
}

// LOAD RECORDS
$notesResult   = $conn->query("SELECT * FROM notes ORDER BY note_name");
$accordsResult = $conn->query("SELECT * FROM accords ORDER BY accord_name");

// EDIT MODE
$editNote   = null;
$editAccord = null;

if (isset($_GET['edit_note'])) {
    $id   = $_GET['edit_note'];
    $stmt = $conn->prepare("SELECT * FROM notes WHERE note_ID = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $editNote = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (isset($_GET['edit_accord'])) {
    $id   = $_GET['edit_accord'];
    $stmt = $conn->prepare("SELECT * FROM accords WHERE accord_ID = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $editAccord = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - Notes & Accords</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .main { margin-top: 20px; }
        .table td, .table th { vertical-align: middle; }
    </style>
</head>
<body>
<?php
// include sidebar if you have one
if (file_exists('admin_sidebar.php')) require 'admin_sidebar.php';
?>

<div class="container main mb-5 p-4">
    <h3 class="mb-4">Notes & Accords</h3>

    <?php if ($message): ?>
        <div class="alert alert-<?= $status ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- NOTES SECTION -->
    <div class="row">
        <div class="col-md-6">
            <h5><?= $editNote ? 'Edit Note' : 'Add Note' ?></h5>
            <form method="post" class="mb-4">
                <input type="hidden" name="entity" value="note">
                <input type="hidden" name="action" value="<?= $editNote ? 'update' : 'create' ?>">

                <div class="form-group">
                    <label>Note ID (7 chars)</label>
                    <input type="text" name="note_ID" maxlength="7" class="form-control"
                           value="<?= $editNote['note_ID'] ?? '' ?>"
                           <?= $editNote ? 'readonly' : '' ?> required>
                </div>
                <div class="form-group">
                    <label>Note Name</label>
                    <input type="text" name="note_name" class="form-control"
                           value="<?= $editNote['note_name'] ?? '' ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">
                    <?= $editNote ? 'Update Note' : 'Add Note' ?>
                </button>
                <?php if ($editNote): ?>
                    <a href="admin_notes_accords.php" class="btn btn-secondary ml-2">Cancel</a>
                <?php endif; ?>
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
                            <th style="width:140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($notesResult->num_rows > 0): ?>
                        <?php while ($row = $notesResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['note_ID']) ?></td>
                                <td><?= htmlspecialchars($row['note_name']) ?></td>
                                <td>
                                    <a href="?edit_note=<?= $row['note_ID'] ?>" class="btn btn-sm btn-info">Edit</a>
                                    <a href="?delete_note=<?= $row['note_ID'] ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Delete note <?= $row['note_name'] ?>?');">
                                        Delete
                                    </a>
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

    <!-- ACCORDS SECTION -->
    <div class="row">
        <div class="col-md-6">
            <h5><?= $editAccord ? 'Edit Accord' : 'Add Accord' ?></h5>
            <form method="post" class="mb-4">
                <input type="hidden" name="entity" value="accord">
                <input type="hidden" name="action" value="<?= $editAccord ? 'update' : 'create' ?>">

                <div class="form-group">
                    <label>Accord ID (7 chars)</label>
                    <input type="text" name="accord_ID" maxlength="7" class="form-control"
                           value="<?= $editAccord['accord_ID'] ?? '' ?>"
                           <?= $editAccord ? 'readonly' : '' ?> required>
                </div>
                <div class="form-group">
                    <label>Accord Name</label>
                    <input type="text" name="accord_name" class="form-control"
                           value="<?= $editAccord['accord_name'] ?? '' ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">
                    <?= $editAccord ? 'Update Accord' : 'Add Accord' ?>
                </button>
                <?php if ($editAccord): ?>
                    <a href="admin_notes_accords.php" class="btn btn-secondary ml-2">Cancel</a>
                <?php endif; ?>
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
                                    <a href="?edit_accord=<?= $row['accord_ID'] ?>" class="btn btn-sm btn-info">Edit</a>
                                    <a href="?delete_accord=<?= $row['accord_ID'] ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Delete accord <?= $row['accord_name'] ?>?');">
                                        Delete
                                    </a>
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