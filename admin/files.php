<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(ROLE_AUTHOR);

$page_title = 'Управление файлами';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

$result = $db->query("SELECT f.*, u.username 
                      FROM files f 
                      JOIN users u ON f.user_id = u.id 
                      ORDER BY f.created_at DESC");

$files = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $files[] = $row;
}

$db->close();

function format_bytes($bytes)
{
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}

function is_image($filename)
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
}
?>

<div class="row">
    <div class="col-md-12">
        <h1>Управление файлами</h1>

        <div class="card mb-3">
            <div class="card-header">
                <h5>Загрузить новый файл</h5>
            </div>
            <div class="card-body">
                <input type="file" id="admin-file-upload" class="form-control">
                <div id="admin-file-upload-result" class="mt-2"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Превью</th>
                                <th>Оригинальное имя</th>
                                <th>Имя файла</th>
                                <th>Размер</th>
                                <th>Тип</th>
                                <th>Загрузил</th>
                                <th>Дата</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($files)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Файлов пока нет</td>
                                </tr>
                            <?php else: ?>
                                    <?php foreach ($files as $file): ?>
                                    <tr>
                                        <td>
                                                    <?php if (is_image($file['filename'])): ?>
                                                <img src="/uploads/<?php echo urlencode($file['filename']); ?>" alt="preview"
                                                    style="max-width: 50px; max-height: 50px;">
                                                    <?php else: ?>
                                                <span
                                                    class="badge bg-secondary"><?php echo strtoupper(pathinfo($file['filename'], PATHINFO_EXTENSION)); ?></span>
                                                    <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($file['original_name']); ?></td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars($file['filename']); ?></small>
                                        </td>
                                        <td><?php echo format_bytes($file['file_size']); ?></td>
                                        <td><small><?php echo htmlspecialchars($file['mime_type']); ?></small></td>
                                        <td><?php echo htmlspecialchars($file['username']); ?></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($file['created_at'])); ?></td>
                                        <td>
                                            <a href="/uploads/<?php echo urlencode($file['filename']); ?>"
                                                class="btn btn-sm btn-info" target="_blank"
                                                download="<?php echo htmlspecialchars($file['original_name']); ?>">Скачать</a>
                                            <button class="btn btn-sm btn-secondary copy-markdown"
                                                data-filename="<?php echo htmlspecialchars($file['filename']); ?>"
                                                data-original="<?php echo htmlspecialchars($file['original_name']); ?>"
                                                data-is-image="<?php echo is_image($file['filename']) ? '1' : '0'; ?>">
                                                Markdown
                                            </button>
                                            <a href="/admin/file_delete.php?id=<?php echo $file['id']; ?>"
                                                class="btn btn-sm btn-danger" onclick="return confirm('Удалить?')">Удалить</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>