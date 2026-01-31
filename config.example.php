<?php
/**
 * Пример конфигурационного файла
 * Скопируйте этот файл в config.php и измените настройки под свои нужды
 */

// Основные настройки приложения
define('SITE_NAME', 'Мой Блог'); // Название сайта
define('SITE_DESCRIPTION', 'Персональный блог'); // Описание сайта
define('SITE_AUTHOR', 'Администратор'); // Автор по умолчанию

// Пути и файлы
define('DB_PATH', __DIR__ . '/db/database.db');
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// Ограничения загрузки
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

// Настройки отображения
define('POSTS_PER_PAGE', 10); // Постов на странице

// Запуск сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Подключение к БД
require_once __DIR__ . '/db/init.php';