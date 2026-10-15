<?php

require_once __DIR__ . '/common/auth.php';
requireLogin();
require_once __DIR__ . '/model/product.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1 || $id > 2147483647) {
    http_response_code(400);
    require_once __DIR__ . '/view/header.php';
    echo '<p>ID sản phẩm không hợp lệ.</p><a href="product_list.php">Về danh sách</a>';
    require_once __DIR__ . '/view/footer.php';
    exit;
}

$product = getProductById($id);

if (!$product) {
    http_response_code(404);
    require_once __DIR__ . '/view/header.php';
    echo '<p>Sản phẩm không tồn tại.</p><a href="product_list.php">Về danh sách</a>';
    require_once __DIR__ . '/view/footer.php';
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $result = false;
    if (validCsrfToken()) {
        try {
            $result = deleteProduct($id);
        } catch (mysqli_sql_exception $e) {
            error_log($e->getMessage());
        }
    }

    if ($result) {
        header('Location: product_list.php');
        exit;
    }

    $error = validCsrfToken()
        ? 'Xóa sản phẩm thất bại hoặc sản phẩm không còn tồn tại.'
        : 'Phiên gửi form không hợp lệ. Vui lòng thử lại.';
}

require_once __DIR__ . '/view/header.php';

?>

<h2>Xóa sản phẩm</h2>

<?php if ($error !== ''): ?>

    <p>
        <?= htmlspecialchars($error) ?>
    </p>

<?php endif; ?>

<p>
    Bạn có chắc chắn muốn xóa sản phẩm này không?
</p>

<p>
    <strong>ID:</strong>
    <?= $product['id'] ?>
</p>

<p>
    <strong>Tên sản phẩm:</strong>
    <?= htmlspecialchars($product['name']) ?>
</p>

<p>
    <strong>Giá:</strong>
    <?= number_format($product['price'], 2) ?>
</p>

<p>
    <strong>Số lượng:</strong>
    <?= $product['quantity'] ?>
</p>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

    <button type="submit">
        Xóa
    </button>

    <a href="product_list.php">
        Hủy
    </a>

</form>

<?php

require_once __DIR__ . '/view/footer.php';

?>
