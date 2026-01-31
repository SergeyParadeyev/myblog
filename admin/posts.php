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
                <div class="table-responsive">
                    <table class="table table-striped admin-posts-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th style="width: 30%;">Заголовок</th>
                                <th style="width: 150px;">Видимость</th>
                                <th style="width: 120px;">Категория</th>
                                <th style="width: 100px;">Автор</th>
                                <th style="width: 120px;">Дата</th>
                                <th style="width: 280px;">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($posts)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Записей пока нет</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($posts as $post): ?>
                                    <tr>
                                        <td><?php echo $post['id']; ?></td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 400px;"
                                                title="<?php echo htmlspecialchars($post['title']); ?>">
                                                <?php echo htmlspecialchars($post['title']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php
                                            echo $post['visibility'] == VISIBILITY_PUBLIC ? 'success' :
                                                ($post['visibility'] == VISIBILITY_AUTHORIZED ? 'warning' : 'danger');
                                            ?>">
                                                <?php echo get_visibility_name($post['visibility']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($post['category_name'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($post['username']); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($post['created_at'])); ?></td>
                                        <td class="actions-column">
                                            <div class="btn-group-vertical btn-group-sm d-inline-flex" role="group">
                                                <a href="/post.php?slug=<?php echo urlencode($post['slug']); ?>"
                                                    class="btn btn-sm btn-info" target="_blank">Просмотр</a>
                                                <a href="/admin/post_edit.php?id=<?php echo $post['id']; ?>"
                                                    class="btn btn-sm btn-warning">Изменить</a>
                                                <a href="/admin/post_delete.php?id=<?php echo $post['id']; ?>"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Удалить?')">Удалить</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>