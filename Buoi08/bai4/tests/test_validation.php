<?php
// Kiểm tra quy tắc dữ liệu không cần bật MySQL.
require_once __DIR__ . '/../../bai2/api/helpers.php';
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$passed = 0;
$failed = 0;

function check(string $label, callable $callback, bool $shouldFail = false): void
{
    global $passed, $failed;
    try {
        $callback();
        $ok = !$shouldFail;
    } catch (InvalidArgumentException $exception) {
        $ok = $shouldFail;
    }
    echo ($ok ? '[PASS] ' : '[FAIL] ') . $label . PHP_EOL;
    $ok ? $passed++ : $failed++;
}

check('Hợp lệ: Bút bi, 5000, 20', fn() => validateProduct(['name' => 'Bút bi', 'price' => 5000, 'stock' => 20]));
check('Hợp lệ: tồn kho bằng 0', fn() => validateProduct(['name' => 'Thước kẻ', 'price' => 8000, 'stock' => 0]));
check('Không hợp lệ: tồn kho -1', fn() => validateProduct(['name' => 'Bút bi', 'price' => 5000, 'stock' => -1]), true);
check('Không hợp lệ: giá 0', fn() => validateProduct(['name' => 'Bút bi', 'price' => 0, 'stock' => 2]), true);
check('Không hợp lệ: giá thập phân', fn() => validateProduct(['name' => 'Bút bi', 'price' => 2.5, 'stock' => 2]), true);
check('Không hợp lệ: giá dạng chuỗi', fn() => validateProduct(['name' => 'Bút bi', 'price' => '5000', 'stock' => 2]), true);
check('Không hợp lệ: tên toàn khoảng trắng', fn() => validateProduct(['name' => '   ', 'price' => 1, 'stock' => 0]), true);
check('Không hợp lệ: tên 101 ký tự', fn() => validateProduct(['name' => str_repeat('a', 101), 'price' => 1, 'stock' => 0]), true);
check('Không hợp lệ: thiếu stock', fn() => validateProduct(['name' => 'Bút bi', 'price' => 5000]), true);
check('ID hợp lệ', fn() => requireProductId('123'));
check('ID không hợp lệ', fn() => requireProductId('-1'), true);

check('Trim khoảng trắng Unicode', function () {
    $product = validateProduct(['name' => "\u{00A0}Bút bi\u{FEFF}", 'price' => 1, 'stock' => 0]);
    if ($product !== ['name' => 'Bút bi', 'price' => 1, 'stock' => 0]) throw new RuntimeException('Tên sau trim không đúng.');
});
check('Tên chỉ chứa khoảng trắng Unicode', fn() => validateProduct(['name' => "\u{00A0}\u{2003}\u{FEFF}", 'price' => 1, 'stock' => 0]), true);
check('Tên 100 ký tự Unicode / INT_MAX', fn() => validateProduct(['name' => str_repeat('😀', 100), 'price' => 2147483647, 'stock' => 2147483647]));
check('Tên 101 ký tự Unicode', fn() => validateProduct(['name' => str_repeat('😀', 101), 'price' => 1, 'stock' => 0]), true);
check('Tên UTF-8 không hợp lệ', fn() => validateProduct(['name' => "\xFF", 'price' => 1, 'stock' => 0]), true);
foreach ([null, true, [], 1, ''] as $name) {
    check('Tên sai kiểu: ' . gettype($name), fn() => validateProduct(['name' => $name, 'price' => 1, 'stock' => 0]), true);
}
foreach ([null, true, [], '1', 1.0, 0, -1, 2147483648] as $price) {
    check('Giá sai: ' . json_encode($price), fn() => validateProduct(['name' => 'A', 'price' => $price, 'stock' => 0]), true);
}
foreach ([null, true, [], '0', 0.0, -1, 2147483648] as $stock) {
    check('Tồn sai: ' . json_encode($stock), fn() => validateProduct(['name' => 'A', 'price' => 1, 'stock' => $stock]), true);
}
foreach ([null, [], '', '0', '-1', '1.5', 'abc', '1 OR 1=1', '2147483648'] as $id) {
    check('ID sai: ' . json_encode($id), fn() => requireProductId($id), true);
}
check('ID INT_MAX', function () {
    if (requireProductId('2147483647') !== 2147483647) throw new RuntimeException('ID không đúng.');
});

echo "Kết quả: $passed đạt, $failed lỗi." . PHP_EOL;
exit($failed === 0 ? 0 : 1);
