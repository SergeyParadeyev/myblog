<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/markdown.php';

require_role(ROLE_AUTHOR);

if (!isset($_GET['id'])) {
    header('Location: /admin/categories.php');
    exit;
}

$category_id = (int) $_GET['id'];
$db = get_db();

$stmt = $db->prepare("SELECT * FROM categories WHERE id = :id");
$stmt->bindValue(':id', $category_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$category = $result->fetchArray(SQLITE3_ASSOC);

if (!$category) {
    die('Категория не найдена');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);

    if (empty($name)) {
        $error = 'Введите название';
    } else {
        $slug = slugify($name);

        $stmt = $db->prepare("UPDATE categories SET name = :name, slug = :slug WHERE id = :id");
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
        $stmt->bindValue(':id', $category_id, SQLITE3_INTEGER);
        $stmt->execute();

        $db->close();
        header('Location: /admin/categories.php');
        exit;
    }
}

$db->close();

$page_title = 'Редактирование категории';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-md-6">
        <h1>Редактирование категории</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Название *</label>
                        <input type="text" name="name" class="form-control"
                            value="<?php echo htmlspecialchars($category['name']); ?>" required>
                    </div>

                    <button type="submit" class="btn btn-success">Сохранить</button>
                    <a href="/admin/categories.php" class="btn btn-secondary">Отмена</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>