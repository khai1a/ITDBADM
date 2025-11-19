<?php
$dbpath = dirname(__DIR__) . "/db_connect.php";
include($dbpath);

$message = '';
$status  = 'danger';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add') {
            $country_ID = $_POST['country_ID'] ?? '';
            $address    = $_POST['address'] ?? '';

            if (empty($country_ID) || empty($address)) {
                throw new Exception("Country and address are required.");
            }

            $conn->query("SET @id = ''");
            $conn->query("CALL getLastBranchID(@id)");
            $branch_ID = $conn->query("SELECT @id")->fetch_assoc()['@id'];

            $country_ID_esc = $conn->real_escape_string($country_ID);
            $address_esc    = $conn->real_escape_string($address);

            $sql = "INSERT INTO branches (branch_ID, country_ID, address)
                    VALUES ('$branch_ID', '$country_ID_esc', '$address_esc')";

            if (!$conn->query($sql)) {
                throw new Exception("Error adding branch: " . $conn->error);
            }

            $message = "Branch $branch_ID successfully created.";
            $status  = "success";
        } else {
            throw new Exception("Invalid action.");
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $status  = "danger";
    }

    header("Location: admin_branches.php?message=" . urlencode($message) . "&status=" . urlencode($status));
    exit;
}

header("Location: admin_branches.php");
exit;
