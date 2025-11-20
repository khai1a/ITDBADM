<?php
$servername = "127.0.0.1";  
$username = "student4";
$password = "Dlsu1234!";
$database = "perfume_shop_copy"; 
$port = 3307; 

$conn = new mysqli($servername, $username, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

