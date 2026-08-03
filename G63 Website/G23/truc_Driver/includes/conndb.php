<?php
$host = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "G63system";

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully";

require 'navbar.php';
?>