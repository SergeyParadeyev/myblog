<?php
define('REGISTER_PAGE', true);

// Настройки приложения
define('DB_PATH', __DIR__ . '/db/database.db');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_EXTENSIONS', [
    // Изображения
    'jpg',
    'jpeg',
    'png',
    'gif',
    'webp',
    'svg',
    'bmp',
    'ico',
    // Видео
    'mp4',
    'avi',
    'mov',
    'wmv',
    'flv',
    'mkv',
    'webm',
    // Документы
    'pdf',
    'doc',
    'docx',
    'xls',
    'xlsx',
    'ppt',
    'pptx',
    'txt',
    'rtf',
    'odt',
    'ods',
    // Архивы
    'zip',
    'rar',
    '7z',
    'tar',
    'gz',
    'bz2',
    // Код
    'html',
    'css',
    'js',
    'json',
    'xml',
    'php',
    'py',
    'java',
    'c',
    'cpp',
    'h',
    'sql',
    // Другое
    'csv',
    'md'
]);

// Роли пользователей
define('ROLE_GUEST', 0);
define('ROLE_TRUSTED', 1);
define('ROLE_AUTHOR', 2);

// Запуск сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Подключение к БД
require_once __DIR__ . '/db/init.php';