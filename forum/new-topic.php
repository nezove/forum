<?php
$page_title = 'Создание новой темы';
require_once 'includes/header.php';

// Проверка авторизации
if (!Auth::check()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Проверка наличия ID категории
if (!isset($_GET['category_id']) || !is_numeric($_GET['category_id'])) {
    header('Location: index.php');
    exit;
}

$category_id = (int)$_GET['category_id'];

// Получение информации о категории
$category = get_category($category_id);

if (!$category) {
    header('Location: index.php');
    exit;
}

// Получение родительских категорий для хлебных крошек
$parents = [];
get_category_parents($category_id, $parents);

// Построение пути для хлебных крошек
$breadcrumbs = [
    ['title' => 'Главная', 'url' => 'index.php']
];

foreach ($parents as $parent) {
    $breadcrumbs[] = ['title' => $parent['name'], 'url' => 'category.php?id=' . $parent['id']];
}

$breadcrumbs[] = ['title' => 'Новая тема', 'url' => ''];

// Обработка отправки формы
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    
    // Валидация
    if (empty($title)) {
        $errors[] = 'Введите заголовок темы';
    } elseif (strlen($title) < 5) {
        $errors[] = 'Заголовок темы должен содержать не менее 5 символов';
    } elseif (strlen($title) > 255) {
        $errors[] = 'Заголовок темы не должен превышать 255 символов';
    }
    
    if (empty($content)) {
        $errors[] = 'Введите содержимое сообщения';
    } elseif (strlen($content) < 10) {
        $errors[] = 'Сообщение должно содержать не менее 10 символов';
    }
    
    // Если ошибок нет, создаем тему
    if (empty($errors)) {
        $result = create_topic($title, $content, $category_id);
        
        if ($result['success']) {
            // Перенаправление на созданную тему
            header('Location: topic.php?id=' . $result['topic_id']);
            exit;
        } else {
            $errors[] = $result['message'];
        }
    }
}
?>

<div class="new-topic-page">
    <?php echo generate_breadcrumbs($breadcrumbs); ?>
    
    <h1 class="page-title">Создание новой темы</h1>
    <div class="category-info">
        <p>Категория: <a href="category.php?id=<?php echo $category_id; ?>"><?php echo htmlspecialchars($category['name']); ?></a></p>
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
            <form method="post" action="new-topic.php?category_id=<?php echo $category_id; ?>">
                <div class="form-group">
                    <label for="title" class="form-label">Заголовок темы</label>
                    <input type="text" id="title" name="title" class="form-control" value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="content" class="form-label">Сообщение</label>
                    <textarea id="content" name="content" class="form-control" rows="10" required><?php echo isset($content) ? htmlspecialchars($content) : ''; ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Создать тему</button>
                    <a href="category.php?id=<?php echo $category_id; ?>" class="btn btn-outline">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>