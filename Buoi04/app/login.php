<?php

require_once __DIR__ . '/common/auth.php';
require_once __DIR__ . '/model/user.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: product_list.php');
    exit;
}

$username = '';
$error = '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if (!validCsrfToken()) {
        $error = 'Phiên gửi form không hợp lệ. Vui lòng thử lại.';
    } elseif ($username === '' || trim($password) === '') {
        $error = 'Vui lòng nhập tên đăng nhập và mật khẩu.';
    } else {
        try {
            $user = getUserByUsername($username);

            if ($user && strlen($password) <= 72 && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                unset($_SESSION['csrf_token']);
                header('Location: product_list.php');
                exit;
            }

            $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        } catch (mysqli_sql_exception $e) {
            error_log($e->getMessage());
            $error = 'Không thể đăng nhập. Vui lòng kiểm tra database hoặc thử lại sau.';
        }
    }
}

require_once __DIR__ . '/view/header.php';
?>

<h2>Đăng nhập</h2>

<?php if ($success !== ''): ?>
    <p role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <p role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <p>
        <label for="username">Tên đăng nhập:</label><br>
        <input type="text" id="username" name="username" maxlength="50" required
               autocomplete="username" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>">
    </p>
    <p>
        <label for="password">Mật khẩu:</label><br>
        <input type="password" id="password" name="password" required autocomplete="current-password">
    </p>
    <button type="submit">Đăng nhập</button>
    <a href="register.php">Đăng ký tài khoản</a>
</form>

<?php require_once __DIR__ . '/view/footer.php'; ?>
