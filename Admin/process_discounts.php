<?php

$dbpath = dirname(__DIR__) . "/db_connect.php";
include($dbpath);

$redirectPage = 'admin_discounts.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $redirectPage");
    exit;
}

$action = $_POST['action'] ?? '';
$message = '';
$status  = 'danger';

if ($action === 'add') {

    $discount_code = strtoupper(trim($_POST['discount'] ?? ''));
    $discount_percent = $_POST['discount_percent'] ?? null;
    $customer_ID = $_POST['customer_ID'] ?? null;
    $valid_from = $_POST['valid_from'] ?? '';
    $valid_until = $_POST['valid_until'] ?? '';

    if ($customer_ID === '' || $customer_ID === 'null') {
        $customer_ID = null;
    }

    // basic validation
    if ($discount_code === '' || $discount_percent === null || $valid_from === '' || $valid_until === '') {
        $message = 'Please fill in all required fields.';
        $status  = 'danger';
    } else {
        try {
            $sql = "INSERT INTO discounts 
                        (discount_code, discount_percent, customer_ID, valid_from, valid_until)
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sdsss",
                $discount_code,
                $discount_percent,
                $customer_ID,
                $valid_from,
                $valid_until
            );

            if ($stmt->execute()) {
                $message = "Discount code $discount_code added successfully.";
                $status  = 'success';
            } else {
                $message = "Error adding discount: " . $stmt->error;
                $status  = 'danger';
            }
            $stmt->close();
        } catch (Exception $e) {
            $message = "Error adding discount: " . $e->getMessage();
            $status  = 'danger';
        }
    }

} elseif ($action === 'delete') {

    $discount_code = $_POST['discount_code'] ?? '';

    if ($discount_code === '') {
        $message = 'No discount code specified.';
        $status  = 'danger';
    } else {
        try {
            $stmt = $conn->prepare("DELETE FROM discounts WHERE discount_code = ?");
            $stmt->bind_param("s", $discount_code);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $message = "Discount code $discount_code deleted.";
                    $status  = 'success';
                } else {
                    $message = "Discount code $discount_code not found.";
                    $status  = 'warning';
                }
            } else {
                $message = "Error deleting discount: " . $stmt->error;
                $status  = 'danger';
            }
            $stmt->close();
        } catch (Exception $e) {
            $message = "Error deleting discount: " . $e->getMessage();
            $status  = 'danger';
        }
    }

} else {
    $message = 'Invalid action.';
    $status  = 'danger';
}

header("Location: {$redirectPage}?message=" . urlencode($message) . "&status=" . urlencode($status));
exit;
