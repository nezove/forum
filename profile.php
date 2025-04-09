<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Проверка наличия ID пользователя
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$user_id = (int)$_GET['id'];
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'topics';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

// Получение данных пользователя
$user = get_user_profile($user_id);

if (!$user) {
    header('Location: index.php');
    exit;
}

$page_title = 'Профиль ' . $user['username'];
require_once 'includes/header.php';

// Проверка принадлежности профиля текущему пользователю
$is_own_profile = Auth::check() && $_SESSION['user_id'] == $user_id;

// Получение данных в зависимости от вкладки
switch ($tab) {
    case 'posts':
        $posts_data = get_user_posts($user_id, $page);
        break;
    case 'bookmarks':
        if (!$is_own_profile) {
            $tab = 'topics'; // Редирект на вкладку тем, если пытается просмотреть чужие закладки
            $topics_data = get_user_topics($user_id, $page);
        } else {
            $bookmarks_data = get_user_bookmarks($user_id, $page);
        }
        break;
    case 'topics':
    default:
        $tab = 'topics'; // Устанавливаем по умолчанию
        $topics_data = get_user_topics($user_id, $page);
        break;
}
?>

<div class="profile-page">
    <div class="profile-header">
        <div class="profile-avatar">
            <img src="assets/uploads/avatars/<?php echo htmlspecialchars($user['avatar']); ?>" alt="<?php echo htmlspecialchars($user['username']); ?>">
        </div>
        <div class="profile-info">
        <h1 class="profile-username"><?php echo htmlspecialchars($user['username']); ?></h1>
            <div class="profile-joined">
                На форуме с <?php echo format_date($user['created_at'], 'full'); ?>
                <?php if ($user['last_visit']): ?>
                <span class="profile-last-visit">
                    • Последний визит: <?php echo format_date($user['last_visit'], 'relative'); ?>
                </span>
                <?php endif; ?>
            </div>
            
            <?php if ($user['bio']): ?>
            <div class="profile-bio">
                <?php echo htmlspecialchars($user['bio']); ?>
            </div>
            <?php endif; ?>
            
            <div class="profile-stats">
                <div class="profile-stat">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                    <?php echo $user['topic_count']; ?> тем
                </div>
                <div class="profile-stat">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    <?php echo $user['post_count']; ?> сообщений
                </div>
                <div class="profile-stat">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                    <?php echo $user['likes_received']; ?> лайков
                </div>
            </div>
            
            <?php if ($is_own_profile): ?>
            <div class="profile-actions mt-3">
                <a href="settings.php" class="btn btn-outline">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    Редактировать профиль
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="profile-tabs">
        <ul class="profile-tabs-list">
            <li class="profile-tab-item">
                <a href="profile.php?id=<?php echo $user_id; ?>&tab=topics" class="profile-tab-link <?php echo $tab === 'topics' ? 'active' : ''; ?>">Темы</a>
            </li>
            <li class="profile-tab-item">
                <a href="profile.php?id=<?php echo $user_id; ?>&tab=posts" class="profile-tab-link <?php echo $tab === 'posts' ? 'active' : ''; ?>">Сообщения</a>
            </li>
            <?php if ($is_own_profile): ?>
            <li class="profile-tab-item">
                <a href="profile.php?id=<?php echo $user_id; ?>&tab=bookmarks" class="profile-tab-link <?php echo $tab === 'bookmarks' ? 'active' : ''; ?>">Закладки</a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
    
    <div class="profile-content">
        <?php if ($tab === 'topics'): ?>
            <?php if (empty($topics_data['topics'])): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                </div>
                <div class="empty-state-text">
                    <h3>Нет тем</h3>
                    <p>Пользователь еще не создал ни одной темы.</p>
                </div>
            </div>
            <?php else: ?>
            <div class="topics-list">
                <?php foreach ($topics_data['topics'] as $topic): ?>
                <div class="topic-card">
                    <div class="topic-info">
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
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                    <?php echo format_date($topic['created_at'], 'relative'); ?>
                                </span>
                                <span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                    <a href="category.php?id=<?php echo $topic['category_id']; ?>"><?php echo htmlspecialchars($topic['category_name']); ?></a>
                                </span>
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
            $pagination_url = "profile.php?id={$user_id}&tab=topics&page={page}";
            echo generate_pagination($topics_data['current_page'], $topics_data['pages'], $pagination_url);
            ?>
            <?php endif; ?>
            
        <?php elseif ($tab === 'posts'): ?>
            <?php if (empty($posts_data['posts'])): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                </div>
                <div class="empty-state-text">
                    <h3>Нет сообщений</h3>
                    <p>Пользователь еще не оставил ни одного сообщения.</p>
                </div>
            </div>
            <?php else: ?>
            <div class="user-posts-list">
                <?php foreach ($posts_data['posts'] as $post): ?>
                <div class="user-post-card">
                    <div class="user-post-header">
                        <div class="user-post-info">
                            <h3 class="user-post-title">
                                <a href="topic.php?id=<?php echo $post['topic_id']; ?>&highlight=<?php echo $post['id']; ?>#post-<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['topic_title']); ?></a>
                            </h3>
                            <div class="user-post-meta">
                                <span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                    <?php echo format_date($post['created_at'], 'relative'); ?>
                                </span>
                                <span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                                    <?php echo $post['like_count']; ?> лайков
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="user-post-content">
                        <div class="user-post-text">
                            <?php 
                            // Обрезаем содержимое до 200 символов
                            $content = strip_tags(format_post_content($post['content']));
                            echo strlen($content) > 200 ? substr($content, 0, 200) . '...' : $content;
                            ?>
                        </div>
                        <a href="topic.php?id=<?php echo $post['topic_id']; ?>&highlight=<?php echo $post['id']; ?>#post-<?php echo $post['id']; ?>" class="user-post-link">Перейти к сообщению →</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php
            // Формирование URL для пагинации
            $pagination_url = "profile.php?id={$user_id}&tab=posts&page={page}";
            echo generate_pagination($posts_data['current_page'], $posts_data['pages'], $pagination_url);
            ?>
            <?php endif; ?>
            
        <?php elseif ($tab === 'bookmarks' && $is_own_profile): ?>
            <?php if (empty($bookmarks_data['bookmarks'])): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                </div>
                <div class="empty-state-text">
                    <h3>Нет закладок</h3>
                    <p>Вы еще не добавили ни одну тему в закладки.</p>
                </div>
            </div>
            <?php else: ?>
            <div class="topics-list">
                <?php foreach ($bookmarks_data['bookmarks'] as $bookmark): ?>
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
                            <button class="btn btn-sm remove-bookmark" data-topic-id="<?php echo $bookmark['id']; ?>" title="Удалить из закладок">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" fill="currentColor"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php
            // Формирование URL для пагинации
            $pagination_url = "profile.php?id={$user_id}&tab=bookmarks&page={page}";
            echo generate_pagination($bookmarks_data['current_page'], $bookmarks_data['pages'], $pagination_url);
            ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>