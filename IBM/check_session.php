<?php 
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Inter-Branch Manager') {
    header("Location: ../login_staff-admin.php");
    exit();
}

?>