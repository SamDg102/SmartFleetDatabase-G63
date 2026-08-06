<?php
// includes/conndb.php

$servername = "localhost";
$username   = "root";       // Username MySQL mặc định (đổi nếu bạn dùng tài khoản khác)
$password   = "";           // Password MySQL (mặc định XAMPP để trống, MAMP mặc định là 'root')
$dbname     = "G63System";  // Tên Database của bạn

// Tạo kết nối đến MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Thiết lập bảng mã UTF-8 để hiển thị tiếng Việt không bị lỗi font
$conn->set_charset("utf8mb4");
?>