<?php
$page_title = 'Главная';
require_once 'includes/header.php';

$db = get_db();

// Получение категории из GET
$category_id = isset($_GET['category']) ? (int) $_GET['category'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$hashtag = isset($_GET['hashtag']) ? trim($_GET['hashtag']) : '';

// Пагинация
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Подсчет общего количества постов
$where_conditions = [];
$params = [];
$join_hashtag = '';

if ($category_id) {
    $where_conditions[] = "p.category_id = :category_id";
    $params[':category_id'] = $category_id;
}

if ($search) {
    $where_conditions[] = "(p.title LIKE :search OR p.content LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($hashtag) {
    $join_hashtag = "INNER JOIN post_hashtags ph ON p.id = ph.post_id 
                     INNER JOIN hashtags h ON ph.hashtag_id = h.id";
    $where_conditions[] = "h.name = :hashtag";
    $params[':hashtag'] = $hashtag;
}

$count_query = "SELECT COUNT(DISTINCT p.id) as total FROM posts p " . $join_hashtag;

if (!empty($where_conditions)) {
    $count_query .= " WHERE " . implode(' AND ', $where_conditions);
}

$stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT);
}
$result = $stmt->execute();
$total = $result->fetchArray(SQLITE3_ASSOC)['total'];
$total_pages = ceil($total / $per_page);

// Получение постов
$query = "SELECT DISTINCT p.*, c.name as category_name, u.username 
          FROM posts p " . $join_hashtag . "
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN users u ON p.user_id = u.id";

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(' AND ', $where_conditions);
}

$query .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT);
}
$stmt->bindValue(':limit', $per_page, SQLITE3_INTEGER);
$stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
$result = $stmt->execute();

$posts = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $posts[] = $row;
}

// Получение категорий для меню
$categories_result = $db->query("SELECT * FROM categories ORDER BY name");
$categories = [];
while ($row = $categories_result->fetchArray(SQLITE3_ASSOC)) {
    $categories[] = $row;
}

// Получение популярных хештегов
$popular_hashtags_result = $db->query("SELECT h.name, COUNT(ph.post_id) as count 
                                       FROM hashtags h 
                                       LEFT JOIN post_hashtags ph ON h.id = ph.hashtag_id 
                                       GROUP BY h.id 
                                       HAVING count > 0
                                       ORDER BY count DESC, h.name ASC 
                                       LIMIT 20");
$popular_hashtags = [];
while ($row = $popular_hashtags_result->fetchArray(SQLITE3_ASSOC)) {
    $popular_hashtags[] = $row;
}

$db->close();
?>

<div class="row">
    <div class="col-md-3">
        <div class="card mb-3">
            <div class="card-header">
                <h5>Категории</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="/"
                    class="list-group-item list-group-item-action <?php echo !$category_id && !$hashtag ? 'active' : ''; ?>">
                    Все записи
                </a>
                <?php foreach ($categories as $cat): ?>
                    <a href="/?category=<?php echo $cat['id']; ?>"
                        class="list-group-item list-group-item-action <?php echo $category_id == $cat['id'] ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!empty($popular_hashtags)): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h5>Популярные хештеги</h5>
                </div>
                <div class="card-body">
                    <div class="hashtag-cloud">
                        <?php foreach ($popular_hashtags as $tag): ?>
                            <a href="/?hashtag=<?php echo urlencode($tag['name']); ?>"
                                class="badge bg-<?php echo $hashtag == $tag['name'] ? 'primary' : 'secondary'; ?> mb-1"
                                style="font-size: <?php echo min(1.2, 0.8 + ($tag['count'] * 0.1)); ?>rem;">
                                #<?php echo htmlspecialchars($tag['name']); ?>
                                <span class="badge bg-light text-dark"><?php echo $tag['count']; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5>Поиск</h5>
            </div>
            <div class="card-body">
                <form method="get">
                    <input type="text" name="search" class="form-control" placeholder="Поиск..."
                        value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary w-100 mt-2">Искать</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <?php if ($hashtag): ?>
            <div class="alert alert-info">
                Поиск по хештегу: <strong>#<?php echo htmlspecialchars($hashtag); ?></strong>
                <a href="/" class="btn btn-sm btn-secondary float-end">Сбросить</a>
            </div>
        <?php endif; ?>

        <?php if ($search): ?>
            <div class="alert alert-info">
                Результаты поиска: <strong><?php echo htmlspecialchars($search); ?></strong>
                <a href="/" class="btn btn-sm btn-secondary float-end">Сбросить</a>
            </div>
        <?php endif; ?>

        <?php if (empty($posts)): ?>
            <div class="alert alert-info">
                <?php if ($hashtag || $search || $category_id): ?>
                    По вашему запросу ничего не найдено.
                <?php else: ?>
                    Записей пока нет.
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h2 class="card-title">
                            <a href="/post.php?slug=<?php echo urlencode($post['slug']); ?>">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </a>
                        </h2>
                        <p class="text-muted small">
                            <?php echo date('d.m.Y H:i', strtotime($post['created_at'])); ?>
                            | Автор: <?php echo htmlspecialchars($post['username']); ?>
                            <?php if ($post['category_name']): ?>
                                | Категория: <?php echo htmlspecialchars($post['category_name']); ?>
                            <?php endif; ?>
                        </p>
                        <div class="post-preview">
                            <?php
                            $preview = strip_tags(substr($post['content'], 0, 300));
                            echo htmlspecialchars($preview) . '...';
                            ?>
                        </div>
                        <a href="/post.php?slug=<?php echo urlencode($post['slug']); ?>" class="btn btn-primary btn-sm mt-2">
                            Читать далее
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($total_pages > 1): ?>
                <nav>
                    <ul class="pagination">
                        <?php
                        $query_params = [];
                        if ($category_id)
                            $query_params[] = 'category=' . $category_id;
                        if ($search)
                            $query_params[] = 'search=' . urlencode($search);
                        if ($hashtag)
                            $query_params[] = 'hashtag=' . urlencode($hashtag);
                        $query_string = !empty($query_params) ? '&' . implode('&', $query_params) : '';
                        ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i . $query_string; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>