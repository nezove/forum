<?php
require_once 'includes/header.php';
// Проверка наличия ID темы
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$topic_id = (int)$_GET['id'];
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$highlight_post = isset($_GET['highlight']) && is_numeric($_GET['highlight']) ? (int)$_GET['highlight'] : null;

// Получение информации о теме
$topic = get_topic($topic_id);

if (!$topic) {
    header('Location: index.php');
    exit;
}

$page_title = $topic['title'];
require_once 'includes/header.php';

// Получение категории и родительских категорий для хлебных крошек
$category = get_category($topic['category_id']);
$parents = [];
get_category_parents($topic['category_id'], $parents);

// Построение пути для хлебных крошек
$breadcrumbs = [
    ['title' => 'Главная', 'url' => 'index.php']
];

foreach ($parents as $parent) {
    $breadcrumbs[] = ['title' => $parent['name'], 'url' => 'category.php?id=' . $parent['id']];
}

$breadcrumbs[] = ['title' => $topic['title'], 'url' => ''];

// Проверка, является ли тема закладкой для текущего пользователя
$is_bookmarked = false;
if (Auth::check()) {
    $db = Database::getInstance();
    $bookmark = $db->fetch(
        "SELECT * FROM bookmarks WHERE topic_id = :topic_id AND user_id = :user_id",
        ['topic_id' => $topic_id, 'user_id' => $_SESSION['user_id']]
    );
    $is_bookmarked = (bool)$bookmark;
}

// Получение сообщений
$posts_data = get_posts($topic_id, $page);
$posts = $posts_data['posts'];
?>

