<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(ROLE_AUTHOR);

$page_title = 'Управление записями';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

$result = $db->query("SELECT p.*, c.name as category_name, u.username 
                      FROM posts p 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      LEFT JOIN users u ON p.user_id = u.id 
                      ORDER BY p.created_at DESC");

$posts = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $posts[] = $row;
}

$db->close();
?>

<div class="row">
    <div class="col-md-12">
        <h1>Управление записями</h1>
        <a href="/admin/post_create.php" class="btn btn-success mb-3">Создать запись</a>

        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Заголовок</th>
                            <th>Категория</th>
                            <th>Автор</th>
                            <th>Дата</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td>
                                    <?php echo $post['id']; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($post['category_name'] ?? '-'); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($post['username']); ?>
                                </td>
                                <td>
                                    <?php echo date('d.m.Y', strtotime($post['created_at'])); ?>
                                </td>
                                <td>
                                    <a href="/post.php?slug=<?php echo urlencode($post['slug']); ?>"
                                        class="btn btn-sm btn-info">Просмотр</a>
                                    <a href="/admin/post_edit.php?id=<?php echo $post['id']; ?>"
                                        class="btn btn-sm btn-warning">Изменить</a>
                                    <a href="/admin/post_delete.php?id=<?php echo $post['id']; ?>"
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