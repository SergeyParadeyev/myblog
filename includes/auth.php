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