<?php
require_once 'config.php';
require_once 'includes/auth.php';

// session_start();

if (is_logged_in()) {
    header('Location: /');
    exit;
}

$error = '';
$success = '';

/* ======================
   Инициализация защиты
====================== */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $_SESSION['form_time'] = time();

    $_SESSION['captcha_a'] = rand(2, 9);
    $_SESSION['captcha_b'] = rand(2, 9);
    $_SESSION['captcha'] = $_SESSION['captcha_a'] + $_SESSION['captcha_b'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ======================
       Антибот-проверки
    ====================== */

    // Honeypot
    if (!empty($_POST['company'])) {
        exit;
    }

    // JS check
    if (($_POST['js_enabled'] ?? '') !== '1') {
        $error = 'Ошибка формы';
    }

    // Время заполнения
    elseif (time() - ($_SESSION['form_time'] ?? 0) < 4) {
        $error = 'Слишком быстрое заполнение формы';
    }

    // Капча
    elseif ((int) ($_POST['captcha'] ?? -1) !== ($_SESSION['captcha'] ?? -2)) {
        $error = 'Неверный ответ на проверочный вопрос';
    }

    /* ======================
       Основная логика
    ====================== */ else {

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

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
                // Создание пользователя
                $stmt = $db->prepare("
                    INSERT INTO users (username, password, role)
                    VALUES (:username, :password, :role)
                ");
                $stmt->bindValue(':username', $username, SQLITE3_TEXT);
                $stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
                $stmt->bindValue(':role', ROLE_TRUSTED, SQLITE3_INTEGER);
                $stmt->execute();

                $success = 'Регистрация успешна! Теперь вы можете войти.';
            }

            $db->close();
        }
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
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <a href="/login.php" class="btn btn-primary">Перейти к входу</a>
                <?php else: ?>
                    <form method="post">

                        <!-- Honeypot -->
                        <div style="display:none">
                            <input type="text" name="company" autocomplete="off">
                        </div>

                        <!-- JS check -->
                        <input type="hidden" name="js_enabled" value="0">

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

                        <div class="mb-3">
                            <label class="form-label">
                                Сколько будет <?= $_SESSION['captcha_a'] ?> + <?= $_SESSION['captcha_b'] ?>?
                            </label>
                            <input type="text" name="captcha" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
                        <a href="/login.php" class="btn btn-link">Уже есть аккаунт?</a>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.querySelector('input[name="js_enabled"]');
        if (el) el.value = '1';
    });
</script>
<!-- 
<script>
    $(function () {
        $('input[name="js_enabled"]').val('1');
    });
</script>
 -->
<?php require_once 'includes/footer.php'; ?>