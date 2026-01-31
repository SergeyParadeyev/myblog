<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(ROLE_AUTHOR);

if (!isset($_GET['id'])) {
    header('Location: /admin/comments.php');
    exit;
}

$comment_id = (int) $_GET['id'];
$post_slug = isset($_GET['post_slug']) ? $_GET['post_slug'] : null;

$db = get_db();

$stmt = $db->prepare("DELETE FROM comments WHERE id = :id");
$stmt->bindValue(':id', $comment_id, SQLITE3_INTEGER);
$stmt->execute();

$db->close();

if ($post_slug) {
    header('Location: /post.php?slug=' . urlencode($post_slug));
} else {
    header('Location: /admin/comments.php');
}
exit;