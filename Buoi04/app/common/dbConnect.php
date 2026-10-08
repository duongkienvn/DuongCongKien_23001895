<?php

$host = 'localhost';
$username = 'root';
$password = 'kien2005';
$database = 'shopping_cart';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die('Kết nối database thất bại: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');