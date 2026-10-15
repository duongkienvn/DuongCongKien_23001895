<?php

require_once __DIR__ . '/common/auth.php';
require_once __DIR__ . '/model/user.php';

$username = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if (!validCsrfToken()) {
        $errors[] = 'Phiên gửi form không hợp lệ. Vui lòng thử lại.';
    }

    if ($username === '') {
        $errors[] = 'Tên đăng nhập không được để trống.';
    } elseif (mb_strlen($username, 'UTF-8') > 50) {
        $errors[] = 'Tên đăng nhập không được quá 50 ký tự.';
    }

    if (trim($password) === '') {
        $errors[] = 'Mật khẩu không được để trống.';
    } elseif (strlen($password) > 72 || strpos($password, "\0") !== false) {
        $errors[] = 'Mật khẩu tối đa 72 byte và không chứa ký tự null.';
    }

    if (empty($errors)) {
        try {
            if (getUserByUsername($username)) {
                $errors[] = 'Tên đăng nhập đã tồn tại.';
            } elseif (addUser($username, $password)) {
                $_SESSION['success'] = 'Đăng ký thành công. Vui lòng đăng nhập.';
                header('Location: login.php');
                exit;
            } else {
                $errors[] = 'Đăng ký thất bại. Vui lòng thử lại.';
            }
        } catch (mysqli_sql_exception $e) {
            // UNIQUE trong database cũng chặn hai yêu cầu đăng ký trùng cùng lúc.
            if ($e->getCode() === 1062) {
                $errors[] = 'Tên đăng nhập đã tồn tại.';
            } else {
                error_log($e->getMessage());
                $errors[] = 'Không thể đăng ký. Vui lòng kiểm tra database hoặc thử lại sau.';
            }
        }
    }
}

require_once __DIR__ . '/view/header.php';
?>

<h2>Đăng ký tài khoản</h2>

<?php foreach ($errors as $error): ?>
    <p role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endforeach; ?>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <p>
        <label for="username">Tên đăng nhập:</label><br>
        <input type="text" id="username" name="username" maxlength="50" required
               autocomplete="username" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>">
    </p>
    <p>
        <label for="password">Mật khẩu:</label><br>
        <input type="password" id="password" name="password" required autocomplete="new-password">
    </p>
    <button type="submit">Đăng ký</button>
    <a href="login.php">Đã có tài khoản? Đăng nhập</a>
</form>

<?php require_once __DIR__ . '/view/footer.php'; ?>
