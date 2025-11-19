<?php 
$dbpath = dirname(__DIR__) . "/db_connect.php";
include($dbpath);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $return_ID = $_POST['return_ID'] ?? '';

    if ($return_ID && in_array($action, ['approve', 'reject'])) {
        if ($action === 'approve') {
            $newStatus = 'Approved';
        } else {
            $newStatus = 'Rejected';
        }

        $stmt = $conn->prepare("
            UPDATE returns
            SET status = ?
            WHERE return_ID = ? AND status = 'Requested'
        ");
        $stmt->bind_param("ss", $newStatus, $return_ID);

        try {
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $message = "Return $return_ID has been $newStatus.";
                $statusClass = 'success';
            } else {
                $message = "No changes made. The return may already be processed.";
                $statusClass = 'warning';
            }
        } catch (Exception $e) {
            $message = 'Error updating return status: ' . $e->getMessage();
            $statusClass = 'danger';
        }

        $stmt->close();
    }
}

if (isset($_POST['page'])) {
  header('Location: ibm_returndetails.php?id=' . $return_ID);
} else {
  header('Location: ibm_managereturns.php?message=' . $message . "&statusClass=" . $statusClass);
}

exit();