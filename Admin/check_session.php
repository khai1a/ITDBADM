<?php 
session_start();


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login_staff-admin.php");
    exit();
}

?>