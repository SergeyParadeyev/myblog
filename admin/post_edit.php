<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/markdown.php';

require_role(ROLE_AUTHOR);

if (!isset($_GET['id'])) {
    header('Location: /admin/posts.php');
    exit;
}

$post_id = (int) $_GET['id'];
$db = get_db();

// Получение записи
$stmt = $db->prepare("SELECT * FROM posts WHERE id = :id");
$stmt->bindValue(':id', $post_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$post = $result->fetchArray(SQLITE3_ASSOC);

if (!$post) {
    die('Запись не найдена');
}

// Получение хештегов
$stmt = $db->prepare("SELECT h.name 
                      FROM hashtags h 
                      JOIN post_hashtags ph ON h.id = ph.hashtag_id 
                      WHERE ph.post_id = :post_id");
$stmt->bindValue(':post_id', $post_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$hashtags = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $hashtags[] = $row['name'];
}

$error = '';

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
        // Обновление записи
        $stmt = $db->prepare("UPDATE posts SET title = :title, content = :content, category_id = :category_id, visibility = :visibility, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->bindValue(':title', $title, SQLITE3_TEXT);
        $stmt->bindValue(':content', $content, SQLITE3_TEXT);
        $stmt->bindValue(':category_id', $category_id, SQLITE3_INTEGER);
        $stmt->bindValue(':visibility', $visibility, SQLITE3_INTEGER);
        $stmt->bindValue(':id', $post_id, SQLITE3_INTEGER);
        $stmt->execute();

        // ... остальной код с хештегами
        // Добавление новых хештегов
        if (!empty($hashtags_input)) {
            $hashtags = array_map('trim', explode(',', $hashtags_input));
            foreach ($hashtags as $tag) {
                if (empty($tag))
                    continue;

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

                $stmt = $db->prepare(
                    "INSERT OR IGNORE INTO post_hashtags (post_id, hashtag_id)
                     VALUES (:post_id, :hashtag_id)"
                );
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
$result = $db->query("SELECT * FROM categories ORDER BY name");
$categories = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $categories[] = $row;
}

$db->close();

$page_title = 'Редактирование записи';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h1>Редактирование записи</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Заголовок *</label>
                        <input type="text" name="title" class="form-control"
                            value="<?php echo htmlspecialchars($post['title']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Категория</label>
                        <select name="category_id" class="form-control">
                            <option value="">Без категории</option>
                            <?php foreach ($categories as $cat): ?>

                                <option value="<?php echo $cat['id']; ?>" <?php echo $post['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Видимость *</label>
                        <select name="visibility" class="form-control">
                            <option value="<?php echo VISIBILITY_PUBLIC; ?>" <?php echo $post['visibility'] == VISIBILITY_PUBLIC ? 'selected' : ''; ?>>
                                Публичный (видно всем, включая гостей)
                            </option>
                            <option value="<?php echo VISIBILITY_AUTHORIZED; ?>" <?php echo $post['visibility'] == VISIBILITY_AUTHORIZED ? 'selected' : ''; ?>>
                                Для авторизованных (только для зарегистрированных)
                            </option>
                            <option value="<?php echo VISIBILITY_ADMIN; ?>" <?php echo $post['visibility'] == VISIBILITY_ADMIN ? 'selected' : ''; ?>>
                                Только для администратора
                            </option>
                        </select>
                        <small class="text-muted">Выберите, кто может видеть эту запись</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Хештеги (через запятую)</label>
                        <input type="text" name="hashtags" class="form-control"
                            value="<?php echo htmlspecialchars(implode(', ', $hashtags)); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Содержимое (Markdown) *</label>
                        <textarea name="content" class="form-control" rows="20"
                            required><?php echo htmlspecialchars($post['content']); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Загрузить файл</label>
                        <input type="file" id="file-upload" class="form-control">
                        <div id="file-upload-result" class="mt-2"></div>
                    </div>

                    <button type="submit" class="btn btn-success">Сохранить</button>
                    <a href="/admin/posts.php" class="btn btn-secondary">Отмена</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>