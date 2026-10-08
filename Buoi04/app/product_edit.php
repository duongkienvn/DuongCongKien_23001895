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

$name = $product['name'];
$price = $product['price'];
$quantity = $product['quantity'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $price = $_POST['price'] ?? '';
    $quantity = $_POST['quantity'] ?? '';

    // Validate tên
    if ($name === '') {
        $errors[] = 'Tên sản phẩm không được để trống.';
    }

    // Validate giá
    if ($price === '' || !is_numeric($price) || $price <= 0) {
        $errors[] = 'Giá sản phẩm phải lớn hơn 0.';
    }

    // Validate số lượng
    if (
        $quantity === ''
        || filter_var($quantity, FILTER_VALIDATE_INT) === false
        || $quantity < 0
    ) {
        $errors[] = 'Số lượng phải là số nguyên lớn hơn hoặc bằng 0.';
    }

    if (empty($errors)) {

        $result = updateProduct(
            $id,
            $name,
            (float) $price,
            (int) $quantity
        );

        if ($result) {
            header('Location: product_list.php');
            exit;
        }

        $errors[] = 'Cập nhật sản phẩm thất bại.';
    }
}

require_once __DIR__ . '/view/header.php';

?>

<h2>Sửa sản phẩm</h2>

<?php if (!empty($errors)): ?>

    <div>

        <?php foreach ($errors as $error): ?>

            <p>
                <?= htmlspecialchars($error) ?>
            </p>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

<form method="POST">

    <p>
        <label>ID:</label>
        <br>

        <input
            type="text"
            value="<?= $id ?>"
            disabled
        >
    </p>

    <p>
        <label for="name">Tên sản phẩm:</label>
        <br>

        <input
            type="text"
            id="name"
            name="name"
            value="<?= htmlspecialchars($name) ?>"
        >
    </p>

    <p>
        <label for="price">Giá:</label>
        <br>

        <input
            type="number"
            id="price"
            name="price"
            step="0.01"
            value="<?= htmlspecialchars($price) ?>"
        >
    </p>

    <p>
        <label for="quantity">Số lượng:</label>
        <br>

        <input
            type="number"
            id="quantity"
            name="quantity"
            value="<?= htmlspecialchars($quantity) ?>"
        >
    </p>

    <button type="submit">
        Cập nhật
    </button>

    <a href="product_list.php">
        Hủy
    </a>

</form>

<?php

require_once __DIR__ . '/view/footer.php';

?>