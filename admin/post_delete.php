<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(ROLE_AUTHOR);

if (!isset($_GET['id'])) {
    header('Location: /admin/posts.php');
    exit;
}

$post_id = (int) $_GET['id'];
$db = get_db();

$stmt = $db->prepare("DELETE FROM posts WHERE id = :id");
$stmt->bindValue(':id', $post_id, SQLITE3_INTEGER);
$stmt->execute();

$db->close();

header('Location: /admin/posts.php');
exit;