<div class="topic-page">
    <?php echo generate_breadcrumbs($breadcrumbs); ?>
    
    <div class="topic-header">
        <div class="topic-title-container">
            <h1 class="topic-title">
                <?php if ($topic['is_sticky']): ?>
                <span class="topic-badge sticky-badge">Важно</span>
                <?php endif; ?>
                <?php if ($topic['is_locked']): ?>
                <span class="topic-badge locked-badge">Закрыто</span>
                <?php endif; ?>
                <?php echo htmlspecialchars($topic['title']); ?>
            </h1>
            <div class="topic-meta">
                <span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <a href="profile.php?id=<?php echo $topic['user_id']; ?>"><?php echo htmlspecialchars($topic['username']); ?></a>
                </span>
                <span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <?php echo format_date($topic['created_at'], 'full'); ?>
                </span>
                <span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    <a href="category.php?id=<?php echo $topic['category_id']; ?>"><?php echo htmlspecialchars($topic['category_name']); ?></a>
                </span>
                <span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    <?php echo $topic['views']; ?> просмотров
                </span>
                <span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    <?php echo $posts_data['total'] - 1; ?> ответов
                </span>
            </div>
        </div>
        
        <div class="topic-actions">
            <?php if (Auth::check()): ?>
            <button class="btn btn-outline bookmark-button <?php echo $is_bookmarked ? 'bookmarked' : ''; ?>" data-topic-id="<?php echo $topic_id; ?>" title="<?php echo $is_bookmarked ? 'Удалить из закладок' : 'Добавить в закладки'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <?php if ($is_bookmarked): ?>
                    <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" fill="currentColor"></path>
                    <?php else: ?>
                    <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                    <?php endif; ?>
                </svg>
            </button>
            
            <?php if ($current_user['is_admin']): ?>
            <div class="dropdown">
                <button class="btn btn-outline dropdown-toggle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                    Управление
                </button>
                <div class="dropdown-menu">
                    <?php if ($topic['is_sticky']): ?>
                    <a href="admin/unsticky-topic.php?id=<?php echo $topic_id; ?>" class="dropdown-item">Открепить тему</a>
                    <?php else: ?>
                    <a href="admin/sticky-topic.php?id=<?php echo $topic_id; ?>" class="dropdown-item">Закрепить тему</a>
                    <?php endif; ?>
                    
                    <?php if ($topic['is_locked']): ?>
                    <a href="admin/unlock-topic.php?id=<?php echo $topic_id; ?>" class="dropdown-item">Разблокировать тему</a>
                    <?php else: ?>
                    <a href="admin/lock-topic.php?id=<?php echo $topic_id; ?>" class="dropdown-item">Заблокировать тему</a>
                    <?php endif; ?>
                    
                    <a href="admin/move-topic.php?id=<?php echo $topic_id; ?>" class="dropdown-item">Переместить тему</a>
                    <a href="admin/delete-topic.php?id=<?php echo $topic_id; ?>" class="dropdown-item text-danger" onclick="return confirm('Вы уверены, что хотите удалить эту тему? Это действие нельзя отменить.')">Удалить тему</a>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="posts-list">
        <?php foreach ($posts as $index => $post): ?>
        <div class="post-card <?php echo $highlight_post == $post['id'] ? 'highlight-post' : ''; ?>" id="post-<?php echo $post['id']; ?>">
            <div class="post-author">
                <div class="post-avatar">
                    <img src="assets/uploads/avatars/<?php echo htmlspecialchars($post['avatar']); ?>" alt="<?php echo htmlspecialchars($post['username']); ?>">
                </div>
                <div class="post-username">
                    <a href="profile.php?id=<?php echo $post['user_id']; ?>"><?php echo htmlspecialchars($post['username']); ?></a>
                </div>
                <div class="post-userstats">
                    Сообщений: <?php echo $post['post_count']; ?>
                </div>
                <div class="post-joined">
                    На форуме с <?php echo format_date($post['user_joined'], 'full'); ?>
                </div>
            </div>
            <div class="post-content">
                <div class="post-body">
                    <?php echo format_post_content($post['content']); ?>
                </div>
                <div class="post-footer">
                    <div class="post-date">
                        <?php echo format_date($post['created_at'], 'full'); ?>
                        <?php if ($post['updated_at']): ?>
                        <span class="post-edited">(отредактировано <?php echo format_date($post['updated_at'], 'relative'); ?>)</span>
                        <?php endif; ?>
                    </div>
                    <div class="post-actions">
                        <?php if (Auth::check()): ?>
                        <button class="btn btn-sm like-button <?php echo $post['user_liked'] ? 'liked' : ''; ?>" data-post-id="<?php echo $post['id']; ?>" title="<?php echo $post['user_liked'] ? 'Убрать лайк' : 'Поставить лайк'; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <?php if ($post['user_liked']): ?>
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" fill="currentColor"></path>
                                <?php else: ?>
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                <?php endif; ?>
                            </svg>
                            <span class="like-count" data-post-id="<?php echo $post['id']; ?>"><?php echo $post['like_count']; ?></span>
                        </button>
                        
                        <button class="btn btn-sm quote-button" data-post-id="<?php echo $post['id']; ?>" data-username="<?php echo htmlspecialchars($post['username']); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                            Цитировать
                        </button>
                        
                        <?php if ($post['user_id'] == $_SESSION['user_id'] || $current_user['is_admin']): ?>
                        <a href="edit-post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            Редактировать
                        </a>
                        
                        <?php if ($index > 0 || $current_user['is_admin']): // Первое сообщение может удалить только админ ?>
                        <a href="delete-post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm text-danger" onclick="return confirm('Вы уверены, что хотите удалить это сообщение? Это действие нельзя отменить.')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            Удалить
                        </a>
                        <?php endif; ?>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php
    // Формирование URL для пагинации
    $pagination_url = "topic.php?id={$topic_id}&page={page}";
    echo generate_pagination($posts_data['current_page'], $posts_data['pages'], $pagination_url);
    ?>
    
    <?php if (Auth::check() && !$topic['is_locked']): ?>
    <div class="reply-form" id="reply-box">
        <h3>Ответить в тему</h3>
        
        <form id="reply-form" data-topic-id="<?php echo $topic_id; ?>">
            <div class="form-group">
                <label for="reply-content" class="form-label">Сообщение</label>
                <textarea id="reply-content" name="content" class="form-control" rows="6" required></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Отправить ответ</button>
        </form>
    </div>
    <?php elseif ($topic['is_locked']): ?>
    <div class="topic-locked-notice">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
        <p>Эта тема закрыта для новых ответов.</p>
    </div>
    <?php else: ?>
    <div class="login-to-reply">
        <p>Пожалуйста, <a href="login.php">войдите</a> или <a href="register.php">зарегистрируйтесь</a>, чтобы ответить в тему.</p>
    </div>
    <?php endif; ?>
</div>

<?php
// Добавляем скрипт для обработки цитирования
?>
<script>
// Инициализация кнопок цитирования
document.addEventListener('DOMContentLoaded', function() {
    initQuoteButtons();
});
</script>

<?php
require_once 'includes/footer.php';
?>