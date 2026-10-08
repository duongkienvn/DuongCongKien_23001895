<?php

require_once __DIR__ . '/model/product.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die('ID sản phẩm không hợp lệ.');
}

$product = getProductById($id);

if (!$product) {
    die('Sản phẩm không tồn tại.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $result = deleteProduct($id);

    if ($result) {
        header('Location: product_list.php');
        exit;
    }

    $error = 'Xóa sản phẩm thất bại.';
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