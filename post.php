<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/markdown.php';

if (!isset($_GET['slug'])) {
    header('Location: /');
    exit;
}

$slug = $_GET['slug'];
$db = get_db();

// Получение поста
$stmt = $db->prepare("SELECT p.*, c.name as category_name, u.username 
                      FROM posts p 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      LEFT JOIN users u ON p.user_id = u.id 
                      WHERE p.slug = :slug");
$stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
$result = $stmt->execute();
$post = $result->fetchArray(SQLITE3_ASSOC);

if (!$post) {
    die('Запись не найдена');
}

// Проверка прав доступа к посту
if (!can_view_post($post['visibility'])) {
    die('<h1>Доступ запрещен</h1><p>У вас нет прав для просмотра этой записи. <a href="/">Вернуться на главную</a></p>');
}

// Получение хештегов
$stmt = $db->prepare("SELECT h.name 
                      FROM hashtags h 
                      JOIN post_hashtags ph ON h.id = ph.hashtag_id 
                      WHERE ph.post_id = :post_id");
$stmt->bindValue(':post_id', $post['id'], SQLITE3_INTEGER);
$result = $stmt->execute();
$hashtags = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $hashtags[] = $row['name'];
}

// Обработка добавления комментария
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    if (can_comment()) {
        $content = trim($_POST['content']);
        if (!empty($content)) {
            $stmt = $db->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (:post_id, :user_id, :content)");
            $stmt->bindValue(':post_id', $post['id'], SQLITE3_INTEGER);
            $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
            $stmt->bindValue(':content', $content, SQLITE3_TEXT);
            $stmt->execute();
            header('Location: /post.php?slug=' . urlencode($slug));
            exit;
        }
    }
}

// Получение комментариев
$stmt = $db->prepare("SELECT c.*, u.username 
                      FROM comments c 
                      JOIN users u ON c.user_id = u.id 
                      WHERE c.post_id = :post_id 
                      ORDER BY c.created_at DESC");
$stmt->bindValue(':post_id', $post['id'], SQLITE3_INTEGER);
$result = $stmt->execute();
$comments = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $comments[] = $row;
}

$db->close();

$page_title = $post['title'];
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <article class="card mb-4">
            <div class="card-body">
                <h1>
                    <?php echo htmlspecialchars($post['title']); ?>
                    <?php if (is_author()): ?>
                        <span class="badge bg-<?php
                        echo $post['visibility'] == VISIBILITY_PUBLIC ? 'success' :
                            ($post['visibility'] == VISIBILITY_AUTHORIZED ? 'warning' : 'danger');
                        ?>" style="font-size: 0.6rem;">
                            <?php echo get_visibility_name($post['visibility']); ?>
                        </span>
                    <?php endif; ?>
                </h1>
                <p class="text-muted">
                    <?php echo date('d.m.Y H:i', strtotime($post['created_at'])); ?>
                    | Автор: <?php echo htmlspecialchars($post['username']); ?>
                    <?php if ($post['category_name']): ?>
                        | Категория: <a
                            href="/?category=<?php echo $post['category_id']; ?>"><?php echo htmlspecialchars($post['category_name']); ?></a>
                    <?php endif; ?>
                </p>

                <?php if (!empty($hashtags)): ?>
                    <div class="mb-3">
                        <?php foreach ($hashtags as $tag): ?>
                            <a href="/?hashtag=<?php echo urlencode($tag); ?>" class="badge bg-secondary text-decoration-none">
                                #<?php echo htmlspecialchars($tag); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <hr>

                <div class="post-content" data-markdown="<?php echo htmlspecialchars($post['content']); ?>">
                    <!-- Контент будет отрендерен через marked.js -->
                </div>

                <?php if (is_author()): ?>
                    <hr>
                    <div class="mt-3">
                        <a href="/admin/post_edit.php?id=<?php echo $post['id']; ?>"
                            class="btn btn-warning">Редактировать</a>
                        <a href="/admin/post_delete.php?id=<?php echo $post['id']; ?>" class="btn btn-danger"
                            onclick="return confirm('Удалить запись?')">Удалить</a>
                    </div>
                <?php endif; ?>
            </div>
        </article>

        <div class="card">
            <div class="card-header">
                <h3>Комментарии (<?php echo count($comments); ?>)</h3>
            </div>
            <div class="card-body">
                <?php if (can_comment()): ?>
                    <form method="post" class="mb-4">
                        <div class="mb-3">
                            <textarea name="content" class="form-control" rows="3" placeholder="Ваш комментарий..."
                                required></textarea>
                        </div>
                        <button type="submit" name="add_comment" class="btn btn-primary">Добавить комментарий</button>
                    </form>
                <?php elseif (!is_logged_in()): ?>
                    <div class="alert alert-info">
                        <a href="/login.php">Войдите</a>, чтобы оставлять комментарии.
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        У вас нет прав для добавления комментариев.
                    </div>
                <?php endif; ?>

                <?php if (empty($comments)): ?>
                    <p class="text-muted">Комментариев пока нет.</p>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="card mb-2">
                            <div class="card-body">
                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($comment['username']); ?>
                                    | <?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?>
                                    <?php if (is_author()): ?>
                                        | <a href="/admin/comment_delete.php?id=<?php echo $comment['id']; ?>&post_slug=<?php echo urlencode($slug); ?>"
                                            onclick="return confirm('Удалить комментарий?')">Удалить</a>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>