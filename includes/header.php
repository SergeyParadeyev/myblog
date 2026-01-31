<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';

$current_user = get_logged_user();
$site_title = SITE_NAME;
if (isset($page_title)) {
    $site_title = htmlspecialchars($page_title) . ' - ' . SITE_NAME;
}

// Определение темы
$theme = 'light'; // По умолчанию
if (isset($_COOKIE['theme']) && in_array($_COOKIE['theme'], ['light', 'dark'])) {
    $theme = $_COOKIE['theme'];
} elseif (isset($_SESSION['theme']) && in_array($_SESSION['theme'], ['light', 'dark'])) {
    $theme = $_SESSION['theme'];
}

$theme_file = $theme === 'dark' ? 'style_dark.css' : 'style.css';
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars(SITE_DESCRIPTION); ?>">
    <meta name="author" content="<?php echo htmlspecialchars(SITE_AUTHOR); ?>">
    <title><?php echo $site_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.10.0/styles/vs.min.css">
    <!-- <link href="/assets/css/style.css" rel="stylesheet"> -->
    <link href="/assets/css/<?php echo $theme_file; ?>" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand d-flex flex-column" href="/">
                <span class="fw-semibold"><?php echo htmlspecialchars(SITE_NAME); ?></span>
                <small class="text-white-50" style="font-size: 0.75rem;">
                    <?php echo htmlspecialchars(SITE_DESCRIPTION); ?>
                </small>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Главная</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link"
                            href="/theme_switcher.php?theme=<?php echo $theme === 'dark' ? 'light' : 'dark'; ?>">
                            <?php echo $theme === 'dark' ? '☀ Светлая' : '🌙 Темная'; ?>
                        </a>
                    </li>
                    <?php if (is_author()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/">Админка</a>
                        </li>
                    <?php endif; ?>
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item">
                            <span class="nav-link">Привет, <?php echo htmlspecialchars($current_user['username']); ?></span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/logout.php">Выйти</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login.php">Войти</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/register.php">Регистрация</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container mt-4">