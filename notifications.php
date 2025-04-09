<?php
$page_title = 'Уведомления';
require_once 'includes/header.php';

// Проверка авторизации
if (!Auth::check()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_id = $_SESSION['user_id'];
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

// Получение уведомлений пользователя
$notifications_data = get_user_notifications($user_id, $page);
$notifications = $notifications_data['notifications'];
?>

<div class="notifications-page">
    <h1 class="page-title">Уведомления</h1>
    
    <?php if (empty($notifications)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
        </div>
        <div class="empty-state-text">
            <h3>Нет уведомлений</h3>
            <p>У вас пока нет уведомлений.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="notifications-list">
        <?php foreach ($notifications as $notification): ?>
        <div class="notification-card <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
            <?php if ($notification['sender_id']): ?>
            <div class="notification-avatar">
                <img src="assets/uploads/avatars/<?php echo htmlspecialchars($notification['sender_avatar']); ?>" alt="<?php echo htmlspecialchars($notification['sender_username']); ?>">
            </div>
            <?php else: ?>
            <div class="notification-avatar">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            </div>
            <?php endif; ?>
            
            <div class="notification-content">
                <div class="notification-message">
                    <?php if ($notification['sender_id']): ?>
                    <a href="profile.php?id=<?php echo $notification['sender_id']; ?>" class="notification-sender"><?php echo htmlspecialchars($notification['sender_username']); ?></a>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($notification['content']); ?>
                </div>
                <div class="notification-time"><?php echo format_date($notification['created_at'], 'relative'); ?></div>
            </div>
            
            <div class="notification-action">
                <a href="<?php echo htmlspecialchars($notification['link']); ?>" class="btn btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                    Просмотреть
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php
    // Формирование URL для пагинации
    $pagination_url = "notifications.php?page={page}";
    echo generate_pagination($notifications_data['current_page'], $notifications_data['pages'], $pagination_url);
    ?>
    <?php endif; ?>
    
    <?php if (!empty($notifications)): ?>
    <div class="notifications-actions mt-4">
        <form method="post" action="api/mark-all-notifications.php">
            <button type="submit" class="btn btn-outline">Отметить все как прочитанные</button>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
?>