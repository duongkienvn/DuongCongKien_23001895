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

$name = $product['name'];
$price = $product['price'];
$quantity = $product['quantity'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
    $price = is_string($_POST['price'] ?? null) ? trim($_POST['price']) : '';
    $quantity = is_string($_POST['quantity'] ?? null) ? trim($_POST['quantity']) : '';

    if (!validCsrfToken()) {
        $errors[] = 'Phiên gửi form không hợp lệ. Vui lòng thử lại.';
    }

    // Validate tên
    if ($name === '') {
        $errors[] = 'Tên sản phẩm không được để trống.';
    } elseif (mb_strlen($name, 'UTF-8') > 100) {
        $errors[] = 'Tên sản phẩm không được quá 100 ký tự.';
    }

    // Validate giá
    if ($price === '' || !is_numeric($price) || $price <= 0) {
        $errors[] = 'Giá sản phẩm phải lớn hơn 0.';
    } elseif (!is_finite((float) $price) || $price < 0.01 || $price > 99999999.99) {
        $errors[] = 'Giá sản phẩm phải từ 0.01 đến 99999999.99.';
    }

    // Validate số lượng
    if (
        $quantity === ''
        || filter_var($quantity, FILTER_VALIDATE_INT) === false
        || $quantity < 0
        || $quantity > 2147483647
    ) {
        $errors[] = 'Số lượng phải là số nguyên từ 0 đến 2147483647.';
    }

    if (empty($errors)) {

        try {
            $result = updateProduct(
                $id,
                $name,
                (float) $price,
                (int) $quantity
            );
        } catch (mysqli_sql_exception $e) {
            error_log($e->getMessage());
            $result = false;
        }

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
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

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
            maxlength="100"
            required
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
            min="0.01"
            max="99999999.99"
            required
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
            min="0"
            max="2147483647"
            step="1"
            required
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
