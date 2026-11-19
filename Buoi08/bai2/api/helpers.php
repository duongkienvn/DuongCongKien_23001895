<?php
/**
 * Các hàm dùng chung của API. Tách riêng để kiểm tra validation
 * mà không phải kết nối cơ sở dữ liệu.
 */

function sendJson(int $httpStatus, array $payload): never
{
    http_response_code($httpStatus);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

/**
 * Đọc body JSON; chỉ chấp nhận JSON object (không phải mảng).
 *
 * @return array<string, mixed>
 */
function readJsonBody(): array
{
    $raw = file_get_contents('php://input');

    try {
        $decoded = json_decode($raw === false ? '' : $raw, false, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new InvalidArgumentException('Nội dung yêu cầu phải là JSON hợp lệ.');
    }

    if (!is_object($decoded)) {
        throw new InvalidArgumentException('Dữ liệu gửi lên phải là đối tượng JSON.');
    }

    return (array) $decoded;
}

/**
 * Kiểm tra dữ liệu trước khi INSERT/UPDATE.
 * PHP không tự ép chuỗi "12" hoặc 12.5 thành số nguyên.
 *
 * @param array<string, mixed> $input
 * @return array{name: string, price: int, stock: int}
 */
function validateProduct(array $input): array
{
    $name = $input['name'] ?? null;
    if (!is_string($name)) {
        throw new InvalidArgumentException('Tên sản phẩm phải là chuỗi ký tự.');
    }

    // Bao gồm khoảng trắng Unicode mà String.trim() của Vue cũng loại bỏ.
    $name = preg_replace('/\A[\s\x{FEFF}]+|[\s\x{FEFF}]+\z/u', '', $name);
    if ($name === null) {
        throw new InvalidArgumentException('Tên sản phẩm phải là UTF-8 hợp lệ.');
    }
    // Đếm số ký tự Unicode (tiếng Việt), không đếm byte UTF-8.
    $length = preg_match_all('/./us', $name);
    if ($length === false || $length < 1 || $length > 100) {
        throw new InvalidArgumentException('Tên sản phẩm phải có từ 1 đến 100 ký tự.');
    }

    $price = $input['price'] ?? null;
    if (!is_int($price) || $price <= 0 || $price > 2147483647) {
        throw new InvalidArgumentException('Đơn giá phải là số nguyên lớn hơn 0 (tối đa 2147483647).');
    }

    $stock = $input['stock'] ?? null;
    if (!is_int($stock) || $stock < 0 || $stock > 2147483647) {
        throw new InvalidArgumentException('Số lượng tồn phải là số nguyên không âm (tối đa 2147483647).');
    }

    return [
        'name' => $name,
        'price' => $price,
        'stock' => $stock,
    ];
}

/** @return int */
function requireProductId(mixed $rawId): int
{
    $id = filter_var($rawId, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 2147483647],
    ]);

    if ($id === false) {
        throw new InvalidArgumentException('ID sản phẩm phải là số nguyên dương hợp lệ trên URL.');
    }

    return $id;
}

/** @return array{page: int, limit: int} */
function validatePagination(array $query): array
{
    $rawPage = $query['page'] ?? '1';
    $rawLimit = $query['limit'] ?? '5';
    if (!is_string($rawPage) || !preg_match('/^[1-9][0-9]*$/D', $rawPage)) {
        throw new InvalidArgumentException('Trang phải là số nguyên dương.');
    }
    if ($rawLimit !== '5') {
        throw new InvalidArgumentException('Mỗi trang phải có đúng 5 sản phẩm (limit=5).');
    }
    // Số trang rất lớn vẫn hợp lệ; API sẽ đưa về trang cuối trước khi tính OFFSET.
    $maximum = (string) PHP_INT_MAX;
    $page = strlen($rawPage) > strlen($maximum)
        || (strlen($rawPage) === strlen($maximum) && strcmp($rawPage, $maximum) > 0)
        ? PHP_INT_MAX : (int) $rawPage;
    return ['page' => $page, 'limit' => 5];
}

function deleteProductImage(?string $image): void
{
    // Chỉ xóa tệp do API này tạo, không nhận đường dẫn tùy ý từ dữ liệu.
    if ($image !== null && preg_match('~^uploads/[a-f0-9]{32}\.(?:jpg|png)$~D', $image)) {
        $path = __DIR__ . '/../' . $image;
        if (is_file($path) && !@unlink($path)) {
            error_log('Cannot remove unused product image: ' . $image);
        }
    }
}
