<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(ROLE_AUTHOR);

$page_title = 'Управление категориями';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

$result = $db->query("SELECT c.*, COUNT(p.id) as posts_count 
                      FROM categories c 
                      LEFT JOIN posts p ON c.id = p.category_id 
                      GROUP BY c.id 
                      ORDER BY c.name");

$categories = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $categories[] = $row;
}

$db->close();
?>

<div class="row">
    <div class="col-md-12">
        <h1>Управление категориями</h1>
        <a href="/admin/category_create.php" class="btn btn-success mb-3">Создать категорию</a>

        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Slug</th>
                            <th>Записей</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <?php echo $cat['id']; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($cat['slug']); ?>
                                </td>
                                <td>
                                    <?php echo $cat['posts_count']; ?>
                                </td>
                                <td>
                                    <a href="/admin/category_edit.php?id=<?php echo $cat['id']; ?>"
                                        class="btn btn-sm btn-warning">Изменить</a>
                                    <a href="/admin/category_delete.php?id=<?php echo $cat['id']; ?>"
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