<?php
// theme_switcher.php
session_start();

if (isset($_GET['theme'])) {
    $theme = $_GET['theme'];
    if (in_array($theme, ['light', 'dark'])) {
        $_SESSION['theme'] = $theme;
        setcookie('theme', $theme, time() + (86400 * 365), '/'); // 1 год
    }
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
exit;