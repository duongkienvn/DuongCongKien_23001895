<?php
// Có thể ghi đè bằng biến môi trường để chạy với database test riêng.
// Mật khẩu riêng của máy đặt trong database.local.php (không theo dõi bằng Git).
// XAMPP mặc định dùng root, mật khẩu rỗng khi không có cấu hình riêng.
function connectDatabase(): PDO
{
    $localFile = __DIR__ . '/database.local.php';
    $local = is_file($localFile) ? require $localFile : [];
    $host = getenv('DB_HOST') !== false ? getenv('DB_HOST') : '127.0.0.1';
    $database = getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'thuc_hanh_8';
    $username = getenv('DB_USER') !== false ? getenv('DB_USER') : 'root';
    $password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : ($local['password'] ?? '');
    $port = getenv('DB_PORT') !== false ? getenv('DB_PORT') : 3306;

    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";

    return new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
