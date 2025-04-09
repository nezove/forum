<?php
$page_title = 'Восстановление пароля';
require_once 'includes/header.php';

// Если пользователь уже авторизован, перенаправляем на главную
if (Auth::check()) {
    header('Location: index.php');
    exit;
}

$step = isset($_GET['step']) ? $_GET['step'] : 'request';
$token = isset($_GET['token']) ? $_GET['token'] : '';
$errors = [];
$success = false;

// Обработка запроса на восстановление пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'request') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $errors[] = 'Введите email';
    } elseif (!validate_email($email)) {
        $errors[] = 'Введите корректный email';
    } else {
        $result = Auth::createResetToken($email);
        
        if ($result['success']) {
            // В реальном проекте здесь должна быть отправка письма с токеном
            // Для демонстрации просто показываем ссылку
            $reset_link = "recover.php?step=reset&token=" . $result['token'];
            $success = true;
        } else {
            $errors[] = $result['message'];
        }
    }
}

// Обработка сброса пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'reset') {
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $reset_token = $_POST['token'];
    
    if (empty($password)) {
        $errors[] = 'Введите пароль';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Пароль должен содержать не менее 6 символов';
    }
    
    if ($password !== $password_confirm) {
        $errors[] = 'Пароли не совпадают';
    }
    
    if (empty($errors)) {
        $result = Auth::resetPassword($reset_token, $password);
        
        if ($result['success']) {
            // Перенаправление на страницу входа
            header('Location: login.php?password_reset=1');
            exit;
        } else {
            $errors[] = $result['message'];
        }
    }
}

// Проверка токена для сброса пароля
if ($step === 'reset' && !empty($token)) {
    $result = Auth::verifyResetToken($token);
    
    if (!$result['success']) {
        $errors[] = $result['message'];
        $token_valid = false;
    } else {
        $token_valid = true;
    }
}
?>

<div class="auth-page">
    <div class="auth-container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Восстановление пароля</h1>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <p>Инструкции по восстановлению пароля отправлены на ваш email.</p>
                    <p class="mb-0">Для демонстрации: <a href="<?php echo $reset_link; ?>">ссылка для сброса пароля</a></p>
                </div>
                <?php endif; ?>
                
                <?php if ($step === 'request' && !$success): ?>
                <form method="post" action="recover.php?step=request">
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required autofocus>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-block">Отправить инструкции</button>
                    </div>
                </form>
                <?php endif; ?>
                
                <?php if ($step === 'reset' && $token_valid): ?>
                <form method="post" action="recover.php?step=reset">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Новый пароль</label>
                        <input type="password" id="password" name="password" class="form-control" required autofocus>
                        <div class="form-text">Пароль должен содержать не менее 6 символов</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirm" class="form-label">Подтверждение пароля</label>
                        <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-block">Сбросить пароль</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <div class="auth-links">
                    <a href="login.php">Вернуться на страницу входа</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>