<?php
require_once 'config.php';
require_once 'includes/auth.php';

if (is_logged_in()) {
    header('Location: /');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if (empty($username) || empty($password)) {
        $error = 'Заполните все поля';
    } elseif ($password !== $password_confirm) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } else {
        $db = get_db();

        // Проверка существования пользователя
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE username = :username");
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);

        if ($row['count'] > 0) {
            $error = 'Пользователь с таким логином уже существует';
        } else {
            // Создание пользователя (доверенный по умолчанию)
            $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");
            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
            $stmt->bindValue(':role', ROLE_TRUSTED, SQLITE3_INTEGER);
            $stmt->execute();

            $success = 'Регистрация успешна! Теперь вы можете войти.';
        }

        $db->close();
    }
}

$page_title = 'Регистрация';
require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3>Регистрация</h3>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <a href="/login.php" class="btn btn-primary">Перейти к входу</a>
                <?php else: ?>
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Логин</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Пароль</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Подтверждение пароля</label>
                            <input type="password" name="password_confirm" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
                        <a href="/login.php" class="btn btn-link">Уже есть аккаунт?</a>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>