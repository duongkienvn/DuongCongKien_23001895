<?php require_once __DIR__ . '/../common/auth.php'; ?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý sản phẩm</title>
</head>

<body>

    <h1>Quản lý sản phẩm</h1>

    <nav>
        <?php if (!empty($_SESSION['user_id'])): ?>
        <a href="product_list.php">Danh sách sản phẩm</a>
        |
        <a href="product_add.php">Thêm sản phẩm</a>
        |
        <a href="logout.php">Đăng xuất</a>
        <p>Đang đăng nhập: <strong><?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?></strong></p>
        <?php else: ?>
        <a href="login.php">Đăng nhập</a>
        |
        <a href="register.php">Đăng ký</a>
        <?php endif; ?>
    </nav>

    <hr>
