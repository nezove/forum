<?php
$page_title = 'Административная панель';
require_once 'includes/header.php';

// Проверка прав администратора
if (!Auth::check() || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance();

// Статистика форума
$stats = [
    'topics' => $db->fetch("SELECT COUNT(*) as count FROM topics")['count'],
    'posts' => $db->fetch("SELECT COUNT(*) as count FROM posts")['count'],
    'users' => $db->fetch("SELECT COUNT(*) as count FROM users")['count'],
    'categories' => $db->fetch("SELECT COUNT(*) as count FROM categories")['count'],
];

// Последние зарегистрированные пользователи
$latest_users = $db->fetchAll(
    "SELECT * FROM users ORDER BY created_at DESC LIMIT 5"
);

// Последние темы
$latest_topics = $db->fetchAll(
    "SELECT t.*, u.username, c.name as category_name
     FROM topics t
     JOIN users u ON t.user_id = u.id
     JOIN categories c ON t.category_id = c.id
     ORDER BY t.created_at DESC LIMIT 5"
);

// Обработка добавления категории
$category_added = false;
$category_error = '';

if (isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] > 0 ? (int)$_POST['parent_id'] : null;
    
    if (empty($name)) {
        $category_error = 'Введите название категории';
    } else {
        // Проверка на существование категории с таким именем
        $existing = $db->fetch(
            "SELECT * FROM categories WHERE name = :name",
            ['name' => $name]
        );
        
        if ($existing) {
            $category_error = 'Категория с таким названием уже существует';
        } else {
            // Добавление категории
            $db->insert('categories', [
                'name' => $name,
                'description' => $description,
                'parent_id' => $parent_id,
                'sort_order' => 0
            ]);
            
            $category_added = true;
        }
    }
}

// Получение всех категорий для выбора родительской
$categories = $db->fetchAll(
    "SELECT * FROM categories ORDER BY parent_id IS NULL DESC, name"
);
?>

