<?php
$page_title = 'Главная';
require_once 'includes/header.php';

$db = get_db();

// Получение параметров из GET
$category_id = isset($_GET['category']) ? (int) $_GET['category'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$hashtag = isset($_GET['hashtag']) ? trim($_GET['hashtag']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'date';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'desc';

// Валидация параметров сортировки
$allowed_sort = ['date', 'title'];
$allowed_order = ['asc', 'desc'];

if (!in_array($sort_by, $allowed_sort)) {
    $sort_by = 'date';
}

if (!in_array($sort_order, $allowed_order)) {
    $sort_order = 'desc';
}

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

// Определение сортировки
$order_clause = '';
if ($sort_by === 'date') {
    $order_clause = "p.created_at " . strtoupper($sort_order);
} elseif ($sort_by === 'title') {
    $order_clause = "p.title " . strtoupper($sort_order);
}

// Получение постов
$query = "SELECT DISTINCT p.*, c.name as category_name, u.username 
          FROM posts p " . $join_hashtag . "
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN users u ON p.user_id = u.id";

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(' AND ', $where_conditions);
}

$query .= " ORDER BY " . $order_clause . " LIMIT :limit OFFSET :offset";

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

// Функция для генерации URL с параметрами
function build_query_string($params_to_add = [])
{
    global $category_id, $search, $hashtag, $sort_by, $sort_order;

    $params = [];

    if (isset($params_to_add['category'])) {
        $params['category'] = $params_to_add['category'];
    } elseif ($category_id) {
        $params['category'] = $category_id;
    }

    if (isset($params_to_add['search'])) {
        $params['search'] = $params_to_add['search'];
    } elseif ($search) {
        $params['search'] = $search;
    }

    if (isset($params_to_add['hashtag'])) {
        $params['hashtag'] = $params_to_add['hashtag'];
    } elseif ($hashtag) {
        $params['hashtag'] = $hashtag;
    }

    if (isset($params_to_add['sort'])) {
        $params['sort'] = $params_to_add['sort'];
    } else {
        $params['sort'] = $sort_by;
    }

    if (isset($params_to_add['order'])) {
        $params['order'] = $params_to_add['order'];
    } else {
        $params['order'] = $sort_order;
    }

    if (isset($params_to_add['page'])) {
        $params['page'] = $params_to_add['page'];
    }

    return http_build_query($params);
}

// Функция для получения противоположного порядка
function toggle_order($current_order)
{
    return $current_order === 'asc' ? 'desc' : 'asc';
}
?>

<div class="row">
    <div class="col-md-3">
        <div class="card mb-3">
            <div class="card-header">
                <h5>Категории</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="/" class="list-group-item list-group-item-action <?php echo !$category_id && !$hashtag ? 'active' : ''; ?>">
                    Все записи
                </a>
                <?php foreach ($categories as $cat): ?>
                    <a href="/?category=<?php echo $cat['id']; ?>" class="list-group-item list-group-item-action <?php echo $category_id == $cat['id'] ? 'active' : ''; ?>">
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
                    <?php if ($category_id): ?>
                        <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                    <?php endif; ?>
                    <?php if ($hashtag): ?>
                        <input type="hidden" name="hashtag" value="<?php echo htmlspecialchars($hashtag); ?>">
                    <?php endif; ?>
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                    <input type="hidden" name="order" value="<?php echo htmlspecialchars($sort_order); ?>">
                    <input type="text" name="search" class="form-control" placeholder="Поиск..." value="<?php echo htmlspecialchars($search); ?>">
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
        
        <!-- Панель сортировки -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Сортировка:</strong>
                    </div>
                    <div class="btn-group" role="group">
                        <!-- Сортировка по дате -->
                        <a href="/?<?php echo build_query_string(['sort' => 'date', 'order' => $sort_by === 'date' ? toggle_order($sort_order) : 'desc']); ?>" 
                           class="btn btn-sm <?php echo $sort_by === 'date' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            По дате
                            <?php if ($sort_by === 'date'): ?>
                                    <?php if ($sort_order === 'desc'): ?>
                                            <i class="bi bi-arrow-down">↓</i>
                                    <?php else: ?>
                                            <i class="bi bi-arrow-up">↑</i>
                                    <?php endif; ?>
                            <?php endif; ?>
                        </a>
                        
                        <!-- Сортировка по заголовку -->
                        <a href="/?<?php echo build_query_string(['sort' => 'title', 'order' => $sort_by === 'title' ? toggle_order($sort_order) : 'asc']); ?>" 
                           class="btn btn-sm <?php echo $sort_by === 'title' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            По названию
                            <?php if ($sort_by === 'title'): ?>
                                    <?php if ($sort_order === 'asc'): ?>
                                            <i class="bi bi-arrow-up">↑</i>
                                    <?php else: ?>
                                            <i class="bi bi-arrow-down">↓</i>
                                    <?php endif; ?>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
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
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="/?<?php echo build_query_string(['page' => $i]); ?>">
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