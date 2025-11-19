<?php
$dbpath = dirname(__DIR__) . "/db_connect.php";
include($dbpath); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $conn->query("SET @id = ''");
    $conn->query("CALL getLastBrandID(@id)");

    $brand_ID = $conn->query("SELECT @id")->fetch_assoc()['@id'];
    $brand_name = trim($_POST['brand_name']);
    $brand_type = $_POST['brand_type'];
    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        $sql = "INSERT INTO brands (brand_ID, brand_name, brand_type)
                VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $brand_ID, $brand_name, $brand_type);
    } elseif ($action === 'update') {
     
        $sql = "UPDATE brands
                SET brand_name = ?, brand_type = ?
                WHERE brand_ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $brand_name, $brand_type, $brand_ID);
    }

    if (isset($stmt)) {
        try {
            $stmt->execute();
            $message = 'Brand saved successfully.';
            $status = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $status = 'danger';
        }
        $stmt->close();
    }
}

// HANDLE DELETE
if (isset($_GET['delete_brand'])) {
    $id = $_GET['delete_brand'];
    $stmt = $conn->prepare("DELETE FROM brands WHERE brand_ID = ?");
    $stmt->bind_param("s", $id);
    try {
        $stmt->execute();
        $message = "Brand $id deleted.";
        $status = 'success';
    } catch (Exception $e) {
        $message = 'Error deleting brand: ' . $e->getMessage();
        $status = 'danger';
    }
    $stmt->close();
}

header('Location: admin_brands.php?message=' . $message . '&status=' . $status);
exit();

?>