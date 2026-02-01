<?php
require_once 'config.php';
require_once 'includes/auth.php';

$success = '';
$error = '';

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Валидация
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Пожалуйста, заполните все поля';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email адрес';
    } elseif (strlen($message) < 10) {
        $error = 'Сообщение должно содержать минимум 10 символов';
    } else {
        // Сохранение в базу данных
        $db = get_db();
        
        $user_id = is_logged_in() ? $_SESSION['user_id'] : null;
        
        $stmt = $db->prepare("INSERT INTO feedback (user_id, name, email, subject, message, ip_address) 
                              VALUES (:user_id, :name, :email, :subject, :message, :ip_address)");
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':subject', $subject, SQLITE3_TEXT);
        $stmt->bindValue(':message', $message, SQLITE3_TEXT);
        $stmt->bindValue(':ip_address', $_SERVER['REMOTE_ADDR'], SQLITE3_TEXT);
        $stmt->execute();
        
        $db->close();
        
        $success = 'Спасибо за ваше сообщение! Мы свяжемся с вами в ближайшее время.';
        
        // Очистка полей после успешной отправки
        $name = '';
        $email = '';
        $subject = '';
        $message = '';
    }
}

// Предзаполнение для авторизованных пользователей
if (is_logged_in() && empty($_POST)) {
    $current_user = get_logged_user();
    $name = $current_user['username'];
    $email = ''; // Email можно добавить в профиль пользователя
}

$page_title = 'Обратная связь';
require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h1>Обратная связь</h1>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <p class="text-muted">
                    Есть вопросы, предложения или нашли ошибку? Напишите нам!
                </p>
                
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Ваше имя *</label>
                        <input type="text" 
                               name="name" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($name ?? ''); ?>"
                               <?php echo is_logged_in() ? 'readonly' : ''; ?>
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" 
                               name="email" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>"
                               placeholder="your@email.com"
                               required>
                        <small class="text-muted">Мы используем email только для ответа на ваше сообщение</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Тема *</label>
                        <select name="subject" class="form-control" required>
                            <option value="">Выберите тему...</option>
                            <option value="Вопрос" <?php echo (isset($subject) && $subject === 'Вопрос') ? 'selected' : ''; ?>>Вопрос</option>
                            <option value="Предложение" <?php echo (isset($subject) && $subject === 'Предложение') ? 'selected' : ''; ?>>Предложение</option>
                            <option value="Ошибка" <?php echo (isset($subject) && $subject === 'Ошибка') ? 'selected' : ''; ?>>Сообщить об ошибке</option>
                            <option value="Другое" <?php echo (isset($subject) && $subject === 'Другое') ? 'selected' : ''; ?>>Другое</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Сообщение *</label>
                        <textarea name="message" 
                                  class="form-control" 
                                  rows="8" 
                                  placeholder="Опишите ваш вопрос или предложение..."
                                  required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                        <small class="text-muted">Минимум 10 символов</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Отправить</button>
                    <a href="/" class="btn btn-secondary">Отмена</a>
                </form>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-body">
                <h5>Другие способы связи</h5>
                <p class="mb-1"><strong>Email:</strong> admin@example.com</p>
                <p class="mb-0"><strong>Время работы:</strong> Пн-Пт, 9:00-18:00</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>