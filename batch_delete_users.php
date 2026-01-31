<?php
/**
 * Скрипт массового удаления пользователей
 * Использование: php batch_delete_users.php
 */

// Проверка запуска из командной строки
if (php_sapi_name() !== 'cli') {
    die("Этот скрипт можно запускать только из командной строки\n");
}

require_once __DIR__ . '/config.php';

echo "=== Массовое удаление пользователей ===\n\n";

// Функция для безопасного ввода
function prompt($message)
{
    echo $message;
    return trim(fgets(STDIN));
}

// Вывод списка пользователей
$db = get_db();

$result = $db->query("SELECT id, username, role, created_at FROM users ORDER BY role DESC, username");

$role_names = [
    ROLE_GUEST => 'Гость',
    ROLE_TRUSTED => 'Доверенный',
    ROLE_AUTHOR => 'Администратор'
];

echo "Список пользователей:\n";
echo str_repeat("-", 70) . "\n";
printf("%-5s %-20s %-15s %-20s\n", "ID", "Логин", "Роль", "Дата создания");
echo str_repeat("-", 70) . "\n";

$users = [];
$admin_count = 0;

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $users[$row['id']] = $row;

    printf(
        "%-5d %-20s %-15s %-20s\n",
        $row['id'],
        $row['username'],
        $role_names[$row['role']] ?? 'Неизвестно',
        date('d.m.Y H:i', strtotime($row['created_at']))
    );

    if ($row['role'] == ROLE_AUTHOR) {
        $admin_count++;
    }
}

echo str_repeat("-", 70) . "\n\n";

if (empty($users)) {
    die("Нет пользователей для удаления.\n");
}

// Ввод ID пользователей для удаления
echo "Введите ID пользователей для удаления через запятую (например: 2,3,5)\n";
echo "Или введите 'role:trusted' для удаления всех доверенных пользователей\n";
echo "Или введите 'role:guest' для удаления всех гостей\n\n";

$input = prompt("ID или фильтр: ");

if (empty($input)) {
    die("Операция отменена.\n");
}

$users_to_delete = [];

// Обработка по роли
if (strpos($input, 'role:') === 0) {
    $role_filter = substr($input, 5);

    if ($role_filter === 'admin' || $role_filter === 'author') {
        die("ОШИБКА: Массовое удаление администраторов запрещено!\n");
    }

    $role_map = [
        'guest' => ROLE_GUEST,
        'trusted' => ROLE_TRUSTED
    ];

    if (!isset($role_map[$role_filter])) {
        die("ОШИБКА: Неизвестная роль '$role_filter'\n");
    }

    $target_role = $role_map[$role_filter];

    foreach ($users as $user) {
        if ($user['role'] == $target_role) {
            $users_to_delete[] = $user;
        }
    }
} else {
    // Обработка списка ID
    $ids = array_map('trim', explode(',', $input));

    foreach ($ids as $id) {
        if (!is_numeric($id)) {
            die("ОШИБКА: Некорректный ID '$id'\n");
        }

        $id = (int) $id;

        if (!isset($users[$id])) {
            die("ОШИБКА: Пользователь с ID $id не найден\n");
        }

        // Проверка на администратора
        if ($users[$id]['role'] == ROLE_AUTHOR) {
            die("ОШИБКА: Нельзя удалить администратора через массовое удаление. Используйте: php delete_user.php\n");
        }

        $users_to_delete[] = $users[$id];
    }
}

if (empty($users_to_delete)) {
    die("Нет пользователей для удаления.\n");
}

// Вывод списка пользователей для удаления
echo "\nБудут удалены следующие пользователи:\n";
echo str_repeat("-", 70) . "\n";

foreach ($users_to_delete as $user) {
    echo "ID: " . $user['id'] . " | " . $user['username'] . " | " . $role_names[$user['role']] . "\n";
}

echo str_repeat("-", 70) . "\n";
echo "Всего: " . count($users_to_delete) . " пользователей\n\n";

// Подтверждение
$confirm = prompt("Подтвердите удаление, введите 'YES': ");

if ($confirm !== 'YES') {
    die("Операция отменена.\n");
}

// Удаление пользователей
$db->exec('BEGIN TRANSACTION');

$deleted_count = 0;
$total_posts = 0;
$total_comments = 0;
$total_files = 0;

try {
    foreach ($users_to_delete as $user) {
        $user_id = $user['id'];

        // Получение статистики
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM posts WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $posts_count = $result->fetchArray(SQLITE3_ASSOC)['count'];
        $total_posts += $posts_count;

        $stmt = $db->prepare("SELECT COUNT(*) as count FROM comments WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $comments_count = $result->fetchArray(SQLITE3_ASSOC)['count'];
        $total_comments += $comments_count;

        // Удаление файлов с диска
        $stmt = $db->prepare("SELECT filename FROM files WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $result = $stmt->execute();

        while ($file = $result->fetchArray(SQLITE3_ASSOC)) {
            $filepath = UPLOAD_DIR . $file['filename'];
            if (file_exists($filepath)) {
                unlink($filepath);
                $total_files++;
            }
        }

        // Удаление записей файлов
        $stmt = $db->prepare("DELETE FROM files WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $stmt->execute();

        // Удаление комментариев
        $stmt = $db->prepare("DELETE FROM comments WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $stmt->execute();

        // Удаление связей постов с хештегами
        $stmt = $db->prepare("DELETE FROM post_hashtags WHERE post_id IN (SELECT id FROM posts WHERE user_id = :user_id)");
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $stmt->execute();

        // Удаление постов
        $stmt = $db->prepare("DELETE FROM posts WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $stmt->execute();

        // Удаление пользователя
        $stmt = $db->prepare("DELETE FROM users WHERE id = :user_id");
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $stmt->execute();

        $deleted_count++;
        echo "✓ Удален: " . $user['username'] . "\n";
    }

    $db->exec('COMMIT');

    echo "\n=== Итого ===\n";
    echo "✓ Удалено пользователей: $deleted_count\n";
    echo "✓ Удалено постов: $total_posts\n";
    echo "✓ Удалено комментариев: $total_comments\n";
    echo "✓ Удалено файлов: $total_files\n";

} catch (Exception $e) {
    $db->exec('ROLLBACK');
    echo "\n✗ Ошибка: " . $e->getMessage() . "\n";
    exit(1);
}

$db->close();