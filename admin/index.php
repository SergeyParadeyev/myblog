<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(ROLE_AUTHOR);

$page_title = 'Админ-панель';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

// Статистика
$posts_count = $db->querySingle("SELECT COUNT(*) FROM posts");
$comments_count = $db->querySingle("SELECT COUNT(*) FROM comments");
$categories_count = $db->querySingle("SELECT COUNT(*) FROM categories");
$files_count = $db->querySingle("SELECT COUNT(*) FROM files");

$db->close();
?>

<div class="row">
    <div class="col-md-12">
        <h1>Админ-панель</h1>
        <hr>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h2>
                    <?php echo $posts_count; ?>
                </h2>
                <p>Записей</p>
                <a href="/admin/posts.php" class="btn btn-primary">Управление</a>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h2>
                    <?php echo $categories_count; ?>
                </h2>
                <p>Категорий</p>
                <a href="/admin/categories.php" class="btn btn-primary">Управление</a>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h2>
                    <?php echo $comments_count; ?>
                </h2>
                <p>Комментариев</p>
                <a href="/admin/comments.php" class="btn btn-primary">Управление</a>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h2>
                    <?php echo $files_count; ?>
                </h2>
                <p>Файлов</p>
                <a href="/admin/files.php" class="btn btn-primary">Управление</a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3>Быстрые действия</h3>
            </div>
            <div class="card-body">
                <a href="/admin/post_create.php" class="btn btn-success">Создать запись</a>
                <a href="/admin/category_create.php" class="btn btn-success">Создать категорию</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>