<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/markdown.php';

require_role(ROLE_AUTHOR);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);

    if (empty($name)) {
        $error = 'Введите название';
    } else {
        $slug = slugify($name);

        $db = get_db();

        // Проверка уникальности
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM categories WHERE slug = :slug");
        $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);

        if ($row['count'] > 0) {
            $error = 'Категория с таким названием уже существует';
        } else {
            $stmt = $db->prepare("INSERT INTO categories (name, slug) VALUES (:name, :slug)");
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
            $stmt->execute();

            $db->close();
            header('Location: /admin/categories.php');
            exit;
        }

        $db->close();
    }
}

$page_title = 'Создание категории';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-md-6">
        <h1>Создание категории</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Название *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-success">Создать</button>
                    <a href="/admin/categories.php" class="btn btn-secondary">Отмена</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>