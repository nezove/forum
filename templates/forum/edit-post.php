<?php
$page_title = 'Редактирование сообщения';
require_once 'includes/header.php';

// Проверка авторизации
if (!Auth::check()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Проверка наличия ID сообщения
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$post_id = (int)$_GET['id'];

// Получение информации о сообщении
$db = Database::getInstance();
$post = $db->fetch(
    "SELECT p.*, t.title as topic_title, t.id as topic_id, t.is_locked, t.user_id as topic_author_id 
     FROM posts p 
     JOIN topics t ON p.topic_id = t.id 
     WHERE p.id = :id",
    ['id' => $post_id]
);

if (!$post) {
    header('Location: index.php');
    exit;
}

// Проверка прав на редактирование (автор сообщения или администратор)
$is_admin = Auth::check() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
$is_author = $post['user_id'] == $_SESSION['user_id'];

if (!$is_admin && !$is_author) {
    header('Location: topic.php?id=' . $post['topic_id']);
    exit;
}

// Проверка, не заблокирована ли тема
if ($post['is_locked'] && !$is_admin) {
    header('Location: topic.php?id=' . $post['topic_id']);
    exit;
}

// Обработка отправки формы
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content']);
    
    // Валидация
    if (empty($content)) {
        $errors[] = 'Введите содержимое сообщения';
    } elseif (strlen($content) < 10) {
        $errors[] = 'Сообщение должно содержать не менее 10 символов';
    }
    
    // Если ошибок нет, обновляем сообщение
    if (empty($errors)) {
        $db->update(
            'posts',
            [
                'content' => $content,
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'id = :id',
            ['id' => $post_id]
        );
        
        // Перенаправление на тему
        header('Location: topic.php?id=' . $post['topic_id'] . '&highlight=' . $post_id . '#post-' . $post_id);
        exit;
    }
}
?>

<div class="edit-post-page">
    <h1 class="page-title">Редактирование сообщения</h1>
    <div class="topic-info">
        <p>Тема: <a href="topic.php?id=<?php echo $post['topic_id']; ?>"><?php echo htmlspecialchars($post['topic_title']); ?></a></p>
    </div>
    
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
            <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="post" action="edit-post.php?id=<?php echo $post_id; ?>">
                <div class="form-group">
                    <label for="content" class="form-label">Сообщение</label>
                    <textarea id="content" name="content" class="form-control" rows="10" required><?php echo isset($content) ? htmlspecialchars($content) : htmlspecialchars($post['content']); ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    <a href="topic.php?id=<?php echo $post['topic_id']; ?>#post-<?php echo $post_id; ?>" class="btn btn-outline">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>