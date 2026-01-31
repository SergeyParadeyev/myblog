<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/markdown.php';

require_login();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'error' => 'Неверный запрос']);
    exit;
}

$file = $_FILES['file'];

// Расшифровка кодов ошибок
$upload_errors = [
    UPLOAD_ERR_OK => 'Нет ошибки',
    UPLOAD_ERR_INI_SIZE => 'Размер файла превышает upload_max_filesize в php.ini (текущее значение: ' . ini_get('upload_max_filesize') . ')',
    UPLOAD_ERR_FORM_SIZE => 'Размер файла превышает MAX_FILE_SIZE в форме',
    UPLOAD_ERR_PARTIAL => 'Файл загружен частично',
    UPLOAD_ERR_NO_FILE => 'Файл не был загружен',
    UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка',
    UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск',
    UPLOAD_ERR_EXTENSION => 'PHP расширение остановило загрузку файла',
];

// Проверка ошибок загрузки
if ($file['error'] !== UPLOAD_ERR_OK) {
    $error_message = isset($upload_errors[$file['error']])
        ? $upload_errors[$file['error']]
        : 'Неизвестная ошибка (код: ' . $file['error'] . ')';

    echo json_encode([
        'success' => false,
        'error' => $error_message,
        'php_settings' => [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time')
        ]
    ]);
    exit;
}

// Проверка размера приложения
if ($file['size'] > MAX_FILE_SIZE) {
    echo json_encode([
        'success' => false,
        'error' => 'Файл слишком большой. Максимум: ' . round(MAX_FILE_SIZE / 1024 / 1024, 2) . ' МБ'
    ]);
    exit;
}

// Проверка расширения
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ALLOWED_EXTENSIONS)) {
    echo json_encode([
        'success' => false,
        'error' => 'Недопустимый тип файла. Разрешены: ' . implode(', ', ALLOWED_EXTENSIONS)
    ]);
    exit;
}

// Создание директории, если не существует
if (!is_dir(UPLOAD_DIR)) {
    if (!mkdir(UPLOAD_DIR, 0755, true)) {
        echo json_encode(['success' => false, 'error' => 'Не удалось создать директорию для загрузок']);
        exit;
    }
}

// Проверка прав записи
if (!is_writable(UPLOAD_DIR)) {
    echo json_encode(['success' => false, 'error' => 'Нет прав на запись в директорию uploads/']);
    exit;
}

// Транслитерация и очистка имени файла
$original_name = $file['name'];
$name_without_ext = pathinfo($original_name, PATHINFO_FILENAME);

// Транслитерация кириллицы
$name_without_ext = transliterate($name_without_ext);

// Очистка от недопустимых символов
$safe_name_without_ext = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name_without_ext);
$safe_name_without_ext = preg_replace('/_+/', '_', $safe_name_without_ext);
$safe_name_without_ext = trim($safe_name_without_ext, '_');

// Если имя пустое после очистки
if (empty($safe_name_without_ext)) {
    $safe_name_without_ext = 'file_' . time();
}

// Ограничение длины
if (strlen($safe_name_without_ext) > 200) {
    $safe_name_without_ext = substr($safe_name_without_ext, 0, 200);
}

$safe_name = $safe_name_without_ext . '.' . $ext;
$filepath = UPLOAD_DIR . $safe_name;

// Поиск свободного имени
$counter = 1;
while (file_exists($filepath)) {
    $safe_name = $safe_name_without_ext . '_' . $counter . '.' . $ext;
    $filepath = UPLOAD_DIR . $safe_name;
    $counter++;

    if ($counter > 1000) {
        $safe_name = 'file_' . uniqid() . '.' . $ext;
        $filepath = UPLOAD_DIR . $safe_name;
        break;
    }
}

// Перемещение файла
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка сохранения файла. Проверьте права на папку uploads/'
    ]);
    exit;
}

// Сохранение в БД
try {
    $db = get_db();
    $stmt = $db->prepare("INSERT INTO files (filename, original_name, file_size, mime_type, user_id) 
                          VALUES (:filename, :original_name, :file_size, :mime_type, :user_id)");
    $stmt->bindValue(':filename', $safe_name, SQLITE3_TEXT);
    $stmt->bindValue(':original_name', $original_name, SQLITE3_TEXT);
    $stmt->bindValue(':file_size', $file['size'], SQLITE3_INTEGER);
    $stmt->bindValue(':mime_type', $file['type'], SQLITE3_TEXT);
    $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $stmt->execute();

    $file_id = $db->lastInsertRowID();
    $db->close();
} catch (Exception $e) {
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    echo json_encode(['success' => false, 'error' => 'Ошибка сохранения в БД: ' . $e->getMessage()]);
    exit;
}

// Успешный ответ
echo json_encode([
    'success' => true,
    'id' => $file_id,
    'filename' => $safe_name,
    'original_name' => $original_name,
    'url' => '/uploads/' . rawurlencode($safe_name),
    'markdown_image' => '![' . $original_name . '](/uploads/' . rawurlencode($safe_name) . ')',
    'markdown_link' => '[' . $original_name . '](/uploads/' . rawurlencode($safe_name) . ')'
], JSON_UNESCAPED_UNICODE);