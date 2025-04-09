<?php
// Начало category.php

// Подключение необходимых файлов
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Проверка наличия ID категории
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$category_id = (int)$_GET['id'];
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

// Получение информации о категории
$category = get_category($category_id);


if (!$category) {
    header('Location: index.php');
    exit;
}

$page_title = $category['name'];
require_once 'includes/header.php';

// Получение родительских категорий для хлебных крошек
$parents = [];
get_category_parents($category_id, $parents);

// Построение пути для хлебных крошек
$breadcrumbs = [
    ['title' => 'Главная', 'url' => 'index.php']
];

foreach ($parents as $index => $parent) {
    if ($index == count($parents) - 1) {
        $breadcrumbs[] = ['title' => $parent['name'], 'url' => ''];
    } else {
        $breadcrumbs[] = ['title' => $parent['name'], 'url' => 'category.php?id=' . $parent['id']];
    }
}

// Получение подкатегорий
$subcategories = get_categories($category_id);

// Получение тем
$topics_data = get_topics($category_id, $page);
$topics = $topics_data['topics'];
?>

<div class="category-page">
    <?php echo generate_breadcrumbs($breadcrumbs); ?>
    
    <div class="category-header">
        <div class="category-title-container">
            <h1 class="category-title"><?php echo htmlspecialchars($category['name']); ?></h1>
            <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
        </div>
        
        <?php if (Auth::check()): ?>
        <div class="category-actions">
            <a href="new-topic.php?category_id=<?php echo $category_id; ?>" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Создать тему
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($subcategories)): ?>
    <div class="subcategories-container mb-6">
        <h2 class="section-title">Подкатегории</h2>
        
        <div class="categories-list">
            <?php foreach ($subcategories as $subcategory): ?>
            <div class="category-card">
                <div class="category-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                </div>
                <div class="category-info">
                    <h2 class="category-name">
                        <a href="category.php?id=<?php echo $subcategory['id']; ?>"><?php echo htmlspecialchars($subcategory['name']); ?></a>
                    </h2>
                    <p class="category-description"><?php echo htmlspecialchars($subcategory['description']); ?></p>
                </div>
                <div class="category-stats">
                    <?php
                    // Подсчет тем и сообщений в подкатегории
                    $db = Database::getInstance();
                    $sub_topics_count = $db->fetch(
                        "SELECT COUNT(*) as count FROM topics WHERE category_id = :category_id",
                        ['category_id' => $subcategory['id']]
                    );
                    
                    $sub_posts_count = $db->fetch(
                        "SELECT COUNT(*) as count FROM posts WHERE topic_id IN (SELECT id FROM topics WHERE category_id = :category_id)",
                        ['category_id' => $subcategory['id']]
                    );
                    ?>
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                        <?php echo $sub_topics_count['count']; ?> тем
                    </div>
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                        <?php echo $sub_posts_count['count']; ?> сообщений
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="topics-container">
        <h2 class="section-title">Темы</h2>
        
        <?php if (empty($topics)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div>
            <div class="empty-state-text">
                <h3>Нет тем</h3>
                <p>В этой категории пока нет тем. Будьте первым, кто создаст новую тему!</p>
            </div>
            <?php if (Auth::check()): ?>
            <a href="new-topic.php?category_id=<?php echo $category_id; ?>" class="btn btn-primary">Создать тему</a>
            <?php else: ?>
            <a href="login.php" class="btn btn-primary">Войти для создания темы</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="topics-list">
            <?php foreach ($topics as $topic): ?>
            <div class="topic-card <?php echo $topic['is_sticky'] ? 'sticky-topic' : ''; ?>">
                <div class="topic-info">
                    <div class="topic-avatar">
                        <img src="assets/uploads/avatars/<?php echo htmlspecialchars($topic['avatar']); ?>" alt="<?php echo htmlspecialchars($topic['username']); ?>">
                    </div>
                    <div class="topic-details">
                        <h3 class="topic-title">
                            <?php if ($topic['is_sticky']): ?>
                            <span class="topic-badge sticky-badge">Важно</span>
                            <?php endif; ?>
                            <?php if ($topic['is_locked']): ?>
                            <span class="topic-badge locked-badge">Закрыто</span>
                            <?php endif; ?>
                            <a href="topic.php?id=<?php echo $topic['id']; ?>"><?php echo htmlspecialchars($topic['title']); ?></a>
                        </h3>
                        <div class="topic-meta">
                            <span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                <a href="profile.php?id=<?php echo $topic['user_id']; ?>"><?php echo htmlspecialchars($topic['username']); ?></a>
                            </span>
                            <span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                <?php echo format_date($topic['created_at'], 'relative'); ?>
                            </span>
                            
                            <?php if ($topic['last_post_date']): ?>
                            <span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 10 20 15 15 20"></polyline><path d="M4 4v7a4 4 0 0 0 4 4h12"></path></svg>
                                <span><?php echo format_date($topic['last_post_date'], 'relative'); ?> от <?php echo htmlspecialchars($topic['last_poster']); ?></span>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="topic-stats">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <?php echo $topic['views']; ?>
                    </div>
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                        <?php echo $topic['replies']; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php
        // Формирование URL для пагинации
        $pagination_url = "category.php?id={$category_id}&page={page}";
        echo generate_pagination($topics_data['current_page'], $topics_data['pages'], $pagination_url);
        ?>
        <?php endif; ?>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>