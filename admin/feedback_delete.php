<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(ROLE_AUTHOR);

if (!isset($_GET['id'])) {
    header('Location: /admin/feedback.php');
    exit;
}

$feedback_id = (int) $_GET['id'];
$db = get_db();

$stmt = $db->prepare("DELETE FROM feedback WHERE id = :id");
$stmt->bindValue(':id', $feedback_id, SQLITE3_INTEGER);
$stmt->execute();

$db->close();

header('Location: /admin/feedback.php');
exit;