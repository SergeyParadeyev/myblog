<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(ROLE_AUTHOR);

if (!isset($_GET['id'])) {
    header('Location: /admin/files.php');
    exit;
}

$file_id = (int) $_GET['id'];
$db = get_db();

// Получение информации о файле
$stmt = $db->prepare("SELECT filename FROM files WHERE id = :id");
$stmt->bindValue(':id', $file_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$file = $result->fetchArray(SQLITE3_ASSOC);

if ($file) {
    // Удаление физического файла
    $filepath = UPLOAD_DIR . $file['filename'];
    if (file_exists($filepath)) {
        unlink($filepath);
    }

    // Удаление записи из БД
    $stmt = $db->prepare("DELETE FROM files WHERE id = :id");
    $stmt->bindValue(':id', $file_id, SQLITE3_INTEGER);
    $stmt->execute();
}

$db->close();

header('Location: /admin/files.php');
exit;