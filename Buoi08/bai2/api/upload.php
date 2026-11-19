<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    sendJson(405, ['success' => false, 'message' => 'Phương thức HTTP không được hỗ trợ.']);
}

$newPath = null;
$pdo = null;
try {
    $maxSize = 2 * 1024 * 1024;
    // PHP có thể bỏ cả POST/FILES khi vượt post_max_size.
    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > $maxSize) {
        sendJson(413, ['success' => false, 'message' => 'Ảnh không được lớn hơn 2 MB.']);
    }
    $id = requireProductId($_POST['product_id'] ?? null);
    $file = $_FILES['image'] ?? null;
    if (!is_array($file) || !isset($file['error']) || !is_int($file['error'])) {
        throw new InvalidArgumentException('Vui lòng chọn một ảnh JPG, JPEG hoặc PNG.');
    }
    if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
        sendJson(413, ['success' => false, 'message' => 'Ảnh không được lớn hơn 2 MB.']);
    }
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        throw new InvalidArgumentException('Vui lòng chọn một ảnh JPG, JPEG hoặc PNG.');
    }
    if ($file['error'] === UPLOAD_ERR_PARTIAL) {
        throw new InvalidArgumentException('Ảnh chưa được tải lên đầy đủ. Vui lòng thử lại.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('PHP upload error: ' . $file['error']);
    }
    $temporary = $file['tmp_name'] ?? null;
    if (!is_string($temporary) || !is_uploaded_file($temporary)) {
        throw new InvalidArgumentException('Tệp ảnh tải lên không hợp lệ.');
    }
    $size = filesize($temporary);
    if ($size === false) throw new RuntimeException('Cannot read uploaded file size.');
    if ($size > $maxSize) {
        sendJson(413, ['success' => false, 'message' => 'Ảnh không được lớn hơn 2 MB.']);
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporary);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    $dimensions = @getimagesize($temporary);
    if ($size === 0 || !isset($extensions[$mime]) || $dimensions === false || $dimensions['mime'] !== $mime) {
        throw new InvalidArgumentException('Chỉ chấp nhận ảnh JPG, JPEG hoặc PNG hợp lệ.');
    }

    $pdo = connectDatabase();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT image FROM products WHERE id = :id FOR UPDATE');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $existing = $stmt->fetch();
    if ($existing === false) {
        $pdo->rollBack();
        sendJson(404, ['success' => false, 'message' => 'Không tìm thấy sản phẩm cần tải ảnh.']);
    }
    $directory = __DIR__ . '/../uploads';
    if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Cannot create uploads directory.');
    }
    $image = 'uploads/' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $newPath = __DIR__ . '/../' . $image;
    if (!@move_uploaded_file($temporary, $newPath)) {
        throw new RuntimeException('Cannot store uploaded image.');
    }
    $stmt = $pdo->prepare('UPDATE products SET image = :image WHERE id = :id');
    $stmt->bindValue(':image', $image, PDO::PARAM_STR);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $pdo->commit();
    $newPath = null;
    deleteProductImage($existing['image']);
    sendJson(200, ['success' => true, 'message' => 'Tải ảnh sản phẩm thành công.', 'data' => ['id' => $id, 'image' => $image]]);
} catch (Throwable $exception) {
    if ($pdo !== null && $pdo->inTransaction()) $pdo->rollBack();
    if ($newPath !== null && is_file($newPath)) @unlink($newPath);
    if ($exception instanceof InvalidArgumentException) {
        sendJson(400, ['success' => false, 'message' => $exception->getMessage()]);
    }
    error_log('Product upload error: ' . $exception->getMessage());
    sendJson(500, ['success' => false, 'message' => 'Không thể lưu ảnh sản phẩm. Vui lòng thử lại.']);
}
