<?php

require_once __DIR__ . '/common/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validCsrfToken()) {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/view/header.php';
?>

<h2>Đăng xuất</h2>
<?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <p role="alert">Phiên gửi form không hợp lệ. Vui lòng thử lại.</p>
<?php endif; ?>
<p>Bạn có muốn đăng xuất không?</p>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <button type="submit">Đăng xuất</button>
    <a href="product_list.php">Hủy</a>
</form>

<?php require_once __DIR__ . '/view/footer.php'; ?>
