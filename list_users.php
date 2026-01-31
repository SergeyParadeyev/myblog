<?php
/**
 * Скрипт вывода списка пользователей
 * Использование: php list_users.php
 */

// Проверка запуска из командной строки
if (php_sapi_name() !== 'cli') {
    die("Этот скрипт можно запускать только из командной строки\n");
}

require_once __DIR__ . '/config.php';

echo "=== Список пользователей ===\n\n";

$db = get_db();

$result = $db->query("SELECT id, username, role, created_at FROM users ORDER BY role DESC, username");

$role_names = [
    ROLE_GUEST => 'Гость',
    ROLE_TRUSTED => 'Доверенный',
    ROLE_AUTHOR => 'Администратор'
];

printf("%-5s %-20s %-15s %-20s\n", "ID", "Логин", "Роль", "Дата создания");
echo str_repeat("-", 65) . "\n";

$total = 0;
$admins = 0;

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    printf(
        "%-5d %-20s %-15s %-20s\n",
        $row['id'],
        $row['username'],
        $role_names[$row['role']] ?? 'Неизвестно',
        date('d.m.Y H:i', strtotime($row['created_at']))
    );

    $total++;
    if ($row['role'] == ROLE_AUTHOR) {
        $admins++;
    }
}

echo str_repeat("-", 65) . "\n";
echo "Всего пользователей: $total\n";
echo "Администраторов: $admins\n";

$db->close();