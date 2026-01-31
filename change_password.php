<?php
/**
 * Скрипт изменения пароля пользователя
 * Использование: php change_password.php
 */

// Проверка запуска из командной строки
if (php_sapi_name() !== 'cli') {
    die("Этот скрипт можно запускать только из командной строки\n");
}

require_once __DIR__ . '/config.php';

echo "=== Изменение пароля пользователя ===\n\n";

// Функция для безопасного ввода
function prompt($message)
{
    echo $message;
    return trim(fgets(STDIN));
}

// Функция для ввода пароля
function prompt_password($message)
{
    echo $message;

    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        system('stty -echo');
        $password = trim(fgets(STDIN));
        system('stty echo');
        echo "\n";
    } else {
        $password = trim(fgets(STDIN));
    }

    return $password;
}

$username = prompt("Введите логин пользователя: ");

if (empty($username)) {
    die("Ошибка: Логин не может быть пустым\n");
}

// Проверка существования пользователя
$db = get_db();

$stmt = $db->prepare("SELECT id, username FROM users WHERE username = :username");
$stmt->bindValue(':username', $username, SQLITE3_TEXT);
$result = $stmt->execute();
$user = $result->fetchArray(SQLITE3_ASSOC);

if (!$user) {
    $db->close();
    die("Ошибка: Пользователь '$username' не найден\n");
}

$password = prompt_password("Введите новый пароль: ");

if (empty($password)) {
    $db->close();
    die("Ошибка: Пароль не может быть пустым\n");
}

if (strlen($password) < 6) {
    $db->close();
    die("Ошибка: Пароль должен быть не менее 6 символов\n");
}

$password_confirm = prompt_password("Подтвердите новый пароль: ");

if ($password !== $password_confirm) {
    $db->close();
    die("Ошибка: Пароли не совпадают\n");
}

// Обновление пароля
$stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
$stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
$stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
$stmt->execute();

echo "\n✓ Пароль пользователя '$username' успешно изменен!\n";

$db->close();