<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/markdown.php';

require_role(ROLE_AUTHOR);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category_id = !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null;
    $hashtags_input = trim($_POST['hashtags']);
    $visibility = isset($_POST['visibility']) ? (int) $_POST['visibility'] : VISIBILITY_PUBLIC;

    // Валидация видимости
    if (!in_array($visibility, [VISIBILITY_PUBLIC, VISIBILITY_AUTHORIZED, VISIBILITY_ADMIN])) {
        $visibility = VISIBILITY_PUBLIC;
    }

    if (empty($title) || empty($content)) {
        $error = 'Заполните обязательные поля';
    } else {
        $slug = slugify($title);

        $db = get_db();

        // Проверка уникальности slug
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM posts WHERE slug = :slug");
        $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);

        if ($row['count'] > 0) {
            $slug .= '-' . time();
        }

        // Создание записи
        $stmt = $db->prepare("INSERT INTO posts (title, slug, content, category_id, user_id, visibility) 
                              VALUES (:title, :slug, :content, :category_id, :user_id, :visibility)");
        $stmt->bindValue(':title', $title, SQLITE3_TEXT);
        $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
        $stmt->bindValue(':content', $content, SQLITE3_TEXT);
        $stmt->bindValue(':category_id', $category_id, SQLITE3_INTEGER);
        $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':visibility', $visibility, SQLITE3_INTEGER);
        $stmt->execute();

        $post_id = $db->lastInsertRowID();

        // Обработка хештегов
        if (!empty($hashtags_input)) {
            $hashtags = array_map('trim', explode(',', $hashtags_input));
            foreach ($hashtags as $tag) {
                if (empty($tag))
                    continue;

                // Поиск или создание хештега
                $stmt = $db->prepare("SELECT id FROM hashtags WHERE name = :name");
                $stmt->bindValue(':name', $tag, SQLITE3_TEXT);
                $result = $stmt->execute();
                $hashtag = $result->fetchArray(SQLITE3_ASSOC);

                if (!$hashtag) {
                    $stmt = $db->prepare("INSERT INTO hashtags (name) VALUES (:name)");
                    $stmt->bindValue(':name', $tag, SQLITE3_TEXT);
                    $stmt->execute();
                    $hashtag_id = $db->lastInsertRowID();
                } else {
                    $hashtag_id = $hashtag['id'];
                }

                // Связывание
                $stmt = $db->prepare("INSERT INTO post_hashtags (post_id, hashtag_id) VALUES (:post_id, :hashtag_id)");
                $stmt->bindValue(':post_id', $post_id, SQLITE3_INTEGER);
                $stmt->bindValue(':hashtag_id', $hashtag_id, SQLITE3_INTEGER);
                $stmt->execute();
            }
        }

        $db->close();

        header('Location: /admin/posts.php');
        exit;
    }
}

// Получение категорий
$db = get_db();
$result = $db->query("SELECT * FROM categories ORDER BY name");
$categories = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $categories[] = $row;
}
$db->close();

$page_title = 'Создание записи';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h1>Создание записи</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Заголовок *</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Категория</label>
                        <select name="category_id" class="form-control">
                            <option value="">Без категории</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Видимость *</label>
                        <select name="visibility" class="form-control">
                            <option value="<?php echo VISIBILITY_PUBLIC; ?>">Публичный (видно всем, включая гостей)
                            </option>
                            <option value="<?php echo VISIBILITY_AUTHORIZED; ?>">Для авторизованных (только для
                                зарегистрированных)</option>
                            <option value="<?php echo VISIBILITY_ADMIN; ?>">Только для администратора</option>
                        </select>
                        <small class="text-muted">Выберите, кто может видеть эту запись</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Хештеги (через запятую)</label>
                        <input type="text" name="hashtags" class="form-control" placeholder="web, php, tutorial">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Содержимое (Markdown) *</label>
                        <textarea name="content" class="form-control" rows="20" required></textarea>
                        <small class="text-muted">Поддерживается Markdown разметка</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Загрузить файл</label>
                        <input type="file" id="file-upload" class="form-control">
                        <div id="file-upload-result" class="mt-2"></div>
                    </div>

                    <button type="submit" class="btn btn-success">Создать</button>
                    <a href="/admin/posts.php" class="btn btn-secondary">Отмена</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>