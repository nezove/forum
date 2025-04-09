<?php
$page_title = 'Все темы';
require_once 'includes/header.php';

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;

// Получение тем с пагинацией
$db = Database::getInstance();
$offset = ($page - 1) * $per_page;

$topics = $db->fetchAll(
    "SELECT t.*, u.username, u.avatar, c.name as category_name, c.id as category_id,
     (SELECT COUNT(*) FROM posts WHERE topic_id = t.id) - 1 as replies,
     (SELECT MAX(p.created_at) FROM posts p WHERE p.topic_id = t.id) as last_post_date,
     (SELECT u2.username FROM posts p2 
      JOIN users u2 ON p2.user_id = u2.id 
      WHERE p2.topic_id = t.id 
      ORDER BY p2.created_at DESC LIMIT 1) as last_poster
     FROM topics t
     JOIN users u ON t.user_id = u.id
     JOIN categories c ON t.category_id = c.id
     ORDER BY t.is_sticky DESC, t.last_reply_at DESC
     LIMIT :limit OFFSET :offset",
    [
        'limit' => $per_page,
        'offset' => $offset
    ]
);

$total = $db->fetch(
    "SELECT COUNT(*) as count FROM topics"
)['count'];

$total_pages = ceil($total / $per_page);

// Фильтрация по категории
$categories = $db->fetchAll("SELECT id, name FROM categories ORDER BY name");
$selected_category = isset($_GET['category']) && is_numeric($_GET['category']) ? (int)$_GET['category'] : 0;

if ($selected_category > 0) {
    $topics = $db->fetchAll(
        "SELECT t.*, u.username, u.avatar, c.name as category_name, c.id as category_id,
         (SELECT COUNT(*) FROM posts WHERE topic_id = t.id) - 1 as replies,
         (SELECT MAX(p.created_at) FROM posts p WHERE p.topic_id = t.id) as last_post_date,
         (SELECT u2.username FROM posts p2 
          JOIN users u2 ON p2.user_id = u2.id 
          WHERE p2.topic_id = t.id 
          ORDER BY p2.created_at DESC LIMIT 1) as last_poster
         FROM topics t
         JOIN users u ON t.user_id = u.id
         JOIN categories c ON t.category_id = c.id
         WHERE t.category_id = :category_id OR t.category_id IN (SELECT id FROM categories WHERE parent_id = :parent_id)
         ORDER BY t.is_sticky DESC, t.last_reply_at DESC
         LIMIT :limit OFFSET :offset",
        [
            'category_id' => $selected_category,
            'parent_id' => $selected_category,
            'limit' => $per_page,
            'offset' => $offset
        ]
    );

    $total = $db->fetch(
        "SELECT COUNT(*) as count FROM topics
         WHERE category_id = :category_id OR category_id IN (SELECT id FROM categories WHERE parent_id = :parent_id)",
        [
            'category_id' => $selected_category,
            'parent_id' => $selected_category
        ]
    )['count'];

    $total_pages = ceil($total / $per_page);
}

// Поиск
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search_query)) {
    $search_term = '%' . $search_query . '%';
    
    $topics = $db->fetchAll(
        "SELECT t.*, u.username, u.avatar, c.name as category_name, c.id as category_id,
         (SELECT COUNT(*) FROM posts WHERE topic_id = t.id) - 1 as replies,
         (SELECT MAX(p.created_at) FROM posts p WHERE p.topic_id = t.id) as last_post_date,
         (SELECT u2.username FROM posts p2 
          JOIN users u2 ON p2.user_id = u2.id 
          WHERE p2.topic_id = t.id 
          ORDER BY p2.created_at DESC LIMIT 1) as last_poster
         FROM topics t
         JOIN users u ON t.user_id = u.id
         JOIN categories c ON t.category_id = c.id
         WHERE t.title LIKE :search_term
         OR t.id IN (SELECT topic_id FROM posts WHERE content LIKE :search_term)
         ORDER BY t.is_sticky DESC, t.last_reply_at DESC
         LIMIT :limit OFFSET :offset",
        [
            'search_term' => $search_term,
            'limit' => $per_page,
            'offset' => $offset
        ]
    );

    $total = $db->fetch(
        "SELECT COUNT(*) as count FROM topics
         WHERE title LIKE :search_term
         OR id IN (SELECT topic_id FROM posts WHERE content LIKE :search_term)",
        ['search_term' => $search_term]
    )['count'];

    $total_pages = ceil($total / $per_page);
}
?>

<div class="all-topics-page">
    <h1 class="page-title">Все темы</h1>
    
    <div class="filters-bar">
        <div class="search-form">
            <form method="get" action="all-topics.php">
                <div class="form-group d-flex">
                    <input type="text" name="search" class="form-control" placeholder="Поиск..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </button>
                </div>
            </form>
        </div>
        
        <div class="category-filter">
            <form method="get" action="all-topics.php">
                <div class="form-group d-flex">
                    <select name="category" class="form-control" onchange="this.form.submit()">
                        <option value="0">Все категории</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $selected_category == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if (!empty($search_query)): ?>
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <?php if (!empty($search_query)): ?>
    <div class="search-results-info">
        <p>Результаты поиска по запросу: <strong><?php echo htmlspecialchars($search_query); ?></strong> (найдено: <?php echo $total; ?>)</p>
        <?php if ($total > 0): ?>
        <a href="all-topics.php" class="btn btn-sm btn-outline">Сбросить поиск</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php if (empty($topics)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        </div>
        <div class="empty-state-text">
            <h3>Нет тем</h3>
            <p>Не найдено ни одной темы по заданным критериям.</p>
        </div>
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
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            <a href="category.php?id=<?php echo $topic['category_id']; ?>"><?php echo htmlspecialchars($topic['category_name']); ?></a>
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
    $pagination_url = "all-topics.php?";
    
    if ($selected_category > 0) {
        $pagination_url .= "category={$selected_category}&";
    }
    
    if (!empty($search_query)) {
        $pagination_url .= "search=" . urlencode($search_query) . "&";
    }
    
    $pagination_url .= "page={page}";
    
    echo generate_pagination($page, $total_pages, $pagination_url);
    ?>
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
?>