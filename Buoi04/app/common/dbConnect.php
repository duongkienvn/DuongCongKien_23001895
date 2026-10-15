<?php

$host = 'localhost';
$username = 'root';
$password = 'kien2005';
$database = 'shopping_cart';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $username, $password, $database);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    exit('Không thể kết nối database. Vui lòng kiểm tra MySQL và cấu hình common/dbConnect.php.');
}
