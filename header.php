<?php
// header.php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Определение текущей страницы
$current_page = basename($_SERVER['PHP_SELF']);

// Получение данных текущего пользователя и количества непрочитанных уведомлений
$current_user = null;
$unread_notifications = 0;

if (Auth::check()) {
    $current_user = Auth::user();
    $unread_notifications = get_unread_notifications_count($_SESSION['user_id']);
}

// Определение предпочитаемой темы
$theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light';

// Обработка переключения темы
if (isset($_POST['toggle_theme'])) {
    $theme = ($theme === 'light') ? 'dark' : 'light';
    $_SESSION['theme'] = $theme;
    
    if (Auth::check()) {
        // Сохранение предпочтения темы в профиле пользователя
        $db = Database::getInstance();
        $db->update(
            'users',
            ['theme' => $theme],
            'id = :id',
            ['id' => $_SESSION['user_id']]
        );
    }
    
    // Перенаправление на ту же страницу для избежания повторной отправки формы
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/main.css?=<?php $random_number = rand(1, 10000); echo $random_number; ?>">
    <?php if ($theme === 'dark'): ?>
    <link rel="stylesheet" href="assets/css/dark-theme.css">
    <?php endif; ?>
    <script src="assets/js/forum.js" defer></script>
    <script src="assets/js/ajax.js" defer></script>
    <script src="assets/js/theme-switcher.js" defer></script>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-content">
                <div class="header-logo">
                    <a href="index.php"><?php echo SITE_NAME; ?></a>
                </div>
                
                <nav class="main-nav">
                    <ul>
                        <li><a href="index.php" class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>">Главная</a></li>
                        <li><a href="categories.php" class="<?php echo $current_page === 'categories.php' ? 'active' : ''; ?>">Категории</a></li>
                        <?php if (Auth::check() && $current_user['is_admin']): ?>
                        <li><a href="admin.php" class="<?php echo $current_page === 'admin.php' ? 'active' : ''; ?>">Админ</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <div class="user-controls">
                    <form method="post" class="theme-toggle">
                        <button type="submit" name="toggle_theme" class="theme-toggle-btn" aria-label="Переключить тему">
                            <?php if ($theme === 'light'): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                            <?php else: ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                            <?php endif; ?>
                        </button>
                    </form>
                    
                    <?php if (Auth::check()): ?>
                    <div class="user-dropdown">
                        <button class="user-dropdown-btn">
                            <div class="user-avatar">
                                <img src="assets/uploads/avatars/<?php echo htmlspecialchars($current_user['avatar']); ?>" alt="<?php echo htmlspecialchars($current_user['username']); ?>">
                            </div>
                            <span class="username"><?php echo htmlspecialchars($current_user['username']); ?></span>
                            <?php if ($unread_notifications > 0): ?>
                            <span class="notification-badge"><?php echo $unread_notifications; ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="dropdown-content">
                            <a href="profile.php?id=<?php echo $_SESSION['user_id']; ?>">Мой профиль</a>
                            <a href="settings.php">Настройки</a>
                            <a href="notifications.php">Уведомления
                                <?php if ($unread_notifications > 0): ?>
                                <span class="notification-badge"><?php echo $unread_notifications; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="bookmarks.php">Закладки</a>
                            <a href="logout.php">Выйти</a>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="auth-buttons">
                        <a href="login.php" class="btn btn-outline">Войти</a>
                        <a href="register.php" class="btn btn-primary">Регистрация</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <main class="site-main">
        <div class="container">