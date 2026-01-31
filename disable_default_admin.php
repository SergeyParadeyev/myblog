<?php
/**
 * Скрипт отключения дефолтного администратора
 * Использование: php disable_default_admin.php
 */

// Проверка запуска из командной строки
if (php_sapi_name() !== 'cli') {
    die("Этот скрипт можно запускать только из командной строки\n");
}

require_once __DIR__ . '/config.php';

echo "=== Отключение дефолтного администратора ===\n\n";

$db = get_db();

// Проверка существования admin
$stmt = $db->prepare("SELECT id, role FROM users WHERE username = 'admin'");
$result = $stmt->execute();
$admin = $result->fetchArray(SQLITE3_ASSOC);

if (!$admin) {
    echo "Пользователь 'admin' не найден.\n";
    $db->close();
    exit(0);
}

if ($admin['role'] != ROLE_AUTHOR) {
    echo "Пользователь 'admin' не является администратором.\n";
    $db->close();
    exit(0);
}

// Проверка наличия других администраторов
$stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role = :role AND username != 'admin'");
$stmt->bindValue(':role', ROLE_AUTHOR, SQLITE3_INTEGER);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);

if ($row['count'] == 0) {
    echo "ОШИБКА: Нельзя удалить единственного администратора!\n";
    echo "Сначала создайте нового администратора командой: php create_admin.php\n";
    $db->close();
    exit(1);
}

echo "Найдено администраторов: " . ($row['count'] + 1) . "\n";
echo "Вы уверены, что хотите удалить пользователя 'admin'? (yes/no): ";
$confirm = trim(fgets(STDIN));

if (strtolower($confirm) !== 'yes' && strtolower($confirm) !== 'y') {
    echo "Операция отменена.\n";
    $db->close();
    exit(0);
}

// Удаление пользователя admin
$stmt = $db->prepare("DELETE FROM users WHERE username = 'admin'");
$stmt->execute();

echo "\n✓ Пользователь 'admin' успешно удален!\n";

$db->close();