<?php
$page_title = 'Перемещение темы';
require_once '../includes/header.php';

// Проверка прав администратора
if (!Auth::check() || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../index.php');
    exit;
}

// Проверка наличия ID темы
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../admin.php');
    exit;
}

$topic_id = (int)$_GET['id'];

// Получение информации о теме
$db = Database::getInstance();
$topic = $db->fetch(
    "SELECT t.*, c.name as category_name
     FROM topics t
     JOIN categories c ON t.category_id = c.id
     WHERE t.id = :id",
    ['id' => $topic_id]
);

if (!$topic) {
    header('Location: ../admin.php');
    exit;
}

// Получение всех категорий
$categories = $db->fetchAll(
    "SELECT * FROM categories ORDER BY parent_id IS NULL DESC, name"
);

// Обработка формы перемещения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['move_topic'])) {
    $new_category_id = (int)$_POST['category_id'];
    
    // Проверка существования категории
    $category = $db->fetch(
        "SELECT * FROM categories WHERE id = :id",
        ['id' => $new_category_id]
    );
    
    if ($category) {
        // Перемещение темы
        $db->update(
            'topics',
            ['category_id' => $new_category_id],
            'id = :id',
            ['id' => $topic_id]
        );
        
        // Перенаправление обратно к теме
        header('Location: ../topic.php?id=' . $topic_id);
        exit;
    }
}
?>

<div class="move-topic-page">
    <h1 class="page-title">Перемещение темы</h1>
    
    <div class="topic-info mb-4">
        <div class="topic-title">
            <strong>Тема:</strong> <a href="../topic.php?id=<?php echo $topic_id; ?>"><?php echo htmlspecialchars($topic['title']); ?></a>
        </div>
        <div class="current-category">
            <strong>Текущая категория:</strong> <?php echo htmlspecialchars($topic['category_name']); ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Выберите новую категорию</h2>
        </div>
        <div class="card-body">
            <form method="post" action="move-topic.php?id=<?php echo $topic_id; ?>">
                <div class="form-group">
                    <label for="category_id" class="form-label">Категория</label>
                    <select id="category_id" name="category_id" class="form-control" required>
                        <option value="">-- Выберите категорию --</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $topic['category_id'] ? 'selected' : ''; ?>>
                            <?php echo $category['parent_id'] !== null ? '-- ' : ''; ?><?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="move_topic" class="btn btn-primary">Переместить тему</button>
                    <a href="../topic.php?id=<?php echo $topic_id; ?>" class="btn btn-outline">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>