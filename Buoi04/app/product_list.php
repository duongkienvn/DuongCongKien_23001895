<?php

require_once __DIR__ . '/model/product.php';

$products = getAllProducts();

require_once __DIR__ . '/view/header.php';

?>

<h2>Danh sách sản phẩm</h2>

<p>
    <a href="product_add.php">Thêm sản phẩm mới</a>
</p>

<?php if (empty($products)): ?>

    <p>Chưa có sản phẩm nào.</p>

<?php else: ?>

    <table border="1" cellpadding="8" cellspacing="0">

        <thead>
            <tr>
                <th>ID</th>
                <th>Tên sản phẩm</th>
                <th>Giá</th>
                <th>Số lượng</th>
                <th>Chức năng</th>
            </tr>
        </thead>

        <tbody>

            <?php foreach ($products as $product): ?>

                <tr>
                    <td>
                        <?= $product['id'] ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($product['name']) ?>
                    </td>

                    <td>
                        <?= number_format($product['price'], 2) ?>
                    </td>

                    <td>
                        <?= $product['quantity'] ?>
                    </td>

                    <td>

                        <a href="product_edit.php?id=<?= $product['id'] ?>">
                            Sửa
                        </a>

                        |

                        <a href="product_delete.php?id=<?= $product['id'] ?>">
                            Xóa
                        </a>

                    </td>
                </tr>

            <?php endforeach; ?>

        </tbody>

    </table>

<?php endif; ?>

<?php

require_once __DIR__ . '/view/footer.php';

?>