<?php
/**
 * Скрипт удаления пользователя из командной строки
 * Использование: php delete_user.php
 */

// Проверка запуска из командной строки
if (php_sapi_name() !== 'cli') {
    die("Этот скрипт можно запускать только из командной строки\n");
}

require_once __DIR__ . '/config.php';

echo "=== Удаление пользователя ===\n\n";

// Функция для безопасного ввода
function prompt($message)
{
    echo $message;
    return trim(fgets(STDIN));
}

// Ввод логина пользователя
$username = prompt("Введите логин пользователя для удаления: ");

if (empty($username)) {
    die("Ошибка: Логин не может быть пустым\n");
}

// Проверка существования пользователя
$db = get_db();

$stmt = $db->prepare("SELECT id, username, role FROM users WHERE username = :username");
$stmt->bindValue(':username', $username, SQLITE3_TEXT);
$result = $stmt->execute();
$user = $result->fetchArray(SQLITE3_ASSOC);

if (!$user) {
    $db->close();
    die("Ошибка: Пользователь '$username' не найден\n");
}

// Информация о пользователе
echo "\nИнформация о пользователе:\n";
echo "ID: " . $user['id'] . "\n";
echo "Логин: " . $user['username'] . "\n";

$role_names = [
    ROLE_GUEST => 'Гость',
    ROLE_TRUSTED => 'Доверенный',
    ROLE_AUTHOR => 'Администратор'
];
echo "Роль: " . ($role_names[$user['role']] ?? 'Неизвестно') . "\n\n";

// Проверка, не является ли пользователь последним администратором
if ($user['role'] == ROLE_AUTHOR) {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role = :role");
    $stmt->bindValue(':role', ROLE_AUTHOR, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);

    if ($row['count'] <= 1) {
        $db->close();
        die("ОШИБКА: Нельзя удалить единственного администратора!\nСначала создайте нового администратора командой: php create_admin.php\n");
    }
}

// Получение статистики
$stmt = $db->prepare("SELECT COUNT(*) as count FROM posts WHERE user_id = :user_id");
$stmt->bindValue(':user_id', $user['id'], SQLITE3_INTEGER);
$result = $stmt->execute();
$posts_count = $result->fetchArray(SQLITE3_ASSOC)['count'];

$stmt = $db->prepare("SELECT COUNT(*) as count FROM comments WHERE user_id = :user_id");
$stmt->bindValue(':user_id', $user['id'], SQLITE3_INTEGER);
$result = $stmt->execute();
$comments_count = $result->fetchArray(SQLITE3_ASSOC)['count'];

$stmt = $db->prepare("SELECT COUNT(*) as count FROM files WHERE user_id = :user_id");
$stmt->bindValue(':user_id', $user['id'], SQLITE3_INTEGER);
$result = $stmt->execute();
$files_count = $result->fetchArray(SQLITE3_ASSOC)['count'];

echo "Будет удалено:\n";
echo "- Постов: $posts_count\n";
echo "- Комментариев: $comments_count\n";
echo "- Файлов: $files_count\n\n";

// Подтверждение удаления
echo "ВНИМАНИЕ! Это действие необратимо!\n";
echo "Все посты, комментарии и файлы пользователя будут удалены.\n\n";

$confirm = prompt("Вы уверены, что хотите удалить пользователя '$username'? Введите 'YES' для подтверждения: ");

if ($confirm !== 'YES') {
    $db->close();
    die("Операция отменена.\n");
}

// Дополнительное подтверждение для администраторов
if ($user['role'] == ROLE_AUTHOR) {
    echo "\nВы удаляете АДМИНИСТРАТОРА!\n";
    $confirm2 = prompt("Повторите подтверждение, введите 'DELETE ADMIN': ");

    if ($confirm2 !== 'DELETE ADMIN') {
        $db->close();
        die("Операция отменена.\n");
    }
}

// Начало транзакции
$db->exec('BEGIN TRANSACTION');

try {
    $user_id = $user['id'];

    // Удаление файлов с диска
    $stmt = $db->prepare("SELECT filename FROM files WHERE user_id = :user_id");
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $result = $stmt->execute();

    $deleted_files = 0;
    while ($file = $result->fetchArray(SQLITE3_ASSOC)) {
        $filepath = UPLOAD_DIR . $file['filename'];
        if (file_exists($filepath)) {
            if (unlink($filepath)) {
                $deleted_files++;
            }
        }
    }

    // Удаление записей файлов из БД
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

    // Удаление постов (каскадно удалит связанные комментарии)
    $stmt = $db->prepare("DELETE FROM posts WHERE user_id = :user_id");
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->execute();

    // Удаление самого пользователя
    $stmt = $db->prepare("DELETE FROM users WHERE id = :user_id");
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->execute();

    // Подтверждение транзакции
    $db->exec('COMMIT');

    echo "\n✓ Пользователь '$username' успешно удален!\n";
    echo "✓ Удалено постов: $posts_count\n";
    echo "✓ Удалено комментариев: $comments_count\n";
    echo "✓ Удалено файлов: $deleted_files из $files_count\n";

} catch (Exception $e) {
    // Откат транзакции в случае ошибки
    $db->exec('ROLLBACK');
    echo "\n✗ Ошибка при удалении пользователя: " . $e->getMessage() . "\n";
    exit(1);
}

$db->close();