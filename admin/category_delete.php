<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(ROLE_AUTHOR);

if (!isset($_GET['id'])) {
    header('Location: /admin/categories.php');
    exit;
}

$category_id = (int) $_GET['id'];
$db = get_db();

$stmt = $db->prepare("DELETE FROM categories WHERE id = :id");
$stmt->bindValue(':id', $category_id, SQLITE3_INTEGER);
$stmt->execute();

$db->close();

header('Location: /admin/categories.php');
exit;