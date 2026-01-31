<?php
// Функции авторизации

function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

function get_logged_user()
{
    if (!is_logged_in()) {
        return null;
    }

    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();

    return $user;
}

function require_login()
{
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

function require_role($min_role)
{
    require_login();
    $user = get_logged_user();

    if ($user['role'] < $min_role) {
        die('Доступ запрещен');
    }
}

function can_comment()
{
    if (!is_logged_in()) {
        return false;
    }
    $user = get_logged_user();
    return $user['role'] >= ROLE_TRUSTED;
}

function is_author()
{
    if (!is_logged_in()) {
        return false;
    }
    $user = get_logged_user();
    return $user['role'] == ROLE_AUTHOR;
}

function get_user_role()
{
    if (!is_logged_in()) {
        return ROLE_GUEST;
    }
    $user = get_logged_user();
    return $user['role'];
}

function can_view_post($post_visibility)
{
    $user_role = get_user_role();

    // Администратор видит всё
    if ($user_role == ROLE_AUTHOR) {
        return true;
    }

    // Авторизованный пользователь видит публичные и для авторизованных
    if ($user_role >= ROLE_TRUSTED && $post_visibility <= VISIBILITY_AUTHORIZED) {
        return true;
    }

    // Гость видит только публичные
    if ($post_visibility == VISIBILITY_PUBLIC) {
        return true;
    }

    return false;
}

function get_visibility_sql_condition()
{
    $user_role = get_user_role();

    if ($user_role == ROLE_AUTHOR) {
        // Администратор видит все посты
        return "1=1";
    } elseif ($user_role >= ROLE_TRUSTED) {
        // Авторизованные видят публичные и для авторизованных
        return "p.visibility IN (" . VISIBILITY_PUBLIC . ", " . VISIBILITY_AUTHORIZED . ")";
    } else {
        // Гости видят только публичные
        return "p.visibility = " . VISIBILITY_PUBLIC;
    }
}

function get_visibility_name($visibility)
{
    switch ($visibility) {
        case VISIBILITY_PUBLIC:
            return 'Публичный';
        case VISIBILITY_AUTHORIZED:
            return 'Для авторизованных';
        case VISIBILITY_ADMIN:
            return 'Только для администратора';
        default:
            return 'Неизвестно';
    }
}