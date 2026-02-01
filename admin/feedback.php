<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(ROLE_AUTHOR);

// Обработка изменения статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $feedback_id = (int) $_POST['feedback_id'];
    $status = $_POST['status'];

    $allowed_statuses = ['new', 'in_progress', 'resolved', 'closed'];
    if (in_array($status, $allowed_statuses)) {
        $db = get_db();
        $stmt = $db->prepare("UPDATE feedback SET status = :status WHERE id = :id");
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->bindValue(':id', $feedback_id, SQLITE3_INTEGER);
        $stmt->execute();
        $db->close();

        header('Location: /admin/feedback.php');
        exit;
    }
}

$page_title = 'Управление обратной связью';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

// Фильтр по статусу
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

$query = "SELECT f.*, u.username 
          FROM feedback f 
          LEFT JOIN users u ON f.user_id = u.id";

if ($status_filter !== 'all') {
    $query .= " WHERE f.status = :status";
}

$query .= " ORDER BY f.created_at DESC";

$stmt = $db->prepare($query);
if ($status_filter !== 'all') {
    $stmt->bindValue(':status', $status_filter, SQLITE3_TEXT);
}
$result = $stmt->execute();

$feedbacks = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $feedbacks[] = $row;
}

// Подсчет по статусам
$stats = [
    'new' => 0,
    'in_progress' => 0,
    'resolved' => 0,
    'closed' => 0,
    'total' => 0
];

$stats_result = $db->query("SELECT status, COUNT(*) as count FROM feedback GROUP BY status");
while ($row = $stats_result->fetchArray(SQLITE3_ASSOC)) {
    $stats[$row['status']] = $row['count'];
    $stats['total'] += $row['count'];
}

$db->close();

function get_status_badge($status)
{
    $badges = [
        'new' => '<span class="badge bg-primary">Новое</span>',
        'in_progress' => '<span class="badge bg-warning">В работе</span>',
        'resolved' => '<span class="badge bg-success">Решено</span>',
        'closed' => '<span class="badge bg-secondary">Закрыто</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>';
}
?>

<div class="row">
    <div class="col-md-12">
        <h1>Управление обратной связью</h1>

        <!-- Статистика -->
        <div class="row mb-3">
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p class="mb-0">Всего</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body">
                        <h3><?php echo $stats['new']; ?></h3>
                        <p class="mb-0">Новых</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body">
                        <h3><?php echo $stats['in_progress']; ?></h3>
                        <p class="mb-0">В работе</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body">
                        <h3><?php echo $stats['resolved']; ?></h3>
                        <p class="mb-0">Решено</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body">
                        <h3><?php echo $stats['closed']; ?></h3>
                        <p class="mb-0">Закрыто</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Фильтр -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="btn-group" role="group">
                    <a href="/admin/feedback.php?status=all"
                        class="btn btn-sm <?php echo $status_filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">Все</a>
                    <a href="/admin/feedback.php?status=new"
                        class="btn btn-sm <?php echo $status_filter === 'new' ? 'btn-primary' : 'btn-outline-primary'; ?>">Новые</a>
                    <a href="/admin/feedback.php?status=in_progress"
                        class="btn btn-sm <?php echo $status_filter === 'in_progress' ? 'btn-primary' : 'btn-outline-primary'; ?>">В
                        работе</a>
                    <a href="/admin/feedback.php?status=resolved"
                        class="btn btn-sm <?php echo $status_filter === 'resolved' ? 'btn-primary' : 'btn-outline-primary'; ?>">Решено</a>
                    <a href="/admin/feedback.php?status=closed"
                        class="btn btn-sm <?php echo $status_filter === 'closed' ? 'btn-primary' : 'btn-outline-primary'; ?>">Закрыто</a>
                </div>
            </div>
        </div>

        <!-- Список сообщений -->
        <?php if (empty($feedbacks)): ?>
            <div class="alert alert-info">
                Сообщений с таким статусом нет.
            </div>
        <?php else: ?>
                <?php foreach ($feedbacks as $feedback): ?>
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?php echo htmlspecialchars($feedback['name']); ?></strong>
                                    <?php if ($feedback['username']): ?>
                                <span class="text-muted">(<?php echo htmlspecialchars($feedback['username']); ?>)</span>
                                    <?php endif; ?>
                            <span class="ms-2"><?php echo get_status_badge($feedback['status']); ?></span>
                        </div>
                        <small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($feedback['created_at'])); ?></small>
                    </div>
                    <div class="card-body">
                        <p class="mb-1">
                            <strong>Email:</strong> <a
                                href="mailto:<?php echo htmlspecialchars($feedback['email']); ?>"><?php echo htmlspecialchars($feedback['email']); ?></a>
                        </p>
                        <p class="mb-1"><strong>Тема:</strong> <?php echo htmlspecialchars($feedback['subject']); ?></p>
                        <p class="mb-1"><strong>IP:</strong> <?php echo htmlspecialchars($feedback['ip_address']); ?></p>
                        <hr>
                        <p class="mb-3"><?php echo nl2br(htmlspecialchars($feedback['message'])); ?></p>

                        <form method="post" class="d-inline">
                            <input type="hidden" name="feedback_id" value="<?php echo $feedback['id']; ?>">
                            <select name="status" class="form-select form-select-sm d-inline-block w-auto">
                                <option value="new" <?php echo $feedback['status'] === 'new' ? 'selected' : ''; ?>>Новое</option>
                                <option value="in_progress" <?php echo $feedback['status'] === 'in_progress' ? 'selected' : ''; ?>>В работе</option>
                                <option value="resolved" <?php echo $feedback['status'] === 'resolved' ? 'selected' : ''; ?>>
                                    Решено</option>
                                <option value="closed" <?php echo $feedback['status'] === 'closed' ? 'selected' : ''; ?>>Закрыто
                                </option>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-sm btn-primary">Обновить статус</button>
                        </form>

                        <a href="/admin/feedback_delete.php?id=<?php echo $feedback['id']; ?>"
                            class="btn btn-sm btn-danger float-end" onclick="return confirm('Удалить сообщение?')">Удалить</a>
                    </div>
                </div>
                <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>