<?php
/**
 * Скрипт создания администратора из командной строки
 * Использование: php create_admin.php
 */

// Проверка запуска из командной строки
if (php_sapi_name() !== 'cli') {
    die("Этот скрипт можно запускать только из командной строки\n");
}

require_once __DIR__ . '/config.php';

echo "=== Создание администратора ===\n\n";

// Функция для безопасного ввода
function prompt($message)
{
    echo $message;
    return trim(fgets(STDIN));
}

// Функция для ввода пароля (скрытие не работает на всех платформах)
function prompt_password($message)
{
    echo $message;

    // Попытка скрыть ввод на Unix/Linux/Mac
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        system('stty -echo');
        $password = trim(fgets(STDIN));
        system('stty echo');
        echo "\n";
    } else {
        // На Windows пароль будет виден
        $password = trim(fgets(STDIN));
    }

    return $password;
}

// Ввод данных
$username = prompt("Введите логин администратора: ");

if (empty($username)) {
    die("Ошибка: Логин не может быть пустым\n");
}

if (strlen($username) < 3) {
    die("Ошибка: Логин должен быть не менее 3 символов\n");
}

$password = prompt_password("Введите пароль: ");

if (empty($password)) {
    die("Ошибка: Пароль не может быть пустым\n");
}

if (strlen($password) < 6) {
    die("Ошибка: Пароль должен быть не менее 6 символов\n");
}

$password_confirm = prompt_password("Подтвердите пароль: ");

if ($password !== $password_confirm) {
    die("Ошибка: Пароли не совпадают\n");
}

// Проверка существования пользователя
$db = get_db();

$stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE username = :username");
$stmt->bindValue(':username', $username, SQLITE3_TEXT);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);

if ($row['count'] > 0) {
    echo "\nПредупреждение: Пользователь '$username' уже существует.\n";
    $overwrite = prompt("Обновить этого пользователя? (yes/no): ");

    if (strtolower($overwrite) !== 'yes' && strtolower($overwrite) !== 'y') {
        $db->close();
        die("Операция отменена\n");
    }

    // Обновление существующего пользователя
    $stmt = $db->prepare("UPDATE users SET password = :password, role = :role WHERE username = :username");
    $stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
    $stmt->bindValue(':role', ROLE_AUTHOR, SQLITE3_INTEGER);
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->execute();

    echo "\n✓ Пользователь '$username' успешно обновлен с правами администратора!\n";
} else {
    // Создание нового пользователя
    $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
    $stmt->bindValue(':role', ROLE_AUTHOR, SQLITE3_INTEGER);
    $stmt->execute();

    echo "\n✓ Администратор '$username' успешно создан!\n";
}

$db->close();

echo "\nДанные для входа:\n";
echo "Логин: $username\n";
echo "Пароль: ********\n";
echo "\nВы можете войти в систему по адресу: http://yoursite.com/login.php\n";