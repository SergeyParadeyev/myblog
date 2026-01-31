<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(ROLE_AUTHOR);

$page_title = 'Управление комментариями';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

$result = $db->query("SELECT c.*, u.username, p.title as post_title, p.slug as post_slug 
                      FROM comments c 
                      JOIN users u ON c.user_id = u.id 
                      JOIN posts p ON c.post_id = p.id 
                      ORDER BY c.created_at DESC");

$comments = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $comments[] = $row;
}

$db->close();
?>

<div class="row">
    <div class="col-md-12">
        <h1>Управление комментариями</h1>

        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Запись</th>
                            <th>Автор</th>
                            <th>Комментарий</th>
                            <th>Дата</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comments as $comment): ?>
                            <tr>
                                <td>
                                    <?php echo $comment['id']; ?>
                                </td>
                                <td>
                                    <a href="/post.php?slug=<?php echo urlencode($comment['post_slug']); ?>">
                                        <?php echo htmlspecialchars($comment['post_title']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($comment['username']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars(substr($comment['content'], 0, 50)) . '...'; ?>
                                </td>
                                <td>
                                    <?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?>
                                </td>
                                <td>
                                    <a href="/admin/comment_delete.php?id=<?php echo $comment['id']; ?>"
                                        class="btn btn-sm btn-danger" onclick="return confirm('Удалить?')">Удалить</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>