<div class="admin-page">
    <h1 class="page-title">Административная панель</h1>
    
    <div class="admin-tabs">
        <ul class="profile-tabs-list">
            <li class="profile-tab-item">
                <a href="#dashboard" class="profile-tab-link active" data-tab="dashboard">Дашборд</a>
            </li>
            <li class="profile-tab-item">
                <a href="#users" class="profile-tab-link" data-tab="users">Пользователи</a>
            </li>
            <li class="profile-tab-item">
                <a href="#categories" class="profile-tab-link" data-tab="categories">Категории</a>
            </li>
            <li class="profile-tab-item">
                <a href="#topics" class="profile-tab-link" data-tab="topics">Темы</a>
            </li>
        </ul>
    </div>
    
    <div class="admin-content">
        <!-- Дашборд -->
        <div class="admin-tab active" id="dashboard-tab">
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['users']; ?></div>
                        <div class="stat-label">Пользователей</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['categories']; ?></div>
                        <div class="stat-label">Категорий</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['topics']; ?></div>
                        <div class="stat-label">Тем</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['posts']; ?></div>
                        <div class="stat-label">Сообщений</div>
                    </div>
                </div>
            </div>
            
            <div class="admin-sections">
                <div class="admin-section">
                    <h2 class="section-title">Новые пользователи</h2>
                    <div class="users-list">
                        <?php foreach ($latest_users as $user): ?>
                        <div class="user-item">
                            <div class="user-avatar">
                                <img src="assets/uploads/avatars/<?php echo htmlspecialchars($user['avatar']); ?>" alt="<?php echo htmlspecialchars($user['username']); ?>">
                            </div>
                            <div class="user-info">
                                <div class="user-name">
                                    <a href="profile.php?id=<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></a>
                                </div>
                                <div class="user-meta">
                                    <span class="user-joined">Регистрация: <?php echo format_date($user['created_at'], 'relative'); ?></span>
                                </div>
                            </div>
                            <div class="user-actions">
                                <a href="admin/edit-user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline">Управление</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="section-footer">
                        <a href="admin/users.php" class="btn btn-outline">Все пользователи</a>
                    </div>
                </div>
                
                <div class="admin-section">
                    <h2 class="section-title">Последние темы</h2>
                    <div class="topics-list">
                        <?php foreach ($latest_topics as $topic): ?>
                        <div class="topic-item">
                            <div class="topic-info">
                                <div class="topic-title">
                                    <a href="topic.php?id=<?php echo $topic['id']; ?>"><?php echo htmlspecialchars($topic['title']); ?></a>
                                </div>
                                <div class="topic-meta">
                                    <span class="topic-author">Автор: <a href="profile.php?id=<?php echo $topic['user_id']; ?>"><?php echo htmlspecialchars($topic['username']); ?></a></span>
                                    <span class="topic-category">Категория: <a href="category.php?id=<?php echo $topic['category_id']; ?>"><?php echo htmlspecialchars($topic['category_name']); ?></a></span>
                                    <span class="topic-date">Создана: <?php echo format_date($topic['created_at'], 'relative'); ?></span>
                                </div>
                            </div>
                            <div class="topic-actions">
                                <a href="admin/edit-topic.php?id=<?php echo $topic['id']; ?>" class="btn btn-sm btn-outline">Управление</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="section-footer">
                        <a href="admin/topics.php" class="btn btn-outline">Все темы</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Пользователи -->
        <div class="admin-tab" id="users-tab">
            <h2 class="tab-title">Управление пользователями</h2>
            
            <div class="search-form mb-4">
                <form method="get" action="admin/users.php">
                    <div class="form-group d-flex">
                        <input type="text" name="search" class="form-control" placeholder="Поиск пользователей...">
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="actions-bar mb-4">
                <a href="admin/users.php" class="btn btn-primary">Список пользователей</a>
                <a href="admin/add-user.php" class="btn btn-outline">Добавить пользователя</a>
            </div>
            
            <div class="quick-stats">
                <div class="stat-item">
                    <div class="stat-label">Всего пользователей:</div>
                    <div class="stat-value"><?php echo $stats['users']; ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Администраторов:</div>
                    <div class="stat-value"><?php echo $db->fetch("SELECT COUNT(*) as count FROM users WHERE is_admin = 1")['count']; ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Новых за сегодня:</div>
                    <div class="stat-value"><?php echo $db->fetch("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()")['count']; ?></div>
                </div>
            </div>
        </div>
        
        <!-- Категории -->
        <div class="admin-tab" id="categories-tab">
            <h2 class="tab-title">Управление категориями</h2>
            
            <div class="actions-bar mb-4">
                <a href="admin/categories.php" class="btn btn-primary">Список категорий</a>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Добавить категорию</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($category_added): ?>
                            <div class="alert alert-success">
                                Категория успешно добавлена
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($category_error): ?>
                            <div class="alert alert-danger">
                                <?php echo $category_error; ?>
                            </div>
                            <?php endif; ?>
                            
                            <form method="post" action="admin.php">
                                <div class="form-group">
                                    <label for="name" class="form-label">Название</label>
                                    <input type="text" id="name" name="name" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="description" class="form-label">Описание</label>
                                    <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="parent_id" class="form-label">Родительская категория</label>
                                    <select id="parent_id" name="parent_id" class="form-control">
                                        <option value="0">-- Нет (корневая категория) --</option>
                                        <?php foreach ($categories as $category): ?>
                                        <?php if ($category['parent_id'] === null): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <button type="submit" name="add_category" class="btn btn-primary">Добавить категорию</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Существующие категории</h3>
                        </div>
                        <div class="card-body">
                            <ul class="categories-tree">
                                <?php foreach ($categories as $category): ?>
                                <?php if ($category['parent_id'] === null): ?>
                                <li class="category-item">
                                    <div class="category-info">
                                        <div class="category-name"><?php echo htmlspecialchars($category['name']); ?></div>
                                        <div class="category-actions">
                                            <a href="admin/edit-category.php?id=<?php echo $category['id']; ?>" class="btn btn-sm btn-outline">Редактировать</a>
                                            <a href="admin/delete-category.php?id=<?php echo $category['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Вы уверены? Это удалит все темы в категории!')">Удалить</a>
                                        </div>
                                    </div>
                                    
                                    <ul class="subcategories-list">
                                        <?php foreach ($categories as $subcategory): ?>
                                        <?php if ($subcategory['parent_id'] == $category['id']): ?>
                                        <li class="subcategory-item">
                                            <div class="category-info">
                                                <div class="category-name"><?php echo htmlspecialchars($subcategory['name']); ?></div>
                                                <div class="category-actions">
                                                    <a href="admin/edit-category.php?id=<?php echo $subcategory['id']; ?>" class="btn btn-sm btn-outline">Редактировать</a>
                                                    <a href="admin/delete-category.php?id=<?php echo $subcategory['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Вы уверены? Это удалит все темы в категории!')">Удалить</a>
                                                </div>
                                            </div>
                                        </li>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                </li>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Темы -->
        <div class="admin-tab" id="topics-tab">
            <h2 class="tab-title">Управление темами</h2>
            
            <div class="search-form mb-4">
                <form method="get" action="admin/topics.php">
                    <div class="form-group d-flex">
                        <input type="text" name="search" class="form-control" placeholder="Поиск тем...">
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="actions-bar mb-4">
                <a href="admin/topics.php" class="btn btn-primary">Список тем</a>
                <a href="admin/reported-topics.php" class="btn btn-outline">Жалобы</a>
            </div>
            
            <div class="quick-stats">
                <div class="stat-item">
                    <div class="stat-label">Всего тем:</div>
                    <div class="stat-value"><?php echo $stats['topics']; ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Закрепленных тем:</div>
                    <div class="stat-value"><?php echo $db->fetch("SELECT COUNT(*) as count FROM topics WHERE is_sticky = 1")['count']; ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Закрытых тем:</div>
                    <div class="stat-value"><?php echo $db->fetch("SELECT COUNT(*) as count FROM topics WHERE is_locked = 1")['count']; ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Переключение вкладок
    const tabLinks = document.querySelectorAll('.profile-tab-link');
    const tabContents = document.querySelectorAll('.admin-tab');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Удаляем активный класс со всех вкладок
            tabLinks.forEach(tab => tab.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Добавляем активный класс к выбранной вкладке
            this.classList.add('active');
            const tabId = this.getAttribute('data-tab') + '-tab';
            document.getElementById(tabId).classList.add('active');
        });
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>