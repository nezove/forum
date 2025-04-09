<?php
$page_title = 'Мои закладки';
require_once 'includes/header.php';

// Проверка авторизации
if (!Auth::check()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_id = $_SESSION['user_id'];
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

// Получение закладок пользователя
$bookmarks_data = get_user_bookmarks($user_id, $page);
$bookmarks = $bookmarks_data['bookmarks'];
?>

<div class="bookmarks-page">
    <h1 class="page-title">Мои закладки</h1>
    
    <?php if (empty($bookmarks)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
        </div>
        <div class="empty-state-text">
            <h3>Нет закладок</h3>
            <p>Вы пока не добавили ни одну тему в закладки.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="topics-list">
        <?php foreach ($bookmarks as $bookmark): ?>
        <div class="topic-card">
            <div class="topic-info">
                <div class="topic-details">
                <h3 class="topic-title">
                        <?php if ($bookmark['is_sticky']): ?>
                        <span class="topic-badge sticky-badge">Важно</span>
                        <?php endif; ?>
                        <?php if ($bookmark['is_locked']): ?>
                        <span class="topic-badge locked-badge">Закрыто</span>
                        <?php endif; ?>
                        <a href="topic.php?id=<?php echo $bookmark['id']; ?>"><?php echo htmlspecialchars($bookmark['title']); ?></a>
                    </h3>
                    <div class="topic-meta">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <a href="profile.php?id=<?php echo $bookmark['user_id']; ?>"><?php echo htmlspecialchars($bookmark['author_name']); ?></a>
                        </span>
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            <?php echo format_date($bookmark['created_at'], 'relative'); ?>
                        </span>
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            <a href="category.php?id=<?php echo $bookmark['category_id']; ?>"><?php echo htmlspecialchars($bookmark['category_name']); ?></a>
                        </span>
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" fill="currentColor"></path></svg>
                            Добавлено <?php echo format_date($bookmark['bookmarked_at'], 'relative'); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="topic-stats">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    <?php echo $bookmark['views']; ?>
                </div>
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    <?php echo $bookmark['replies']; ?>
                </div>
                <div>
                    <button class="btn btn-sm bookmark-button bookmarked" data-topic-id="<?php echo $bookmark['id']; ?>" title="Удалить из закладок">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" fill="currentColor"></path></svg>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php
    // Формирование URL для пагинации
    $pagination_url = "bookmarks.php?page={page}";
    echo generate_pagination($bookmarks_data['current_page'], $bookmarks_data['pages'], $pagination_url);
    ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация кнопок закладок
    initBookmarkButtons();
});
</script>

<?php
require_once 'includes/footer.php';
?>