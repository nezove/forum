<?php
$page_title = 'Настройки профиля';
require_once 'includes/header.php';

// Проверка авторизации
if (!Auth::check()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user = Auth::user();

// Обработка отправки формы профиля
$profile_updated = false;
$avatar_error = '';

if (isset($_POST['update_profile'])) {
    $bio = trim($_POST['bio']);
    
    // Обновление данных пользователя
    $db = Database::getInstance();
    $db->update(
        'users',
        ['bio' => $bio],
        'id = :id',
        ['id' => $user_id]
    );
    
    $profile_updated = true;
    
    // Обновляем данные пользователя
    $user = Auth::user();
}

// Обработка загрузки аватара
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] != UPLOAD_ERR_NO_FILE) {
    $result = upload_avatar($_FILES['avatar'], $user_id);
    
    if ($result['success']) {
        $profile_updated = true;
        // Обновляем данные пользователя
        $user = Auth::user();
    } else {
        $avatar_error = $result['message'];
    }
}

// Обработка изменения пароля
$password_updated = false;
$password_error = '';

if (isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Проверка текущего пароля
    if (!password_verify($current_password, $user['password'])) {
        $password_error = 'Неверный текущий пароль';
    } else if ($new_password !== $confirm_password) {
        $password_error = 'Новые пароли не совпадают';
    } else if (strlen($new_password) < 6) {
        $password_error = 'Пароль должен содержать не менее 6 символов';
    } else {
        // Обновление пароля
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $db = Database::getInstance();
        $db->update(
            'users',
            ['password' => $password_hash],
            'id = :id',
            ['id' => $user_id]
        );
        
        $password_updated = true;
    }
}

// Обработка настроек уведомлений
$notifications_updated = false;

if (isset($_POST['update_notifications'])) {
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    
    $db = Database::getInstance();
    $db->update(
        'users',
        ['email_notifications' => $email_notifications],
        'id = :id',
        ['id' => $user_id]
    );
    
    $notifications_updated = true;
    
    // Обновляем данные пользователя
    $user = Auth::user();
}
?>

<div class="settings-page">
    <h1 class="page-title">Настройки профиля</h1>
    
    <?php if ($profile_updated): ?>
    <div class="alert alert-success">
        Профиль успешно обновлен
    </div>
    <?php endif; ?>
    
    <?php if ($password_updated): ?>
    <div class="alert alert-success">
        Пароль успешно изменен
    </div>
    <?php endif; ?>
    
    <?php if ($notifications_updated): ?>
    <div class="alert alert-success">
        Настройки уведомлений обновлены
    </div>
    <?php endif; ?>
    
    <div class="settings-tabs">
        <ul class="profile-tabs-list">
            <li class="profile-tab-item">
                <a href="#profile" class="profile-tab-link active" data-tab="profile">Профиль</a>
            </li>
            <li class="profile-tab-item">
                <a href="#security" class="profile-tab-link" data-tab="security">Безопасность</a>
            </li>
            <li class="profile-tab-item">
                <a href="#notifications" class="profile-tab-link" data-tab="notifications">Уведомления</a>
            </li>
        </ul>
    </div>
    
    <div class="settings-content">
        <!-- Профиль -->
        <div class="settings-tab active" id="profile-tab">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Информация профиля</h2>
                </div>
                <div class="card-body">
                    <form action="settings.php" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="username" class="form-label">Имя пользователя</label>
                            <input type="text" id="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            <div class="form-text">Имя пользователя не может быть изменено</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            <div class="form-text">Email не может быть изменен</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="bio" class="form-label">О себе</label>
                            <textarea id="bio" name="bio" class="form-control" rows="4"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Аватар</label>
                            <div class="avatar-uploader">
                                <div class="current-avatar">
                                    <img src="assets/uploads/avatars/<?php echo htmlspecialchars($user['avatar']); ?>" alt="Аватар">
                                </div>
                                <div class="avatar-controls">
                                    <input type="file" id="avatar" name="avatar" class="form-control" accept="image/jpeg,image/png,image/gif">
                                    <?php if ($avatar_error): ?>
                                    <div class="form-error"><?php echo $avatar_error; ?></div>
                                    <?php endif; ?>
                                    <div class="form-text">Рекомендуемый размер: 200x200 пикселей. Максимальный размер: 2MB.</div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">Сохранить изменения</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Безопасность -->
        <div class="settings-tab" id="security-tab">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Изменение пароля</h2>
                </div>
                <div class="card-body">
                    <?php if ($password_error): ?>
                    <div class="alert alert-danger">
                        <?php echo $password_error; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form action="settings.php" method="post">
                        <div class="form-group">
                            <label for="current_password" class="form-label">Текущий пароль</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password" class="form-label">Новый пароль</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Подтвердите новый пароль</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <button type="submit" name="update_password" class="btn btn-primary">Изменить пароль</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Уведомления -->
        <div class="settings-tab" id="notifications-tab">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Настройки уведомлений</h2>
                </div>
                <div class="card-body">
                    <form action="settings.php" method="post">
                        <div class="form-check">
                            <input type="checkbox" id="email_notifications" name="email_notifications" class="form-check-input" <?php echo isset($user['email_notifications']) && $user['email_notifications'] ? 'checked' : ''; ?>>
                            <label for="email_notifications" class="form-check-label">Получать уведомления по электронной почте</label>
                        </div>
                        
                        <button type="submit" name="update_notifications" class="btn btn-primary mt-3">Сохранить настройки</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Переключение вкладок настроек
    const tabLinks = document.querySelectorAll('.profile-tab-link');
    const tabContents = document.querySelectorAll('.settings-tab');
    
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