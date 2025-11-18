<?php 

$dbpath = dirname(__DIR__) . "/db_connect.php";

include($dbpath);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['staff_ID'])) {
    $staff_ID = $conn->real_escape_string($_POST['staff_ID']);
    $username = $conn->real_escape_string($_POST['username']);
    $role     = $conn->real_escape_string($_POST['role']);
    $branch_ID = !empty($_POST['branch_ID']) ? $conn->real_escape_string($_POST['branch_ID']) : null;
    $password = isset($_POST['password']) ? $_POST['password'] : "";

    // Build update query
    if ($password !== "") {
        // Update including password (using SHA2 like your other scripts)
        $query = "
            UPDATE staff
            SET username = '$username',
                role = '$role',
                branch_ID = " . ($branch_ID ? "'$branch_ID'" : "NULL") . ",
                password = SHA2('$password', 256)
            WHERE staff_ID = '$staff_ID'
        ";
    } else {
        // Update without touching password
        $query = "
            UPDATE staff
            SET username = '$username',
                role = '$role',
                branch_ID = " . ($branch_ID ? "'$branch_ID'" : "NULL") . "
            WHERE staff_ID = '$staff_ID'
        ";
    }

    if ($conn->query($query)) {
        $message = "Staff account $staff_ID updated successfully.";
    } else {
        $message = "Error updating staff account: " . $conn->error;
    }
}

header('Location: admin_viewstaff.php?message=' . $message);
?>