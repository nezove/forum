<?php
$page_title = 'Главная';
require_once 'includes/header.php';

// Получение категорий верхнего уровня
$categories = get_categories();
?>

<div class="page-header">
    <h1 class="page-title">Форум</h1>
    <p class="page-description">Добро пожаловать на наш форум! Здесь вы можете обсуждать различные темы, делиться опытом
        и находить ответы на свои вопросы.</p>
</div>

<div class="categories-list">
    <?php foreach ($categories as $category): ?>
    <div class="category-card">
        <div class="category-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
        </div>
        <div class="category-info">
            <h2 class="category-name">
                <a
                    href="category.php?id=<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></a>
            </h2>
            <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>

            <?php
            // Получение подкатегорий
            $subcategories = get_categories($category['id']);
            if (!empty($subcategories)):
            ?>
            <div class="subcategories">
                <span>Подкатегории:</span>
                <?php foreach ($subcategories as $index => $subcategory): ?>
                <a href="category.php?id=<?php echo $subcategory['id']; ?>"
                    class="subcategory-link"><?php echo htmlspecialchars($subcategory['name']); ?></a>
                <?php if ($index < count($subcategories) - 1): ?> • <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="category-stats">
            <?php
            // Подсчет тем и сообщений в категории
            $db = Database::getInstance();
            $topics_count = $db->fetch(
                "SELECT COUNT(*) as count FROM topics WHERE category_id = :category_id OR category_id IN (SELECT id FROM categories WHERE parent_id = :parent_id)",
                ['category_id' => $category['id'], 'parent_id' => $category['id']]
            );
            
            $posts_count = $db->fetch(
                "SELECT COUNT(*) as count FROM posts WHERE topic_id IN (SELECT id FROM topics WHERE category_id = :category_id OR category_id IN (SELECT id FROM categories WHERE parent_id = :parent_id))",
                ['category_id' => $category['id'], 'parent_id' => $category['id']]
            );
            ?>
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z">
                    </path>
                </svg>
                <?php echo $topics_count['count']; ?> тем
            </div>
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <?php echo $posts_count['count']; ?> сообщений
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="latest-activity mt-8">
    <h2>Последние темы</h2>

    <div class="topics-list">
        <?php
        // Получение последних тем
        $db = Database::getInstance();
        $latest_topics = $db->fetchAll(
            "SELECT t.*, u.username, u.avatar, c.name as category_name,
             (SELECT COUNT(*) FROM posts WHERE topic_id = t.id) - 1 as replies
             FROM topics t
             JOIN users u ON t.user_id = u.id
             JOIN categories c ON t.category_id = c.id
             ORDER BY t.created_at DESC
             LIMIT 5"
        );
        
        foreach ($latest_topics as $topic):
        ?>
        <div class="topic-card">
            <div class="topic-info">
                <div class="topic-avatar">
                    <img src="assets/uploads/avatars/<?php echo htmlspecialchars($topic['avatar']); ?>"
                        alt="<?php echo htmlspecialchars($topic['username']); ?>">
                </div>
                <div class="topic-details">
                    <h3 class="topic-title">
                        <a
                            href="topic.php?id=<?php echo $topic['id']; ?>"><?php echo htmlspecialchars($topic['title']); ?></a>
                    </h3>
                    <div class="topic-meta">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <a
                                href="profile.php?id=<?php echo $topic['user_id']; ?>"><?php echo htmlspecialchars($topic['username']); ?></a>
                        </span>
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <?php echo format_date($topic['created_at'], 'relative'); ?>
                        </span>
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <polygon
                                    points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2">
                                </polygon>
                            </svg>
                            <a
                                href="category.php?id=<?php echo $topic['category_id']; ?>"><?php echo htmlspecialchars($topic['category_name']); ?></a>
                        </span>
                    </div>
                </div>
            </div>
            <div class="topic-stats">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <?php echo $topic['views']; ?>
                </div>
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <?php echo $topic['replies']; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="text-center mt-4">
        <a href="all-topics.php" class="btn btn-outline">Просмотреть все темы</a>
    </div>
</div>

<?php
// Отображение статистики форума
$db = Database::getInstance();
$stats = [
    'topics' => $db->fetch("SELECT COUNT(*) as count FROM topics")['count'],
    'posts' => $db->fetch("SELECT COUNT(*) as count FROM posts")['count'],
    'users' => $db->fetch("SELECT COUNT(*) as count FROM users")['count'],
    'newest_user' => $db->fetch("SELECT username, id FROM users ORDER BY created_at DESC LIMIT 1")
];
?>

<div class="forum-stats mt-8">
    <h2>Статистика форума</h2>

    <div class="stats-container">
        <div class="stat-item">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z">
                    </path>
                </svg>
            </div>
            <div class="stat-value"><?php echo $stats['topics']; ?></div>
            <div class="stat-label">Тем</div>
        </div>

        <div class="stat-item">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
            </div>
            <div class="stat-value"><?php echo $stats['posts']; ?></div>
            <div class="stat-label">Сообщений</div>
        </div>

        <div class="stat-item">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div class="stat-value"><?php echo $stats['users']; ?></div>
            <div class="stat-label">Пользователей</div>
        </div>

        <div class="stat-item">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <div class="stat-value">
                <a href="profile.php?id=<?php echo $stats['newest_user']['id']; ?>">
                    <?php echo htmlspecialchars($stats['newest_user']['username']); ?>
                </a>
            </div>
            <div class="stat-label">Новый пользователь</div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>