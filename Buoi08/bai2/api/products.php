<?php
// Câu 2: REST API PHP thuần + PDO.
// GET    /bai2/api/products.php        => danh sách sản phẩm
// POST   /bai2/api/products.php        => thêm sản phẩm (body JSON)
// PUT    /bai2/api/products.php?id=1   => sửa sản phẩm  (body JSON)
// DELETE /bai2/api/products.php?id=1   => xóa sản phẩm

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE'], true)) {
    header('Allow: GET, POST, PUT, DELETE');
    sendJson(405, ['success' => false, 'message' => 'Phương thức HTTP không được hỗ trợ.']);
}

try {
    // Kiểm tra ID và body trước khi thao tác dữ liệu.
    $id = null;
    $product = null;

    if ($method === 'PUT' || $method === 'DELETE') {
        $id = requireProductId($_GET['id'] ?? null);
    }

    if ($method === 'POST' || $method === 'PUT') {
        // Chỉ nhận JSON, không chấp nhận simple cross-origin POST dạng text/plain.
        $contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
        if ($contentType !== 'application/json') {
            sendJson(415, ['success' => false, 'message' => 'Content-Type phải là application/json.']);
        }
        $product = validateProduct(readJsonBody());
    }

    $pdo = connectDatabase();

    switch ($method) {
        case 'GET':
            $stmt = $pdo->query('SELECT id, name, price, stock FROM products ORDER BY id ASC');
            $products = $stmt->fetchAll();

            // Bảo đảm ID/giá/tồn là number trong JSON (không phải string).
            foreach ($products as &$row) {
                $row['id'] = (int) $row['id'];
                $row['price'] = (int) $row['price'];
                $row['stock'] = (int) $row['stock'];
            }
            unset($row);

            sendJson(200, ['success' => true, 'data' => $products]);

        case 'POST':
            $stmt = $pdo->prepare(
                'INSERT INTO products (name, price, stock) VALUES (:name, :price, :stock)'
            );
            $stmt->bindValue(':name', $product['name'], PDO::PARAM_STR);
            $stmt->bindValue(':price', $product['price'], PDO::PARAM_INT);
            $stmt->bindValue(':stock', $product['stock'], PDO::PARAM_INT);
            $stmt->execute();

            sendJson(201, [
                'success' => true,
                'message' => 'Thêm sản phẩm thành công.',
                'data' => ['id' => (int) $pdo->lastInsertId()],
            ]);

        case 'PUT':
            $stmt = $pdo->prepare(
                'UPDATE products SET name = :name, price = :price, stock = :stock WHERE id = :id'
            );
            $stmt->bindValue(':name', $product['name'], PDO::PARAM_STR);
            $stmt->bindValue(':price', $product['price'], PDO::PARAM_INT);
            $stmt->bindValue(':stock', $product['stock'], PDO::PARAM_INT);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                // rowCount() = 0 cũng có thể do lưu lại dữ liệu KHÔNG đổi.
                $check = $pdo->prepare('SELECT id FROM products WHERE id = :id');
                $check->bindValue(':id', $id, PDO::PARAM_INT);
                $check->execute();

                if ($check->fetchColumn() === false) {
                    sendJson(404, ['success' => false, 'message' => 'Không tìm thấy sản phẩm cần sửa.']);
                }
            }

            sendJson(200, ['success' => true, 'message' => 'Cập nhật sản phẩm thành công.']);

        case 'DELETE':
            $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                sendJson(404, ['success' => false, 'message' => 'Không tìm thấy sản phẩm cần xóa.']);
            }

            sendJson(200, ['success' => true, 'message' => 'Xóa sản phẩm thành công.']);
    }
} catch (InvalidArgumentException $exception) {
    sendJson(400, ['success' => false, 'message' => $exception->getMessage()]);
} catch (PDOException $exception) {
    // Không đưa username, password hoặc thông tin DB vào phản hồi API.
    error_log('Database error in products API: ' . $exception->getMessage());
    sendJson(500, ['success' => false, 'message' => 'Có lỗi khi kết nối hoặc thao tác cơ sở dữ liệu.']);
} catch (Throwable $exception) {
    error_log('Unexpected products API error: ' . $exception->getMessage());
    sendJson(500, ['success' => false, 'message' => 'Đã xảy ra lỗi trên máy chủ.']);
}
