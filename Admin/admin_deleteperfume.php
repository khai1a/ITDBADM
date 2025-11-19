<?php 
require'check_session.php';

$dbpath = dirname(__DIR__) . "/db_connect.php";
include($dbpath); 

$message = '';
$status = '';

if (isset($_GET['id'])) {
  try {
    $perfume_ID = $_GET['id'];
    $conn->query("DELETE FROM perfumes WHERE perfume_ID = '$perfume_ID'");
    $message = 'Succesfully deleted perfume!';
    $status = 'success';
  } catch (Exception $e) {
    $message = "Error deleting perfume: " . $e->getMessage();
    $status = 'danger';
  }
}

if (isset($_GET['id'])) {
    header("Location: admin_viewperfumes.php");
} else {
    header('Location: ../login_staff-admin.php');
}

?>